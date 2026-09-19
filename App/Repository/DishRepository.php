<?php
namespace App\Repository;

use App\Core\Database;

class DishRepository
{
    public function findAll(): array
    {
        $sql = "SELECT d.id, d.name, d.description,
                       GROUP_CONCAT(CONCAT(m.title, ' — ', md.category) ORDER BY m.title SEPARATOR ', ') AS menus
                FROM dishes d
                LEFT JOIN menu_dishes md ON md.dish_id = d.id
                LEFT JOIN menus m ON m.id = md.menu_id
                GROUP BY d.id, d.name, d.description
                ORDER BY d.name";
        return Database::getPDO()->query($sql)->fetchAll();
    }

    public function create(string $name, string $description, ?int $menuId, ?string $category): int
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('INSERT INTO dishes (name, description) VALUES (:name, :description)');
        $stmt->execute(['name'=>$name,'description'=>$description !== '' ? $description : null]);
        $id=(int)$pdo->lastInsertId();
        if ($menuId && in_array($category, ['starter','main','dessert'], true)) {
            $this->assign($menuId,$id,$category);
        }
        return $id;
    }

    public function update(int $id, string $name, string $description, ?int $menuId, ?string $category): void
    {
        $stmt=Database::getPDO()->prepare('UPDATE dishes SET name=:name, description=:description, updated_at=NOW() WHERE id=:id');
        $stmt->execute(['id'=>$id,'name'=>$name,'description'=>$description !== '' ? $description : null]);
        if ($menuId && in_array($category, ['starter','main','dessert'], true)) {
            $this->assign($menuId,$id,$category);
        }
    }

    public function delete(int $id): void
    {
        $stmt=Database::getPDO()->prepare('DELETE FROM dishes WHERE id=:id');
        $stmt->execute(['id'=>$id]);
    }

    private function assign(int $menuId,int $dishId,string $category): void
    {
        $stmt=Database::getPDO()->prepare(
            'INSERT INTO menu_dishes (menu_id,dish_id,category,position) VALUES (:menu,:dish,:category,1)
             ON DUPLICATE KEY UPDATE category=VALUES(category)'
        );
        $stmt->execute(['menu'=>$menuId,'dish'=>$dishId,'category'=>$category]);
    }
}
