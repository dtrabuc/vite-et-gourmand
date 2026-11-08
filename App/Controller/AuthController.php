<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Session;
use App\Repository\UserRepository;
use App\Service\AuthService;
use App\Service\MailService;
use App\Service\PasswordPolicy;
use App\Service\PhoneValidator;
use App\Service\RateLimiter;
use InvalidArgumentException;
use Throwable;

final class AuthController extends BaseController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly UserRepository $userRepository,
        private readonly MailService $mailService,
        private readonly RateLimiter $rateLimiter,
        private readonly PhoneValidator $phoneValidator,
        private readonly PasswordPolicy $passwordPolicy
    ) {
    }

    public function showLogin(): void
    {
        // lien « Connectez-vous pour commander » : page où revenir après la connexion
        $redirect = $_GET['redirect'] ?? null;

        if (is_string($redirect)) {
            Session::rememberReturnUrl($redirect);
        }

        $this->render('auth/login');
    }

    public function login(): void
    {
        $user = $this->authService->login(
            (string) ($_POST['email'] ?? ''),
            (string) ($_POST['password'] ?? '')
        );

        if ($user === null) {
            $this->rateLimiter->hit('/login');
            Session::flash('login_error', 'Identifiants invalides, compte indisponible ou accès réservé à l’espace équipe.');
            $this->redirect('/login');
        }

        $this->rateLimiter->clear('/login');

        if (!in_array($user->getRole(), ['user'], true)) {
            Session::flash(
                'login_error',
                'Ce compte appartient à l’équipe. Utilisez l’accès « Espace équipe » (bas de page, « Accès réservé »).'
            );
            $this->redirect('/admin/login');
        }

        Session::login($user->getId(), $user->getRole(), [
            'email' => $user->getEmail(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'must_change_password' => $user->mustChangePassword(),
        ]);

        if ($user->mustChangePassword()) {
            $this->redirect('/profile');
        }

        // retour à la page demandée avant la connexion (une seule fois), sinon l'accueil
        $this->redirect(Session::pullReturnUrl('/'));
    }

    public function showRegister(): void
    {
        $this->render('auth/register', [
            'phoneInput' => true, // page avec champ téléphone : charge intl-tel-input
            'oldInput' => (array) Session::pullFlash('register_old_input', []),
            'errors' => (array) Session::pullFlash('register_errors', []),
        ]);
    }

    public function register(): void
    {
        $data = $this->collectUserInput();
        $errors = $this->validateRegistrationInput($data);
        // saisie renvoyée au formulaire en cas d'erreur : jamais le mot de passe en session
        $keptInput = array_diff_key($data, ['password' => true, 'password_confirm' => true]);

        if ($errors !== []) {
            Session::flash('register_errors', $errors);
            Session::flash('register_old_input', $keptInput);
            $this->redirect('/register');
        }

        try {
            $this->authService->register($data);
        } catch (InvalidArgumentException $exception) {
            // email déjà pris, téléphone ou mot de passe refusé : message affichable
            Session::flash('register_errors', ['general' => $exception->getMessage()]);
            Session::flash('register_old_input', $keptInput);
            $this->redirect('/register');
        } catch (Throwable $exception) {
            error_log('Inscription : ' . $exception->getMessage());
            Session::flash('register_errors', ['general' => 'Impossible de créer ce compte.']);
            Session::flash('register_old_input', $keptInput);
            $this->redirect('/register');
        }

        // le mail de bienvenue ne bloque pas l'inscription s'il échoue
        try {
            $this->mailService->sendWelcomeEmail($data['email'], $data['first_name']);
        } catch (Throwable $exception) {
            error_log('Mail de bienvenue : ' . $exception->getMessage());
        }

        Session::flash('register_success', 'Compte créé. Vous pouvez maintenant vous connecter avec votre mot de passe.');
        $this->redirect('/login');
    }

    public function profile(): void
    {
        $user = $this->userRepository->findById((int) Session::id());

        if ($user === null) {
            Session::logout();
            $this->redirect('/login');
        }

        $this->render('auth/profile', [
            'phoneInput' => true, // page avec champ téléphone : charge intl-tel-input
            'user' => [
                'email' => $user->getEmail(),
                'first_name' => $user->getFirstName(),
                'last_name' => $user->getLastName(),
                'phone' => $user->getPhone(),
                'gsm' => $user->getGsm(),
                'address' => $user->getAddress(),
            ],
            'mustChangePassword' => $user->mustChangePassword(),
            'errors' => (array) Session::pullFlash('profile_errors', []),
            'oldInput' => (array) Session::pullFlash('profile_old_input', []),
            'passwordErrors' => (array) Session::pullFlash('password_errors', []),
        ]);
    }

    public function updateProfile(): void
    {
        $data = [
            'email' => trim((string) ($_POST['email'] ?? '')),
            'first_name' => trim((string) ($_POST['first_name'] ?? '')),
            'last_name' => trim((string) ($_POST['last_name'] ?? '')),
            'phone' => trim((string) ($_POST['phone'] ?? '')),
            'gsm' => trim((string) ($_POST['gsm'] ?? '')),
            'address' => trim((string) ($_POST['address'] ?? '')),
        ];

        $errors = [];

        foreach ($data as $field => $value) {
            if ($value === '') {
                $errors[$field] = 'Ce champ est requis.';
            }
        }

        if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Adresse email invalide.';
        }

        foreach (['phone', 'gsm'] as $field) {
            $phoneError = $data[$field] !== ''
                ? $this->phoneValidator->validate($data[$field])
                : null;

            if ($phoneError !== null) {
                $errors[$field] = $phoneError;
            }
        }

        if ($errors !== []) {
            Session::flash('profile_errors', $errors);
            Session::flash('profile_old_input', $data);
            $this->redirect('/profile');
        }

        try {
            $this->authService->updateProfile((int) Session::id(), $data);
            Session::flash('profile_success', 'Profil mis à jour avec succès.');
        } catch (Throwable $exception) {
            Session::flash('profile_errors', ['general' => $exception->getMessage()]);
        }

        $this->redirect('/profile');
    }

    public function changePassword(): void
    {
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        if ($new !== $confirm) {
            Session::flash('password_errors', [
                'confirm_password' => 'Les mots de passe ne correspondent pas.',
            ]);
            $this->redirect('/profile');
        }

        try {
            $this->authService->changePassword((int) Session::id(), $current, $new);
            $_SESSION['must_change_password'] = false;
            Session::flash('password_success', 'Mot de passe modifié avec succès.');
        } catch (Throwable $exception) {
            Session::flash('password_errors', ['new_password' => $exception->getMessage()]);
        }

        $this->redirect('/profile');
    }

    public function logout(): never
    {
        Session::logout();
        $this->redirect('/login');
    }

    public function showForgotPassword(): void
    {
        $this->render('auth/forgot_password');
    }

    public function forgotPassword(): void
    {
        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('forgot_error', 'Adresse email invalide.');
            $this->redirect('/forgot-password');
        }

        $token = $this->authService->createResetToken($email);

        if ($token !== null) {
            $user = $this->userRepository->findByEmail($email);
            $baseUrl = rtrim((string) config('app.url', 'http://127.0.0.1:8000'), '/');

            try {
                $this->mailService->sendPasswordResetEmail(
                    $email,
                    $user?->getFirstName() ?? '',
                    $baseUrl . '/reset-password/' . rawurlencode($token)
                );
            } catch (Throwable $exception) {
                error_log('Réinitialisation du mot de passe : ' . $exception->getMessage());
            }
        }

        Session::flash(
            'forgot_success',
            'Si cette adresse existe, un lien de réinitialisation vous sera envoyé.'
        );
        $this->redirect('/forgot-password');
    }

    public function showResetPassword(string $token): void
    {
        if ($this->authService->validateResetToken($token) === null) {
            Session::flash('reset_error', 'Le lien est invalide ou expiré.');
            $this->redirect('/forgot-password');
        }

        $this->render('auth/reset_password', ['token' => $token]);
    }

    public function resetPassword(string $token = ''): void
    {
        $token = trim($token !== '' ? $token : (string) ($_POST['token'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        if ($password !== $confirm) {
            Session::flash('reset_errors', [
                'confirm_password' => 'Les mots de passe ne correspondent pas.',
            ]);
            $this->redirect('/reset-password/' . rawurlencode($token));
        }

        try {
            $this->authService->resetPassword($token, $password);
            Session::flash('reset_success', 'Mot de passe réinitialisé. Vous pouvez vous connecter.');
            $this->redirect('/login');
        } catch (Throwable $exception) {
            Session::flash('reset_errors', ['password' => $exception->getMessage()]);
            $this->redirect('/reset-password/' . rawurlencode($token));
        }
    }

    private function collectUserInput(): array
    {
        return [
            'email' => mb_strtolower(trim((string) ($_POST['email'] ?? ''))),
            'first_name' => trim((string) ($_POST['first_name'] ?? '')),
            'last_name' => trim((string) ($_POST['last_name'] ?? '')),
            'phone' => trim((string) ($_POST['phone'] ?? '')),
            'address' => trim((string) ($_POST['address'] ?? '')),
            'password' => (string) ($_POST['password'] ?? ''),
            'password_confirm' => (string) ($_POST['password_confirm'] ?? ''),
        ];
    }

    private function validateRegistrationInput(array $data): array
    {
        $errors = [];

        foreach (['email', 'first_name', 'last_name', 'phone', 'address'] as $field) {
            if ($data[$field] === '') {
                $errors[$field] = 'Ce champ est requis.';
            }
        }

        if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Adresse email invalide.';
        }

        foreach (['phone'] as $field) {
            $phoneError = $data[$field] !== '' && !isset($errors[$field])
                ? $this->phoneValidator->validate($data[$field])
                : null;

            if ($phoneError !== null) {
                $errors[$field] = $phoneError;
            }
        }

        $passwordErrors = $this->passwordPolicy->validate($data['password']);
        if ($passwordErrors !== []) {
            $errors['password'] = implode(' ', $passwordErrors);
        } elseif ($data['password'] !== $data['password_confirm']) {
            $errors['password_confirm'] = 'Les mots de passe ne correspondent pas.';
        }

        if (($_POST['privacy_consent'] ?? '') !== '1') {
            $errors['privacy_consent'] = 'Vous devez accepter la politique de confidentialité et les CGV pour créer un compte.';
        }

        return $errors;
    }
}
