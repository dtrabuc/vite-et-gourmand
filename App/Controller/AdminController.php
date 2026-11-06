<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Session;
use App\Repository\OpeningHoursRepository;
use App\Service\AdminService;
use App\Service\AuthService;
use App\Service\CommentService;
use App\Service\MailService;
use App\Service\MenuService;
use App\Service\OrderService;
use App\Service\QuoteService;
use App\Service\RateLimiter;
use Throwable;

final class AdminController extends BaseController
{
    public function __construct(
        private readonly AdminService $adminService,
        private readonly AuthService $authService,
        private readonly MenuService $menuService,
        private readonly CommentService $commentService,
        private readonly OrderService $orderService,
        private readonly OpeningHoursRepository $openingHoursRepository,
        private readonly QuoteService $quoteService,
        private readonly RateLimiter $rateLimiter,
        private readonly MailService $mailService
    ) {
    }

    public function showLogin(): void
    {
        $this->render('auth/admin_login');
    }

    public function login(): void
    {
        $user = $this->authService->login(
            (string) ($_POST['email'] ?? ''),
            (string) ($_POST['password'] ?? '')
        );

        if ($user === null) {
            $this->rateLimiter->hit('/admin/login');
            Session::flash('login_error', 'Identifiants invalides ou compte indisponible.');
            $this->redirect('/admin/login');
        }

        $this->rateLimiter->clear('/admin/login');

        $role = $user->getRole();

        if ($role === 'user') {
            Session::flash(
                'login_error',
                'Ce compte est un compte client. Utilisez la connexion client.'
            );
            $this->redirect('/login');
        }

        if (!in_array($role, ['employee', 'admin'], true)) {
            Session::flash(
                'login_error',
                'Le rôle de ce compte ne permet pas d’accéder à l’espace équipe.'
            );
            $this->redirect('/admin/login');
        }

        Session::login($user->getId(), $role, [
            'email' => $user->getEmail(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
        ]);

        // page de l'espace équipe demandée avant la connexion (une seule fois), sinon l'accueil du rôle
        $this->redirect(Session::pullReturnUrl($role === 'admin' ? '/admin/dashboard' : '/admin/orders'));
    }

    public function dashboard(): void
    {
        $this->render('admin/dashboard', [
            'stats' => $this->adminService->getDashboardStats(),
        ]);
    }

    public function orders(): void
    {
        $status = trim((string) ($_GET['status'] ?? ''));
        $customer = trim((string) ($_GET['customer'] ?? ''));

        $orders = $this->orderService->getOrdersForStaff(
            $status !== '' ? $status : null,
            $customer !== '' ? $customer : null
        );
        $history = [];

        foreach ($orders as $order) {
            $history[$order->getId()] = $this->orderService->getOrderHistory($order->getId());
        }

        $this->render('admin/orders', [
            'phoneInput' => true, // page avec champ téléphone : charge intl-tel-input
            'orders' => $orders,
            'history' => $history,
            'editableStatuses' => OrderService::STAFF_EDITABLE_STATUSES,
            'transitions' => OrderService::ALLOWED_TRANSITIONS,
            'selectedStatus' => $status,
            'customer' => $customer,
        ]);
    }

    /**
     * Modification du contenu d'une commande après contact avec le client.
     */
    public function updateOrder(int $id): never
    {
        try {
            $this->orderService->updateOrderByStaff(
                $id,
                (int) Session::id(),
                $this->postFields(OrderService::EDITABLE_FIELDS),
                trim((string) ($_POST['contact_mode'] ?? '')),
                trim((string) ($_POST['modification_reason'] ?? ''))
            );
            Session::flash('admin_success', 'Commande modifiée. Le changement est inscrit dans son historique.');
        } catch (\InvalidArgumentException $exception) {
            Session::flash('admin_error', $exception->getMessage());
        } catch (Throwable $exception) {
            error_log('Order staff update error: ' . $exception->getMessage());
            Session::flash('admin_error', 'La commande n’a pas pu être modifiée.');
        }

        $this->redirect('/admin/orders#order-' . $id);
    }

    public function quotes(): void
    {
        $status = trim((string) ($_GET['status'] ?? ''));

        $this->render('admin/quotes', [
            'quotes' => $this->quoteService->findForStaff($status !== '' ? $status : null),
            'selectedStatus' => $status,
        ]);
    }

    public function updateQuoteStatus(int $id): void
    {
        $status = trim((string) ($_POST['status'] ?? ''));
        $reply = trim((string) ($_POST['reply'] ?? ''));

        try {
            $this->quoteService->updateStatus($id, $status, $reply);
            Session::flash('admin_success', 'Demande de devis mise à jour et client informé par email.');
        } catch (Throwable $exception) {
            Session::flash('admin_error', $exception->getMessage());
        }

        $this->redirect('/admin/quotes');
    }

    public function updateOrderStatus(int $id): void
    {
        $status = trim((string) ($_POST['status'] ?? ''));
        $reason = trim((string) ($_POST['cancellation_reason'] ?? ''));
        $contactMode = trim((string) ($_POST['contact_mode'] ?? ''));
        $notes = trim((string) ($_POST['notes'] ?? ''));
        $equipmentLoaned = array_key_exists('equipment_loaned', $_POST)
            ? (string) $_POST['equipment_loaned'] === '1'
            : false;

        if ($contactMode === '') {
            Session::flash('admin_error', 'Le mode de contact du client est obligatoire avant toute modification de commande.');
            $this->redirect('/admin/orders');
        }

        if ($status === 'cancelled' && $reason === '') {
            Session::flash('admin_error', 'Pour une annulation, le motif est obligatoire.');
            $this->redirect('/admin/orders');
        }

        $notes = 'Contact client : ' . $contactMode
            . ($notes !== '' ? ' — ' . $notes : '');

        try {
            $this->orderService->updateOrderStatus(
                $id,
                $status,
                (int) Session::id(),
                $notes,
                $reason !== '' ? $reason : null,
                $equipmentLoaned
            );
            Session::flash('admin_success', 'Statut de la commande mis à jour.');
        } catch (Throwable $exception) {
            Session::flash('admin_error', $exception->getMessage());
        }

        $this->redirect('/admin/orders');
    }

    public function openingHours(): void
    {
        $this->render('admin/opening_hours', [
            'openingHours' => $this->openingHoursRepository->findAll(),
        ]);
    }

    public function updateOpeningHours(): never
    {
        $windowsByDay = $_POST['windows'] ?? [];

        for ($day = 1; $day <= 7; $day++) {
            $isOpen = isset($_POST['is_open'][$day]);
            $dayWindows = is_array($windowsByDay[$day] ?? null) ? $windowsByDay[$day] : [];

            $opening1 = $isOpen ? trim((string) ($dayWindows[0]['opening'] ?? '')) : '';
            $closing1 = $isOpen ? trim((string) ($dayWindows[0]['closing'] ?? '')) : '';
            $opening2 = $isOpen ? trim((string) ($dayWindows[1]['opening'] ?? '')) : '';
            $closing2 = $isOpen ? trim((string) ($dayWindows[1]['closing'] ?? '')) : '';

            $this->openingHoursRepository->saveDay(
                $day,
                $isOpen && $opening1 !== '' && $closing1 !== '',
                $opening1 !== '' ? $opening1 : null,
                $closing1 !== '' ? $closing1 : null,
                $opening2 !== '' ? $opening2 : null,
                $closing2 !== '' ? $closing2 : null
            );
        }

        Session::flash('admin_success', 'Horaires mis à jour.');
        $this->redirect('/admin/hours');
    }

    public function customers(): void
    {
        $search = trim((string) ($_GET['search'] ?? ''));
        $active = isset($_GET['active']) && $_GET['active'] !== ''
            ? (string) $_GET['active'] === '1'
            : null;

        $this->render('admin/customers', [
            'customers' => $this->adminService->getCustomers($search !== '' ? $search : null, $active),
            'search' => $search,
            'active' => $active,
            'isAdmin' => Session::role() === 'admin',
        ]);
    }

    public function disableCustomer(int $id): never
    {
        $this->adminService->disableCustomer($id);
        $this->redirect('/admin/customers');
    }

    public function enableCustomer(int $id): never
    {
        $this->adminService->enableCustomer($id);
        $this->redirect('/admin/customers');
    }

    public function createEmployee(): void
    {
        $data = [
            'email' => trim((string) ($_POST['email'] ?? '')),
            'password' => (string) ($_POST['password'] ?? ''),
            'first_name' => trim((string) ($_POST['first_name'] ?? '')),
            'last_name' => trim((string) ($_POST['last_name'] ?? '')),
            'phone' => trim((string) ($_POST['phone'] ?? '')),
            'gsm' => trim((string) ($_POST['gsm'] ?? '')),
            'address' => trim((string) ($_POST['address'] ?? '')),
        ];

        try {
            $this->adminService->createEmployee($data);

            // Mail d'information sans le mot de passe (communiqué par l'administrateur).
            $mailSent = $this->mailService->sendEmployeeAccountEmail(
                mb_strtolower($data['email']),
                $data['first_name'],
                rtrim((string) config('app.url', ''), '/') . '/admin/login'
            );

            Session::flash(
                'admin_success',
                $mailSent
                    ? 'Employé créé avec succès. Un e-mail l’en a informé (sans le mot de passe).'
                    : 'Employé créé avec succès, mais l’e-mail d’information n’a pas pu être envoyé.'
            );
        } catch (Throwable $exception) {
            Session::flash('admin_error', $exception->getMessage());
        }

        $this->redirect('/admin/employees');
    }

    public function getEmployees(): void
    {
        $this->render('admin/employees', [
            'phoneInput' => true, // page avec champ téléphone : charge intl-tel-input
            'employees' => $this->adminService->getEmployees(),
        ]);
    }

    public function disableEmployee(int $id): never
    {
        $this->flashResult(
            fn () => $this->adminService->disableEmployee($id),
            'Compte employé désactivé.',
            'Le compte employé n’a pas pu être désactivé.'
        );
        $this->redirect('/admin/employees');
    }

    public function enableEmployee(int $id): never
    {
        $this->flashResult(
            fn () => $this->adminService->enableEmployee($id),
            'Compte employé réactivé.',
            'Le compte employé n’a pas pu être réactivé.'
        );
        $this->redirect('/admin/employees');
    }

    public function getMenus(): void
    {
        // Les menus épuisés restent visibles pour pouvoir être réapprovisionnés.
        $this->render('admin/menus', [
            'menus' => $this->menuService->getMenusForAdmin(),
            'themes' => MenuService::THEMES,
        ]);
    }

    public function createMenu(): void
    {
        try {
            $this->menuService->createMenu($_POST);
            Session::flash('menu_success', 'Menu créé avec succès.');
        } catch (Throwable $exception) {
            Session::flash('menu_error', $exception->getMessage());
        }

        $this->redirect('/admin/menus');
    }

    public function updateMenu(int $id): void
    {
        try {
            $this->menuService->updateMenu($id, $_POST);
            Session::flash('menu_success', 'Menu mis à jour avec succès.');
        } catch (Throwable $exception) {
            Session::flash('menu_error', $exception->getMessage());
        }

        $this->redirect('/admin/menus');
    }

    public function deleteMenu(int $id): never
    {
        $this->flashResult(
            fn () => $this->menuService->deleteMenu($id),
            'Menu désactivé : il n’est plus proposé aux clients.',
            'Le menu n’a pas pu être désactivé.'
        );
        $this->redirect('/admin/menus');
    }

    public function getPendingComments(): void
    {
        $this->render('admin/comments', [
            'comments' => $this->commentService->getPendingComments(),
        ]);
    }

    public function validateComment(string $id): never
    {
        $this->flashResult(
            fn () => $this->commentService->validateComment($id),
            'Avis validé : il est maintenant visible sur l’accueil.',
            'L’avis n’a pas pu être validé.'
        );
        $this->redirect('/admin/comments/pending');
    }

    public function rejectComment(string $id): never
    {
        $this->flashResult(
            fn () => $this->commentService->rejectComment($id),
            'Avis refusé.',
            'L’avis n’a pas pu être refusé.'
        );
        $this->redirect('/admin/comments/pending');
    }

    /**
     * Exécute une action de formulaire et affiche le résultat par message flash.
     */
    private function flashResult(callable $action, string $success, string $failure): void
    {
        try {
            $action();
            Session::flash('admin_success', $success);
        } catch (\InvalidArgumentException $exception) {
            Session::flash('admin_error', $exception->getMessage());
        } catch (Throwable $exception) {
            error_log($failure . ' ' . $exception->getMessage());
            Session::flash('admin_error', $failure);
        }
    }

    public function revenuePage(): void
    {
        $from = trim((string) ($_GET['from'] ?? ''));
        $to = trim((string) ($_GET['to'] ?? ''));
        $menuId = isset($_GET['menu_id']) && is_numeric($_GET['menu_id'])
            ? (int) $_GET['menu_id']
            : null;

        $this->render('admin/revenue', [
            'revenue' => $this->adminService->getRevenueByMenu(
                $from !== '' ? $from : null,
                $to !== '' ? $to : null,
                $menuId
            ),
            'menus' => $this->menuService->getMenusForAdmin(),
            'from' => $from,
            'to' => $to,
            'menuId' => $menuId,
        ]);
    }

}
