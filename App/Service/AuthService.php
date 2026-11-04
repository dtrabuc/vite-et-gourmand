<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class AuthService
{
    public function __construct(
        private UserRepository $userRepository,
        private PasswordPolicy $passwordPolicy,
        private PhoneValidator $phoneValidator
    ) {
    }

    /** @return array{user_id:int, temporary_password:string} */
    public function register(array $data): array
    {
        $email = $this->normalizeEmail((string) ($data['email'] ?? ''));

        foreach (['phone'] as $field) {
            $value = trim((string) ($data[$field] ?? ''));
            if ($value !== '') {
                $data[$field] = $this->normalizedPhone($value);
            }
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Adresse email invalide.');
        }

        if ($this->userRepository->findByEmail($email) !== null) {
            throw new InvalidArgumentException('Cette adresse email est déjà utilisée.');
        }

        $temporaryPassword = $this->generateTemporaryPassword();
        $userId = $this->userRepository->create([
            'email' => $email,
            'password' => $this->passwordPolicy->hash($temporaryPassword),
            'role' => 'user',
            'first_name' => trim((string) ($data['first_name'] ?? '')),
            'last_name' => trim((string) ($data['last_name'] ?? '')),
            'phone' => trim((string) ($data['phone'] ?? '')),
            'gsm' => '',
            'address' => trim((string) ($data['address'] ?? '')),
            'is_active' => true,
            'must_change_password' => true,
        ]);
        return ['user_id' => $userId, 'temporary_password' => $temporaryPassword];
    }

    public function login(string $email, string $password): ?User
    {
        $email = $this->normalizeEmail($email);
        $user = $this->userRepository->findByEmail($email);

        if ($user === null || !$this->userRepository->isActive($user->getId())) {
            return null;
        }

        $lockedUntil = $user->getLockedUntil();

        if (
            $lockedUntil !== null
            && $lockedUntil->getTimestamp() > time()
        ) {
            return null;
        }

        $storedPassword = $user->getPasswordHash();

        // Seul un hash reconnu par password_verify() est accepté :
        // un mot de passe stocké en clair ne permet jamais de se connecter.
        if (!password_verify($password, $storedPassword)) {
            $failedAttempts = $user->getFailedAttempts() + 1;

            $this->userRepository->updateFailedAttempts(
                $user->getId(),
                $failedAttempts,
                $failedAttempts >= 5
                    ? new DateTimeImmutable('+15 minutes')
                    : null
            );

            return null;
        }

        if ($this->passwordPolicy->needsRehash($storedPassword)) {
            $this->userRepository->updatePassword(
                $user->getId(),
                $this->passwordPolicy->hash($password),
                $user->mustChangePassword()
            );
        }

        $this->userRepository->updateFailedAttempts(
            $user->getId(),
            0,
            null
        );

        return $user;
    }

    public function changePassword(
        int $userId,
        string $currentPassword,
        string $newPassword
    ): void {
        $user = $this->userRepository->findById($userId);

        if (
            $user === null
            || !password_verify(
                $currentPassword,
                $user->getPasswordHash()
            )
        ) {
            throw new InvalidArgumentException(
                'Mot de passe actuel incorrect.'
            );
        }

        $this->assertStrongPassword($newPassword);

        $this->userRepository->updatePassword(
            $userId,
            $this->passwordPolicy->hash($newPassword)
        );
        $this->userRepository->updateFailedAttempts(
            $userId,
            0,
            null
        );
    }

    public function updateProfile(int $userId, array $data): void
    {
        $email = $this->normalizeEmail((string) ($data['email'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Adresse email invalide.');
        }

        $existing = $this->userRepository->findByEmail($email);

        if (
            $existing !== null
            && $existing->getId() !== $userId
        ) {
            throw new InvalidArgumentException(
                'Cette adresse email est déjà utilisée.'
            );
        }

        $data['email'] = $email;

        foreach (['phone'] as $field) {
            $value = trim((string) ($data[$field] ?? ''));

            if ($value !== '') {
                $data[$field] = $this->normalizedPhone($value);
            }
        }

        $this->userRepository->update($userId, $data);
    }

    public function createResetToken(string $email): ?string
    {
        $user = $this->userRepository->findByEmail(
            $this->normalizeEmail($email)
        );

        if (
            $user === null
            || !$this->userRepository->isActive($user->getId())
        ) {
            return null;
        }

        $token = bin2hex(random_bytes(32));

        $this->userRepository->updateResetToken(
            $user->getId(),
            hash('sha256', $token),
            new DateTimeImmutable('+1 hour')
        );

        return $token;
    }

    public function validateResetToken(string $token): ?User
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }

        return $this->userRepository->findByResetTokenHash(
            hash('sha256', $token)
        );
    }

    public function resetPassword(
        string $token,
        string $newPassword
    ): void {
        $user = $this->validateResetToken($token);

        if ($user === null) {
            throw new InvalidArgumentException(
                'Le lien de réinitialisation est invalide ou expiré.'
            );
        }

        $this->assertStrongPassword($newPassword);

        $this->userRepository->updatePassword(
            $user->getId(),
            $this->passwordPolicy->hash($newPassword)
        );
        $this->userRepository->clearResetToken($user->getId());
    }

    private function assertStrongPassword(string $password): void
    {
        $errors = $this->passwordPolicy->validate($password);

        if ($errors !== []) {
            throw new InvalidArgumentException(implode(' ', $errors));
        }
    }

    private function generateTemporaryPassword(): string
    {
        $groups = [
            'ABCDEFGHJKLMNPQRSTUVWXYZ',
            'abcdefghijkmnopqrstuvwxyz',
            '23456789',
            '!@#$%*-_+',
        ];
        $characters = [];

        foreach ($groups as $group) {
            $characters[] = $group[random_int(0, strlen($group) - 1)];
        }

        $allCharacters = implode('', $groups);
        while (count($characters) < 18) {
            $characters[] = $allCharacters[random_int(0, strlen($allCharacters) - 1)];
        }

        for ($index = count($characters) - 1; $index > 0; $index--) {
            $swapIndex = random_int(0, $index);
            [$characters[$index], $characters[$swapIndex]] = [$characters[$swapIndex], $characters[$index]];
        }

        return implode('', $characters);
    }

    /**
     * Valide puis normalise un numéro au format E.164.
     */
    private function normalizedPhone(string $phone): string
    {
        $error = $this->phoneValidator->validate($phone);

        if ($error !== null) {
            throw new InvalidArgumentException($error);
        }

        return $this->phoneValidator->normalize($phone);
    }

    private function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
