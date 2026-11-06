<?php
declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

final class QuoteRequestRepository
{
    public function create(array $data): int
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'INSERT INTO quote_requests (
                user_id, email, first_name, last_name, phone, company,
                event_date, number_of_people, service_type,
                event_location, postal_code, request_details
            ) VALUES (
                :user_id, :email, :first_name, :last_name, :phone, :company,
                :event_date, :number_of_people, :service_type,
                :event_location, :postal_code, :request_details
            )'
        );

        $stmt->execute([
            'user_id' => $data['user_id'],
            'email' => $data['email'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'],
            'company' => $data['company'],
            'event_date' => $data['event_date'],
            'number_of_people' => $data['number_of_people'],
            'service_type' => $data['service_type'],
            'event_location' => $data['event_location'],
            'postal_code' => $data['postal_code'],
            'request_details' => $data['request_details'],
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function findForStaff(?string $status = null): array
    {
        $sql = 'SELECT q.*,
                       CONCAT(COALESCE(q.first_name, \'\'), \' \',
                              COALESCE(q.last_name, \'\')) AS contact_name
                FROM quote_requests q
                WHERE 1 = 1';
        $params = [];

        if ($status !== null && $status !== '') {
            $sql .= ' AND q.status = :status';
            $params['status'] = $status;
        }

        $sql .= ' ORDER BY q.event_date ASC, q.created_at ASC';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM quote_requests WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $request = $stmt->fetch();

        return $request === false ? null : $request;
    }

    public function updateStatus(int $id, string $status, ?string $reply): void
    {
        Database::pdo()->prepare(
            'UPDATE quote_requests
             SET status = :status,
                 employee_reply = :reply,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        )->execute([
            'id' => $id,
            'status' => $status,
            'reply' => $reply,
        ]);
    }
}
