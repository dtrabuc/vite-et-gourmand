<?php
declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

/**
 * Plats (entrée, plat, dessert) et leurs allergènes (table dish_allergens).
 */
final class DishRepository
{
    /**
     * @return array<int, array<string, mixed>> Plats avec leurs allergènes (ids et noms).
     */
    public function findAll(bool $activeOnly = true): array
    {
        $sql = 'SELECT id, name, description, category, dietary_regime, is_active
                FROM dishes'
            . ($activeOnly ? ' WHERE is_active = 1' : '')
            . " ORDER BY FIELD(category, 'starter', 'main', 'dessert'), name";

        $rows = Database::pdo()->query($sql)->fetchAll();

        return $this->attachAllergens($rows);
    }

    public function findById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, name, description, category, dietary_regime, is_active
             FROM dishes WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return $this->attachAllergens([$row])[0];
    }

    /**
     * @param int[] $ids
     * @return array<int, array<string, mixed>> Plats actifs indexés par id.
     */
    public function findActiveByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));

        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Database::pdo()->prepare(
            "SELECT id, name, category FROM dishes WHERE is_active = 1 AND id IN ({$placeholders})"
        );
        $stmt->execute($ids);

        $dishes = [];
        foreach ($stmt->fetchAll() as $row) {
            $dishes[(int) $row['id']] = $row;
        }

        return $dishes;
    }

    public function findAllAllergens(): array
    {
        return Database::pdo()
            ->query('SELECT id, name, code FROM allergens ORDER BY name')
            ->fetchAll();
    }

    /**
     * @param int[] $ids
     * @return int[] Identifiants d'allergènes existants parmi ceux fournis.
     */
    public function existingAllergenIds(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));

        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Database::pdo()->prepare("SELECT id FROM allergens WHERE id IN ({$placeholders})");
        $stmt->execute($ids);

        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    /**
     * @param int[] $allergenIds
     */
    public function create(array $data, array $allergenIds): int
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO dishes (name, description, category, dietary_regime)
                 VALUES (:name, :description, :category, :dietary_regime)'
            );
            $stmt->execute([
                'name' => $data['name'],
                'description' => $data['description'],
                'category' => $data['category'],
                'dietary_regime' => $data['dietary_regime'],
            ]);
            $id = (int) $pdo->lastInsertId();

            $this->replaceAllergens($id, $allergenIds);
            $pdo->commit();

            return $id;
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * @param int[] $allergenIds
     */
    public function update(int $id, array $data, array $allergenIds): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'UPDATE dishes
                 SET name = :name, description = :description, category = :category,
                     dietary_regime = :dietary_regime, updated_at = NOW()
                 WHERE id = :id'
            );
            $stmt->execute([
                'id' => $id,
                'name' => $data['name'],
                'description' => $data['description'],
                'category' => $data['category'],
                'dietary_regime' => $data['dietary_regime'],
            ]);

            $this->replaceAllergens($id, $allergenIds);
            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * Suppression logique : le plat disparaît des menus affichés et de l'admin,
     * sans casser l'historique des compositions.
     */
    public function deactivate(int $id): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE dishes SET is_active = 0, updated_at = NOW() WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }

    /**
     * @return string[] Titres des menus actifs qui contiennent ce plat.
     */
    public function findMenuTitlesUsingDish(int $dishId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT m.title
             FROM menu_dishes md
             INNER JOIN menus m ON m.id = md.menu_id
             WHERE md.dish_id = :dish_id AND m.is_active = 1
             ORDER BY m.title'
        );
        $stmt->execute(['dish_id' => $dishId]);

        return array_map('strval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    /**
     * @param int[] $allergenIds
     */
    private function replaceAllergens(int $dishId, array $allergenIds): void
    {
        $pdo = Database::pdo();
        $pdo->prepare('DELETE FROM dish_allergens WHERE dish_id = :dish_id')
            ->execute(['dish_id' => $dishId]);

        $insert = $pdo->prepare(
            'INSERT INTO dish_allergens (dish_id, allergen_id) VALUES (:dish_id, :allergen_id)'
        );

        foreach (array_unique($allergenIds) as $allergenId) {
            $insert->execute(['dish_id' => $dishId, 'allergen_id' => (int) $allergenId]);
        }
    }

    private function attachAllergens(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $ids = array_map(static fn (array $row): int => (int) $row['id'], $rows);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Database::pdo()->prepare(
            "SELECT da.dish_id, a.id, a.name
             FROM dish_allergens da
             INNER JOIN allergens a ON a.id = da.allergen_id
             WHERE da.dish_id IN ({$placeholders})
             ORDER BY a.name"
        );
        $stmt->execute($ids);

        $byDish = [];
        foreach ($stmt->fetchAll() as $row) {
            $byDish[(int) $row['dish_id']][] = ['id' => (int) $row['id'], 'name' => (string) $row['name']];
        }

        return array_map(
            static function (array $row) use ($byDish): array {
                $allergens = $byDish[(int) $row['id']] ?? [];
                $row['id'] = (int) $row['id'];
                $row['is_active'] = (bool) $row['is_active'];
                $row['allergen_ids'] = array_column($allergens, 'id');
                $row['allergens'] = array_column($allergens, 'name');

                return $row;
            },
            $rows
        );
    }
}
