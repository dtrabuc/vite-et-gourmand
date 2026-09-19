<?php
namespace App\Service;

use App\Core\Database;

class ContactService
{
    public function createMessage(string $email, string $subject, string $message): int
    {
        $stmt = Database::getPDO()->prepare(
            'INSERT INTO contact_messages (email, subject, message) VALUES (:email, :subject, :message)'
        );
        $stmt->execute([
            'email' => $email,
            'subject' => $subject,
            'message' => $message,
        ]);
        return (int) Database::getPDO()->lastInsertId();
    }
}
