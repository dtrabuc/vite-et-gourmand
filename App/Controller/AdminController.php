<?php
namespace App\Controller;

use App\Service\AdminService;
use App\Service\AuthService;
use App\Service\MenuService;
use App\Service\CommentService;
use App\Service\MailService;
use App\Repository\UserRepository;
use App\Repository\OrderRepository;
use App\Repository\MenuRepository;
use App\Repository\CommentRepository;

class AdminController extends BaseController
{
    private AdminService $adminService;
    private AuthService $authService;
    private MenuService $menuService;
    private CommentService $commentService;

    public function __construct()
    {
        $this->adminService = new AdminService(
            new UserRepository(),
            new OrderRepository(),
            new MenuRepository()
        );
        $this->authService = new AuthService(new UserRepository());
        $this->menuService = new MenuService(new MenuRepository());
        $this->commentService = new CommentService(new CommentRepository());
    }

    public function showLogin(): void
    {
        // Apply guest middleware
        (new \App\Middleware\Guest())();

        $this->render('auth/admin_login');
    }

    public function login(): void
    {
        // Apply guest middleware
        (new \App\Middleware\Guest())();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Method Not Allowed';
            return;
        }

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $user = $this->authService->login($email, $password);

        if ($user === null || !in_array($user->getRole(), ['employee', 'admin'])) {
            // Redirect back with error
            $_SESSION['login_error'] = 'Identifiants invalides ou insuffisants de privilèges';
            header('Location: /admin/login');
            exit;
        }

        // Set session
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['email'] = $user->getEmail();
        $_SESSION['role'] = $user->getRole();
        $_SESSION['first_name'] = $user->getFirstName();
        $_SESSION['last_name'] = $user->getLastName();

