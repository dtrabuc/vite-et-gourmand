<?php
namespace App\Controller;

use App\Service\OrderService;
use App\Service\AuthService;
use App\Service\MailService;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use App\Repository\MenuRepository;

class OrderController extends BaseController
{
    private OrderService $orderService;
    private AuthService $authService;

    public function __construct()
    {
        $this->orderService = new OrderService(
            new OrderRepository(),
            new UserRepository(),
            new MenuRepository(),
            new MailService()
        );
        $this->authService = new AuthService(new UserRepository());
    }

    public function index(): void
    {
        // Apply auth middleware
        (new \App\Middleware\Auth())();

        $userId = $_SESSION['user_id'] ?? 0;
        if ($userId === 0) {
            header('Location: /login');
            exit;
        }

        $orders = $this->orderService->getUserOrders($userId);

        $this->render('order/index', ['orders' => $orders]);
    }

    public function create(): void
    {
        // Apply auth middleware
        (new \App\Middleware\Auth())();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Method Not Allowed';
            return;
        }

        $userId = $_SESSION['user_id'] ?? 0;
        if ($userId === 0) {
            header('Location: /login');
            exit;
        }

        // Get and validate input
        $menuId = $_POST['menu_id'] ?? null;
        $numberOfPeople = $_POST['number_of_people'] ?? null;
        $deliveryDate = $_POST['delivery_date'] ?? null;
        $deliveryTime = $_POST['delivery_time'] ?? null;
        $deliveryAddress = trim((string)($_POST['delivery_address'] ?? ''));
        $deliveryCity = trim((string)($_POST['delivery_city'] ?? ''));
        $deliveryDistanceKm = isset($_POST['delivery_distance_km']) && $_POST['delivery_distance_km'] !== ''
            ? (float)$_POST['delivery_distance_km'] : null;

        $errors = [];

        if (empty($menuId) || !is_numeric($menuId)) {
            $errors['menu_id'] = 'Menu requis';
        }
        if (empty($numberOfPeople) || !is_numeric($numberOfPeople) || (int)$numberOfPeople < 1) {
            $errors['number_of_people'] = 'Nombre de personnes requis et doit être supérieur à 0';
        }
        if (empty($deliveryDate)) {
            $errors['delivery_date'] = 'Date de livraison requise';
        }
        if (empty($deliveryTime)) {
            $errors['delivery_time'] = 'Heure de livraison requise';
        }
        if ($deliveryAddress === '') {
            $errors['delivery_address'] = 'Adresse de livraison requise';
        }
        if ($deliveryCity === '') {
            $errors['delivery_city'] = 'Ville de livraison requise';
        }
        if ($deliveryCity !== '' && mb_strtolower($deliveryCity) !== 'bordeaux' && ($deliveryDistanceKm === null || $deliveryDistanceKm < 0)) {
            $errors['delivery_distance_km'] = 'Distance de livraison requise hors Bordeaux';
        }

        if (!empty($errors)) {
            // For AJAX requests, return JSON
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'errors' => $errors]);
                return;
            }

            // For form redirects
            $_SESSION['order_errors'] = $errors;
            $_SESSION['order_old_input'] = $_POST;
            header('Location: /orders/new');
            exit;
        }

        try {
            $result = $this->orderService->createOrder(
                (int)$userId,
                (int)$menuId,
                (int)$numberOfPeople,
                $deliveryDate,
                $deliveryTime,
                $deliveryAddress,
                $deliveryCity,
                $deliveryDistanceKm
            );

            // For AJAX requests
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'data' => $result]);
                return;
            }

            // Redirect to order confirmation page
            $_SESSION['order_success'] = 'Commande créée avec succès';
            header('Location: /orders/confirmation/' . $result['order_id']);
            exit;
        } catch (\InvalidArgumentException $e) {
            // Validation error from service
            $_SESSION['order_errors'] = ['general' => $e->getMessage()];
            header('Location: /orders/new');
            exit;
        } catch (\Exception $e) {
            // Other error
            error_log('Order creation error: ' . $e->getMessage());
            $_SESSION['order_errors'] = ['general' => 'Une erreur est survenue lors de la création de la commande'];
            header('Location: /orders/new');
            exit;
        }
    }

    public function new(): void
    {
        (new \App\Middleware\Auth())();

        $menus = (new MenuRepository())->findAll();
        $selectedMenuId = isset($_GET['menu']) ? (int) $_GET['menu'] : 0;

        $this->render('order/new', [
            'menus' => $menus,
            'selectedMenuId' => $selectedMenuId,
        ]);
    }

    public function confirmation(array $params): void
    {
        // Apply auth middleware
        (new \App\Middleware\Auth())();

        $orderId = (int)$params[0] ?? 0;
        if ($orderId <= 0) {
            header('Location: /');
            exit;
        }

        $order = $this->orderService->getOrderById($orderId);

        if ($order === null || $order->getUserId() !== ($_SESSION['user_id'] ?? 0)) {
            header('Location: /');
            exit;
        }

        // Get menu details for display
        $menuService = new \App\Service\MenuService(new \App\Repository\MenuRepository());
        $menu = $menuService->getMenuById($order->getMenuId());

        $this->render('order/confirmation', [
            'order' => $order,
            'menu' => $menu
        ]);
    }

    public function updateStatus(array $params): void
    {
        // Apply auth middleware
        (new \App\Middleware\Auth())();

        if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            // Handle form submission via POST with _method=PUT
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['_method']) || strtoupper($_POST['_method']) !== 'PUT') {
                http_response_code(405);
                echo 'Method Not Allowed';
                return;
            }
        }

        $orderId = (int)$params[0] ?? 0;
        if ($orderId <= 0) {
            http_response_code(400);
            echo 'Invalid order ID';
            return;
        }

        $status = $_POST['status'] ?? '';

        // Validate status transition (basic validation - more complex logic could be in service)
        $validStatuses = ['pending', 'accepted', 'preparing', 'delivering', 'delivered', 'awaiting_return', 'completed', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            http_response_code(400);
            echo 'Invalid status';
            return;
        }

        $userId = $_SESSION['user_id'] ?? 0;
        $role = $_SESSION['role'] ?? '';

        // Only allow employees and admins to update status (or the owner for cancellation?)
        // For simplicity, we'll allow authenticated users to update their own orders to cancelled if pending
        // In a real app, we'd have more complex business logic
        $order = $this->orderService->getOrderById($orderId);
        if ($order === null) {
            http_response_code(404);
            echo 'Order not found';
            return;
        }

        // Check permissions: user owns the order OR is employee/admin
        $isOwner = $order->getUserId() === $userId;
        $isEmployeeOrAdmin = in_array($role, ['employee', 'admin']);

        if (!$isOwner && !$isEmployeeOrAdmin) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        // Additional business logic: if user is owner, only allow cancellation if pending
        if ($isOwner && !$isEmployeeOrAdmin) {
            if ($status !== 'cancelled' || $order->getStatus() !== 'pending') {
                http_response_code(403);
                echo 'You can only cancel pending orders';
                return;
            }
        }

        try {
            $this->orderService->updateOrderStatus($orderId, $status, $userId, 'Status updated via interface');

            // For AJAX requests
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Statut mis à jour']);
                return;
            }

            $_SESSION['order_status_success'] = 'Statut de la commande mis à jour';
            header('Location: /orders');
            exit;
        } catch (\Exception $e) {
            error_log('Order status update error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal Server Error';
        }
    }

    
    }
