<?php
namespace Src\Service;

use Src\Entity\Order;
use Src\Entity\User;
use Src\Entity\Menu;
use Src\Repository\OrderRepository;
use Src\Repository\UserRepository;
use Src\Repository\MenuRepository;
use Src\Service\MailService;

class OrderService
{
    private OrderRepository $orderRepository;
    private UserRepository $userRepository;
    private MenuRepository $menuRepository;
    private MailService $mailService;

    public function __construct(
        OrderRepository $orderRepository,
        UserRepository $userRepository,
        MenuRepository $menuRepository,
        MailService $mailService
    ) {
        $this->orderRepository = $orderRepository;
        $this->userRepository = $userRepository;
        $this->menuRepository = $menuRepository;
        $this->mailService = $mailService;
    }

    public function createOrder(int $userId, int $menuId, int $numberOfPeople, string $deliveryDate, string $deliveryTime, string $deliveryAddress): array
    {
        // Get user and menu
        $user = $this->userRepository->findById($userId);
        $menu = $this->menuRepository->findById($menuId);

        if ($user === null) {
            throw new \InvalidArgumentException('Utilisateur non trouvé');
        }

        if ($menu === null) {
            throw new \InvalidArgumentException('Menu non trouvé');
        }

        // Validate number of people >= minimum required
        if ($numberOfPeople < $menu->getMinPeople()) {
            throw new \InvalidArgumentException(
                'Le nombre de personnes doit être supérieur ou égal au minimum requis pour ce menu (' .
                $menu->getMinPeople() . ' personnes)'
            );
        }

        // Calculate menu price with potential discount
        $menuPrice = $menu->getBasePrice() * $numberOfPeople;
        $discountApplied = false;

        // Apply 10% discount if number of people >= minimum + 5
        if ($numberOfPeople >= ($menu->getMinPeople() + 5)) {
            $menuPrice *= 0.9; // 10% discount
            $discountApplied = true;
        }

        // Calculate delivery cost (simplified: 5€ base + 0.59€/km for addresses outside Bordeaux)
        // For simplicity, we'll use a fixed delivery cost of 5€ for now
        // In a real implementation, we would calculate distance from Bordeaux to delivery address
        $deliveryCost = 5.00; // Simplified - would be calculated based on distance

        // Calculate total price
        $totalPrice = $menuPrice + $deliveryCost;

        // Create order
        $orderData = [
            'user_id' => $userId,
            'menu_id' => $menuId,
            'number_of_people' => $numberOfPeople,
            'order_date' => date('Y-m-d H:i:s'),
            'delivery_date' => $deliveryDate,
            'delivery_time' => $deliveryTime,
            'delivery_address' => $deliveryAddress,
            'delivery_cost' => $deliveryCost,
            'menu_price' => $menuPrice,
            'total_price' => $totalPrice,
            'status' => 'pending',
        ];

        $orderId = $this->orderRepository->create($orderData);

        // Decrease menu stock
        $this->orderRepository->decreaseMenuStock($menuId);

        // Add to order status history
        $this->orderRepository->addToHistory($orderId, 'pending', $userId, 'Commande créée');

        // Send confirmation email
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

        return [
            'success' => true,
            'order_id' => $orderId,
            'menu_price' => $menuPrice,
            'delivery_cost' => $deliveryCost,
            'total_price' => $totalPrice,
            'discount_applied' => $discountApplied,
        ];
    }

    public function getUserOrders(int $userId): array
    {
        return $this->orderRepository->findByUserId($userId);
    }

    public function getOrderById(int $orderId): ?Order
    {
        return $this->orderRepository->findById($orderId);
    }

    public function updateOrderStatus(int $orderId, string $status, int $changedByUserId = null, string $notes = ''): void
    {
        // Get the order to verify it exists
        $order = $this->orderRepository->findById($orderId);
        if ($order === null) {
            throw new \InvalidArgumentException('Commande non trouvée');
        }

        // Update status
        $this->orderRepository->updateStatus($orderId, $status);

        // Add to history
        $this->orderRepository->addToHistory($orderId, $status, $changedByUserId, $notes);
    }

    public function getOrderHistory(int $orderId): array
    {
        return $this->orderRepository->getHistory($orderId);
    }
}