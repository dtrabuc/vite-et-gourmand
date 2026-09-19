<?php
namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;

class AuthService
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function validatePassword(string $password): ?array
    {
        $errors = [];
        if (strlen($password) < 10) {
            $errors[] = 'Le mot de passe doit contenir au moins 10 caractères';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins une majuscule';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins une minuscule';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins un chiffre';
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins un caractère spécial';
        }
        return empty($errors) ? null : $errors;
    }

    public function register(array $data): int
    {
        $password = (string) ($data['password'] ?? '');
        $passwordErrors = $this->validatePassword($password);
        if ($passwordErrors !== null) {
            throw new \InvalidArgumentException(implode("\n", $passwordErrors));
        }

        // Valider puis hacher le mot de passe
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $userData = [
            'email' => $data['email'],
            'password' => $hashedPassword,
            'role' => 'user',
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'],
            'gsm' => $data['gsm'],
            'address' => $data['address'],
        ];

        return $this->userRepository->create($userData);
    }

    public function login(string $email, string $password): ?User
    {
        $user = $this->userRepository->findByEmail($email);

        if ($user === null || !$this->userRepository->isActive($user->getId())) {
            return null;
        }

        // Check if account is locked
        if ($user->getLockedUntil() !== null && $user->getLockedUntil() > new \DateTimeImmutable()) {
            return null; // Locked
        }

        if (password_verify($password, $user->getPasswordHash())) {
            $this->userRepository->updateFailedAttempts($user->getId(), 0, null);
            return $user;
        }

        $newFailedAttempts = $user->getFailedAttempts() + 1;
        $lockedUntil = null;
        if ($newFailedAttempts >= 5) {
            $lockedUntil = (new \DateTimeImmutable())->modify('+15 minutes');
        }
        $this->userRepository->updateFailedAttempts($user->getId(), $newFailedAttempts, $lockedUntil);

        return null;
    }

    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            return false;
        }

        if (!password_verify($currentPassword, $user->getPasswordHash())) {
            return false;
        }

        $passwordErrors = $this->validatePassword($newPassword);
        if ($passwordErrors !== null) {
            throw new \InvalidArgumentException(implode("\n", $passwordErrors));
        }

        $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->userRepository->updatePassword($userId, $hashedNewPassword);

        $this->userRepository->updateFailedAttempts($userId, 0, null);

        return true;
    }

    public function updateProfile(int $userId, array $data): void
    {
        $this->userRepository->update($userId, $data);
    }

    public function createResetToken(string $email): ?string
    {
        $user = $this->userRepository->findByEmail($email);

        if ($user === null) {
            return null;
        }

        $token = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $token);

        $expiresAt = (new \DateTimeImmutable())->modify('+1 hour');

        $this->userRepository->updateResetToken($user->getId(), $hashedToken, $expiresAt);

        return $token;
    }

    public function validateResetToken(string $token): ?User
    {
        $hashedToken = hash('sha256', $token);
        $user = $this->userRepository->findByResetTokenHash($hashedToken);

        return $user;
    }

    public function resetPassword(string $token, string $newPassword): bool
    {
        $user = $this->validateResetToken($token);
        if ($user === null) {
            return false;
        }

        $passwordErrors = $this->validatePassword($newPassword);
        if ($passwordErrors !== null) {
            return false;
        }

        $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $this->userRepository->updatePassword($user->getId(), $hashedNewPassword);
        $this->userRepository->clearResetToken($user->getId());

        return true;
    }
}
