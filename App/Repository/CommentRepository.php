<?php
namespace App\Repository;

use App\Entity\Comment;
use App\Core\Database;

class CommentRepository
{
    public function findAllValidated(): array
    {
        $database = Database::getMongoDatabase();
        $collection = $database->selectCollection('comments');

        $cursor = $collection->find(['isValidated' => true]);

        $reviews = [];
        foreach ($cursor as $doc) {
            // Get user details from MariaDB
            $userRepository = new \App\Repository\UserRepository();
            $user = $userRepository->findById((int)$doc['userId']);

            $reviews[] = [
                'id' => (string)$doc['_id'], // MongoDB ID as string
                'user_id' => (string)$doc['userId'],
                'menu_id' => isset($doc['menuId']) ? (string)$doc['menuId'] : null,
                'rating' => (int)$doc['rating'],
                'comment' => $doc['comment'],
                'is_validated' => (bool)$doc['isValidated'],
                'created_at' => $doc['createdAt'] instanceof \MongoDB\BSON\UTCDateTime
                    ? \DateTimeImmutable::createFromMutable($doc['createdAt']->toDateTime())
                    : null,
                'updated_at' => $doc['updatedAt'] instanceof \MongoDB\BSON\UTCDateTime
                    ? \DateTimeImmutable::createFromMutable($doc['updatedAt']->toDateTime())
                    : null,
                'first_name' => $user !== null ? $user->getFirstName() : '',
                'last_name' => $user !== null ? $user->getLastName() : '',
                'user_name' => $user !== null ? $user->getFirstName() . ' ' . $user->getLastName() : ($doc['authorName'] ?? ''),
            ];
        }

        return $reviews;
    }

    public function getHomepageReviews(): array
    {
        $database = Database::getMongoDatabase();
        $collection = $database->selectCollection('comments');

        $cursor = $collection->find(['isValidated' => true])->sort(['createdAt' => -1])->limit(3);

        $reviews = [];
        foreach ($cursor as $doc) {
            // Get user details from MariaDB
            $userRepository = new \App\Repository\UserRepository();
            $user = $userRepository->findById((int)$doc['userId']);

            $reviews[] = [
                'id' => (string)$doc['_id'], // MongoDB ID as string
                'user_id' => (string)$doc['userId'],
                'menu_id' => isset($doc['menuId']) ? (string)$doc['menuId'] : null,
                'rating' => (int)$doc['rating'],
                'comment' => $doc['comment'],
                'is_validated' => (bool)$doc['isValidated'],
                'created_at' => $doc['createdAt'] instanceof \MongoDB\BSON\UTCDateTime
                    ? \DateTimeImmutable::createFromMutable($doc['createdAt']->toDateTime())
                    : null,
                'updated_at' => $doc['updatedAt'] instanceof \MongoDB\BSON\UTCDateTime
                    ? \DateTimeImmutable::createFromMutable($doc['updatedAt']->toDateTime())
                    : null,
                'first_name' => $user !== null ? $user->getFirstName() : ($doc['authorName'] ?? ''),
                'last_name' => $user !== null ? $user->getLastName() : '',
            ];
        }

        return $reviews;
    }

    public function create(array $data): int
    {
        $database = Database::getMongoDatabase();
        $collection = $database->selectCollection('comments');

        $document = [
            'userId' => (int)$data['user_id'],
            'menuId' => $data['menu_id'] ?? null,
            'rating' => (int)$data['rating'],
            'comment' => $data['comment'],
            'isValidated' => (bool)($data['is_validated'] ?? false),
            'createdAt' => new \MongoDB\BSON\UTCDateTime(new \DateTimeImmutable()),
            'updatedAt' => new \MongoDB\BSON\UTCDateTime(new \DateTimeImmutable())
        ];

        $result = $collection->insertOne($document);
        return (int)$result->getInsertedCount();
    }

    public function updateValidation(string $id, bool $isValidated): void
    {
        $database = Database::getMongoDatabase();
        $collection = $database->selectCollection('comments');

        $filter = ['_id' => new \MongoDB\BSON\ObjectId((string)$id)];
        $update = [
            '$set' => [
                'isValidated' => $isValidated,
                'updatedAt' => new \MongoDB\BSON\UTCDateTime(new \DateTimeImmutable())
            ]
        ];

        $collection->updateOne($filter, $update);
    }
}
