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

    /** JSON pour la cloche : nombre de non lues et nouvelles notifications depuis ?after=ID. */
    public function feed(): never
    {
        $userId = Session::id();

        if ($userId === null) {
            $this->json(['success' => false, 'error' => 'Connexion requise.'], 401);
        }

        $after = filter_var($_GET['after'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        $feed = $this->notificationService->feed($userId, $after === false ? 0 : $after);
        $feed['items'] = array_map(static fn (array $n): array => [
            'id' => (int) $n['id'],
            'title' => (string) $n['title'],
            'message' => (string) $n['message'],
            'link' => !empty($n['order_id']) ? '/orders' : (!empty($n['quote_request_id']) ? '/quote' : '/notifications'),
        ], $feed['items']);

        $this->json(['success' => true, 'data' => $feed]);
    }
}
