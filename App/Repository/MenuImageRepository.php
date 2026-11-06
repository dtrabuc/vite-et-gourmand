<?php
declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use DateTimeImmutable;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;

/**
 * Galerie d'images des menus (collection MongoDB "menu_images").
 * Index unique { menuId, position } : les positions vont de 1 à n sans trou.
 */
final class MenuImageRepository
{
    /**
     * @return array<int, array{id: string, menu_id: int, position: int, url: string, alt_text: string, source: string}>
     */
    public function findByMenu(int $menuId): array
    {
        // une image sans URL ne peut pas s'afficher : on la saute
        return array_values(array_filter(
            $this->allOfMenu($menuId),
            static fn (array $image): bool => $image['url'] !== ''
        ));
    }

    /**
     * Toutes les images du menu, même sans URL : sert à la renumérotation,
     * pour que chaque document garde une position unique.
     */
    private function allOfMenu(int $menuId): array
    {
        $cursor = $this->collection()->find(
            ['menuId' => $menuId],
            ['sort' => ['position' => 1]]
        );

        $images = [];
        foreach ($cursor as $document) {
            $images[] = $this->toArray($document);
        }

        return $images;
    }

    /**
     * Première image (couverture) de plusieurs menus en une seule requête.
     *
     * @param int[] $menuIds
     * @return array<int, array> Indexé par menu ; les menus sans image sont absents.
     */
    public function findCovers(array $menuIds): array
    {
        $menuIds = array_values(array_unique(array_map('intval', $menuIds)));

        if ($menuIds === []) {
            return [];
        }

        $cursor = $this->collection()->find(
            ['menuId' => ['$in' => $menuIds]],
            ['sort' => ['menuId' => 1, 'position' => 1]]
        );

        $covers = [];
        foreach ($cursor as $document) {
            $image = $this->toArray($document);

            // la couverture est la première image qui a vraiment une URL
            if ($image['url'] !== '') {
                $covers[$image['menu_id']] ??= $image;
            }
        }

        return $covers;
    }

    public function findOne(int $menuId, string $imageId): ?array
    {
        $objectId = $this->objectId($imageId);

        if ($objectId === null) {
            return null;
        }

        $document = $this->collection()->findOne(['_id' => $objectId, 'menuId' => $menuId]);

        return $document === null ? null : $this->toArray($document);
    }

    public function add(int $menuId, string $url, string $altText, string $source): void
    {
        $last = $this->collection()->findOne(
            ['menuId' => $menuId],
            ['sort' => ['position' => -1]]
        );

        $this->collection()->insertOne([
            'menuId' => $menuId,
            'position' => $last === null ? 1 : ((int) $last['position']) + 1,
            'url' => $url,
            'altText' => $altText,
            'source' => $source,
            'updatedAt' => new UTCDateTime(new DateTimeImmutable()),
        ]);
    }

    public function delete(int $menuId, string $imageId): void
    {
        $objectId = $this->objectId($imageId);

        if ($objectId === null) {
            return;
        }

        $this->collection()->deleteOne(['_id' => $objectId, 'menuId' => $menuId]);
        $this->renumber($menuId, array_column($this->allOfMenu($menuId), 'id'));
    }

    /**
     * Échange la position d'une image avec sa voisine (-1 : vers le haut, +1 : vers le bas).
     */
    public function move(int $menuId, string $imageId, int $direction): void
    {
        $ids = array_column($this->allOfMenu($menuId), 'id');
        $index = array_search($imageId, $ids, true);

        if ($index === false) {
            return;
        }

        $target = $index + ($direction < 0 ? -1 : 1);

        if (!isset($ids[$target])) {
            return;
        }

        [$ids[$index], $ids[$target]] = [$ids[$target], $ids[$index]];
        $this->renumber($menuId, $ids);
    }

    /**
     * Réattribue les positions 1..n dans l'ordre donné. Passage par des positions
     * négatives pour ne jamais violer l'index unique { menuId, position }.
     *
     * @param string[] $orderedIds
     */
    private function renumber(int $menuId, array $orderedIds): void
    {
        foreach ([-1, 1] as $sign) {
            foreach (array_values($orderedIds) as $index => $id) {
                $objectId = $this->objectId($id);

                if ($objectId === null) {
                    continue;
                }

                $this->collection()->updateOne(
                    ['_id' => $objectId, 'menuId' => $menuId],
                    ['$set' => [
                        'position' => $sign * ($index + 1),
                        'updatedAt' => new UTCDateTime(new DateTimeImmutable()),
                    ]]
                );
            }
        }
    }

    private function toArray(object|array $document): array
    {
        return [
            'id' => (string) $document['_id'],
            'menu_id' => (int) $document['menuId'],
            'position' => (int) $document['position'],
            'url' => (string) ($document['url'] ?? ''),
            'alt_text' => (string) ($document['altText'] ?? 'Image du menu'),
            'source' => (string) ($document['source'] ?? ''),
        ];
    }

    private function objectId(string $id): ?ObjectId
    {
        if (!preg_match('/^[a-f0-9]{24}$/i', $id)) {
            return null;
        }

        return new ObjectId($id);
    }

    private function collection(): Collection
    {
        return Database::mongoDatabase()->selectCollection('menu_images');
    }
}
