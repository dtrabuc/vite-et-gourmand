<?php
declare(strict_types=1);

namespace App\Service;

use App\Repository\NotificationRepository;
use App\Repository\UserRepository;

final readonly class NotificationService
{
    public function __construct(
        private NotificationRepository $notificationRepository,
        private UserRepository $userRepository
    ) {
    }

    public function notifyStaff(
        string $type,
        string $title,
        string $message,
        ?int $orderId = null,
        ?int $quoteRequestId = null
    ): void {
        foreach ($this->userRepository->findStaffIds() as $staffId) {
            $this->notify($staffId, $type, $title, $message, $orderId, $quoteRequestId);
        }
    }

    public function notify(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?int $orderId = null,
        ?int $quoteRequestId = null
    ): int {
        return $this->notificationRepository->create(
            $userId,
            $type,
            $title,
            $message,
            $orderId,
            $quoteRequestId
        );
    }

    public function forUser(int $userId, int $limit = 30): array
    {
        return $this->notificationRepository->findForUser($userId, $limit);
    }

    public function markRead(int $id, int $userId): void
    {
        $this->notificationRepository->markRead($id, $userId);
    }

    public function markAllRead(int $userId): void
    {
        $this->notificationRepository->markAllRead($userId);
    }

    public function unreadCount(int $userId): int
    {
        return $this->notificationRepository->countUnread($userId);
    }

    /** @return array{unread: int, last_id: int, items: list<array>} */
    public function feed(int $userId, int $afterId): array
    {
        return [
            'unread' => $this->notificationRepository->countUnread($userId),
            'last_id' => $this->notificationRepository->lastId($userId),
            'items' => $afterId > 0 ? $this->notificationRepository->findAfter($userId, $afterId) : [],
        ];
    }
}
