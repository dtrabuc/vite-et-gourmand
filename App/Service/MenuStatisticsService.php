<?php
declare(strict_types=1);

namespace App\Service;

use App\Repository\MenuStatisticsRepository;
use App\Repository\OrderRepository;

/**
 * Agrège les commandes terminées (MariaDB) et les stocke dans MongoDB pour le
 * graphique du tableau de bord.
 */
final readonly class MenuStatisticsService
{
    public function __construct(
        private OrderRepository $orderRepository,
        private MenuStatisticsRepository $menuStatisticsRepository
    ) {
    }

    public function aggregateAndStore(?string $periodStart = null, ?string $periodEnd = null): void
    {
        // Même critère que le chiffre d'affaires SQL : commandes terminées uniquement.
        $rows = $this->orderRepository->completedTotalsByMenu($periodStart, $periodEnd);

        $periodIdentifier = match (true) {
            $periodStart !== null && $periodEnd !== null => $periodStart . '_to_' . $periodEnd,
            $periodStart !== null => 'from_' . $periodStart,
            $periodEnd !== null => 'to_' . $periodEnd,
            default => 'all_time',
        };

        try {
            foreach ($rows as $row) {
                $this->menuStatisticsRepository->upsert(
                    (int) $row['menu_id'],
                    (string) $row['menu_title'],
                    $periodIdentifier,
                    $periodStart,
                    $periodEnd,
                    (int) $row['order_count'],
                    (float) $row['revenue']
                );
            }
        } catch (\Throwable $exception) {
            error_log('Statistiques MongoDB indisponibles : ' . $exception->getMessage());
        }
    }

    public function getStatistics(?string $periodIdentifier = null): array
    {
        return $this->menuStatisticsRepository->findByPeriod($periodIdentifier);
    }
}
