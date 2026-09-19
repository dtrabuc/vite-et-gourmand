<?php
namespace App\Repository;

use App\Core\Database;

class OpeningHoursRepository
{
    public function findAll(): array
    {
        $stmt = Database::getPDO()->query(
            'SELECT day_of_week, is_open, opening_time, closing_time FROM opening_hours ORDER BY day_of_week'
        );
        return $stmt->fetchAll();
    }
}
