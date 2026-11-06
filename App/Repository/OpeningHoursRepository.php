<?php
declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

final class OpeningHoursRepository
{
    public function saveDay(
        int $day,
        bool $isOpen,
        ?string $opening,
        ?string $closing,
        ?string $opening2 = null,
        ?string $closing2 = null
    ): void {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO opening_hours
                (day_of_week, is_open, opening_time, closing_time, opening_time_2, closing_time_2)
             VALUES (:day, :open, :opening, :closing, :opening2, :closing2)
             ON DUPLICATE KEY UPDATE
                is_open = VALUES(is_open),
                opening_time = VALUES(opening_time),
                closing_time = VALUES(closing_time),
                opening_time_2 = VALUES(opening_time_2),
                closing_time_2 = VALUES(closing_time_2)'
        );
        $stmt->execute([
            'day' => $day,
            'open' => $isOpen ? 1 : 0,
            'opening' => $opening,
            'closing' => $closing,
            'opening2' => $opening2,
            'closing2' => $closing2,
        ]);
    }

    public function findAll(): array
    {
        $stmt = Database::pdo()->query(
            'SELECT day_of_week, is_open, opening_time, closing_time, opening_time_2, closing_time_2
             FROM opening_hours
             ORDER BY day_of_week'
        );

        return $stmt->fetchAll();
    }

    public function findByDay(int $dayOfWeek): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT day_of_week, is_open, opening_time, closing_time, opening_time_2, closing_time_2
             FROM opening_hours
             WHERE day_of_week = :day'
        );
        $stmt->execute(['day' => $dayOfWeek]);

        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * @return array<int, array{0:string,1:string}>
     */
    public function getWindowsByDay(int $dayOfWeek): array
    {
        $row = $this->findByDay($dayOfWeek);

        if ($row === null || (int) $row['is_open'] !== 1) {
            return [];
        }

        $windows = [];
        $opening1 = trim((string) ($row['opening_time'] ?? ''));
        $closing1 = trim((string) ($row['closing_time'] ?? ''));
        $opening2 = trim((string) ($row['opening_time_2'] ?? ''));
        $closing2 = trim((string) ($row['closing_time_2'] ?? ''));

        if ($opening1 !== '' && $closing1 !== '') {
            $windows[] = [$opening1, $closing1];
        }

        if ($opening2 !== '' && $closing2 !== '') {
            $windows[] = [$opening2, $closing2];
        }

        return $windows;
    }
}