        // Redirect according to role
        header('Location: ' . ($user->getRole() === 'admin' ? '/admin/dashboard' : '/admin/orders'));
        exit;
    }

    public function dashboard(): void
    {
        // Apply auth middleware
        (new \App\Middleware\Auth())();
        // Apply admin middleware
        (new \App\Middleware\Admin())();

        $stats = $this->adminService->getDashboardStats();

        $this->render('admin/dashboard', ['stats' => $stats]);
    }

    public function dishes(): void
    {
        (new \App\Middleware\Staff())();
        $this->render('admin/dishes', [
            'dishes' => (new \App\Repository\DishRepository())->findAll(),
            'menus' => (new MenuRepository())->findAll(),
        ]);
    }

    public function createDish(): void
    {
        (new \App\Middleware\Staff())();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); return; }
        try {
            $name=trim((string)$_POST['name']); $description=trim((string)$_POST['description']);
            if($name==='') throw new \InvalidArgumentException('Le nom du plat est requis.');
            (new \App\Repository\DishRepository())->create(
                $name,$description,
                isset($_POST['menu_id']) && is_numeric($_POST['menu_id']) ? (int)$_POST['menu_id'] : null,
                $_POST['category'] ?? null
            );
            $_SESSION['admin_success']='Plat créé.';
        } catch(\Throwable $e) { $_SESSION['admin_error']=$e->getMessage(); }
        header('Location: /admin/dishes'); exit;
    }

    public function updateDish(array $params): void
    {
        (new \App\Middleware\Staff())();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); return; }
        try {
            (new \App\Repository\DishRepository())->update(
                (int)($params[0]??0), trim((string)$_POST['name']), trim((string)$_POST['description']),
                isset($_POST['menu_id']) && is_numeric($_POST['menu_id']) ? (int)$_POST['menu_id'] : null,
                $_POST['category'] ?? null
            );
            $_SESSION['admin_success']='Plat modifié.';
        } catch(\Throwable $e) { $_SESSION['admin_error']=$e->getMessage(); }
        header('Location: /admin/dishes'); exit;
    }

    public function deleteDish(array $params): void
    {
        (new \App\Middleware\Staff())();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); return; }
        try { (new \App\Repository\DishRepository())->delete((int)($params[0]??0)); $_SESSION['admin_success']='Plat supprimé.'; }
        catch(\Throwable $e) { $_SESSION['admin_error']='Impossible de supprimer ce plat : il est peut-être encore associé à un menu.'; }
        header('Location: /admin/dishes'); exit;
    }

    public function openingHours(): void
    {
        (new \App\Middleware\Staff())();
        $this->render('admin/opening_hours', ['openingHours' => (new \App\Repository\OpeningHoursRepository())->findAll()]);
    }

    public function updateOpeningHours(): void
    {
        (new \App\Middleware\Staff())();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); return; }
        $repo = new \App\Repository\OpeningHoursRepository();
        for ($day = 1; $day <= 7; $day++) {
            $isOpen = isset($_POST['is_open'][$day]);
            $opening = $isOpen ? trim((string)($_POST['opening_time'][$day] ?? '')) : null;
            $closing = $isOpen ? trim((string)($_POST['closing_time'][$day] ?? '')) : null;
            $repo->saveDay($day, $isOpen, $opening !== '' ? $opening : null, $closing !== '' ? $closing : null);
        }
        $_SESSION['admin_success'] = 'Horaires mis à jour.';
        header('Location: /admin/hours'); exit;
    }

    public function orders(): void
    {
        (new \App\Middleware\Staff())();
        $status = trim((string)($_GET['status'] ?? ''));
        $customer = trim((string)($_GET['customer'] ?? ''));
        $orders = (new OrderRepository())->findForStaff($status !== '' ? $status : null, $customer !== '' ? $customer : null);
        $this->render('admin/orders', ['orders' => $orders, 'selectedStatus' => $status, 'customer' => $customer]);
    }

    public function updateOrderStatus(array $params): void
    {
        (new \App\Middleware\Staff())();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); return; }
        $orderId = (int)($params[0] ?? 0);
        $status = trim((string)($_POST['status'] ?? ''));
        $reason = trim((string)($_POST['cancellation_reason'] ?? ''));
        $contactMode = trim((string)($_POST['contact_mode'] ?? ''));
        $notes = trim((string)($_POST['notes'] ?? ''));
        $equipmentLoaned = array_key_exists('equipment_loaned', $_POST) ? ((string) $_POST['equipment_loaned'] === '1') : false;
        if ($contactMode === '') {
            $_SESSION['admin_error'] = 'Le mode de contact du client est obligatoire avant toute modification de commande.';
            header('Location: /admin/orders'); exit;
        }
        if ($status === 'cancelled' && $reason === '') {
            $_SESSION['admin_error'] = 'Pour une annulation, le motif est obligatoire.';
            header('Location: /admin/orders'); exit;
        }
        $notes = 'Contact client : ' . $contactMode . ($notes !== '' ? ' — ' . $notes : '');
        try {
            (new \App\Service\OrderService(new OrderRepository(), new UserRepository(), new MenuRepository(), new MailService()))
                ->updateOrderStatus($orderId, $status, (int)$_SESSION['user_id'], $notes, $reason !== '' ? $reason : null, $equipmentLoaned);
            $_SESSION['admin_success'] = 'Statut de la commande mis à jour.';
        } catch (\Throwable $e) {
            $_SESSION['admin_error'] = $e->getMessage();
        }
        header('Location: /admin/orders'); exit;
    }

    public function createEmployee(): void
    {
        // Apply auth middleware
        (new \App\Middleware\Auth())();
        // Apply admin middleware
        (new \App\Middleware\Admin())();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Method Not Allowed';
            return;
        }

        // Validate input
        $errors = [];

        $requiredFields = ['email', 'password', 'first_name', 'last_name', 'phone', 'gsm', 'address'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field] ?? '')) {
                $errors[$field] = 'Ce champ est requis';
            }
        }

        // Email format
        if (!empty($_POST['email'] ?? '') && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email invalide';
        }

        // Password validation (ECF requirements)
        $password = $_POST['password'] ?? '';
        if (strlen($password) < 10) {
            $errors['password'] = 'Le mot de passe doit contenir au moins 10 caractères';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors['password'] = 'Le mot de passe doit contenir au moins une majuscule';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors['password'] = 'Le mot de passe doit contenir au moins une minuscule';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors['password'] = 'Le mot de passe doit contenir au moins un chiffre';
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors['password'] = 'Le mot de passe doit contenir au moins un caractère spécial';
        }

        if (!empty($errors)) {
            $_SESSION['admin_errors'] = $errors;
            $_SESSION['admin_old_input'] = $_POST;
            header('Location: /admin/employees');
            exit;
        }

        try {
            $password = (string)($_POST['password'] ?? '');
            if (strlen($password) < 10 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
                throw new \InvalidArgumentException('Le mot de passe doit contenir au moins 10 caractères avec majuscule, minuscule, chiffre et caractère spécial.');
            }
            // Create employee
            $employeeId = $this->adminService->createEmployee([
                'email' => $_POST['email'],
                'password' => $password,
                'role' => 'employee',
                'first_name' => $_POST['first_name'],
                'last_name' => $_POST['last_name'],
                'phone' => $_POST['phone'],
                'gsm' => $_POST['gsm'],
                'address' => $_POST['address'],
            ]);

            $mail = new MailService(
                $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@viteetgourmand.com',
                $_ENV['MAIL_FROM_NAME'] ?? 'Vite & Gourmand'
            );
            $mail->send($_POST['email'], 'Création de votre compte employé', "Bonjour " . $_POST['first_name'] . ",\\n\\nUn compte employé Vite & Gourmand vient d'être créé pour vous.\\nVotre mot de passe n'est pas communiqué dans cet email : rapprochez-vous de l'administrateur pour l'obtenir.\\n\\nL'équipe Vite & Gourmand");

            $_SESSION['admin_success'] = 'Employé créé avec succès';
            header('Location: /admin/employees');
            exit;
        } catch (\Exception $e) {
            error_log('Error creating employee: ' . $e->getMessage());
            $_SESSION['admin_error'] = 'Une erreur est survenue lors de la création de l\'employé';
            header('Location: /admin/employees');
            exit;
        }
    }

    public function getEmployees(): void
    {
        // Apply auth middleware
        (new \App\Middleware\Auth())();
        // Apply admin middleware
        (new \App\Middleware\Admin())();

        $employees = $this->adminService->getEmployees();
        $this->render('admin/employees', ['employees' => $employees]);
    }

    public function disableEmployee(array $params): void
    {
        // Apply auth middleware
        (new \App\Middleware\Auth())();
        // Apply admin middleware
        (new \App\Middleware\Admin())();

        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Method Not Allowed';
            return;
        }

        $id = (int)$params[0] ?? 0;
        if ($id <= 0) {
            http_response_code(400);
            echo 'Invalid employee ID';
            return;
        }

        try {
            $this->adminService->disableEmployee($id);
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            error_log('Error disabling employee: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal Server Error';
        }
    }

    public function enableEmployee(array $params): void
    {
        // Apply auth middleware
        (new \App\Middleware\Auth())();
        // Apply admin middleware
        (new \App\Middleware\Admin())();

        if ($_SERVER['REQUEST_METHOD'] !== 'PATCH' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Method Not Allowed';
            return;
        }

        $id = (int)$params[0] ?? 0;
        if ($id <= 0) {
            http_response_code(400);
            echo 'Invalid employee ID';
            return;
        }

        try {
            $this->adminService->enableEmployee($id);
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            error_log('Error enabling employee: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal Server Error';
        }
    }

    public function revenuePage(): void
    {
        (new \App\Middleware\Admin())();
        $from=trim((string)($_GET['from']??'')); $to=trim((string)($_GET['to']??''));
        $menuId=isset($_GET['menu_id'])&&is_numeric($_GET['menu_id'])?(int)$_GET['menu_id']:null;
        $this->render('admin/revenue', [
            'revenue'=>$this->adminService->getRevenueByMenu($from!==''?$from:null,$to!==''?$to:null,$menuId),
            'menus'=>(new MenuRepository())->findAll(),
            'from'=>$from,'to'=>$to,'menuId'=>$menuId
        ]);
    }

    public function revenueByMenu(): void
    {
        // Apply auth middleware
        (new \App\Middleware\Auth())();
        // Apply admin middleware
        (new \App\Middleware\Admin())();

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo 'Method Not Allowed';
            return;
        }

        $from = isset($_GET['from']) ? trim((string)$_GET['from']) : null;
        $to = isset($_GET['to']) ? trim((string)$_GET['to']) : null;
        $menuId = isset($_GET['menu_id']) && is_numeric($_GET['menu_id']) ? (int)$_GET['menu_id'] : null;
        $revenue = $this->adminService->getRevenueByMenu($from, $to, $menuId);

        header('Content-Type: application/json');
        echo json_encode($revenue);
    }

    // Menu management
    public function getMenus(): void
    {
        // Apply auth middleware
        (new \App\Middleware\Auth())();
        // Apply admin middleware
        (new \App\Middleware\Staff())();

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo 'Method Not Allowed';
            return;
        }

        $menus = $this->menuService->getAllMenus();
        $this->render('admin/menus', ['menus' => $menus]);
    }

    public function createMenu(): void
    {
        // Apply auth middleware
        (new \App\Middleware\Auth())();
        // Apply admin middleware
        (new \App\Middleware\Staff())();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Method Not Allowed';
            return;
        }

        // Validate input
        $errors = [];

        $requiredFields = ['title', 'description', 'theme', 'dietary_regime', 'min_people', 'base_price', 'conditions', 'available_stock'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field] ?? '')) {
                $errors[$field] = 'Ce champ est requis';
            }
        }

        // Validate numeric fields
        if (!empty($_POST['min_people']) && !is_numeric($_POST['min_people'])) {
            $errors['min_people'] = 'Le nombre minimum de personnes doit être un nombre';
        }
        if (!empty($_POST['base_price']) && !is_numeric($_POST['base_price'])) {
            $errors['base_price'] = 'Le prix de base doit être un nombre';
        }
        if (!empty($_POST['available_stock']) && !is_numeric($_POST['available_stock'])) {
            $errors['available_stock'] = 'Le stock disponible doit être un nombre';
        }

        if (!empty($errors)) {
            $_SESSION['menu_errors'] = $errors;
            $_SESSION['menu_old_input'] = $_POST;
            header('Location: /admin/menus');
            exit;
        }

        try {
            $menuId = $this->menuService->createMenu([
                'title' => $_POST['title'],
                'description' => $_POST['description'],
                'theme' => $_POST['theme'],
                'dietary_regime' => $_POST['dietary_regime'],
                'min_people' => (int)$_POST['min_people'],
                'base_price' => (float)$_POST['base_price'],
                'conditions' => $_POST['conditions'],
                'available_stock' => (int)$_POST['available_stock'],
            ]);

            $_SESSION['menu_success'] = 'Menu créé avec succès';
            header('Location: /admin/menus');
            exit;
        } catch (\Exception $e) {
            error_log('Error creating menu: ' . $e->getMessage());
            $_SESSION['menu_error'] = 'Une erreur est survenue lors de la création du menu';
            header('Location: /admin/menus');
            exit;
        }
    }

    public function updateMenu(array $params): void
    {
        // Apply auth middleware
        (new \App\Middleware\Auth())();
        // Apply admin middleware
        (new \App\Middleware\Staff())();

        if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            // Handle form submission via POST with _method=PUT
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['_method']) || strtoupper($_POST['_method']) !== 'PUT') {
                http_response_code(405);
                echo 'Method Not Allowed';
                return;
            }
        }

        $id = (int)$params[0] ?? 0;
        if ($id <= 0) {
            http_response_code(400);
            echo 'Invalid menu ID';
            return;
        }

        // Validate input
        $errors = [];

        $requiredFields = ['title', 'description', 'theme', 'dietary_regime', 'min_people', 'base_price', 'conditions', 'available_stock'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field] ?? '')) {
                $errors[$field] = 'Ce champ est requis';
            }
        }

        // Validate numeric fields
        if (!empty($_POST['min_people']) && !is_numeric($_POST['min_people'])) {
            $errors['min_people'] = 'Le nombre minimum de personnes doit être un nombre';
        }
        if (!empty($_POST['base_price']) && !is_numeric($_POST['base_price'])) {
            $errors['base_price'] = 'Le prix de base doit être un nombre';
        }
        if (!empty($_POST['available_stock']) && !is_numeric($_POST['available_stock'])) {
            $errors['available_stock'] = 'Le stock disponible doit être un nombre';
        }

        if (!empty($errors)) {
            $_SESSION['menu_errors'] = $errors;
            $_SESSION['menu_old_input'] = $_POST;
            header('Location: /admin/menus/edit/' . $id);
            exit;
        }

        try {
            $this->menuService->updateMenu($id, [
                'title' => $_POST['title'],
                'description' => $_POST['description'],
                'theme' => $_POST['theme'],
                'dietary_regime' => $_POST['dietary_regime'],
                'min_people' => (int)$_POST['min_people'],
                'base_price' => (float)$_POST['base_price'],
                'conditions' => $_POST['conditions'],
                'available_stock' => (int)$_POST['available_stock'],
            ]);

            $_SESSION['menu_success'] = 'Menu mis à jour avec succès';
            header('Location: /admin/menus');
            exit;
        } catch (\Exception $e) {
            error_log('Error updating menu: ' . $e->getMessage());
            $_SESSION['menu_error'] = 'Une erreur est survenue lors de la mise à jour du menu';
            header('Location: /admin/menus/edit/' . $id);
            exit;
        }
    }

    public function deleteMenu(array $params): void
    {
        // Apply auth middleware
        (new \App\Middleware\Auth())();
        // Apply admin middleware
        (new \App\Middleware\Staff())();

        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Method Not Allowed';
            return;
        }

        $id = (int)$params[0] ?? 0;
        if ($id <= 0) {
            http_response_code(400);
            echo 'Invalid menu ID';
            return;
        }

        try {
            $this->menuService->deleteMenu($id);
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            error_log('Error deleting menu: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal Server Error';
        }
    }

    // Comment management
    public function getPendingComments(): void
    {
        // Apply auth middleware
        (new \App\Middleware\Auth())();
        // Apply admin middleware
        (new \App\Middleware\Staff())();

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo 'Method Not Allowed';
            return;
        }

        $comments = $this->commentService->getPendingComments();
        $this->render('admin/comments', ['comments' => $comments]);
    }

    public function validateComment(array $params): void
    {
        // Apply auth middleware
        (new \App\Middleware\Auth())();
        // Apply admin middleware
        (new \App\Middleware\Staff())();

        if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            // Handle form submission via POST with _method=PUT
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['_method']) || strtoupper($_POST['_method']) !== 'PUT') {
                http_response_code(405);
                echo 'Method Not Allowed';
                return;
            }
        }

        $id = (int)$params[0] ?? 0;
        if ($id <= 0) {
            http_response_code(400);
            echo 'Invalid comment ID';
            return;
        }

        try {
            $this->commentService->validateComment($id);
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            error_log('Error validating comment: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal Server Error';
        }
    }

    public function rejectComment(array $params): void
    {
        // Apply auth middleware
        (new \App\Middleware\Auth())();
        // Apply admin middleware
        (new \App\Middleware\Staff())();

        if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            // Handle form submission via POST with _method=PUT
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['_method']) || strtoupper($_POST['_method']) !== 'PUT') {
                http_response_code(405);
                echo 'Method Not Allowed';
                return;
            }
        }

        $id = (int)$params[0] ?? 0;
        if ($id <= 0) {
            http_response_code(400);
            echo 'Invalid comment ID';
            return;
        }

        try {
            $this->commentService->rejectComment($id);
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            error_log('Error rejecting comment: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal Server Error';
        }
    }
}
