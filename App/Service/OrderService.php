<?php
namespace App\Service;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use App\Repository\MenuRepository;
use App\Core\Database;

class OrderService
{
    public function __construct(
        private OrderRepository $orderRepository,
        private UserRepository $userRepository,
        private MenuRepository $menuRepository,
        private MailService $mailService,
        ?MenuStatisticsService $menuStatisticsService = null
    ) {
        $this->menuStatisticsService = $menuStatisticsService ?? new MenuStatisticsService($orderRepository, $menuRepository);
    }

    private MenuStatisticsService $menuStatisticsService;

    public function createOrder(
        int $userId,
        int $menuId,
        int $numberOfPeople,
        string $deliveryDate,
        string $deliveryTime,
        string $deliveryAddress,
        string $deliveryCity,
        string $deliveryPostalCode,
        ?float $deliveryDistanceKm = null
    ): array {
        $user = $this->userRepository->findById($userId);
        $menu = $this->menuRepository->findById($menuId);

        if ($user === null) {
            throw new \InvalidArgumentException('Utilisateur non trouvé.');
        }
        if ($menu === null) {
            throw new \InvalidArgumentException('Menu non trouvé ou indisponible.');
        }
        if ($menu->getMinPeople() < 1) {
            throw new \InvalidArgumentException('Le minimum de personnes du menu est invalide.');
        }
        if ($numberOfPeople < $menu->getMinPeople()) {
            throw new \InvalidArgumentException(
                'Le nombre de personnes doit être supérieur ou égal au minimum requis pour ce menu (' .
                $menu->getMinPeople() . ' personnes).'
            );
        }
        if (trim($deliveryAddress) === '' || trim($deliveryCity) === '') {
            throw new \InvalidArgumentException('L’adresse et la ville de livraison sont requises.');
        }

        [$menuPrice, $discountRate] = $this->calculateMenuPrice($menu->getBasePrice(), $menu->getMinPeople(), $numberOfPeople);

        $deliveryCost = $this->calculateDeliveryCost($deliveryCity, $deliveryDistanceKm);
        $totalPrice = round($menuPrice + $deliveryCost, 2);

        $orderData = [
            'user_id' => $userId,
            'menu_id' => $menuId,
            'number_of_people' => $numberOfPeople,
            'order_date' => date('Y-m-d H:i:s'),
            'delivery_date' => $deliveryDate,
            'delivery_time' => $deliveryTime,
            'delivery_address' => trim($deliveryAddress),
            'delivery_city' => trim($deliveryCity),
            'delivery_postal_code' => trim($deliveryPostalCode),
            'delivery_distance_km' => $deliveryDistanceKm,
            'delivery_cost' => $deliveryCost,
            'menu_price' => $menuPrice,
            'discount_rate' => $discountRate,
            'total_price' => $totalPrice,
            'status' => 'pending',
            'equipment_loaned' => false,
        ];

        $pdo = Database::getPDO();
        $pdo->beginTransaction();

        try {
            $orderId = $this->orderRepository->create($orderData);
            $this->orderRepository->decreaseMenuStock($menuId);
            $this->orderRepository->addToHistory($orderId, 'pending', $userId, 'Commande créée');
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        try {
            $this->mailService->sendOrderConfirmationEmail(
                $user->getEmail(),
                $user->getFirstName(),
                [
                    'id' => $orderId,
                    'order_date' => $orderData['order_date'],
                    'menu_title' => $menu->getTitle(),
                    'number_of_people' => $numberOfPeople,
                    'menu_price' => $menuPrice,
                    'delivery_cost' => $deliveryCost,
                    'total_price' => $totalPrice,
                ]
            );
        } catch (\Throwable $e) {
            error_log('Order confirmation email error: ' . $e->getMessage());
        }

        return [
            'success' => true,
            'order_id' => $orderId,
            'menu_price' => $menuPrice,
            'discount_rate' => $discountRate,
            'delivery_cost' => $deliveryCost,
            'total_price' => $totalPrice,
        ];
    }

    private function calculateDeliveryCost(string $deliveryCity, ?float $deliveryDistanceKm): float
    {
        $city = mb_strtolower(trim($deliveryCity));

        if ($city === '') {
            throw new \InvalidArgumentException('La ville de livraison est requise.');
        }
        if ($city === 'bordeaux') {
            return 0.00;
        }
        if ($deliveryDistanceKm === null || $deliveryDistanceKm < 0) {
            throw new \InvalidArgumentException('La distance de livraison est requise hors Bordeaux.');
        }

        return round(5.00 + (0.59 * $deliveryDistanceKm), 2);
    }

    public function getUserOrders(int $userId): array
    {
        return $this->orderRepository->findByUserId($userId);
    }

    public function getOrderById(int $orderId): ?Order
    {
        return $this->orderRepository->findById($orderId);
    }

    public function updateCustomerOrder(int $orderId, int $userId, int $numberOfPeople, string $deliveryDate, string $deliveryTime, string $address, string $city, string $postalCode, ?float $distanceKm): void
    {
        $order = $this->orderRepository->findById($orderId);
        if ($order === null || $order->getUserId() !== $userId) {
            throw new \InvalidArgumentException('Commande introuvable.');
        }
        if ($order->getStatus() !== 'pending') {
            throw new \InvalidArgumentException('Cette commande ne peut plus être modifiée.');
        }
        if ($numberOfPeople < 1) {
            throw new \InvalidArgumentException('Le nombre de personnes doit être supérieur à 0.');
        }
        if (!preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $deliveryDate) || $deliveryDate < date('Y-m-d')) {
            throw new \InvalidArgumentException('La date de livraison est invalide.');
        }
        if (!preg_match('/^\\d{2}:\\d{2}$/', $deliveryTime)) {
            throw new \InvalidArgumentException('L’heure de livraison est invalide.');
        }
        $timeParts = array_map('intval', explode(':', $deliveryTime));
        if ($timeParts[0] > 23 || $timeParts[1] > 59) {
            throw new \InvalidArgumentException('L’heure de livraison est invalide.');
        }
        if (trim($address) === '' || trim($city) === '') {
            throw new \InvalidArgumentException('L’adresse et la ville sont requises.');
        }
        if ($distanceKm !== null && $distanceKm < 0) {
            throw new \InvalidArgumentException('La distance de livraison est invalide.');
        }
        $menu = $this->menuRepository->findById($order->getMenuId());
        if ($menu === null || $numberOfPeople < $menu->getMinPeople()) {
            throw new \InvalidArgumentException('Le nombre de personnes est inférieur au minimum du menu.');
        }
        [$menuPrice, $discountRate] = $this->calculateMenuPrice($menu->getBasePrice(), $menu->getMinPeople(), $numberOfPeople);
        $deliveryCost = $this->calculateDeliveryCost($city, $distanceKm);
        $this->orderRepository->updateCustomerOrder(
            $orderId, $numberOfPeople, $deliveryDate, $deliveryTime, trim($address), trim($city),
            trim($postalCode), $distanceKm, $menuPrice, $deliveryCost, $discountRate,
            round($menuPrice + $deliveryCost, 2)
        );
    }

