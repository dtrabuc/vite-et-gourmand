<?php
namespace App\Repository;

use App\Entity\User;
use App\Core\Database;

class UserRepository
{
    public function findByEmail(string $email): ?User
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        $user = new User();
        $user->setId((int)$row['id']);
        $user->setEmail($row['email']);
        $user->setPasswordHash($row['password']);
        $user->setRole($row['role']);
        $user->setFirstName($row['first_name']);
        $user->setLastName($row['last_name']);
        $user->setPhone($row['phone']);
        $user->setGsm($row['gsm']);
        $user->setAddress($row['address']);
        $user->setCreatedAt($row['created_at'] ? new \DateTimeImmutable($row['created_at']) : null);
        $user->setUpdatedAt($row['updated_at'] ? new \DateTimeImmutable($row['updated_at']) : null);
        $user->setFailedAttempts((int)$row['failed_attempts']);
        $user->setLockedUntil($row['locked_until'] ? new \DateTimeImmutable($row['locked_until']) : null);
        $user->setResetTokenHash($row['reset_token_hash']);
        $user->setResetTokenExpiresAt($row['reset_token_expires_at'] ? new \DateTimeImmutable($row['reset_token_expires_at']) : null);

        return $user;
    }

    public function findById(int $id): ?User
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        $user = new User();
        $user->setId((int)$row['id']);
        $user->setEmail($row['email']);
        $user->setPasswordHash($row['password']);
        $user->setRole($row['role']);
        $user->setFirstName($row['first_name']);
        $user->setLastName($row['last_name']);
        $user->setPhone($row['phone']);
        $user->setGsm($row['gsm']);
        $user->setAddress($row['address']);
        $user->setCreatedAt($row['created_at'] ? new \DateTimeImmutable($row['created_at']) : null);
        $user->setUpdatedAt($row['updated_at'] ? new \DateTimeImmutable($row['updated_at']) : null);

        return $user;
    }

    public function create(array $data): int
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('INSERT INTO users (email, password, role, first_name, last_name, phone, gsm, address, failed_attempts, locked_until, reset_token_hash, reset_token_expires_at)
                               VALUES (:email, :password, :role, :first_name, :last_name, :phone, :gsm, :address, :failed_attempts, :locked_until, :reset_token_hash, :reset_token_expires_at)');
        $stmt->execute([
            'email' => $data['email'],
            'password' => $data['password'], // Already hashed
            'role' => $data['role'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'],
            'gsm' => $data['gsm'],
            'address' => $data['address'],
            'failed_attempts' => 0,
            'locked_until' => null,
            'reset_token_hash' => null,
            'reset_token_expires_at' => null,
        ]);

        return (int)$pdo->lastInsertId();
    }

    public function setActive(int $id, bool $active): void
    {
        $stmt = Database::getPDO()->prepare("UPDATE users SET is_active = :active, updated_at = NOW() WHERE id = :id AND role = 'employee'");
        $stmt->execute(['id' => $id, 'active' => $active ? 1 : 0]);
    }

    public function isActive(int $id): bool
    {
        $stmt = Database::getPDO()->prepare('SELECT is_active FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $value = $stmt->fetchColumn();
        return $value !== false && (int)$value === 1;
    }

    public function update(int $id, array $data): void
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('UPDATE users SET
                               email = :email,
                               first_name = :first_name,
                               last_name = :last_name,
                               phone = :phone,
                               gsm = :gsm,
                               address = :address,
                               updated_at = NOW()
                               WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'email' => $data['email'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'],
            'gsm' => $data['gsm'],
            'address' => $data['address'],
        ]);
    }

    public function updatePassword(int $id, string $hashedPassword): void
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('UPDATE users SET password = :password, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'password' => $hashedPassword,
        ]);
    }

    public function updateFailedAttempts(int $id, int $failedAttempts, ?\DateTimeInterface $lockedUntil): void
    {
        $pdo = Database::getPDO();
        $lockedUntilString = $lockedUntil ? $lockedUntil->format('Y-m-d H:i:s') : null;
        $stmt = $pdo->prepare('UPDATE users SET failed_attempts = :failed_attempts, locked_until = :locked_until, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'failed_attempts' => $failedAttempts,
            'locked_until' => $lockedUntilString,
        ]);
    }

    public function findByResetTokenHash(string $tokenHash): ?User
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE reset_token_hash = :token_hash AND reset_token_expires_at > NOW()');
        $stmt->execute(['token_hash' => $tokenHash]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        $user = new User();
        $user->setId((int)$row['id']);
        $user->setEmail($row['email']);
        $user->setPasswordHash($row['password']);
        $user->setRole($row['role']);
        $user->setFirstName($row['first_name']);
        $user->setLastName($row['last_name']);
        $user->setPhone($row['phone']);
        $user->setGsm($row['gsm']);
        $user->setAddress($row['address']);
        $user->setCreatedAt($row['created_at'] ? new \DateTimeImmutable($row['created_at']) : null);
        $user->setUpdatedAt($row['updated_at'] ? new \DateTimeImmutable($row['updated_at']) : null);
        $user->setFailedAttempts((int)$row['failed_attempts']);
        $user->setLockedUntil($row['locked_until'] ? new \DateTimeImmutable($row['locked_until']) : null);
        $user->setResetTokenHash($row['reset_token_hash']);
        $user->setResetTokenExpiresAt($row['reset_token_expires_at'] ? new \DateTimeImmutable($row['reset_token_expires_at']) : null);

        return $user;
    }

    public function updateResetToken(int $userId, string $hashedToken, \DateTimeInterface $expiresAt): void
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('UPDATE users SET reset_token_hash = :token_hash, reset_token_expires_at = :expires_at, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'id' => $userId,
            'token_hash' => $hashedToken,
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
        ]);
    }

    public function clearResetToken(int $userId): void
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('UPDATE users SET reset_token_hash = NULL, reset_token_expires_at = NULL, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'id' => $userId,
        ]);
    }
}
