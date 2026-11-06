<?php
declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

final class NotificationRepository
{
    public function create(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?int $orderId = null,
        ?int $quoteRequestId = null
    ): int {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO user_notifications
                (user_id, order_id, quote_request_id, type, title, message)
             VALUES
                (:user_id, :order_id, :quote_request_id, :type, :title, :message)'
        );

        $stmt->execute([
            'user_id' => $userId,
            'order_id' => $orderId,
            'quote_request_id' => $quoteRequestId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    public function findForUser(int $userId, int $limit = 30): array
    {
        $limit = max(1, min($limit, 100));

        $stmt = Database::pdo()->prepare(
            "SELECT *
             FROM user_notifications
             WHERE user_id = :user_id
             ORDER BY created_at DESC
             LIMIT {$limit}"
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public function markRead(int $id, int $userId): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE user_notifications
             SET is_read = 1
             WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute([
            'id' => $id,
            'user_id' => $userId,
        ]);
    }

    public function markAllRead(int $userId): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE user_notifications
             SET is_read = 1
             WHERE user_id = :user_id AND is_read = 0'
        );
        $stmt->execute(['user_id' => $userId]);
    }
}
