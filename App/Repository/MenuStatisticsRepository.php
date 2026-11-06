<?php
declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use DateTimeImmutable;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;

/**
 * Statistiques de commandes par menu (collection MongoDB "menu_statistics").
 */
final class MenuStatisticsRepository
{
    public function upsert(
        int $menuId,
        string $menuTitle,
        string $periodIdentifier,
        ?string $periodStart,
        ?string $periodEnd,
        int $orderCount,
        float $revenue
    ): void {
        $this->collection()->updateOne(
            ['menuId' => $menuId, 'periodIdentifier' => $periodIdentifier],
            ['$set' => [
                'menuId' => $menuId,
                'menuTitle' => $menuTitle,
                'periodStart' => $periodStart,
                'periodEnd' => $periodEnd,
                'periodIdentifier' => $periodIdentifier,
                'orderCount' => $orderCount,
                'revenue' => $revenue,
                'updatedAt' => new UTCDateTime(new DateTimeImmutable()),
            ]],
            ['upsert' => true]
        );
    }

    /**
     * @return array<int, array<string, mixed>> Triées par nombre de commandes décroissant.
     */
    public function findByPeriod(?string $periodIdentifier = null): array
    {
        $filter = $periodIdentifier !== null ? ['periodIdentifier' => $periodIdentifier] : [];
        $cursor = $this->collection()->find($filter, ['sort' => ['orderCount' => -1]]);

        $stats = [];
        foreach ($cursor as $document) {
            $stats[] = [
                'menuId' => (int) $document['menuId'],
                'menuTitle' => (string) ($document['menuTitle'] ?? ''),
                'periodStart' => $document['periodStart'] ?? null,
                'periodEnd' => $document['periodEnd'] ?? null,
                'periodIdentifier' => (string) ($document['periodIdentifier'] ?? ''),
                'orderCount' => (int) ($document['orderCount'] ?? 0),
                'revenue' => (float) ($document['revenue'] ?? 0),
                'updatedAt' => ($document['updatedAt'] ?? null) instanceof UTCDateTime
                    ? $document['updatedAt']->toDateTime()
                    : null,
            ];
        }

        return $stats;
    }

    private function collection(): Collection
    {
        return Database::mongoDatabase()->selectCollection('menu_statistics');
    }
}
