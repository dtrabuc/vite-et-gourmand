<?php
namespace App\Repository;

use App\Entity\Menu;
use App\Core\Database;

class MenuRepository
{
    public function findAll(): array
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->query('SELECT * FROM menus WHERE available_stock > 0');
        $rows = $stmt->fetchAll();

        $menus = [];
        foreach ($rows as $row) {
            $menu = new Menu();
            $menu->setId((int)$row['id']);
            $menu->setTitle($row['title']);
            $menu->setDescription($row['description']);
            $menu->setTheme($row['theme']);
            $menu->setMinPeople((int)$row['min_people']);
            $menu->setBasePrice((float)$row['base_price']);
            $menu->setConditions($row['conditions']);
            $menu->setAvailableStock((int)$row['available_stock']);
            $menu->setCreatedAt($row['created_at'] ? new \DateTimeImmutable($row['created_at']) : null);
            $menu->setUpdatedAt($row['updated_at'] ? new \DateTimeImmutable($row['updated_at']) : null);

            $menus[] = $menu;
        }

        return $menus;
    }

    public function findById(int $id): ?Menu
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('SELECT * FROM menus WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        $menu = new Menu();
        $menu->setId((int)$row['id']);
        $menu->setTitle($row['title']);
        $menu->setDescription($row['description']);
        $menu->setTheme($row['theme']);
        $menu->setMinPeople((int)$row['min_people']);
        $menu->setBasePrice((float)$row['base_price']);
        $menu->setConditions($row['conditions']);
        $menu->setAvailableStock((int)$row['available_stock']);
        $menu->setCreatedAt($row['created_at'] ? new \DateTimeImmutable($row['created_at']) : null);
        $menu->setUpdatedAt($row['updated_at'] ? new \DateTimeImmutable($row['updated_at']) : null);

        return $menu;
    }

    public function filter(array $filters): array
    {
        $pdo = Database::getPDO();

        $query = 'SELECT * FROM menus WHERE available_stock > 0';
        $params = [];

        if (!empty($filters['max_price'])) {
            $query .= ' AND base_price <= :max_price';
            $params[':max_price'] = $filters['max_price'];
        }

        if (!empty($filters['theme'])) {
            $query .= ' AND theme = :theme';
            $params[':theme'] = $filters['theme'];
        }

        if (!empty($filters['min_people'])) {
            $query .= ' AND min_people >= :min_people';
            $params[':min_people'] = $filters['min_people'];
        }

        // Add other filters as needed (dietary restrictions, etc.)

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $menus = [];
        foreach ($rows as $row) {
            $menu = new Menu();
            $menu->setId((int)$row['id']);
            $menu->setTitle($row['title']);
            $menu->setDescription($row['description']);
            $menu->setTheme($row['theme']);
            $menu->setMinPeople((int)$row['min_people']);
            $menu->setBasePrice((float)$row['base_price']);
            $menu->setConditions($row['conditions']);
            $menu->setAvailableStock((int)$row['available_stock']);
            $menu->setCreatedAt($row['created_at'] ? new \DateTimeImmutable($row['created_at']) : null);
            $menu->setUpdatedAt($row['updated_at'] ? new \DateTimeImmutable($row['updated_at']) : null);

            $menus[] = $menu;
        }

        return $menus;
    }

    public function create(Menu $menu): int
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('INSERT INTO menus (title, description, theme, min_people, base_price, conditions, available_stock)
                               VALUES (:title, :description, :theme, :min_people, :base_price, :conditions, :available_stock)');
        $stmt->execute([
            'title' => $menu->getTitle(),
            'description' => $menu->getDescription(),
            'theme' => $menu->getTheme(),
            'min_people' => $menu->getMinPeople(),
            'base_price' => $menu->getBasePrice(),
            'conditions' => $menu->getConditions(),
            'available_stock' => $menu->getAvailableStock(),
        ]);

        return (int)$pdo->lastInsertId();
    }

    public function update(Menu $menu): void
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('UPDATE menus SET
                               title = :title,
                               description = :description,
                               theme = :theme,
                               min_people = :min_people,
                               base_price = :base_price,
                               conditions = :conditions,
                               available_stock = :available_stock,
                               updated_at = NOW()
                               WHERE id = :id');
        $stmt->execute([
            'id' => $menu->getId(),
            'title' => $menu->getTitle(),
            'description' => $menu->getDescription(),
            'theme' => $menu->getTheme(),
            'min_people' => $menu->getMinPeople(),
            'base_price' => $menu->getBasePrice(),
            'conditions' => $menu->getConditions(),
            'available_stock' => $menu->getAvailableStock(),
        ]);
    }

    public function delete(int $id): void
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('DELETE FROM menus WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}