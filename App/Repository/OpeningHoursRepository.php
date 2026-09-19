<?php
namespace App\Repository;

use App\Core\Database;

class OpeningHoursRepository
{
    public function saveDay(int $day, bool $isOpen, ?string $opening, ?string $closing): void
    {
        $stmt = Database::getPDO()->prepare(
            'INSERT INTO opening_hours (day_of_week, is_open, opening_time, closing_time)
             VALUES (:day, :open, :opening, :closing)
             ON DUPLICATE KEY UPDATE is_open = VALUES(is_open), opening_time = VALUES(opening_time), closing_time = VALUES(closing_time)'
        );
        $stmt->execute(['day'=>$day,'open'=>$isOpen?1:0,'opening'=>$opening,'closing'=>$closing]);
    }

    public function findAll(): array
    {
        $stmt = Database::getPDO()->query(
            'SELECT day_of_week, is_open, opening_time, closing_time FROM opening_hours ORDER BY day_of_week'
        );
        return $stmt->fetchAll();
    }
}