    public function updateOrderStatus(int $orderId, string $status, ?int $changedByUserId = null, string $notes = '', ?string $cancellationReason = null, ?bool $equipmentLoaned = null): void
    {
        $order = $this->orderRepository->findById($orderId);
        if ($order === null) {
            throw new \InvalidArgumentException('Commande non trouvée.');
        }

        $validStatuses = ['pending', 'accepted', 'preparing', 'delivering', 'delivered', 'awaiting_return', 'completed', 'cancelled'];
        if (!in_array($status, $validStatuses, true)) {
            throw new \InvalidArgumentException('Statut de commande invalide.');
        }

        if ($order->getStatus() === $status) {
            throw new \InvalidArgumentException('La commande possède déjà ce statut.');
        }

        if ($equipmentLoaned !== null && $order->isEquipmentLoaned() !== $equipmentLoaned) {
            $this->orderRepository->setEquipmentLoaned($orderId, $equipmentLoaned);
            $order->setEquipmentLoaned($equipmentLoaned);
        }

        $allowedTransitions = [
            'pending' => ['accepted', 'cancelled'],
            'accepted' => ['preparing', 'cancelled'],
            'preparing' => ['delivering', 'cancelled'],
            'delivering' => ['delivered', 'cancelled'],
            'delivered' => ['awaiting_return', 'completed'],
            'awaiting_return' => ['completed'],
            'completed' => [],
            'cancelled' => [],
        ];

        if (!in_array($status, $allowedTransitions[$order->getStatus()] ?? [], true)) {
            throw new \InvalidArgumentException('Transition de statut non autorisée.');
        }

        if ($order->getStatus() === 'delivered' && $status === 'awaiting_return' && !$order->isEquipmentLoaned()) {
            throw new \InvalidArgumentException('Le statut « en attente du retour de matériel » nécessite un prêt de matériel.');
        }
        if ($order->getStatus() === 'delivered' && $status === 'completed' && $order->isEquipmentLoaned()) {
            throw new \InvalidArgumentException('Cette commande doit passer par le retour du matériel avant d’être terminée.');
        }

        if ($status === 'cancelled' && trim((string) $cancellationReason) === '') {
            throw new \InvalidArgumentException('Un motif est obligatoire pour annuler une commande.');
        }

        $this->orderRepository->updateStatus($orderId, $status, $status === 'cancelled' ? trim((string) $cancellationReason) : null);
        if ($status === 'cancelled' && $order->getStatus() !== 'cancelled') {
            $this->orderRepository->increaseMenuStock($order->getMenuId());
        }
        $this->orderRepository->addToHistory($orderId, $status, $changedByUserId, $notes);

        $user = $this->userRepository->findById($order->getUserId());
        if ($user !== null) {
            try {
                if ($status === 'awaiting_return') {
                    $this->mailService->sendEquipmentReturnNoticeEmail($user->getEmail(), $user->getFirstName(), $orderId);
                } elseif ($status === 'completed') {
                    $this->mailService->sendReviewInvitationEmail($user->getEmail(), $user->getFirstName(), $orderId);
                    try {
                        $this->menuStatisticsService->aggregateAndStore();
                    } catch (\Throwable $statisticsError) {
                        error_log('Mise à jour des statistiques MongoDB impossible : ' . $statisticsError->getMessage());
                    }
                }
            } catch (\Throwable $e) { error_log('Order status email error: ' . $e->getMessage()); }
        }
    }

    public function getOrderHistory(int $orderId): array
    {
        return $this->orderRepository->getHistory($orderId);
    }
}
