<?php
namespace App\Service;

use App\Entity\Menu;
use App\Repository\MenuRepository;

class MenuService
{
    private MenuRepository $menuRepository;
    private \App\Service\CacheService $cacheService;

    public function __construct(MenuRepository $menuRepository, ?\App\Service\CacheService $cacheService = null)
    {
        $this->menuRepository = $menuRepository;
        // Try to use RedisCacheService first, fall back to basic CacheService
        $this->cacheService = $cacheService ?? new \App\Service\RedisCacheService();
    }

    public function getAllMenus(): array
    {
        // Try to get from cache first
        $cached = $this->cacheService->get('menus_all');
        if ($cached !== null) {
            return $cached;
        }
        
        // If not in cache, get from database
        $menus = $this->menuRepository->findAll();
        
        // Store in cache for 1 hour (menus don't change frequently)
        $this->cacheService->set('menus_all', $menus, 3600);
        
        return $menus;
    }

    public function getMenuById(int $id): ?Menu
    {
        // Try to get from cache first
        $cached = $this->cacheService->get("menu_{$id}");
        if ($cached !== null) {
            return $cached;
        }
        
        // If not in cache, get from database
        $menu = $this->menuRepository->findById($id);
        
        // Store in cache for 1 hour
        if ($menu !== null) {
            $this->cacheService->set("menu_{$id}", $menu, 3600);
        }
        
        return $menu;
    }

    public function filterMenus(array $filters): array
    {
        // Create a cache key based on the filters
        $cacheKey = 'menus_filtered_' . md5(json_encode($filters));
        
        // Try to get from cache first
        $cached = $this->cacheService->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        // If not in cache, get from database
        $menus = $this->menuRepository->filter($filters);
        
        // Store in cache for 30 minutes (filtered results might change more often)
        $this->cacheService->set($cacheKey, $menus, 1800);
        
        return $menus;
    }

    public function createMenu(array $data): int
    {
        // Validate data
        if (empty($data['title'])) {
            throw new \InvalidArgumentException('Le titre est requis');
        }
        if (empty($data['description'])) {
            throw new \InvalidArgumentException('La description est requise');
        }
        if (empty($data['theme'])) {
            throw new \InvalidArgumentException('Le thème est requis');
        }
        if (empty($data['min_people']) || !is_numeric($data['min_people']) || (int)$data['min_people'] < 1) {
            throw new \InvalidArgumentException('Le nombre minimum de personnes doit être un nombre positif');
        }
        if (empty($data['base_price']) || !is_numeric($data['base_price']) || (float)$data['base_price'] < 0) {
            throw new \InvalidArgumentException('Le prix de base doit être un nombre positif');
        }
        if (empty($data['conditions'])) {
            throw new \InvalidArgumentException('Les conditions sont requises');
        }
        if (!isset($data['available_stock']) || !is_numeric($data['available_stock']) || (int)$data['available_stock'] < 0) {
            throw new \InvalidArgumentException('Le stock disponible doit être un nombre positif ou zéro');
        }

        $menu = new Menu();
        $menu->setTitle($data['title']);
        $menu->setDescription($data['description']);
        $menu->setTheme($data['theme']);
        $menu->setDietaryRegime($data['dietary_regime'] ?? 'classic');
        $menu->setMinPeople((int)$data['min_people']);
        $menu->setBasePrice((float)$data['base_price']);
        $menu->setConditions($data['conditions']);
        $menu->setAvailableStock((int)$data['available_stock']);
        // createdAt and updatedAt will be set by the repository (default to current timestamp)

        $id = $this->menuRepository->create($menu);
        
        // Clear related cache entries since data has changed
        $this->clearMenuCache();
        
        return $id;
    }

    public function updateMenu(int $id, array $data): void
    {
        // Validate data (same as create but allow partial updates)
        $menu = $this->menuRepository->findById($id);
        if ($menu === null) {
            throw new \InvalidArgumentException('Menu non trouvé');
        }

        if (isset($data['title']) && $data['title'] !== '') {
            $menu->setTitle($data['title']);
        }
        if (isset($data['description']) && $data['description'] !== '') {
            $menu->setDescription($data['description']);
        }
        if (isset($data['theme']) && $data['theme'] !== '') {
            $menu->setTheme($data['theme']);
        }
        if (isset($data['dietary_regime']) && $data['dietary_regime'] !== '') {
            $menu->setDietaryRegime($data['dietary_regime']);
        }
        if (isset($data['min_people']) && is_numeric($data['min_people']) && (int)$data['min_people'] >= 1) {
            $menu->setMinPeople((int)$data['min_people']);
        }
        if (isset($data['base_price']) && is_numeric($data['base_price']) && (float)$data['base_price'] >= 0) {
            $menu->setBasePrice((float)$data['base_price']);
        }
        if (isset($data['conditions']) && $data['conditions'] !== '') {
            $menu->setConditions($data['conditions']);
        }
        if (isset($data['available_stock']) && is_numeric($data['available_stock']) && (int)$data['available_stock'] >= 0) {
            $menu->setAvailableStock((int)$data['available_stock']);
        }

        $this->menuRepository->update($menu);
        
        // Clear related cache entries since data has changed
        $this->clearMenuCache();
    }

    public function deleteMenu(int $id): void
    {
        $this->menuRepository->delete($id);
        
        // Clear related cache entries since data has changed
        $this->clearMenuCache();
    }

    /**
     * Clear menu-related cache entries
     */
    private function clearMenuCache(): void
    {
        $this->cacheService->delete('menus_all');
        // Note: For a more sophisticated implementation with patterned deletion,
        // we would need to iterate through cache directory and delete matching files
        // For simplicity, we're clearing the main caches and letting filtered caches expire
    }
}
