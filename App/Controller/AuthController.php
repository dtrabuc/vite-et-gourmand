<?php
namespace App\Controller;

use App\Service\AuthService;
use App\Repository\UserRepository;

class AuthController extends BaseController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService(new UserRepository());
    }

    public function showLogin(): void
    {

        $this->render('auth/login');
    }

    public function login(): void
    {
        (new \App\Middleware\Guest())();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Method Not Allowed';
            return;
        }

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $user = $this->authService->login($email, $password);

        if ($user === null) {
            $_SESSION['login_error'] = 'Identifiants invalides';
            header('Location: /login');
            exit;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['email'] = $user->getEmail();
        $_SESSION['role'] = $user->getRole();
        $_SESSION['first_name'] = $user->getFirstName();
        $_SESSION['last_name'] = $user->getLastName();

        switch ($user->getRole()) {
            case 'admin':
                header('Location: /admin/dashboard');
                break;
            case 'employee':
                header('Location: /admin/orders');
                break;
            default:
                header('Location: /');
                break;
        }
        exit;
    }

    public function showRegister(): void
    {
        (new \App\Middleware\Guest())();

        $this->render('auth/register');
    }

    public function register(): void
    {
        (new \App\Middleware\Guest())();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Method Not Allowed';
            return;
        }

        $errors = [];

        $requiredFields = ['email', 'password', 'first_name', 'last_name', 'phone', 'gsm', 'address'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field] ?? '')) {
                $errors[$field] = 'Ce champ est requis';
            }
        }

        if (!empty($_POST['email'] ?? '') && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email invalide';
        }

        $password = (string) ($_POST['password'] ?? '');
        $passwordErrors = $this->authService->validatePassword($password);
        if ($passwordErrors !== null) {
            $errors['password'] = $passwordErrors[0];
        }

        if (!empty($errors)) {
            $_SESSION['register_errors'] = $errors;
            $_SESSION['register_old_input'] = $_POST;
            header('Location: /register');
            exit;
        }

        try {
            $userId = $this->authService->register([
                'email' => $_POST['email'],
                'password' => $password,
                'role' => 'user', // Default role for registration
                'first_name' => $_POST['first_name'],
                'last_name' => $_POST['last_name'],
                'phone' => $_POST['phone'],
                'gsm' => $_POST['gsm'],
                'address' => $_POST['address'],
            ]);

            $mailService = new \App\Service\MailService(
                $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@viteetgourmand.com',
                $_ENV['MAIL_FROM_NAME'] ?? 'Vite & Gourmand'
            );
            $mailService->sendWelcomeEmail($_POST['email'], $_POST['first_name']);

            $_SESSION['register_success'] = 'Inscription réussie. Vous pouvez maintenant vous connecter.';
            header('Location: /login');
            exit;
        } catch (\Exception $e) {
            $_SESSION['register_error'] = 'Une erreur est survenue lors de l\'inscription';
            header('Location: /register');
            exit;
        }
    }

    public function profile(): void
    {
        (new \App\Middleware\Auth())();

        $userId = $_SESSION['user_id'] ?? 0;
        if ($userId === 0) {
            header('Location: /login');
            exit;
        }

        $user = (new UserRepository())->findById((int) $userId);
        if ($user === null) { header('Location: /login'); exit; }
        $this->render('auth/profile', ['user' => [
            'email' => $user->getEmail(), 'first_name' => $user->getFirstName(), 'last_name' => $user->getLastName(),
            'phone' => $user->getPhone(), 'gsm' => $user->getGsm(), 'address' => $user->getAddress(),
        ]]);
    }

    public function updateProfile(): void
    {
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

        $errors = [];

        $requiredFields = ['email', 'first_name', 'last_name', 'phone', 'gsm', 'address'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field] ?? '')) {
                $errors[$field] = 'Ce champ est requis';
            }
        }

        if (!empty($_POST['email'] ?? '') && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email invalide';
        }

        if (!empty($errors)) {
            // For AJAX requests, return JSON
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'errors' => $errors]);
                return;
            }

            $_SESSION['profile_errors'] = $errors;
            $_SESSION['profile_old_input'] = $_POST;
            header('Location: /profile');
            exit;
        }

        try {
            $this->authService->updateProfile((int) $userId, [
                'email' => trim($_POST['email']), 'first_name' => trim($_POST['first_name']), 'last_name' => trim($_POST['last_name']),
                'phone' => trim($_POST['phone']), 'gsm' => trim($_POST['gsm']), 'address' => trim($_POST['address']),
            ]);
        } catch (\Throwable $e) {
            error_log('Profile update error: ' . $e->getMessage());
            $_SESSION['profile_errors'] = ['general' => 'Impossible de mettre à jour le profil.'];
            header('Location: /profile'); exit;
        }
        $_SESSION['email'] = trim($_POST['email']);
        $_SESSION['first_name'] = trim($_POST['first_name']);
        $_SESSION['last_name'] = trim($_POST['last_name']);
        $_SESSION['phone'] = trim($_POST['phone']);
        $_SESSION['gsm'] = trim($_POST['gsm']);
        $_SESSION['address'] = trim($_POST['address']);

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Profil mis à jour avec succès']);
            return;
        }

        $_SESSION['profile_success'] = 'Profil mis à jour avec succès';
        header('Location: /profile');
        exit;
    }

    public function changePassword(): void
    {
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

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $errors = [];

        if (empty($currentPassword)) {
            $errors['current_password'] = 'Le mot de passe actuel est requis';
        }
        if (empty($newPassword)) {
            $errors['new_password'] = 'Le nouveau mot de passe est requis';
        } elseif (strlen($newPassword) < 10) {
            $errors['new_password'] = 'Le mot de passe doit contenir au moins 10 caractères';
        } elseif (!preg_match('/[A-Z]/', $newPassword)) {
            $errors['new_password'] = 'Le mot de passe doit contenir au moins une majuscule';
        } elseif (!preg_match('/[a-z]/', $newPassword)) {
            $errors['new_password'] = 'Le mot de passe doit contenir au moins une minuscule';
        } elseif (!preg_match('/[0-9]/', $newPassword)) {
            $errors['new_password'] = 'Le mot de passe doit contenir au moins un chiffre';
        } elseif (!preg_match('/[^A-Za-z0-9]/', $newPassword)) {
            $errors['new_password'] = 'Le mot de passe doit contenir au moins un caractère spécial';
        }
        if ($newPassword !== $confirmPassword) {
            $errors['confirm_password'] = 'Les mots de passe ne correspondent pas';
        }

        if (!empty($errors)) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'errors' => $errors]);
                return;
            }

            $_SESSION['password_errors'] = $errors;
            $_SESSION['password_old_input'] = $_POST;
            header('Location: /profile');
            exit;
        }

        if ($this->authService->changePassword($userId, $currentPassword, $newPassword)) {
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Mot de passe modifié avec succès']);
                return;
            }

            $_SESSION['password_success'] = 'Mot de passe modifié avec succès';
            header('Location: /profile');
            exit;
        } else {
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'errors' => ['current_password' => 'Mot de passe actuel incorrect']]);
                return;
            }

            $_SESSION['password_errors'] = ['current_password' => 'Mot de passe actuel incorrect'];
            header('Location: /profile');
            exit;
        }
    }

    public function logout(): void
    {
        (new \App\Middleware\Auth())();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Method Not Allowed';
            return;
        }

        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();

        header('Location: /login');
        exit;
    }

    public function showForgotPassword(): void
    {
        (new \App\Middleware\Guest())();

        $this->render('auth/forgot_password');
    }

    public function forgotPassword(): void
    {
        (new \App\Middleware\Guest())();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Method Not Allowed';
            return;
        }

        $email = $_POST['email'] ?? '';

        if (empty($email)) {
            $_SESSION['forgot_error'] = 'L\'email est requis';
            header('Location: /forgot-password');
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['forgot_error'] = 'Email invalide';
            header('Location: /forgot-password');
            exit;
        }

        $token = $this->authService->createResetToken($email);

        // Toujours utiliser le même message afin de ne pas révéler l'existence d'un compte.
        if ($token !== null) {
            $user = (new UserRepository())->findByEmail($email);
            $baseUrl = rtrim($_ENV['APP_URL'] ?? 'http://127.0.0.1:8080', '/');
            $resetUrl = $baseUrl . '/reset-password/' . rawurlencode($token);
            $mailService = new \App\Service\MailService(
                $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@viteetgourmand.com',
                $_ENV['MAIL_FROM_NAME'] ?? 'Vite & Gourmand'
            );
            if ($user !== null && !$mailService->sendPasswordResetEmail($email, $user->getFirstName(), $resetUrl)) {
                error_log('Impossible d\'envoyer le mail de réinitialisation à ' . $email);
            }
        }

        $_SESSION['forgot_success'] = 'Si cet email existe dans notre système, vous recevrez un lien de réinitialisation';
        header('Location: /forgot-password');
        exit;
    }

    public function showResetPassword(string $token): void
    {
        (new \App\Middleware\Guest())();

        $authService = new \App\Service\AuthService(
            new \App\Repository\UserRepository()
        );
        $user = $authService->validateResetToken($token);

        if ($user === null) {
            $_SESSION['reset_error'] = 'Le token de réinitialisation est invalide ou expiré';
            header('Location: /forgot-password');
            exit;
        }

        $this->render('auth/reset_password', ['token' => $token]);
    }

    public function resetPassword(): void
    {
        (new \App\Middleware\Guest())();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Method Not Allowed';
            return;
        }

        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $errors = [];
        $passwordErrors = $this->authService->validatePassword($password);
        if ($passwordErrors !== null) {
            $errors['password'] = $passwordErrors[0];
        }
        if ($password !== $confirmPassword) {
            $errors['confirm_password'] = 'Les mots de passe ne correspondent pas';
        }

        if (!empty($errors)) {
            $_SESSION['reset_errors'] = $errors;
            $_SESSION['reset_old_input'] = $_POST;
            header("Location: /reset-password/$token");
            exit;
        }

        if (!$this->authService->resetPassword($token, $password)) {
            $_SESSION['reset_errors'] = ['general' => 'Le lien de réinitialisation est invalide ou expiré.'];
            header("Location: /reset-password/$token"); exit;
        }
        $_SESSION['reset_success'] = 'Mot de passe réinitialisé avec succès. Vous pouvez maintenant vous connecter.';
        header('Location: /login');
        exit;
    }
}
