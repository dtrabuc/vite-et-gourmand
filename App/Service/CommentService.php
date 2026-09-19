<?php
namespace App\Service;

use App\Repository\CommentRepository;

class CommentService
{
    public function __construct(
        private CommentRepository $commentRepository,
        private ?CacheService $cacheService = null
    ) {
        $this->cacheService ??= new RedisCacheService();
    }

    public function getPendingComments(): array
    {
        return $this->commentRepository->findPending();
    }

    public function getAllValidated(): array
    {
        $cached = $this->cacheService->get('comments_all_validated');
        if ($cached !== null) {
            return $cached;
        }

        $comments = $this->commentRepository->findAllValidated();
        $this->cacheService->set('comments_all_validated', $comments, 1800);

        return $comments;
    }

    public function getApprovedReviews(): array
    {
        return $this->getAllValidated();
    }

    public function getHomepageReviews(): array
    {
        $cached = $this->cacheService->get('comments_homepage');
        if ($cached !== null) {
            return $cached;
        }

        $comments = $this->commentRepository->getHomepageReviews();
        $this->cacheService->set('comments_homepage', $comments, 300);

        return $comments;
    }

    public function getByOrderId(int $orderId): ?array
    {
        return $this->commentRepository->findByOrderId($orderId);
    }

    public function create(array $data): int
    {
        if (empty($data['user_id']) || !is_numeric($data['user_id'])) {
            throw new \InvalidArgumentException('L’identifiant utilisateur est requis.');
        }

        if (!isset($data['rating']) || !is_numeric($data['rating']) ||
            (int) $data['rating'] < 1 || (int) $data['rating'] > 5) {
            throw new \InvalidArgumentException('La note doit être comprise entre 1 et 5.');
        }

        if (trim((string) ($data['comment'] ?? '')) === '') {
            throw new \InvalidArgumentException('Le commentaire est obligatoire.');
        }

        $orderId = (int) ($data['order_id'] ?? 0);
        if ($orderId <= 0 || $this->commentRepository->findByOrderId($orderId) !== null) {
            throw new \InvalidArgumentException('Un avis existe déjà pour cette commande ou la commande est invalide.');
        }

        $id = $this->commentRepository->create([
            'user_id' => (int) $data['user_id'],
            'order_id' => $orderId,
            'menu_id' => $data['menu_id'] ?? null,
            'rating' => (int) $data['rating'],
            'comment' => trim((string) $data['comment']),
            'is_validated' => false,
        ]);

        $this->clearCommentCache();
        return $id;
    }

    public function validateComment(string $id): void
    {
        $this->commentRepository->updateValidation($id, true);
        $this->clearCommentCache();
    }

    public function rejectComment(string $id): void
    {
        // Sinon l'avis reste dans la liste en attente.
        $this->commentRepository->delete($id);
        $this->clearCommentCache();
    }

    private function clearCommentCache(): void
    {
        $this->cacheService->delete('comments_all_validated');
        $this->cacheService->delete('comments_homepage');
    }
}
