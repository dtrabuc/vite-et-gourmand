<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\Order;
use App\Core\Database;

class OrderRepository
{
    public function create(array $data): int
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('INSERT INTO orders
            (user_id, menu_id, number_of_people, order_date, delivery_date, delivery_time,
             delivery_address, delivery_city, delivery_postal_code, delivery_distance_km,
                 delivery_cost, customization, service_type, payment_method, delivery_instructions, contact_phone, menu_price, discount_rate, total_price, status, equipment_loaned)
            VALUES (:user_id, :menu_id, :number_of_people, :order_date, :delivery_date, :delivery_time,
                    :delivery_address, :delivery_city, :delivery_postal_code, :delivery_distance_km,
                    :delivery_cost, :customization, :service_type, :payment_method, :delivery_instructions, :contact_phone, :menu_price, :discount_rate, :total_price, :status, :equipment_loaned)');
        $stmt->execute([
            'user_id' => $data['user_id'],
            'menu_id' => $data['menu_id'],
            'number_of_people' => $data['number_of_people'],
            'order_date' => $data['order_date'],
            'delivery_date' => $data['delivery_date'],
            'delivery_time' => $data['delivery_time'],
            'delivery_address' => $data['delivery_address'],
            'delivery_city' => $data['delivery_city'],
            'delivery_postal_code' => $data['delivery_postal_code'] ?? null,
            'delivery_distance_km' => $data['delivery_distance_km'] ?? null,
            'delivery_cost' => $data['delivery_cost'],
            'customization' => $data['customization'] ?? null,
            'service_type' => $data['service_type'] ?? 'delivery',
            'payment_method' => $data['payment_method'] ?? 'cash_on_site',
            'delivery_instructions' => $data['delivery_instructions'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'menu_price' => $data['menu_price'],
            'discount_rate' => $data['discount_rate'] ?? 0,
            'total_price' => $data['total_price'],
            'status' => $data['status'],
            'equipment_loaned' => !empty($data['equipment_loaned']) ? 1 : 0,
        ]);
        $id = (int) $pdo->lastInsertId();

        if ($id <= 0) {
            throw new \RuntimeException('La commande n’a pas pu être enregistrée.');
        }

        $number = 'VG-' . date('Ymd') . '-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
        $numberStmt = $pdo->prepare('UPDATE orders SET order_number = :number WHERE id = :id');
        $numberStmt->execute(['number' => $number, 'id' => $id]);

        if ($numberStmt->rowCount() !== 1) {
            throw new \RuntimeException('La référence de commande n’a pas pu être enregistrée.');
        }

        return $id;
    }

    public function findForStaff(?string $status = null, ?string $customer = null): array
    {
        $sql = "SELECT o.*, CONCAT(u.first_name, ' ', u.last_name) AS customer_name, u.email AS customer_email,
                       m.title AS menu_title
                FROM orders o
                JOIN users u ON u.id = o.user_id
                JOIN menus m ON m.id = o.menu_id
                WHERE 1=1";
        $params = [];
        if ($status !== null && $status !== '') { $sql .= ' AND o.status = :status'; $params['status'] = $status; }
        if ($customer !== null && $customer !== '') {
            $sql .= ' AND (u.email LIKE :customer OR u.first_name LIKE :customer OR u.last_name LIKE :customer)';
            $params['customer'] = '%' . $customer . '%';
        }
        $sql .= ' ORDER BY o.delivery_date ASC, o.delivery_time ASC';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return $this->hydrateMany($stmt->fetchAll());
    }

    public function findByUserId(int $userId): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC');
        $stmt->execute(['user_id' => $userId]);
        return $this->hydrateMany($stmt->fetchAll());
    }

