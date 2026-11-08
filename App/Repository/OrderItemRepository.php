<?php
declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

/** Choix du client pour une commande (table order_items). */
final class OrderItemRepository
{
    /**
     * Remplace les choix d'une commande. À appeler dans la transaction de la commande.
     *
     * @param list<array{slot_dish_id: int, dish_id: int, category: string, quantity: int, without_allergens: list<int>}> $items
     */
    public function replaceForOrder(int $orderId, array $items): void
    {
        $pdo = Database::pdo();
        $pdo->prepare('DELETE FROM order_items WHERE order_id = :order_id')->execute(['order_id' => $orderId]);

        $insert = $pdo->prepare(
            'INSERT INTO order_items (order_id, slot_dish_id, dish_id, category, quantity, without_allergens)
             VALUES (:order_id, :slot, :dish, :category, :quantity, :without)'
        );
        foreach ($items as $item) {
            $insert->execute([
                'order_id' => $orderId,
                'slot' => $item['slot_dish_id'],
                'dish' => $item['dish_id'],
                'category' => $item['category'],
                'quantity' => $item['quantity'],
                'without' => implode(',', $item['without_allergens']),
            ]);
        }
    }

    /**
     * Choix de plusieurs commandes, avec le nom des plats et des allergènes retirés.
     *
     * @param int[] $orderIds
     * @return array<int, list<array{slot_dish_id: int, dish_id: int, dish_name: string, category: string, quantity: int, without_allergens: list<int>, without_names: list<string>}>>
     */
    public function findForOrders(array $orderIds): array
    {
        $orderIds = array_values(array_unique(array_map('intval', $orderIds)));
        if ($orderIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $stmt = Database::pdo()->prepare(
            "SELECT oi.order_id, oi.slot_dish_id, oi.dish_id, d.name AS dish_name, oi.category, oi.quantity, oi.without_allergens
             FROM order_items oi
             INNER JOIN dishes d ON d.id = oi.dish_id
             WHERE oi.order_id IN ({$placeholders})
             ORDER BY oi.order_id, FIELD(oi.category, 'aperitif', 'starter', 'main', 'side', 'dessert', 'digestif'), oi.id"
        );
        $stmt->execute($orderIds);
        $rows = $stmt->fetchAll();

        $allergenNames = [];
        foreach (Database::pdo()->query('SELECT id, name FROM allergens') as $allergen) {
            $allergenNames[(int) $allergen['id']] = (string) $allergen['name'];
        }

        $result = [];
        foreach ($rows as $row) {
            $without = array_values(array_filter(array_map('intval', explode(',', (string) $row['without_allergens']))));
            $result[(int) $row['order_id']][] = [
                'slot_dish_id' => (int) $row['slot_dish_id'],
                'dish_id' => (int) $row['dish_id'],
                'dish_name' => (string) $row['dish_name'],
                'category' => (string) $row['category'],
                'quantity' => (int) $row['quantity'],
                'without_allergens' => $without,
                'without_names' => array_values(array_map(static fn (int $id): string => $allergenNames[$id] ?? '?', $without)),
            ];
        }

        return $result;
    }
}
