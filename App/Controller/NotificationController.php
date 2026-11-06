<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Session;
use App\Service\NotificationService;

final class NotificationController extends BaseController
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {
    }

    public function index(): void
    {
        $userId = Session::id();

        if ($userId === null) {
            $this->redirect('/login');
        }

        // Consultation sans effet de bord : le marquage « lu » passe par un POST.
        $this->render('notification/index', [
            'notifications' => $this->notificationService->forUser($userId),
        ]);
    }

    public function readAll(): never
    {
        $userId = Session::id();

        if ($userId === null) {
            $this->redirect('/login');
        }

        $this->notificationService->markAllRead($userId);
        $this->redirect('/notifications');
    }

    public function read(int $id): void
    {
        $userId = Session::id();

        if ($userId === null) {
            $this->redirect('/login');
        }

        $this->notificationService->markRead($id, $userId);
        $this->redirect('/notifications');
    }
}
