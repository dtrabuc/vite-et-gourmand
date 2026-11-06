<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\Exception\FileException;
use App\Core\Labels;
use App\Core\UploadFile;
use App\Entity\Menu;
use App\Repository\DishRepository;
use App\Repository\MenuImageRepository;
use App\Repository\MenuRepository;
use InvalidArgumentException;

final readonly class MenuService
{
    /** Thèmes de menu prévus par le cahier des charges (liste fermée). */
    public const THEMES = ['Noël', 'Pâques', 'Classique', 'Évènement'];

    public function __construct(
        private MenuRepository $menuRepository,
        private CacheService $cacheService,
        private DishRepository $dishRepository,
        private MenuImageRepository $menuImageRepository
    ) {
    }

    /**
     * @return int[] Plats composant le menu.
     */
    public function getDishIds(int $menuId): array
    {
        return $this->menuRepository->findDishIds($menuId);
    }

    /**
     * Enregistre la composition d'un menu : au moins une entrée, un plat et un dessert,
     * choisis parmi les plats actifs. Un même plat peut appartenir à plusieurs menus.
     *
     * @param mixed $dishIds Valeur brute du formulaire (tableau d'identifiants).
     */
    public function saveComposition(int $menuId, mixed $dishIds): void
    {
        if ($this->getMenuById($menuId) === null) {
            throw new InvalidArgumentException('Menu introuvable.');
        }

        $ids = is_array($dishIds)
            ? array_values(array_unique(array_filter(array_map('intval', $dishIds), static fn (int $id): bool => $id > 0)))
            : [];
        $dishes = $this->dishRepository->findActiveByIds($ids);

        if (count($dishes) !== count($ids)) {
            throw new InvalidArgumentException('Un des plats sélectionnés est introuvable ou supprimé.');
        }

        $ordered = [];
        foreach (array_keys(DishService::CATEGORIES) as $category) {
            $inCategory = array_filter($dishes, static fn (array $dish): bool => $dish['category'] === $category);

            if ($inCategory === []) {
                throw new InvalidArgumentException(
                    'Un menu doit comporter au moins une entrée, un plat et un dessert.'
                );
            }

            // Ordre d'affichage : entrées, plats puis desserts, dans l'ordre de sélection.
            foreach ($ids as $id) {
                if (isset($inCategory[$id])) {
                    $ordered[] = $id;
                }
            }
        }

        $this->menuRepository->replaceDishes($menuId, $ordered);
        $this->clearCache($menuId);
    }

    public function getImages(int $menuId): array
    {
        try {
            return $this->menuImageRepository->findByMenu($menuId);
        } catch (\Throwable $exception) {
            error_log('Galerie du menu ' . $menuId . ' indisponible : ' . $exception->getMessage());
            return [];
        }
    }

    /**
     * @param Menu[] $menus
     * @return array<int, array> Plats par menu (une seule requête).
     */
    public function getDishesForMenus(array $menus): array
    {
        return $this->menuRepository->findDishesForMenus(
            array_map(static fn (Menu $menu): int => $menu->getId(), $menus)
        );
    }

    /**
     * @param Menu[] $menus
     * @return array<int, array> Couverture par menu (une seule requête MongoDB).
     */
    public function getCovers(array $menus): array
    {
        try {
            return $this->menuImageRepository->findCovers(
                array_map(static fn (Menu $menu): int => $menu->getId(), $menus)
            );
        } catch (\Throwable $exception) {
            error_log('Images de menus indisponibles : ' . $exception->getMessage());
            return [];
        }
    }

    /**
     * Ajoute à la galerie l'image envoyée dans le champ "image".
     */
    public function addImage(int $menuId, string $altText): void
    {
        if ($this->getMenuById($menuId) === null) {
            throw new InvalidArgumentException('Menu introuvable.');
        }

        $altText = trim($altText);

        if ($altText === '' || mb_strlen($altText) > 200) {
            throw new InvalidArgumentException('Le texte alternatif est requis (200 caractères maximum).');
        }

        try {
            $url = UploadFile::upload('image', 'menus');
        } catch (FileException $exception) {
            throw new InvalidArgumentException($exception->getMessage(), 0, $exception);
        }

        try {
            $this->menuImageRepository->add($menuId, $url, $altText, 'upload');
        } catch (\Throwable $exception) {
            UploadFile::remove($url);
            throw $exception;
        }
    }

    public function moveImage(int $menuId, string $imageId, string $direction): void
    {
        if (!in_array($direction, ['up', 'down'], true)) {
            throw new InvalidArgumentException('Déplacement invalide.');
        }

        $this->menuImageRepository->move($menuId, $imageId, $direction === 'up' ? -1 : 1);
    }

    public function deleteImage(int $menuId, string $imageId): void
    {
        $image = $this->menuImageRepository->findOne($menuId, $imageId);

        if ($image === null) {
            throw new InvalidArgumentException('Image introuvable.');
        }

        $this->menuImageRepository->delete($menuId, $imageId);

        try {
            UploadFile::remove($image['url']);
        } catch (FileException $exception) {
            error_log('Fichier d’image non supprimé : ' . $exception->getMessage());
        }
    }

    public function getAllMenus(): array
    {
        $cached = $this->cacheService->get('menus_all');

        if (is_array($cached)) {
            return array_map($this->menuFromCache(...), $cached);
        }

        $menus = $this->menuRepository->findAll();
        $this->cacheService->set('menus_all', array_map($this->menuToCache(...), $menus), 300);

        return $menus;
    }

    /**
     * Tous les menus actifs, y compris ceux dont le stock est épuisé (administration).
     */
    public function getMenusForAdmin(): array
    {
        return $this->menuRepository->findAllForOrderSelection();
    }

    public function getMenuById(int $id): ?Menu
    {
        if ($id < 1) {
            return null;
        }

        $key = 'menu_' . $id;
        $cached = $this->cacheService->get($key);

        if (is_array($cached)) {
            return $this->menuFromCache($cached);
        }

        $menu = $this->menuRepository->findById($id);

        if ($menu !== null) {
            $this->cacheService->set($key, $this->menuToCache($menu), 300);
        }

        return $menu;
    }

    public function getMenuDetails(int $id): array
    {
        if ($id < 1) {
            return ['dishes' => [], 'allergens' => []];
        }

        return $this->menuRepository->findDetails($id);
    }

    public function filterMenus(array $filters): array
    {
        $normalized = [];

        foreach (
            ['max_price', 'min_price', 'theme', 'dietary_regime', 'min_people']
            as $key
        ) {
            if (
                isset($filters[$key])
                && $filters[$key] !== ''
            ) {
                $normalized[$key] = $filters[$key];
            }
        }

        return $this->menuRepository->filter($normalized);
    }

    public function createMenu(array $data): int
    {
        $menu = $this->hydrateInput($data);
        $id = $this->menuRepository->create($menu);
        $this->clearCache($id);

        return $id;
    }

    public function updateMenu(int $id, array $data): void
    {
        $menu = $this->menuRepository->findById($id);

        if ($menu === null) {
            throw new \InvalidArgumentException('Menu non trouvé.');
        }

        if (isset($data['title']) && trim((string) $data['title']) !== '') {
            $menu->setTitle(trim((string) $data['title']));
        }

        if (isset($data['description']) && trim((string) $data['description']) !== '') {
            $menu->setDescription(trim((string) $data['description']));
        }

        if (isset($data['theme']) && trim((string) $data['theme']) !== '') {
            $menu->setTheme($this->validTheme((string) $data['theme']));
        }

        if (isset($data['dietary_regime']) && trim((string) $data['dietary_regime']) !== '') {
            $menu->setDietaryRegime($this->validRegime((string) $data['dietary_regime']));
        }

        if (isset($data['min_people']) && is_numeric($data['min_people']) && (int) $data['min_people'] > 0) {
            $menu->setMinPeople((int) $data['min_people']);
        }

        if (isset($data['base_price']) && is_numeric($data['base_price']) && (float) $data['base_price'] >= 0) {
            $menu->setBasePrice((float) $data['base_price']);
        }

        if (isset($data['conditions']) && trim((string) $data['conditions']) !== '') {
            $menu->setConditions(trim((string) $data['conditions']));
        }

        if (isset($data['available_stock']) && is_numeric($data['available_stock']) && (int) $data['available_stock'] >= 0) {
            $menu->setAvailableStock((int) $data['available_stock']);
        }

        $this->menuRepository->update($menu);
        $this->clearCache($id);
    }

    public function deleteMenu(int $id): void
    {
        $this->menuRepository->delete($id);
        $this->clearCache($id);
    }

    private function hydrateInput(array $data): Menu
    {
        $title = trim((string) ($data['title'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $theme = trim((string) ($data['theme'] ?? ''));
        $conditions = trim((string) ($data['conditions'] ?? ''));

        if ($title === '') {
            throw new \InvalidArgumentException('Le titre est requis.');
        }

        if ($description === '') {
            throw new \InvalidArgumentException('La description est requise.');
        }

        $theme = $this->validTheme($theme);

        if ($conditions === '') {
            throw new \InvalidArgumentException('Les conditions sont requises.');
        }

        $minPeople = filter_var(
            $data['min_people'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        $stock = filter_var(
            $data['available_stock'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 0]]
        );

        $price = filter_var(
            $data['base_price'] ?? null,
            FILTER_VALIDATE_FLOAT
        );

        if ($minPeople === false) {
            throw new \InvalidArgumentException(
                'Le nombre minimum de personnes est invalide.'
            );
        }

        if ($stock === false) {
            throw new \InvalidArgumentException(
                'Le stock disponible est invalide.'
            );
        }

        if ($price === false || $price < 0) {
            throw new \InvalidArgumentException(
                'Le prix de base est invalide.'
            );
        }

        $menu = new Menu();
        $menu->setTitle($title);
        $menu->setDescription($description);
        $menu->setTheme($theme);
        $menu->setDietaryRegime(
            $this->validRegime((string) ($data['dietary_regime'] ?? 'classic'))
        );
        $menu->setMinPeople($minPeople);
        $menu->setBasePrice($price);
        $menu->setConditions($conditions);
        $menu->setAvailableStock($stock);

        return $menu;
    }

    /**
     * Même contrôle que DishService : sinon une valeur trafiquée arrive jusqu'à l'ENUM SQL (erreur 500).
     */
    private function validRegime(string $regime): string
    {
        $regime = trim($regime);

        if (!array_key_exists($regime, Labels::DIETARY_REGIME)) {
            throw new InvalidArgumentException('Régime alimentaire invalide.');
        }

        return $regime;
    }

    private function validTheme(string $theme): string
    {
        $theme = trim($theme);

        if (!in_array($theme, self::THEMES, true)) {
            throw new InvalidArgumentException('Le thème doit être : ' . implode(', ', self::THEMES) . '.');
        }

        return $theme;
    }

    /**
     * Le cache n'accepte que des tableaux et des scalaires (aucun objet désérialisé).
     */
    private function menuToCache(Menu $menu): array
    {
        return [
            'id' => $menu->getId(),
            'title' => $menu->getTitle(),
            'description' => $menu->getDescription(),
            'theme' => $menu->getTheme(),
            'dietary_regime' => $menu->getDietaryRegime(),
            'min_people' => $menu->getMinPeople(),
            'base_price' => $menu->getBasePrice(),
            'conditions' => $menu->getConditions(),
            'available_stock' => $menu->getAvailableStock(),
            'created_at' => $menu->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updated_at' => $menu->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }

    private function menuFromCache(array $data): Menu
    {
        $menu = new Menu();
        $menu->setId((int) $data['id']);
        $menu->setTitle((string) $data['title']);
        $menu->setDescription((string) $data['description']);
        $menu->setTheme((string) $data['theme']);
        $menu->setDietaryRegime((string) $data['dietary_regime']);
        $menu->setMinPeople((int) $data['min_people']);
        $menu->setBasePrice((float) $data['base_price']);
        $menu->setConditions((string) $data['conditions']);
        $menu->setAvailableStock((int) $data['available_stock']);
        $menu->setCreatedAt(is_string($data['created_at'] ?? null) ? new \DateTimeImmutable($data['created_at']) : null);
        $menu->setUpdatedAt(is_string($data['updated_at'] ?? null) ? new \DateTimeImmutable($data['updated_at']) : null);

        return $menu;
    }

    /**
     * À appeler après tout changement de stock hors de ce service (commande, annulation).
     */
    public function invalidateMenu(int $id): void
    {
        $this->clearCache($id);
    }

    private function clearCache(int $id): void
    {
        $this->cacheService->delete('menus_all');
        $this->cacheService->delete('menu_' . $id);
    }
}
