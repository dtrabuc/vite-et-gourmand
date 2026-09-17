<?php
namespace App\Repository;

use App\Entity\Order;
use App\Core\Database;

class OrderRepository
{
    public function create(array $data): int
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('INSERT INTO orders (user_id, menu_id, number_of_people, order_date, delivery_date, delivery_time, delivery_address, delivery_cost, menu_price, total_price, status)
                               VALUES (:user_id, :menu_id, :number_of_people, :order_date, :delivery_date, :delivery_time, :delivery_address, :delivery_cost, :menu_price, :total_price, :status)');
        $stmt->execute([
            'user_id' => $data['user_id'],
            'menu_id' => $data['menu_id'],
            'number_of_people' => $data['number_of_people'],
            'order_date' => $data['order_date'],
            'delivery_date' => $data['delivery_date'],
            'delivery_time' => $data['delivery_time'],
            'delivery_address' => $data['delivery_address'],
            'delivery_cost' => $data['delivery_cost'],
            'menu_price' => $data['menu_price'],
            'total_price' => $data['total_price'],
            'status' => $data['status'],
        ]);

        return (int)$pdo->lastInsertId();
    }

    public function findByUserId(int $userId): array
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC');
        $stmt->execute(['user_id' => $userId]);
        $rows = $stmt->fetchAll();

        $orders = [];
        foreach ($rows as $row) {
            $order = new Order();
            $order->setId((int)$row['id']);
            $order->setUserId((int)$row['user_id']);
            $order->setMenuId((int)$row['menu_id']);
            $order->setNumberOfPeople((int)$row['number_of_people']);
            $order->setOrderDate($row['order_date']);
            $order->setDeliveryDate($row['delivery_date']);
            $order->setDeliveryTime($row['delivery_time']);
            $order->setDeliveryAddress($row['delivery_address']);
            $order->setDeliveryCost((float)$row['delivery_cost']);
            $order->setMenuPrice((float)$row['menu_price']);
            $order->setTotalPrice((float)$row['total_price']);
            $order->setStatus($row['status']);
            $order->setCreatedAt($row['created_at'] ? new \DateTimeImmutable($row['created_at']) : null);
            $order->setUpdatedAt($row['updated_at'] ? new \DateTimeImmutable($row['updated_at']) : null);

            $orders[] = $order;
        }

        return $orders;
    }

    public function findById(int $id): ?Order
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        $order = new Order();
        $order->setId((int)$row['id']);
        $order->setUserId((int)$row['user_id']);
        $order->setMenuId((int)$row['menu_id']);
        $order->setNumberOfPeople((int)$row['number_of_people']);
        $order->setOrderDate($row['order_date']);
        $order->setDeliveryDate($row['delivery_date']);
        $order->setDeliveryTime($row['delivery_time']);
        $order->setDeliveryAddress($row['delivery_address']);
        $order->setDeliveryCost((float)$row['delivery_cost']);
        $order->setMenuPrice((float)$row['menu_price']);
        $order->setTotalPrice((float)$row['total_price']);
        $order->setStatus($row['status']);
        $order->setCreatedAt($row['created_at'] ? new \DateTimeImmutable($row['created_at']) : null);
        $order->setUpdatedAt($row['updated_at'] ? new \DateTimeImmutable($row['updated_at']) : null);

        return $order;
    }

    public function updateStatus(int $id, string $status): void
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('UPDATE orders SET status = :status, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'status' => $status,
        ]);
    }

    public function getHistory(int $orderId): array
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('SELECT * FROM order_status_history WHERE order_id = :order_id ORDER BY changed_at ASC');
        $stmt->execute(['order_id' => $orderId]);
        $rows = $stmt->fetchAll();

        $history = [];
        foreach ($rows as $row) {
            $history[] = [
                'id' => (int)$row['id'],
                'order_id' => (int)$row['order_id'],
                'status' => $row['status'],
                'changed_by' => $row['changed_by'] !== null ? (int)$row['changed_by'] : null,
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
        $stmt = $pdo->prepare('UPDATE menus SET available_stock = available_stock - 1 WHERE id = :id AND available_stock > 0');
        $stmt->execute(['id' => $menuId]);
    }
}