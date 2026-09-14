<?php
namespace Src\Repository;

use Src\Entity\Comment;
use Src\Database\Database;

class CommentRepository
{
    public function findAllValidated(): array
    {
        $mongo = Database::getMongo();
        $database = $mongo->selectDatabase('viteetgourmand');
        $collection = $database->selectCollection('comments');

        $cursor = $collection->find(['isValidated' => true]);

        $reviews = [];
        foreach ($cursor as $doc) {
            // Get user details from MariaDB
            $userRepository = new \Src\Repository\UserRepository();
            $user = $userRepository->findById((int)$doc['userId']);

            $reviews[] = [
                'id' => (string)$doc['_id'], // MongoDB ID as string
                'user_id' => (string)$doc['userId'],
                'menu_id' => isset($doc['menuId']) ? (string)$doc['menuId'] : null,
                'rating' => (int)$doc['rating'],
                'comment' => $doc['comment'],
                'is_validated' => (bool)$doc['isValidated'],
                'created_at' => $doc['createdAt'] instanceof \MongoDB\BSON\UTCDateTime
                    ? (new \DateTimeImmutable())->setTimestamp($doc['createdAt']->getSeconds() / 1000)
                    : null,
                'updated_at' => $doc['updatedAt'] instanceof \MongoDB\BSON\UTCDateTime
                    ? (new \DateTimeImmutable())->setTimestamp($doc['updatedAt']->getSeconds() / 1000)
                    : null,
                'first_name' => $user !== null ? $user->getFirstName() : '',
                'last_name' => $user !== null ? $user->getLastName() : '',
                'user_name' => $user !== null ? $user->getFirstName() . ' ' . $user->getLastName() : '',
            ];
        }

        return $reviews;
    }

    public function getHomepageReviews(): array
    {
        $mongo = Database::getMongo();
        $database = $mongo->selectDatabase('viteetgourmand');
        $collection = $database->selectCollection('comments');

        $cursor = $collection->find(['isValidated' => true])->sort(['createdAt' => -1])->limit(3);

        $reviews = [];
        foreach ($cursor as $doc) {
            // Get user details from MariaDB
            $userRepository = new \Src\Repository\UserRepository();
            $user = $userRepository->findById((int)$doc['userId']);

            $reviews[] = [
                'id' => (string)$doc['_id'], // MongoDB ID as string
                'user_id' => (string)$doc['userId'],
                'menu_id' => isset($doc['menuId']) ? (string)$doc['menuId'] : null,
                'rating' => (int)$doc['rating'],
                'comment' => $doc['comment'],
                'is_validated' => (bool)$doc['isValidated'],
                'created_at' => $doc['createdAt'] instanceof \MongoDB\BSON\UTCDateTime
                    ? (new \DateTimeImmutable())->setTimestamp($doc['createdAt']->getSeconds() / 1000)
                    : null,
                'updated_at' => $doc['updatedAt'] instanceof \MongoDB\BSON\UTCDateTime
                    ? (new \DateTimeImmutable())->setTimestamp($doc['updatedAt']->getSeconds() / 1000)
                    : null,
                'first_name' => $user !== null ? $user->getFirstName() : '',
                'last_name' => $user !== null ? $user->getLastName() : '',
            ];
        }

        return $reviews;
    }

    public function create(array $data): int
    {
        // For now, we'll still insert into MariaDB for compatibility with existing code.
        // In the future, we should switch to MongoDB.
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('INSERT INTO comments (user_id, menu_id, rating, comment, is_validated)
                               VALUES (:user_id, :menu_id, :rating, :comment, :is_validated)');
        $stmt->execute([
            'user_id' => $data['user_id'],
            'menu_id' => $data['menu_id'] ?? null,
            'rating' => $data['rating'],
            'comment' => $data['comment'],
            'is_validated' => $data['is_validated'] ?? 0,
        ]);

        return (int)$pdo->lastInsertId();
    }

    public function updateValidation(int $id, bool $isValidated): void
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('UPDATE comments SET is_validated = :is_validated, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'is_validated' => $isValidated ? 1 : 0,
        ]);
    }
}
