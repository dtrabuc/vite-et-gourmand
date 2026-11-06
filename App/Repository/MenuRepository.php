<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\Menu;
use App\Core\Database;
use App\Core\Labels;

class MenuRepository
{
    public function findAll(): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->query('SELECT * FROM menus WHERE is_active = 1 AND available_stock > 0 ORDER BY id DESC');
        return $this->hydrateMany($stmt->fetchAll());
    }

    public function findById(int $id): ?Menu
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT * FROM menus WHERE id = :id AND is_active = 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $this->hydrate($row);
    }

    public function findAllForOrderSelection(): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->query(
            'SELECT * FROM menus
             WHERE is_active = 1
             ORDER BY id DESC'
        );

        return $this->hydrateMany($stmt->fetchAll());
    }

    public function filter(array $filters): array
    {
        $query = 'SELECT * FROM menus WHERE is_active = 1 AND available_stock > 0';
        $params = [];

        if (isset($filters['max_price']) && $filters['max_price'] !== '') {
            $maxPrice = filter_var($filters['max_price'], FILTER_VALIDATE_FLOAT);
            if ($maxPrice === false || $maxPrice < 0) {
                throw new \InvalidArgumentException('Le prix maximum est invalide.');
            }
            $query .= ' AND base_price <= :max_price';
            $params['max_price'] = $maxPrice;
        }

        if (isset($filters['min_price']) && $filters['min_price'] !== '') {
            $minPrice = filter_var($filters['min_price'], FILTER_VALIDATE_FLOAT);
            if ($minPrice === false || $minPrice < 0) {
                throw new \InvalidArgumentException('Le prix minimum est invalide.');
            }
            $query .= ' AND base_price >= :min_price';
            $params['min_price'] = $minPrice;
        }

        if (!empty($filters['theme'])) {
            $query .= ' AND theme = :theme';
            $params['theme'] = trim((string) $filters['theme']);
        }

        if (!empty($filters['dietary_regime'])) {
            $regime = trim((string) $filters['dietary_regime']);
            if (!array_key_exists($regime, Labels::DIETARY_REGIME)) {
                throw new \InvalidArgumentException('Le régime alimentaire est invalide.');
            }
            $query .= ' AND dietary_regime = :dietary_regime';
            $params['dietary_regime'] = $regime;
        }

        if (isset($filters['min_people']) && $filters['min_people'] !== '') {
            $minPeople = filter_var($filters['min_people'], FILTER_VALIDATE_INT);
            if ($minPeople === false || $minPeople < 1) {
                throw new \InvalidArgumentException('Le nombre de personnes est invalide.');
            }
            $query .= ' AND min_people <= :min_people';
            $params['min_people'] = $minPeople;
        }

        if (isset($params['min_price'], $params['max_price']) && $params['min_price'] > $params['max_price']) {
            throw new \InvalidArgumentException('La fourchette de prix est invalide.');
        }

        $query .= ' ORDER BY id DESC';
        // Connexion ouverte seulement une fois tous les filtres validés.
        $stmt = Database::pdo()->prepare($query);
        $stmt->execute($params);
        return $this->hydrateMany($stmt->fetchAll());
    }

    public function findDetails(int $menuId): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'SELECT
                d.id,
                d.name,
                d.description,
                d.category,
                d.dietary_regime,
                md.position
             FROM menu_dishes md
             INNER JOIN dishes d ON d.id = md.dish_id
             WHERE md.menu_id = :menu_id
               AND d.is_active = 1
             ORDER BY md.position, d.id'
        );
        $stmt->execute(['menu_id' => $menuId]);
        $rows = $stmt->fetchAll();

        // Allergènes de chaque plat actif du menu.
        $allergenStmt = $pdo->prepare(
            'SELECT da.dish_id, a.name
             FROM menu_dishes md
             INNER JOIN dishes d ON d.id = md.dish_id AND d.is_active = 1
             INNER JOIN dish_allergens da ON da.dish_id = md.dish_id
             INNER JOIN allergens a ON a.id = da.allergen_id
             WHERE md.menu_id = :menu_id
             ORDER BY a.name'
        );
        $allergenStmt->execute(['menu_id' => $menuId]);

        $byDish = [];
        foreach ($allergenStmt->fetchAll() as $row) {
            $byDish[(int) $row['dish_id']][] = (string) $row['name'];
        }

        foreach ($rows as &$row) {
            $row['allergens'] = $byDish[(int) $row['id']] ?? [];
        }
        unset($row);

        $allAllergens = array_values(array_unique(array_merge([], ...array_values($byDish))));
        sort($allAllergens);

        return [
            'dishes' => $rows,
            'allergens' => $allAllergens,
        ];
    }

    /**
     * Plats actifs de plusieurs menus en une seule requête (listes de menus).
     *
     * @param int[] $menuIds
     * @return array<int, array<int, array{name: string, category: string}>> Indexé par menu.
     */
    public function findDishesForMenus(array $menuIds): array
    {
        $menuIds = array_values(array_unique(array_map('intval', $menuIds)));

        if ($menuIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($menuIds), '?'));
        $stmt = Database::pdo()->prepare(
            "SELECT md.menu_id, d.name, d.category
             FROM menu_dishes md
             INNER JOIN dishes d ON d.id = md.dish_id
             WHERE md.menu_id IN ({$placeholders}) AND d.is_active = 1
             ORDER BY md.menu_id, md.position, d.id"
        );
        $stmt->execute($menuIds);

        $dishes = array_fill_keys($menuIds, []);
        foreach ($stmt->fetchAll() as $row) {
            $dishes[(int) $row['menu_id']][] = [
                'name' => (string) $row['name'],
                'category' => (string) $row['category'],
            ];
        }

        return $dishes;
    }

    /**
     * @return int[] Identifiants des plats composant le menu, dans l'ordre.
     */
    public function findDishIds(int $menuId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT dish_id FROM menu_dishes WHERE menu_id = :menu_id ORDER BY position, dish_id'
        );
        $stmt->execute(['menu_id' => $menuId]);

        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    /**
     * Remplace la composition du menu. Les positions suivent l'ordre du tableau.
     *
     * @param int[] $dishIds
     */
    public function replaceDishes(int $menuId, array $dishIds): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            $pdo->prepare('DELETE FROM menu_dishes WHERE menu_id = :menu_id')
                ->execute(['menu_id' => $menuId]);

            $insert = $pdo->prepare(
                'INSERT INTO menu_dishes (menu_id, dish_id, position) VALUES (:menu_id, :dish_id, :position)'
            );

            foreach (array_values($dishIds) as $index => $dishId) {
                $insert->execute([
                    'menu_id' => $menuId,
                    'dish_id' => $dishId,
                    'position' => $index + 1,
                ]);
            }

            $pdo->prepare('UPDATE menus SET updated_at = NOW() WHERE id = :id')
                ->execute(['id' => $menuId]);

            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function create(Menu $menu): int
    {
        $pdo = Database::pdo();
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
        $pdo = Database::pdo();
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
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('UPDATE menus SET is_active = 0, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private function hydrateMany(array $rows): array
    {
        return array_map($this->hydrate(...), $rows);
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
