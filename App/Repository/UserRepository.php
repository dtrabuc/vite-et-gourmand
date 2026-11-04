<?php
declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Entity\User;
use DateTimeImmutable;
use DateTimeInterface;

final class UserRepository
{
    public function findByEmail(string $email): ?User
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM users WHERE email = :email LIMIT 1'
        );
        $stmt->execute(['email' => mb_strtolower(trim($email))]);

        $row = $stmt->fetch();

        return $row === false ? null : $this->hydrate($row);
    }

    public function findById(int $id): ?User
    {
        if ($id < 1) {
            return null;
        }

        $stmt = Database::pdo()->prepare(
            'SELECT * FROM users WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch();

        return $row === false ? null : $this->hydrate($row);
    }

    public function create(array $data): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO users (
                email, password, role, first_name, last_name, phone, gsm, address,
                is_active, must_change_password
            ) VALUES (
                :email, :password, :role, :first_name, :last_name, :phone, :gsm, :address,
                :is_active, :must_change_password
            )'
        );

        $stmt->execute([
            'email' => mb_strtolower(trim((string) $data['email'])),
            'password' => (string) $data['password'],
            'role' => (string) ($data['role'] ?? 'user'),
            'first_name' => trim((string) $data['first_name']),
            'last_name' => trim((string) $data['last_name']),
            'phone' => trim((string) $data['phone']),
            'gsm' => trim((string) ($data['gsm'] ?? '')),
            'address' => trim((string) $data['address']),
            'is_active' => (bool) ($data['is_active'] ?? true) ? 1 : 0,
            'must_change_password' => (bool) ($data['must_change_password'] ?? false) ? 1 : 0,
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    public function deleteNewCustomer(int $id): void
    {
        $stmt = Database::pdo()->prepare(
            'DELETE FROM users WHERE id = :id AND role = :role AND must_change_password = 1'
        );
        $stmt->execute(['id' => $id, 'role' => 'user']);
    }

    public function isActive(int $id): bool
    {
        $stmt = Database::pdo()->prepare(
            'SELECT is_active FROM users WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        return (int) $stmt->fetchColumn() === 1;
    }

    public function update(int $id, array $data): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE users SET
                email = :email,
                first_name = :first_name,
                last_name = :last_name,
                phone = :phone,
                gsm = :gsm,
                address = :address,
                updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );

        $stmt->execute([
            'id' => $id,
            'email' => mb_strtolower(trim((string) $data['email'])),
            'first_name' => trim((string) $data['first_name']),
            'last_name' => trim((string) $data['last_name']),
            'phone' => trim((string) $data['phone']),
            'gsm' => trim((string) $data['gsm']),
            'address' => trim((string) $data['address']),
        ]);
    }

    public function updatePassword(int $id, string $hash, bool $mustChangePassword = false): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE users
             SET password = :password, must_change_password = :must_change_password,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'password' => $hash,
            'must_change_password' => $mustChangePassword ? 1 : 0,
        ]);
    }

    public function updateFailedAttempts(int $id, int $failedAttempts, ?DateTimeInterface $lockedUntil): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE users
             SET failed_attempts = :failed_attempts,
                 locked_until = :locked_until,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );

        $stmt->execute([
            'id' => $id,
            'failed_attempts' => $failedAttempts,
            'locked_until' => $lockedUntil?->format('Y-m-d H:i:s'),
        ]);
    }

    public function updateResetToken(int $userId, string $hash, DateTimeInterface $expiresAt): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE users
             SET reset_token_hash = :hash,
                 reset_token_expires_at = :expires_at,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $userId,
            'hash' => $hash,
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
        ]);
    }

    public function findByResetTokenHash(string $hash): ?User
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM users
             WHERE reset_token_hash = :hash
               AND reset_token_expires_at > CURRENT_TIMESTAMP
             LIMIT 1'
        );
        $stmt->execute(['hash' => $hash]);

        $row = $stmt->fetch();

        return $row === false ? null : $this->hydrate($row);
    }

    public function clearResetToken(int $userId): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE users
             SET reset_token_hash = NULL,
                 reset_token_expires_at = NULL,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $stmt->execute(['id' => $userId]);
    }

    public function findCustomers(?string $search = null, ?bool $active = null): array
    {
        $sql = 'SELECT id, email, first_name, last_name, phone, gsm, address,
                       is_active, created_at, updated_at
                FROM users
                WHERE role = :role';
        $params = ['role' => 'user'];

        if ($search !== null && trim($search) !== '') {
            $sql .= ' AND (
                email LIKE :search
                OR first_name LIKE :search
                OR last_name LIKE :search
                OR phone LIKE :search
                OR gsm LIKE :search
            )';
            $params['search'] = '%' . trim($search) . '%';
        }

        if ($active !== null) {
            $sql .= ' AND is_active = :active';
            $params['active'] = $active ? 1 : 0;
        }

        $sql .= ' ORDER BY created_at DESC';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function findEmployees(): array
    {
        $stmt = Database::pdo()->query(
            "SELECT id, email, first_name, last_name, role, is_active, created_at
             FROM users
             WHERE role = 'employee'
             ORDER BY created_at DESC"
        );

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'email' => $row['email'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'role' => $row['role'],
                'is_active' => (bool) $row['is_active'],
                'created_at' => !empty($row['created_at'])
                    ? new DateTimeImmutable($row['created_at'])
                    : null,
            ],
            $stmt->fetchAll()
        );
    }

    public function countAll(): int
    {
        return (int) Database::pdo()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    public function findStaffIds(): array
    {
        $stmt = Database::pdo()->query(
            "SELECT id FROM users WHERE role IN ('employee', 'admin') AND is_active = 1"
        );

        return array_map(static fn (array $row): int => (int) $row['id'], $stmt->fetchAll());
    }

    public function setCustomerActive(int $id, bool $active): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE users
             SET is_active = :active, updated_at = CURRENT_TIMESTAMP
             WHERE id = :id AND role = :role'
        );

        $stmt->execute([
            'id' => $id,
            'active' => $active ? 1 : 0,
            'role' => 'user',
        ]);
    }

    public function setActive(int $id, bool $active): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE users
             SET is_active = :active, updated_at = CURRENT_TIMESTAMP
             WHERE id = :id AND role = :role'
        );

        $stmt->execute([
            'id' => $id,
            'active' => $active ? 1 : 0,
            'role' => 'employee',
        ]);
    }

    private function hydrate(array $row): User
    {
        $user = new User();
        $user->setId((int) $row['id']);
        $user->setEmail((string) $row['email']);
        $user->setPasswordHash((string) $row['password']);
        $user->setRole((string) $row['role']);
        $user->setMustChangePassword((bool) ($row['must_change_password'] ?? false));
        $user->setFirstName((string) $row['first_name']);
        $user->setLastName((string) $row['last_name']);
        $user->setPhone((string) ($row['phone'] ?? ''));
        $user->setGsm((string) ($row['gsm'] ?? ''));
        $user->setAddress((string) ($row['address'] ?? ''));
        $user->setCreatedAt(!empty($row['created_at']) ? new DateTimeImmutable($row['created_at']) : null);
        $user->setUpdatedAt(!empty($row['updated_at']) ? new DateTimeImmutable($row['updated_at']) : null);
        $user->setFailedAttempts((int) ($row['failed_attempts'] ?? 0));
        $user->setLockedUntil(!empty($row['locked_until']) ? new DateTimeImmutable($row['locked_until']) : null);
        $user->setResetTokenHash($row['reset_token_hash'] ?? null);
        $user->setResetTokenExpiresAt(
            !empty($row['reset_token_expires_at'])
                ? new DateTimeImmutable($row['reset_token_expires_at'])
                : null
        );

        return $user;
    }
}
