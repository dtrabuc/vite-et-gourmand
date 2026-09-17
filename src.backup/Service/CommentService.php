<?php
namespace Src\Service;

use Src\Entity\Comment;
use Src\Repository\CommentRepository;

class CommentService
{
    private CommentRepository $commentRepository;
    private \Src\Service\CacheService $cacheService;

    public function __construct(CommentRepository $commentRepository, ?\Src\Service\CacheService $cacheService = null)
    {
        $this->commentRepository = $commentRepository;
        // Try to use RedisCacheService first, fall back to basic CacheService
        $this->cacheService = $cacheService ?? new \Src\Service\RedisCacheService();
    }

    public function getAllValidated(): array
    {
        // Try to get from cache first
        $cached = $this->cacheService->get('comments_all_validated');
        if ($cached !== null) {
            return $cached;
        }
        
        // If not in cache, get from database
        $comments = $this->commentRepository->findAllValidated();
        
        // Store in cache for 30 minutes (comments can be validated/modified more frequently)
        $this->cacheService->set('comments_all_validated', $comments, 1800);
        
        return $comments;
    }

    public function getApprovedReviews(): array
    {
        return $this->getAllValidated();
    }

    public function getHomepageReviews(): array
    {
        // Try to get from cache first
        $cached = $this->cacheService->get('comments_homepage');
        if ($cached !== null) {
            return $cached;
        }
        
        // If not in cache, get from database
        $comments = $this->commentRepository->getHomepageReviews();
        
        // Store in cache for 5 minutes (homepage needs to be relatively fresh)
        $this->cacheService->set('comments_homepage', $comments, 300);
        
        return $comments;
    }

    public function create(array $data): int
    {
        // Validate data
        if (empty($data['user_id']) || !is_numeric($data['user_id'])) {
            throw new \InvalidArgumentException('User ID is required and must be numeric');
        }
        if (empty($data['rating']) || !is_numeric($data['rating']) || $data['rating'] < 1 || $data['rating'] > 5) {
            throw new \InvalidArgumentException('Rating must be an integer between 1 and 5');
        }
        if (empty($data['comment'])) {
            throw new \InvalidArgumentException('Comment is required');
        }

        $id = $this->commentRepository->create([
            'user_id' => (int)$data['user_id'],
            'menu_id' => $data['menu_id'] ?? null,
            'rating' => (int)$data['rating'],
            'comment' => $data['comment'],
            'is_validated' => $data['is_validated'] ?? 0,
        ]);
        
        // Clear related cache entries since data has changed
        $this->clearCommentCache();
        
        return $id;
    }

    public function validateComment(int $id): void
    {
        $this->commentRepository->updateValidation($id, true);
        
        // Clear related cache entries since data has changed
        $this->clearCommentCache();
    }

    public function rejectComment(int $id): void
    {
        $this->commentRepository->updateValidation($id, false);
        
        // Clear related cache entries since data has changed
        $this->clearCommentCache();
    }

    /**
     * Clear comment-related cache entries
     */
    private function clearCommentCache(): void
    {
        $this->cacheService->delete('comments_all_validated');
        $this->cacheService->delete('comments_homepage');
    }
}
