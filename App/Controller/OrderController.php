<?php
namespace App\Controller;

use App\Service\OrderService;
use App\Service\AuthService;
use App\Service\MailService;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use App\Repository\MenuRepository;
use App\Service\CommentService;
use App\Repository\CommentRepository;

class OrderController extends BaseController
{
    private OrderService $orderService;
    private AuthService $authService;
    private CommentService $commentService;

    public function __construct()
    {
        $this->orderService = new OrderService(
            new OrderRepository(),
            new UserRepository(),
            new MenuRepository(),
            new MailService()
        );
        $this->authService = new AuthService(new UserRepository());
        $this->commentService = new CommentService(new CommentRepository());
    }

    public function index(): void
    {
        (new \App\Middleware\Auth())();
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId === 0) {
            header('Location: /login');
            exit;
        }

        $orders = $this->orderService->getUserOrders($userId);
        $history = [];
        $reviews = [];
        foreach ($orders as $order) {
            $history[$order->getId()] = $this->orderService->getOrderHistory($order->getId());
            $reviews[$order->getId()] = $this->commentService->getByOrderId($order->getId());
        }
        $this->render('order/index', [
            'orders' => $orders,
            'history' => $history,
            'reviews' => $reviews,
        ]);
    }

    public function create(): void
    {
        (new \App\Middleware\Auth())();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Method Not Allowed';
            return;
        }

        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId === 0) {
            header('Location: /login');
            exit;
        }

        $menuId = $_POST['menu_id'] ?? null;
        $numberOfPeople = $_POST['number_of_people'] ?? null;
        $deliveryDate = trim((string) ($_POST['delivery_date'] ?? ''));
        $deliveryTime = trim((string) ($_POST['delivery_time'] ?? ''));
        $deliveryAddress = trim((string) ($_POST['delivery_address'] ?? ''));
        $deliveryCity = trim((string) ($_POST['delivery_city'] ?? ''));
        $deliveryPostalCode = trim((string) ($_POST['delivery_postal_code'] ?? ''));
        $deliveryDistanceKm = isset($_POST['delivery_distance_km']) && $_POST['delivery_distance_km'] !== ''
            ? (float) $_POST['delivery_distance_km']
            : null;

        $errors = [];

        if (empty($menuId) || !is_numeric($menuId)) {
            $errors['menu_id'] = 'Menu requis.';
        }
        if (empty($numberOfPeople) || !is_numeric($numberOfPeople) || (int) $numberOfPeople < 1) {
            $errors['number_of_people'] = 'Nombre de personnes requis et supérieur à 0.';
        }
        if ($deliveryDate === '') {
            $errors['delivery_date'] = 'Date de livraison requise.';
        } elseif (!preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $deliveryDate)) {
            $errors['delivery_date'] = 'Date de livraison invalide.';
        } elseif ($deliveryDate < date('Y-m-d')) {
            $errors['delivery_date'] = 'La date de livraison ne peut pas être passée.';
        }
        if ($deliveryTime === '') {
            $errors['delivery_time'] = 'Heure de livraison requise.';
        } elseif (!preg_match('/^\\d{2}:\\d{2}$/', $deliveryTime)) {
            $errors['delivery_time'] = 'Heure de livraison invalide.';
        }
        if ($deliveryAddress === '') {
            $errors['delivery_address'] = 'Adresse de livraison requise.';
        }
        if ($deliveryCity === '') {
            $errors['delivery_city'] = 'Ville de livraison requise.';
        }
        if ($deliveryCity !== '' && mb_strtolower($deliveryCity) !== 'bordeaux' &&
            ($deliveryDistanceKm === null || $deliveryDistanceKm < 0)) {
            $errors['delivery_distance_km'] = 'Distance de livraison requise hors Bordeaux.';
        }

        if ($errors !== []) {
            $_SESSION['order_errors'] = $errors;
            $_SESSION['order_old_input'] = $_POST;
            header('Location: /orders/new');
            exit;
        }

        try {
            $result = $this->orderService->createOrder(
                $userId,
                (int) $menuId,
                (int) $numberOfPeople,
                $deliveryDate,
                $deliveryTime,
                $deliveryAddress,
                $deliveryCity,
                $deliveryPostalCode,
                $deliveryDistanceKm
            );

            header('Location: /orders/confirmation/' . $result['order_id']);
            exit;
        } catch (\InvalidArgumentException $e) {
            $_SESSION['order_errors'] = ['general' => $e->getMessage()];
            $_SESSION['order_old_input'] = $_POST;
            header('Location: /orders/new');
            exit;
        } catch (\Throwable $e) {
            error_log('Order creation error: ' . $e->getMessage());
            $_SESSION['order_errors'] = ['general' => 'Une erreur est survenue lors de la création de la commande.'];
            $_SESSION['order_old_input'] = $_POST;
            header('Location: /orders/new');
            exit;
        }
    }

    public function new(): void
    {
        (new \App\Middleware\Auth())();

        $menus = (new MenuRepository())->findAll();
        $selectedMenuId = isset($_GET['menu']) ? (int) $_GET['menu'] : 0;
        $user = (new UserRepository())->findById((int) ($_SESSION['user_id'] ?? 0));

        $this->render('order/new', [
            'menus' => $menus,
            'selectedMenuId' => $selectedMenuId,
            'orderUser' => $user,
        ]);
    }

    public function confirmation(array $params): void
    {
        (new \App\Middleware\Auth())();

        $orderId = (int) ($params[0] ?? 0);
        if ($orderId <= 0) {
            header('Location: /');
            exit;
        }

        $order = $this->orderService->getOrderById($orderId);
        if ($order === null || $order->getUserId() !== (int) ($_SESSION['user_id'] ?? 0)) {
            header('Location: /');
            exit;
        }

        $menu = (new \App\Service\MenuService(new MenuRepository()))->getMenuById($order->getMenuId());

        $this->render('order/confirmation', [
            'order' => $order,
            'menu' => $menu,
        ]);
    }

    public function updateCustomerOrder(array $params): void
    {
        (new \App\Middleware\Auth())();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); return; }
        $orderId = (int)($params[0] ?? 0);
        $userId = (int)($_SESSION['user_id'] ?? 0);
        try {
            $distance = ($_POST['delivery_distance_km'] ?? '') !== '' ? (float)$_POST['delivery_distance_km'] : null;
            $this->orderService->updateCustomerOrder(
                $orderId, $userId, (int)$_POST['number_of_people'], trim((string)$_POST['delivery_date']),
                trim((string)$_POST['delivery_time']), trim((string)$_POST['delivery_address']),
                trim((string)$_POST['delivery_city']), trim((string)$_POST['delivery_postal_code']), $distance
            );
            $_SESSION['order_success'] = 'Commande modifiée.';
        } catch (\Throwable $e) {
            $_SESSION['order_error'] = $e->getMessage();
        }
        header('Location: /orders');
        exit;
    }

    public function review(array $params): void
    {
        (new \App\Middleware\Auth())();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); return; }

        $orderId = (int)($params[0] ?? 0);
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $order = $this->orderService->getOrderById($orderId);
        if ($order === null || $order->getUserId() !== $userId) { http_response_code(403); return; }
        if ($order->getStatus() !== 'completed') {
            $_SESSION['order_error'] = 'Un avis est possible après la fin de la prestation.';
            header('Location: /orders'); exit;
        }

        try {
            $this->commentService->create([
                'user_id' => $userId,
                'order_id' => $orderId,
                'menu_id' => $order->getMenuId(),
                'rating' => (int)($_POST['rating'] ?? 0),
                'comment' => trim((string)($_POST['comment'] ?? '')),
            ]);
            $_SESSION['order_success'] = 'Votre avis a été transmis pour validation.';
        } catch (\Throwable $e) {
            $_SESSION['order_error'] = $e->getMessage();
        }
        header('Location: /orders');
        exit;
    }

    public function updateStatus(array $params): void
    {
        (new \App\Middleware\Auth())();

        $orderId = (int) ($params[0] ?? 0);
        $status = trim((string) ($_POST['status'] ?? ''));
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $role = (string) ($_SESSION['role'] ?? '');

        if ($orderId <= 0 || $status === '') {
            http_response_code(400);
            echo 'Requête invalide';
            return;
        }

        $order = $this->orderService->getOrderById($orderId);
        if ($order === null) {
            http_response_code(404);
            echo 'Commande introuvable';
            return;
        }

        $isOwner = $order->getUserId() === $userId;
        $isStaff = in_array($role, ['employee', 'admin'], true);

        if (!$isOwner && !$isStaff) {
            http_response_code(403);
            echo 'Accès refusé';
            return;
        }

        $cancellationReason = trim((string) ($_POST['cancellation_reason'] ?? ''));
        $notes = trim((string) ($_POST['notes'] ?? ''));
        $equipmentLoaned = $isStaff && array_key_exists('equipment_loaned', $_POST)
            ? ((string) $_POST['equipment_loaned'] === '1')
            : null;

        if (!$isStaff && ($status !== 'cancelled' || $order->getStatus() !== 'pending')) {
            http_response_code(403);
            echo 'Une commande ne peut être annulée par le client que lorsqu’elle est en attente.';
            return;
        }

        if ($status === 'cancelled' && $isStaff) {
            $contactMode = trim((string) ($_POST['contact_mode'] ?? ''));
            if ($contactMode === '') {
                http_response_code(400);
                echo 'Le mode de contact du client est obligatoire pour une annulation.';
                return;
            }
            $notes = 'Contact client : ' . $contactMode . ($notes !== '' ? ' — ' . $notes : '');
        }

        if ($status === 'cancelled' && $cancellationReason === '') {
            http_response_code(400);
            echo 'Le motif d’annulation est obligatoire.';
            return;
        }

        try {
            $this->orderService->updateOrderStatus(
                $orderId,
                $status,
                $userId,
                $notes,
                $cancellationReason !== '' ? $cancellationReason : null,
                $equipmentLoaned
            );

            header('Location: /orders');
            exit;
        } catch (\InvalidArgumentException $e) {
            http_response_code(400);
            echo $e->getMessage();
        } catch (\Throwable $e) {
            error_log('Order status update error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Erreur serveur';
        }
    }
}