    public function findById(int $id): ?Order
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $this->hydrate($row);
    }

    public function increaseMenuStock(int $menuId): void
    {
        $stmt = Database::pdo()->prepare('UPDATE menus SET available_stock = available_stock + 1 WHERE id = :id');
        $stmt->execute(['id' => $menuId]);
    }

    /**
     * Met à jour le contenu d'une commande (tout sauf le menu et le statut).
     */
    public function updateDetails(int $id, array $data): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE orders SET number_of_people = :people, delivery_date = :delivery_date, delivery_time = :delivery_time,
             service_type = :service_type, contact_phone = :contact_phone,
             delivery_address = :address, delivery_city = :city, delivery_postal_code = :postal_code,
             delivery_distance_km = :distance, delivery_instructions = :instructions,
             menu_price = :menu_price, delivery_cost = :delivery_cost,
             discount_rate = :discount_rate, total_price = :total_price, updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'people' => $data['number_of_people'],
            'delivery_date' => $data['delivery_date'],
            'delivery_time' => $data['delivery_time'],
            'service_type' => $data['service_type'],
            'contact_phone' => $data['contact_phone'],
            'address' => $data['delivery_address'],
            'city' => $data['delivery_city'],
            'postal_code' => $data['delivery_postal_code'],
            'distance' => $data['delivery_distance_km'],
            'instructions' => $data['delivery_instructions'],
            'menu_price' => $data['menu_price'],
            'delivery_cost' => $data['delivery_cost'],
            'discount_rate' => $data['discount_rate'],
            'total_price' => $data['total_price'],
        ]);
    }

    public function setEquipmentLoaned(int $id, bool $equipmentLoaned): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE orders SET equipment_loaned = :equipment_loaned, updated_at = NOW() WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'equipment_loaned' => $equipmentLoaned ? 1 : 0,
        ]);
    }

    public function updateStatus(int $id, string $status, ?string $cancellationReason = null): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('UPDATE orders SET status = :status, cancellation_reason = :reason, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id, 'status' => $status, 'reason' => $cancellationReason]);
    }

    public function getHistory(int $orderId): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT * FROM order_status_history WHERE order_id = :order_id ORDER BY changed_at ASC');
        $stmt->execute(['order_id' => $orderId]);
        $history = [];
        foreach ($stmt->fetchAll() as $row) {
            $history[] = [
                'id' => (int) $row['id'],
                'order_id' => (int) $row['order_id'],
                'status' => $row['status'],
                'changed_by' => $row['changed_by'] !== null ? (int) $row['changed_by'] : null,
                'changed_at' => $row['changed_at'] ? new \DateTimeImmutable($row['changed_at']) : null,
                'notes' => $row['notes'],
            ];
        }
        return $history;
    }

    public function addToHistory(int $orderId, string $status, ?int $changedBy, string $notes = ''): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('INSERT INTO order_status_history (order_id, status, changed_by, changed_at, notes)
                               VALUES (:order_id, :status, :changed_by, NOW(), :notes)');
        $stmt->execute([
            'order_id' => $orderId,
            'status' => $status,
            'changed_by' => $changedBy,
            'notes' => $notes,
        ]);
    }

    public function countAll(): int
    {
        return (int) Database::pdo()->query('SELECT COUNT(*) FROM orders')->fetchColumn();
    }

    public function countByStatus(string $status): int
    {
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM orders WHERE status = :status');
        $stmt->execute(['status' => $status]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Chiffre d'affaires des commandes terminées.
     */
    public function sumCompletedRevenue(): float
    {
        return (float) Database::pdo()->query(
            "SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE status = 'completed'"
        )->fetchColumn();
    }

    /**
     * Commandes terminées et chiffre d'affaires par menu (tous les menus, y compris sans vente).
     */
    public function revenueByMenu(?string $from = null, ?string $to = null, ?int $menuId = null): array
    {
        $sql = "SELECT
                    m.id AS menu_id,
                    m.title AS menu_title,
                    COUNT(o.id) AS order_count,
                    COALESCE(SUM(o.total_price), 0) AS revenue
                FROM menus m
                LEFT JOIN orders o
                    ON o.menu_id = m.id
                   AND o.status = 'completed'";

        $where = [];
        $params = [];

        // la période va dans le ON du LEFT JOIN : dans le WHERE, elle ferait
        // disparaître les menus sans commande au lieu de les laisser à 0 €
        if ($from !== null && $from !== '') {
            $sql .= ' AND o.delivery_date >= :from_date';
            $params['from_date'] = $from;
        }

        if ($to !== null && $to !== '') {
            $sql .= ' AND o.delivery_date <= :to_date';
            $params['to_date'] = $to;
        }

        if ($menuId !== null && $menuId > 0) {
            $where[] = 'm.id = :menu_id';
            $params['menu_id'] = $menuId;
        }

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' GROUP BY m.id, m.title ORDER BY revenue DESC';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Nombre de commandes terminées et CA par menu, sur une période facultative
     * (source des statistiques MongoDB).
     */
    public function completedTotalsByMenu(?string $periodStart = null, ?string $periodEnd = null): array
    {
        $sql = "SELECT o.menu_id, m.title AS menu_title, COUNT(o.id) AS order_count, SUM(o.total_price) AS revenue
                FROM orders o
                INNER JOIN menus m ON m.id = o.menu_id
                WHERE o.status = 'completed'";
        $params = [];

        if ($periodStart !== null) {
            $sql .= ' AND o.delivery_date >= :period_start';
            $params['period_start'] = $periodStart;
        }

        if ($periodEnd !== null) {
            $sql .= ' AND o.delivery_date <= :period_end';
            $params['period_end'] = $periodEnd;
        }

        $sql .= ' GROUP BY o.menu_id, m.title';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function decreaseMenuStock(int $menuId): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('UPDATE menus SET available_stock = available_stock - 1
                               WHERE id = :id AND is_active = 1 AND available_stock > 0');
        $stmt->execute(['id' => $menuId]);
        if ($stmt->rowCount() !== 1) {
            throw new \RuntimeException('Le stock du menu n’est plus disponible.');
        }
    }

    private function hydrateMany(array $rows): array
    {
        return array_map($this->hydrate(...), $rows);
    }

    private function hydrate(array $row): Order
    {
        $order = new Order();
        $order->setId((int) $row['id']);
        $order->setOrderNumber($row['order_number'] ?? null);
        $order->setUserId((int) $row['user_id']);
        $order->setMenuId((int) $row['menu_id']);
        $order->setNumberOfPeople((int) $row['number_of_people']);
        $order->setOrderDate($row['order_date']);
        $order->setDeliveryDate($row['delivery_date']);
        $order->setDeliveryTime($row['delivery_time']);
        $order->setDeliveryAddress($row['delivery_address']);
        $order->setDeliveryCity((string) ($row['delivery_city'] ?? ''));
        $order->setDeliveryPostalCode((string) ($row['delivery_postal_code'] ?? ''));
        $order->setDeliveryDistanceKm($row['delivery_distance_km'] !== null ? (float) $row['delivery_distance_km'] : null);
        $order->setCustomization($row['customization'] ?? null);
        $order->setServiceType((string) ($row['service_type'] ?? 'delivery'));
        $order->setPaymentMethod((string) ($row['payment_method'] ?? 'cash_on_site'));
        $order->setDeliveryInstructions($row['delivery_instructions'] ?? null);
        $order->setContactPhone((string) ($row['contact_phone'] ?? ''));
        $order->setDeliveryCost((float) $row['delivery_cost']);
        $order->setMenuPrice((float) $row['menu_price']);
        $order->setDiscountRate((float) ($row['discount_rate'] ?? 0));
        $order->setTotalPrice((float) $row['total_price']);
        $order->setStatus($row['status']);
        $order->setEquipmentLoaned(!empty($row['equipment_loaned']));
        $order->setCancellationReason($row['cancellation_reason'] ?? null);
        $order->setCreatedAt(!empty($row['created_at']) ? new \DateTimeImmutable($row['created_at']) : null);
        $order->setUpdatedAt(!empty($row['updated_at']) ? new \DateTimeImmutable($row['updated_at']) : null);
        $order->setCustomerName(isset($row['customer_name']) ? (string) $row['customer_name'] : null);
        $order->setCustomerEmail(isset($row['customer_email']) ? (string) $row['customer_email'] : null);
        $order->setMenuTitle(isset($row['menu_title']) ? (string) $row['menu_title'] : null);
        return $order;
    }
}
