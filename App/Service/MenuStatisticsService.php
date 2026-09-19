<?php
namespace App\Service;

use App\Repository\OrderRepository;
use App\Repository\MenuRepository;
use App\Core\Database;

class MenuStatisticsService
{
    private OrderRepository $orderRepository;
    private MenuRepository $menuRepository;

    public function __construct(OrderRepository $orderRepository, MenuRepository $menuRepository)
    {
        $this->orderRepository = $orderRepository;
        $this->menuRepository = $menuRepository;
    }

    public function aggregateAndStore(?string $periodStart = null, ?string $periodEnd = null): void
    {
        $pdo = Database::getPDO();

        $query = '
            SELECT o.menu_id, COUNT(o.id) as order_count, SUM(o.total_price) as revenue
            FROM orders o
            WHERE o.status IN (\'completed\', \'delivered\')
        ';
        $params = [];

        if ($periodStart !== null) {
            $query .= ' AND DATE(o.delivery_date) >= :period_start';
            $params[':period_start'] = $periodStart;
        }
        if ($periodEnd !== null) {
            $query .= ' AND DATE(o.delivery_date) <= :period_end';
            $params[':period_end'] = $periodEnd;
        }

        $query .= ' GROUP BY o.menu_id';

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $database = Database::getMongoDatabase();
        $collection = $database->selectCollection('menu_statistics');

        $periodIdentifier = '';
        if ($periodStart !== null && $periodEnd !== null) {
            $periodIdentifier = $periodStart . '_to_' . $periodEnd;
        } elseif ($periodStart !== null) {
            $periodIdentifier = 'from_' . $periodStart;
        } elseif ($periodEnd !== null) {
            $periodIdentifier = 'to_' . $periodEnd;
        } else {
            $periodIdentifier = 'all_time';
        }

        foreach ($rows as $row) {
            $menuId = (int)$row['menu_id'];
            $orderCount = (int)$row['order_count'];
            $revenue = (float)$row['revenue'];

            $menu = $this->menuRepository->findById($menuId);
            $menuTitle = $menu !== null ? $menu->getTitle() : 'Menu inconnu';

            $document = [
                'menuId' => $menuId,
                'menuTitle' => $menuTitle,
                'periodStart' => $periodStart ?? null,
                'periodEnd' => $periodEnd ?? null,
                'periodIdentifier' => $periodIdentifier,
                'orderCount' => $orderCount,
                'revenue' => $revenue,
                'updatedAt' => new \MongoDB\BSON\UTCDateTime(new \DateTimeImmutable()),
            ];

            $collection->updateOne(
                ['menuId' => $menuId, 'periodIdentifier' => $periodIdentifier],
                ['$set' => $document],
                ['upsert' => true]
            );
        }
    }

    public function getStatistics(?string $periodIdentifier = null): array
    {
        $database = Database::getMongoDatabase();
        $collection = $database->selectCollection('menu_statistics');

        $filter = [];
        if ($periodIdentifier !== null) {
            $filter['periodIdentifier'] = $periodIdentifier;
        }

        $cursor = $collection->find($filter)->sort(['orderCount' => -1]);

        $stats = [];
        foreach ($cursor as $doc) {
            $stats[] = [
                'menuId' => (string)$doc['menuId'],
                'menuTitle' => $doc['menuTitle'] ?? '',
                'periodStart' => $doc['periodStart'] ?? null,
                'periodEnd' => $doc['periodEnd'] ?? null,
                'periodIdentifier' => $doc['periodIdentifier'] ?? '',
                'orderCount' => (int)$doc['orderCount'],
                'revenue' => (float)$doc['revenue'],
                'updatedAt' => $doc['updatedAt'] instanceof \MongoDB\BSON\UTCDateTime
                    ? $doc['updatedAt']->toDateTime()
                    : null,
            ];
        }

        return $stats;
    }
}