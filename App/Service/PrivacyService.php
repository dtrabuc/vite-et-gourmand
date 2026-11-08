<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use App\Repository\OrderItemRepository;
use App\Repository\UserRepository;
use InvalidArgumentException;
use MongoDB\BSON\UTCDateTime;

/**
 * Droits RGPD du client, depuis son profil :
 * - accès et portabilité : export de toutes ses données (JSON) ;
 * - effacement : suppression du compte. Les données personnelles sont effacées ;
 *   les commandes restent pour la comptabilité (10 ans), sans identité, sans
 *   adresse ni téléphone, ni données d'allergie.
 */
final readonly class PrivacyService
{
    /** États d'une commande encore en cours : le compte ne peut pas être supprimé. */
    private const ACTIVE_STATUSES = ['pending', 'accepted', 'preparing', 'delivering', 'delivered', 'awaiting_return'];

    public function __construct(
        private UserRepository $userRepository,
        private OrderItemRepository $orderItemRepository
    ) {
    }

    public function export(int $userId): array
    {
        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            throw new InvalidArgumentException('Compte introuvable.');
        }
        $pdo = Database::pdo();

        $orders = $pdo->prepare(
            'SELECT o.id, o.order_number, m.title AS menu, o.number_of_people, o.order_date, o.delivery_date, o.delivery_time,
                    o.service_type, o.delivery_address, o.delivery_postal_code, o.delivery_city, o.delivery_instructions,
                    o.customization, o.contact_phone, o.menu_price, o.discount_rate, o.delivery_cost, o.total_price, o.status
             FROM orders o INNER JOIN menus m ON m.id = o.menu_id
             WHERE o.user_id = :user_id ORDER BY o.order_date'
        );
        $orders->execute(['user_id' => $userId]);
        $orders = $orders->fetchAll();
        $items = $this->orderItemRepository->findForOrders(array_column($orders, 'id'));
        $history = $pdo->prepare(
            'SELECT h.order_id, h.status, h.changed_at FROM order_status_history h
             INNER JOIN orders o ON o.id = h.order_id WHERE o.user_id = :user_id ORDER BY h.changed_at'
        );
        $history->execute(['user_id' => $userId]);
        $historyByOrder = [];
        foreach ($history->fetchAll() as $row) {
            $historyByOrder[(int) $row['order_id']][] = ['status' => $row['status'], 'date' => $row['changed_at']];
        }
        foreach ($orders as &$order) {
            $order['choix'] = OrderCompositionService::describe($items[(int) $order['id']] ?? []);
            $order['suivi'] = $historyByOrder[(int) $order['id']] ?? [];
        }
        unset($order);

        $notifications = $pdo->prepare('SELECT title, message, is_read, created_at FROM user_notifications WHERE user_id = :user_id ORDER BY created_at');
        $notifications->execute(['user_id' => $userId]);
        $quotes = $pdo->prepare(
            'SELECT event_date, number_of_people, service_type, event_location, postal_code, request_details, status, employee_reply, created_at
             FROM quote_requests WHERE user_id = :user_id ORDER BY created_at'
        );
        $quotes->execute(['user_id' => $userId]);

        $reviews = [];
        try {
            foreach (Database::mongoDatabase()->selectCollection('comments')->find(['userId' => $userId]) as $doc) {
                $reviews[] = [
                    'commande' => (int) ($doc['orderId'] ?? 0),
                    'note' => (int) ($doc['rating'] ?? 0),
                    'commentaire' => (string) ($doc['comment'] ?? ''),
                    'publie' => (bool) ($doc['isValidated'] ?? false),
                    'date' => isset($doc['createdAt']) && $doc['createdAt'] instanceof UTCDateTime
                        ? $doc['createdAt']->toDateTime()->format('Y-m-d H:i') : null,
                ];
            }
        } catch (\Throwable $exception) {
            error_log('Export RGPD : avis MongoDB indisponibles : ' . $exception->getMessage());
        }

        return [
            'exporte_le' => date('Y-m-d H:i:s'),
            'compte' => [
                'email' => $user->getEmail(),
                'prenom' => $user->getFirstName(),
                'nom' => $user->getLastName(),
                'telephone' => $user->getPhone(),
                'adresse' => $user->getAddress(),
                'role' => $user->getRole(),
            ],
            'commandes' => $orders,
            'notifications' => $notifications->fetchAll(),
            'demandes_de_devis' => $quotes->fetchAll(),
            'avis' => $reviews,
        ];
    }

    public function deleteAccount(int $userId, string $password): void
    {
        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            throw new InvalidArgumentException('Compte introuvable.');
        }
        if ($user->getRole() !== 'user') {
            throw new InvalidArgumentException('Les comptes de l’équipe sont supprimés par l’administrateur.');
        }
        if (!password_verify($password, $user->getPasswordHash())) {
            throw new InvalidArgumentException('Mot de passe incorrect : le compte n’a pas été supprimé.');
        }

        $pdo = Database::pdo();
        $placeholders = implode(',', array_fill(0, count(self::ACTIVE_STATUSES), '?'));
        $active = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status IN ({$placeholders})");
        $active->execute([$userId, ...self::ACTIVE_STATUSES]);
        if ((int) $active->fetchColumn() > 0) {
            throw new InvalidArgumentException(
                'Une commande est encore en cours : annulez-la ou attendez qu’elle soit terminée avant de supprimer le compte.'
            );
        }

        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                "UPDATE users SET email = :email, password = :password, first_name = 'Compte', last_name = 'supprimé',
                        phone = '', gsm = '', address = '', reset_token_hash = NULL, reset_token_expires_at = NULL,
                        failed_attempts = 0, locked_until = NULL, is_active = 0
                 WHERE id = :id"
            )->execute([
                'email' => 'compte-supprime-' . $userId . '@supprime.invalid',
                'password' => password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT),
                'id' => $userId,
            ]);
            // commandes gardées pour la comptabilité, sans données personnelles
            $pdo->prepare(
                "UPDATE orders SET delivery_address = '', delivery_instructions = NULL, customization = NULL, contact_phone = ''
                 WHERE user_id = :id"
            )->execute(['id' => $userId]);
            $pdo->prepare(
                "UPDATE order_items oi INNER JOIN orders o ON o.id = oi.order_id SET oi.without_allergens = '' WHERE o.user_id = :id"
            )->execute(['id' => $userId]);
            $pdo->prepare('DELETE FROM user_notifications WHERE user_id = :id')->execute(['id' => $userId]);
            $pdo->prepare(
                "UPDATE quote_requests SET email = :email, first_name = NULL, last_name = NULL, phone = NULL, company = NULL,
                        event_location = NULL, request_details = NULL, user_id = NULL
                 WHERE user_id = :id"
            )->execute(['email' => 'compte-supprime-' . $userId . '@supprime.invalid', 'id' => $userId]);
            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }

        // avis publiés sous le nom du client : retirés
        try {
            Database::mongoDatabase()->selectCollection('comments')->deleteMany(['userId' => $userId]);
        } catch (\Throwable $exception) {
            error_log('Suppression de compte : avis MongoDB non supprimés pour ' . $userId . ' : ' . $exception->getMessage());
        }
    }
}
