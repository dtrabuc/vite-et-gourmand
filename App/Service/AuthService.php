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

    /**
     * Validate password against ECF requirements (12 chars, upper, lower, digit, special)
     * @param string $password
     * @return array|null Returns null if valid, otherwise array of error messages
     */
    private function validatePassword(string $password): ?array
    {
        $errors = [];
        if (strlen($password) < 12) {
            $errors[] = 'Le mot de passe doit contenir au moins 12 caractères';
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
        // Validate password
        $passwordErrors = $this->validatePassword($data['password']);
        if ($passwordErrors !== null) {
            // In a real application, we would throw an exception or return an error.
            // For now, we'll just let the controller handle it via validation.
            // We'll still hash and create the user, but note that the controller should have validated.
            // To be safe, we'll throw an exception if validation fails.
            throw new \InvalidArgumentException(implode("\n", $passwordErrors));
        }

        // Validate and hash the password
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

        $userData = [
            'email' => $data['email'],
            'password' => $hashedPassword,
            // Le rôle est imposé côté serveur lors d'une inscription publique.
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

        if ($user === null) {
            // Simulate user not found to prevent user enumeration
            // We still return null, but we could also log the attempt.
            return null;
        }

        // Check if account is locked
        if ($user->getLockedUntil() !== null && $user->getLockedUntil() > new \DateTimeImmutable()) {
            return null; // Locked
        }

        if (password_verify($password, $user->getPasswordHash())) {
            // Successful login: reset failed attempts and lock
            $this->userRepository->updateFailedAttempts($user->getId(), 0, null);
            return $user;
        }

        // Failed login: increment failed attempts and lock if >=5
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

        // Validate new password
        $passwordErrors = $this->validatePassword($newPassword);
        if ($passwordErrors !== null) {
            throw new \InvalidArgumentException(implode("\n", $passwordErrors));
        }

        $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->userRepository->updatePassword($userId, $hashedNewPassword);

        // Reset failed attempts on password change (optional, but good practice)
        $this->userRepository->updateFailedAttempts($userId, 0, null);

        return true;
    }

    public function updateProfile(int $userId, array $data): void
    {
        $this->userRepository->update($userId, $data);
    }

    /**
     * Generate a password reset token for the given email.
     * @param string $email
     * @return string|null Returns the plain token if successful, null if user not found.
     */
    public function createResetToken(string $email): ?string
    {
        $user = $this->userRepository->findByEmail($email);

        if ($user === null) {
            // Do not reveal that the user does not exist
            return null;
        }

        // Generate a cryptographically secure random token
        $token = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $token);

        // Set expiration to 1 hour from now
        $expiresAt = (new \DateTimeImmutable())->modify('+1 hour');

        // Store the hashed token and expiration in the user record
        $this->userRepository->updateResetToken($user->getId(), $hashedToken, $expiresAt);

        return $token;
    }

    /**
     * Validate a password reset token and return the associated user if valid.
     * @param string $token
     * @return User|null
     */
    public function validateResetToken(string $token): ?User
    {
        $hashedToken = hash('sha256', $token);
        $user = $this->userRepository->findByResetTokenHash($hashedToken);

        return $user; // Will be null if not found or expired
    }

    /**
     * Reset the password for the given token.
     * @param string $token
     * @param string $newPassword
     * @return bool True if password was reset, false otherwise.
     */
    public function resetPassword(string $token, string $newPassword): bool
    {
        // Validate the token
        $user = $this->validateResetToken($token);
        if ($user === null) {
            return false;
        }

        // Validate the new password
        $passwordErrors = $this->validatePassword($newPassword);
        if ($passwordErrors !== null) {
            return false;
        }

        // Hash the new password
        $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update the password and clear the reset token
        $this->userRepository->updatePassword($user->getId(), $hashedNewPassword);
        $this->userRepository->clearResetToken($user->getId());

        return true;
    }
}
