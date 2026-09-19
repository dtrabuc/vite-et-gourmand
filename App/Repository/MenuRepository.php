<?php
namespace App\Repository;

use App\Entity\Menu;
use App\Core\Database;

class MenuRepository
{
    public function findAll(): array
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->query('SELECT * FROM menus WHERE is_active = 1 AND available_stock > 0 ORDER BY id DESC');
        return $this->hydrateMany($stmt->fetchAll());
    }

    public function findById(int $id): ?Menu
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('SELECT * FROM menus WHERE id = :id AND is_active = 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $this->hydrate($row);
    }

    public function filter(array $filters): array
    {
        $pdo = Database::getPDO();
        $query = 'SELECT * FROM menus WHERE is_active = 1 AND available_stock > 0';
        $params = [];

        if (isset($filters['max_price']) && $filters['max_price'] !== '') {
            $query .= ' AND base_price <= :max_price';
            $params['max_price'] = (float) $filters['max_price'];
        }
        if (isset($filters['min_price']) && $filters['min_price'] !== '') {
            $query .= ' AND base_price >= :min_price';
            $params['min_price'] = (float) $filters['min_price'];
        }
        if (!empty($filters['theme'])) {
            $query .= ' AND theme = :theme';
            $params['theme'] = $filters['theme'];
        }
        if (!empty($filters['dietary_regime'])) {
            $query .= ' AND dietary_regime = :dietary_regime';
            $params['dietary_regime'] = $filters['dietary_regime'];
        }
        if (isset($filters['min_people']) && $filters['min_people'] !== '') {
            $query .= ' AND min_people <= :min_people';
            $params['min_people'] = (int) $filters['min_people'];
        }

        $query .= ' ORDER BY id DESC';
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $this->hydrateMany($stmt->fetchAll());
    }

    public function findDetails(int $menuId): array
    {
        $pdo = Database::getPDO();

        $imagesStmt = $pdo->prepare('SELECT path, alt_text, position FROM menu_images WHERE menu_id = :menu_id ORDER BY position ASC');
        $imagesStmt->execute(['menu_id' => $menuId]);

        $dishesStmt = $pdo->prepare('SELECT d.id, d.name, d.description, md.category, md.position
            FROM menu_dishes md
            INNER JOIN dishes d ON d.id = md.dish_id
            WHERE md.menu_id = :menu_id
            ORDER BY FIELD(md.category, "starter", "main", "dessert"), md.position ASC');
        $dishesStmt->execute(['menu_id' => $menuId]);

        $allergensStmt = $pdo->prepare('SELECT DISTINCT a.name
            FROM dish_allergens da
            INNER JOIN allergens a ON a.id = da.allergen_id
            INNER JOIN menu_dishes md ON md.dish_id = da.dish_id
            WHERE md.menu_id = :menu_id
            ORDER BY a.name');
        $allergensStmt->execute(['menu_id' => $menuId]);

        return [
            'images' => $imagesStmt->fetchAll(),
            'dishes' => $dishesStmt->fetchAll(),
            'allergens' => array_column($allergensStmt->fetchAll(), 'name'),
        ];
    }

    public function create(Menu $menu): int
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('INSERT INTO menus
            (title, description, theme, dietary_regime, min_people, base_price, conditions, available_stock)
            VALUES (:title, :description, :theme, :dietary_regime, :min_people, :base_price, :conditions, :available_stock)');
        $stmt->execute([
            'title' => $menu->getTitle(),
            'description' => $menu->getDescription(),
            'theme' => $menu->getTheme(),
            'dietary_regime' => $menu->getDietaryRegime(),
            'min_people' => $menu->getMinPeople(),
            'base_price' => $menu->getBasePrice(),
            'conditions' => $menu->getConditions(),
            'available_stock' => $menu->getAvailableStock(),
        ]);
        return (int) $pdo->lastInsertId();
    }

    public function update(Menu $menu): void
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('UPDATE menus SET
            title = :title, description = :description, theme = :theme,
            dietary_regime = :dietary_regime, min_people = :min_people,
            base_price = :base_price, conditions = :conditions,
            available_stock = :available_stock, updated_at = NOW()
            WHERE id = :id');
        $stmt->execute([
            'id' => $menu->getId(),
            'title' => $menu->getTitle(),
            'description' => $menu->getDescription(),
            'theme' => $menu->getTheme(),
            'dietary_regime' => $menu->getDietaryRegime(),
            'min_people' => $menu->getMinPeople(),
            'base_price' => $menu->getBasePrice(),
            'conditions' => $menu->getConditions(),
            'available_stock' => $menu->getAvailableStock(),
        ]);
    }

    public function delete(int $id): void
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('UPDATE menus SET is_active = 0, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private function hydrateMany(array $rows): array
    {
        return array_map(fn(array $row): Menu => $this->hydrate($row), $rows);
    }

    private function hydrate(array $row): Menu
    {
        $menu = new Menu();
        $menu->setId((int) $row['id']);
        $menu->setTitle($row['title']);
        $menu->setDescription($row['description']);
        $menu->setTheme($row['theme']);
        $menu->setDietaryRegime($row['dietary_regime'] ?? 'classic');
        $menu->setMinPeople((int) $row['min_people']);
        $menu->setBasePrice((float) $row['base_price']);
        $menu->setConditions($row['conditions']);
        $menu->setAvailableStock((int) $row['available_stock']);
        $menu->setCreatedAt(!empty($row['created_at']) ? new \DateTimeImmutable($row['created_at']) : null);
        $menu->setUpdatedAt(!empty($row['updated_at']) ? new \DateTimeImmutable($row['updated_at']) : null);
        return $menu;
    }
}
