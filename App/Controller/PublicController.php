<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Exception\NotFoundException;
use App\Core\Labels;
use App\Core\Session;
use App\Entity\Menu;
use App\Service\CommentService;
use App\Service\DeliveryDistanceService;
use App\Service\MailService;
use App\Service\MenuService;

final class PublicController extends BaseController
{
    public function __construct(
        private readonly MenuService $menuService,
        private readonly CommentService $commentService,
        private readonly MailService $mailService
    ) {
    }

    public function index(): void
    {
        try {
            $menus = array_slice($this->menuService->getAllMenus(), 0, 3);
            $reviews = $this->commentService->getHomepageReviews();
        } catch (\Throwable $exception) {
            error_log($exception->__toString());
            $menus ??= [];
            $reviews ??= [];
        }

        $menus ??= [];
        $reviews ??= [];

        $this->render('home/index', [
            'reviews' => $reviews,
            'menus' => $menus,
            'menuCovers' => $this->menuService->getCovers($menus),
            'user' => Session::id(),
        ]);
    }

    public function menusPage(): void
    {
        try {
            $menus = $this->menuService->getAllMenus();
        } catch (\Throwable $exception) {
            error_log('Impossible de charger le catalogue : ' . $exception->getMessage());
            $menus = [];
        }

        // Plats et couvertures de tous les menus : une requête chacun (pas de N+1).
        $menuDetails = array_map(
            static fn (array $dishes): array => ['dishes' => $dishes],
            $this->menuService->getDishesForMenus($menus)
        );

        $this->render('home/menus', [
            'menus' => $menus,
            'menuDetails' => $menuDetails,
            'menuCovers' => $this->menuService->getCovers($menus),
            'themes' => MenuService::THEMES,
            'user' => Session::id(),
        ]);
    }

    public function menuDetail(int $id): void
    {
        // menu absent ou désactivé : même page 404 que le reste du site
        $menu = $this->menuService->getMenuById($id);

        if ($menu === null) {
            throw new NotFoundException('Menu introuvable.');
        }

        $details = $this->menuService->getMenuDetails($id);
        $this->render('home/menu_detail', [
            'menu' => $menu,
            'details' => $details,
            'images' => $this->menuService->getImages($id),
            'user' => Session::id(),
        ]);
    }

    public function getMenus(): never
    {
        $this->json([
            'success' => true,
            'data' => $this->serializeMenus($this->menuService->getAllMenus()),
        ]);
    }

    public function getMenuById(int $id): never
    {
        if ($id < 1) {
            $this->json(['success' => false, 'error' => 'Menu introuvable.'], 404);
        }

        $menu = $this->menuService->getMenuById($id);

        if ($menu === null) {
            $this->json(['success' => false, 'error' => 'Menu introuvable.'], 404);
        }

        $details = $this->menuService->getMenuDetails($id);

        $this->json([
            'success' => true,
            'data' => $this->serializeMenu(
                $menu,
                $details['dishes'] ?? [],
                $this->menuService->getImages($id),
                $details['allergens'] ?? []
            ),
        ]);
    }

    public function filterMenus(): void
    {
        $filters = [];

        foreach (['max_price', 'min_price', 'theme', 'dietary_regime', 'min_people'] as $key) {
            if (isset($_GET[$key]) && $_GET[$key] !== '') {
                $filters[$key] = $_GET[$key];
            }
        }

        try {
            $menus = $this->menuService->filterMenus($filters);
        } catch (\InvalidArgumentException $exception) {
            // Saisie de filtre invalide : message lisible, pas d'erreur 500.
            $this->json(['success' => false, 'error' => $exception->getMessage()], 422);
        }

        $this->json([
            'success' => true,
            'data' => $this->serializeMenus($menus),
        ]);
    }

    public function legal(): void
    {
        $this->render('home/legal', $this->legalData() + [
            'hosting' => is_array(config('hosting')) ? config('hosting') : [],
        ]);
    }

    public function cgv(): void
    {
        $this->render('home/cgv', $this->legalData());
    }

    public function privacy(): void
    {
        $this->render('home/privacy', $this->legalData() + [
            'sessionLifetime' => (int) config('session.lifetime', 120),
        ]);
    }

    /**
     * Coordonnées de l'entreprise communes aux pages légales.
     */
    private function legalData(): array
    {
        [$street, $city, $postalCode] = DeliveryDistanceService::COMPANY_ADDRESS;

        return [
            'companyAddress' => $street . ', ' . $postalCode . ' ' . $city,
            'contactEmail' => $this->mailService->companyAddress(),
        ];
    }

    /**
     * Liste de menus pour le filtrage dynamique : plats et couvertures chargés
     * en une requête chacun pour l'ensemble des menus.
     */
    private function serializeMenus(array $menus): array
    {
        $dishes = $this->menuService->getDishesForMenus($menus);
        $covers = $this->menuService->getCovers($menus);

        return array_map(
            fn (Menu $menu): array => $this->serializeMenu(
                $menu,
                $dishes[$menu->getId()] ?? [],
                isset($covers[$menu->getId()]) ? [$covers[$menu->getId()]] : []
            ),
            $menus
        );
    }

    private function serializeMenu(Menu $menu, array $dishes, array $images, ?array $allergens = null): array
    {
        $data = [
            'id' => $menu->getId(),
            'title' => $menu->getTitle(),
            'description' => $menu->getDescription(),
            'theme' => $menu->getTheme(),
            'dietary_regime' => $menu->getDietaryRegime(),
            'dietary_regime_label' => Labels::dietaryRegime($menu->getDietaryRegime()),
            'min_people' => $menu->getMinPeople(),
            'base_price' => $menu->getBasePrice(),
            'conditions' => $menu->getConditions(),
            'available_stock' => $menu->getAvailableStock(),
            'dishes' => $dishes,
            'images' => array_map(
                static fn (array $image): array => ['url' => $image['url'], 'alt_text' => $image['alt_text']],
                $images
            ),
        ];

        if ($allergens !== null) {
            $data['allergens'] = $allergens;
        }

        return $data;
    }
}
