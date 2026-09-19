<?php
namespace App\Repository;

use App\Entity\Order;
use App\Core\Database;

class OrderRepository
{
    public function create(array $data): int
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('INSERT INTO orders
            (user_id, menu_id, number_of_people, order_date, delivery_date, delivery_time,
             delivery_address, delivery_city, delivery_postal_code, delivery_distance_km,
             delivery_cost, menu_price, discount_rate, total_price, status)
            VALUES (:user_id, :menu_id, :number_of_people, :order_date, :delivery_date, :delivery_time,
                    :delivery_address, :delivery_city, :delivery_postal_code, :delivery_distance_km,
                    :delivery_cost, :menu_price, :discount_rate, :total_price, :status)');
        $stmt->execute([
            'user_id' => $data['user_id'],
            'menu_id' => $data['menu_id'],
            'number_of_people' => $data['number_of_people'],
            'order_date' => $data['order_date'],
            'delivery_date' => $data['delivery_date'],
            'delivery_time' => $data['delivery_time'],
            'delivery_address' => $data['delivery_address'],
            'delivery_city' => $data['delivery_city'],
            'delivery_postal_code' => $data['delivery_postal_code'] ?? null,
            'delivery_distance_km' => $data['delivery_distance_km'] ?? null,
            'delivery_cost' => $data['delivery_cost'],
            'menu_price' => $data['menu_price'],
            'discount_rate' => $data['discount_rate'] ?? 0,
            'total_price' => $data['total_price'],
            'status' => $data['status'],
        ]);
        return (int) $pdo->lastInsertId();
    }

    public function findByUserId(int $userId): array
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC');
        $stmt->execute(['user_id' => $userId]);
        return $this->hydrateMany($stmt->fetchAll());
    }

    public function findById(int $id): ?Order
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $this->hydrate($row);
    }

    public function updateStatus(int $id, string $status, ?string $cancellationReason = null): void
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('UPDATE orders SET status = :status, cancellation_reason = :reason, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id, 'status' => $status, 'reason' => $cancellationReason]);
    }

    public function getHistory(int $orderId): array
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('SELECT * FROM order_status_history WHERE order_id = :order_id ORDER BY changed_at ASC');
        $stmt->execute(['order_id' => $orderId]);
        $history = [];
        foreach ($stmt->fetchAll() as $row) {
            $history[] = [
                'id' => (int) $row['id'],
                'order_id' => (int) $row['order_id'],
                'status' => $row['status'],
                'changed_by' => $row['changed_by'] !== null ? (int) $row['changed_by'] : null,
                'changed_at' => $row['changed_at'] ? new \DateTimeImmutable($row['changed_at']) : null,
                'notes' => $row['notes'],
            ];
        }
        return $history;
    }

    public function addToHistory(int $orderId, string $status, ?int $changedBy, string $notes = ''): void
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('INSERT INTO order_status_history (order_id, status, changed_by, changed_at, notes)
                               VALUES (:order_id, :status, :changed_by, NOW(), :notes)');
        $stmt->execute([
            'order_id' => $orderId,
            'status' => $status,
            'changed_by' => $changedBy,
            'notes' => $notes,
        ]);
    }

    public function decreaseMenuStock(int $menuId): void
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('UPDATE menus SET available_stock = available_stock - 1
                               WHERE id = :id AND is_active = 1 AND available_stock > 0');
        $stmt->execute(['id' => $menuId]);
        if ($stmt->rowCount() !== 1) {
            throw new \RuntimeException('Le stock du menu n’est plus disponible.');
        }
    }

    private function hydrateMany(array $rows): array
    {
        return array_map(fn(array $row): Order => $this->hydrate($row), $rows);
    }

    private function hydrate(array $row): Order
    {
        $order = new Order();
        $order->setId((int) $row['id']);
        $order->setUserId((int) $row['user_id']);
        $order->setMenuId((int) $row['menu_id']);
        $order->setNumberOfPeople((int) $row['number_of_people']);
        $order->setOrderDate($row['order_date']);
        $order->setDeliveryDate($row['delivery_date']);
        $order->setDeliveryTime($row['delivery_time']);
        $order->setDeliveryAddress($row['delivery_address']);
        $order->setDeliveryCity((string) ($row['delivery_city'] ?? ''));
        $order->setDeliveryPostalCode((string) ($row['delivery_postal_code'] ?? ''));
        $order->setDeliveryDistanceKm($row['delivery_distance_km'] !== null ? (float) $row['delivery_distance_km'] : null);
        $order->setDeliveryCost((float) $row['delivery_cost']);
        $order->setMenuPrice((float) $row['menu_price']);
        $order->setDiscountRate((float) ($row['discount_rate'] ?? 0));
        $order->setTotalPrice((float) $row['total_price']);
        $order->setStatus($row['status']);
        $order->setCancellationReason($row['cancellation_reason'] ?? null);
        $order->setCreatedAt(!empty($row['created_at']) ? new \DateTimeImmutable($row['created_at']) : null);
        $order->setUpdatedAt(!empty($row['updated_at']) ? new \DateTimeImmutable($row['updated_at']) : null);
        return $order;
    }
}
