<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Exception\NotFoundException;
use App\Core\Labels;
use App\Core\Session;
use App\Repository\UserRepository;
use App\Service\CommentService;
use App\Service\MenuService;
use App\Service\OrderService;
use InvalidArgumentException;

final class OrderController extends BaseController
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly UserRepository $userRepository,
        private readonly MenuService $menuService,
        private readonly CommentService $commentService
    ) {
    }

    public function index(): void
    {
        $userId = Session::id();

        if ($userId === null) {
            $this->redirect('/login');
        }

        $orders = $this->orderService->getUserOrders($userId);
        $history = [];
        $reviews = [];

        foreach ($orders as $order) {
            $history[$order->getId()] = $this->orderService->getOrderHistory($order->getId());
            $reviews[$order->getId()] = $this->commentService->getByOrderId($order->getId());
        }

        $this->render('order/index', [
            'phoneInput' => true, // page avec champ téléphone : charge intl-tel-input
            'orders' => $orders,
            'history' => $history,
            'reviews' => $reviews,
        ]);
    }

    public function new(): void
    {
        $userId = Session::id();

        if ($userId === null) {
            $this->redirect('/login');
        }

        $menus = $this->menuService->getAllMenus();
        $user = $this->userRepository->findById($userId);

        $errors = Session::pullFlash('order_errors', []);
        $oldInput = Session::pullFlash('order_old_input', []);

        // Menu pré-sélectionné : valeur ressaisie après une erreur, sinon ?menu=ID.
        $requestedMenuId = is_array($oldInput) && isset($oldInput['menu_id'])
            ? $oldInput['menu_id']
            : ($_GET['menu'] ?? null);
        $selectedMenuId = filter_var($requestedMenuId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $selectedMenu = $selectedMenuId !== false ? $this->menuService->getMenuById($selectedMenuId) : null;

        $this->render('order/new', [
            'phoneInput' => true, // page avec champ téléphone : charge intl-tel-input
            'menus' => $menus,
            'selectedMenu' => $selectedMenu,
            'selectedServiceType' => is_array($oldInput) && array_key_exists(
                (string) ($oldInput['service_type'] ?? ''),
                Labels::SERVICE_TYPE
            ) ? $oldInput['service_type'] : 'delivery',
            'errors' => is_array($errors) ? $errors : [],
            'oldInput' => is_array($oldInput) ? $oldInput : [],
            'orderUser' => $user,
        ]);
    }

    public function menuOptions(): never
    {
        $numberOfPeople = filter_var(
            $_GET['number_of_people'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($numberOfPeople === false) {
            $this->json(['success' => false, 'error' => 'Nombre de convives invalide.'], 422);
        }

        $this->json([
            'success' => true,
            'data' => [
                'menus' => $this->orderService->getMenuAvailabilityForPeople($numberOfPeople),
            ],
        ]);
    }

    public function availability(): never
    {
        $date = trim((string) ($_GET['date'] ?? ''));

        if ($date === '') {
            $this->json(['success' => false, 'error' => 'Date de prestation requise.'], 422);
        }

        try {
            $this->json([
                'success' => true,
                'data' => $this->orderService->getServiceDateAvailability($date),
            ]);
        } catch (InvalidArgumentException $exception) {
            $this->json(['success' => false, 'error' => $exception->getMessage()], 422);
        }
    }

    public function pricePreview(): never
    {
        $menuId = filter_var(
            $_GET['menu_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        $numberOfPeople = filter_var(
            $_GET['number_of_people'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($menuId === false || $numberOfPeople === false) {
            $this->json(['success' => false, 'error' => 'Menu ou nombre de convives invalide.'], 422);
        }

        $serviceType = trim((string) ($_GET['service_type'] ?? 'delivery'));

        try {
            $this->json([
                'success' => true,
                'data' => $this->orderService->calculatePricePreview(
                    $menuId,
                    $numberOfPeople,
                    $serviceType,
                    trim((string) ($_GET['delivery_address'] ?? '')),
                    trim((string) ($_GET['delivery_postal_code'] ?? '')),
                    trim((string) ($_GET['delivery_city'] ?? ''))
                ),
            ]);
        } catch (InvalidArgumentException $exception) {
            $this->json(['success' => false, 'error' => $exception->getMessage()], 422);
        }
    }

    public function create(): void
    {
        $userId = Session::id();

        if ($userId === null) {
            $this->redirect('/login');
        }

        $input = $this->postFields([
            'menu_id',
            'number_of_people',
            'confirmed_guest_count',
            'service_type',
            'delivery_date',
            'delivery_time',
            'delivery_address',
            'delivery_city',
            'delivery_postal_code',
            'delivery_instructions',
            'contact_phone',
        ]);

        // Contrôles propres au formulaire. Les règles métier (menu, stock, minimum,
        // créneau, téléphone, adresse, livraison) sont appliquées par OrderService.
        $menuId = filter_var($input['menu_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $numberOfPeople = filter_var($input['number_of_people'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $errors = [];

        if ($menuId === false) {
            $errors['menu_id'] = 'Menu requis.';
        }

        if ($numberOfPeople === false) {
            $errors['number_of_people'] = 'Nombre de personnes requis et supérieur à 0.';
        } elseif ((int) $input['confirmed_guest_count'] !== $numberOfPeople) {
            $errors['number_of_people'] = 'Confirmez le nombre de convives avant de sélectionner un menu.';
        }

        if ($errors !== []) {
            Session::flash('order_errors', $errors);
            Session::flash('order_old_input', $input);
            $this->redirect('/orders/new');
        }

        try {
            $result = $this->orderService->createOrder(
                $userId,
                $menuId,
                $numberOfPeople,
                $input['delivery_date'],
                $input['delivery_time'],
                $input['delivery_address'],
                $input['delivery_city'],
                $input['delivery_postal_code'],
                null,
                $input['service_type'] !== '' ? $input['service_type'] : 'delivery',
                'cash_on_site',
                $input['delivery_instructions'],
                $input['contact_phone']
            );

            $this->redirect('/orders/confirmation/' . $result['order_id']);
        } catch (InvalidArgumentException $exception) {
            Session::flash('order_errors', ['general' => $exception->getMessage()]);
            Session::flash('order_old_input', $input);
            $this->redirect('/orders/new');
        } catch (\Throwable $exception) {
            error_log('Order creation error: ' . $exception->getMessage());
            Session::flash('order_errors', ['general' => 'Une erreur est survenue lors de la création de la commande.']);
            Session::flash('order_old_input', $input);
            $this->redirect('/orders/new');
        }
    }

    public function confirmation(int $id): void
    {
        $userId = Session::id();
        $order = $this->orderService->getOrderById($id);

        if ($userId === null || $order === null || $order->getUserId() !== $userId) {
            $this->redirect('/');
        }

        $menu = $this->menuService->getMenuById($order->getMenuId());

        $this->render('order/confirmation', [
            'order' => $order,
            'menu' => $menu,
        ]);
    }

    public function updateCustomerOrder(int $id): void
    {
        $userId = Session::id();

        if ($userId === null) {
            $this->redirect('/login');
        }

        try {
            $this->orderService->updateCustomerOrder(
                $id,
                $userId,
                $this->postFields(OrderService::EDITABLE_FIELDS)
            );
            Session::flash('order_success', 'Commande modifiée.');
        } catch (InvalidArgumentException $exception) {
            Session::flash('order_error', $exception->getMessage());
        } catch (\Throwable $exception) {
            error_log('Order customer update error: ' . $exception->getMessage());
            Session::flash('order_error', 'La commande n’a pas pu être modifiée.');
        }

        $this->redirect('/orders');
    }

    public function review(int $id): void
    {
        $userId = Session::id();

        if ($userId === null) {
            $this->redirect('/login');
        }

        $order = $this->orderService->getOrderById($id);

        // commande inconnue ou d'un autre client : 404 commune, on ne dit pas si elle existe
        if ($order === null || $order->getUserId() !== $userId) {
            throw new NotFoundException('Commande introuvable.');
        }

        if ($order->getStatus() !== 'completed') {
            Session::flash('order_error', 'Un avis est possible après la fin de la prestation.');
            $this->redirect('/orders');
        }

        try {
            $this->commentService->create([
                'user_id' => $userId,
                'order_id' => $id,
                'menu_id' => $order->getMenuId(),
                'rating' => (int) ($_POST['rating'] ?? 0),
                'comment' => trim((string) ($_POST['comment'] ?? '')),
            ]);
            Session::flash('order_success', 'Votre avis a été transmis pour validation.');
        } catch (\Throwable $exception) {
            Session::flash('order_error', $exception->getMessage());
        }

        $this->redirect('/orders');
    }

    public function updateStatus(int $id): void
    {
        $userId = Session::id();
        $role = Session::role();

        if ($userId === null) {
            $this->redirect('/login');
        }

        $order = $this->orderService->getOrderById($id);
        $isStaff = in_array($role, ['employee', 'admin'], true);

        // commande inconnue ou d'un autre client : 404 commune
        if ($order === null || ($order->getUserId() !== $userId && !$isStaff)) {
            throw new NotFoundException('Commande introuvable.');
        }

        $status = trim((string) ($_POST['status'] ?? ''));
        $cancellationReason = trim((string) ($_POST['cancellation_reason'] ?? ''));
        $notes = trim((string) ($_POST['notes'] ?? ''));
        $equipmentLoaned = $isStaff && array_key_exists('equipment_loaned', $_POST)
            ? ((string) $_POST['equipment_loaned'] === '1')
            : null;

        // formulaire HTML (bouton « Annuler la commande ») : erreurs en message flash, plus de texte brut
        if (!$isStaff && ($status !== 'cancelled' || $order->getStatus() !== 'pending')) {
            $this->failStatusUpdate('Une commande ne peut être annulée par le client que lorsqu’elle est en attente.');
        }

        if ($status === 'cancelled' && $isStaff) {
            $contactMode = trim((string) ($_POST['contact_mode'] ?? ''));
            if ($contactMode === '') {
                $this->failStatusUpdate('Le mode de contact du client est obligatoire pour une annulation.');
            }

            $notes = 'Contact client : ' . $contactMode
                . ($notes !== '' ? ' — ' . $notes : '');
        }

        if ($status === 'cancelled' && $cancellationReason === '') {
            $this->failStatusUpdate('Le motif d’annulation est obligatoire.');
        }

        try {
            $this->orderService->updateOrderStatus(
                $id,
                $status,
                $userId,
                $notes,
                $cancellationReason !== '' ? $cancellationReason : null,
                $equipmentLoaned
            );
        } catch (\InvalidArgumentException $exception) {
            $this->failStatusUpdate($exception->getMessage());
        } catch (\Throwable $exception) {
            error_log('Order status update error: ' . $exception->getMessage());
            $this->failStatusUpdate('La commande n’a pas pu être mise à jour.');
        }

        $this->redirect('/orders');
    }

    private function failStatusUpdate(string $message): never
    {
        Session::flash('order_error', $message);
        $this->redirect('/orders');
    }
}
