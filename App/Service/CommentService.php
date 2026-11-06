<?php
declare(strict_types=1);

namespace App\Service;

use App\Repository\CommentRepository;
use App\Repository\OrderRepository;

final readonly class CommentService
{
    public function __construct(
        private CommentRepository $commentRepository,
        private OrderRepository $orderRepository,
        private CacheService $cacheService
    ) {
    }

    public function getPendingComments(): array
    {
        return $this->commentRepository->findPending();
    }

    public function getHomepageReviews(): array
    {
        $cached = $this->cacheService->get('comments_homepage');
        if (is_array($cached)) {
            return $this->reviewsFromCache($cached);
        }

        $comments = $this->commentRepository->getHomepageReviews();
        $this->cacheService->set('comments_homepage', $this->reviewsToCache($comments), 300);

        return $comments;
    }

    /**
     * Le cache n'accepte aucun objet : les dates sont stockées au format ISO 8601.
     */
    private function reviewsToCache(array $reviews): array
    {
        return array_map(
            static function (array $review): array {
                foreach (['created_at', 'updated_at'] as $field) {
                    if (($review[$field] ?? null) instanceof \DateTimeInterface) {
                        $review[$field] = $review[$field]->format(\DateTimeInterface::ATOM);
                    }
                }

                return $review;
            },
            $reviews
        );
    }

    private function reviewsFromCache(array $reviews): array
    {
        return array_map(
            static function (array $review): array {
                foreach (['created_at', 'updated_at'] as $field) {
                    if (is_string($review[$field] ?? null)) {
                        $review[$field] = new \DateTimeImmutable($review[$field]);
                    }
                }

                return $review;
            },
            $reviews
        );
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
        if ($orderId <= 0 || $this->orderRepository->findById($orderId) === null) {
            throw new \InvalidArgumentException('Une commande valide est requise pour laisser un avis.');
        }

        if ($this->commentRepository->findByOrderId($orderId) !== null) {
            throw new \InvalidArgumentException('Un avis existe déjà pour cette commande.');
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
            $this->commentRepository->delete($id);
        $this->clearCommentCache();
    }

    private function clearCommentCache(): void
    {
        $this->cacheService->delete('comments_homepage');
    }
}
