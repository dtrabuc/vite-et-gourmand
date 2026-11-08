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
use App\Service\MenuPricing;
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

        // Avec un nombre de personnes : les menus commandables d'abord (comme menus.js).
        $people = $this->requestedPeople();
        if ($people !== null) {
            usort($menus, static fn (Menu $a, Menu $b): int =>
                ($a->getMinPeople() > $people) <=> ($b->getMinPeople() > $people));
        }

        // Plats et couvertures de tous les menus : une requête chacun (pas de N+1).
        $menuDetails = array_map(
            static fn (array $dishes): array => ['dishes' => $dishes],
            $this->menuService->getDishesForMenus($menus)
        );

        $this->render('home/menus', [
            'people' => $people,
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
            'people' => $this->requestedPeople() ?? $menu->getMinPeople(),
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
            'data' => $this->serializeMenus($menus, $this->requestedPeople()),
        ]);
    }

    /**
     * Prix d'un menu pour un nombre de convives (fiche menu, avant connexion).
     * Le montant définitif est recalculé à la commande.
     */
    public function menuQuote(int $id): never
    {
        $menu = $this->menuService->getMenuById($id);
        $people = $this->requestedPeople();

        if ($menu === null) {
            $this->json(['success' => false, 'error' => 'Menu introuvable.'], 404);
        }

        if ($people === null) {
            $this->json(['success' => false, 'error' => 'Nombre de personnes invalide (1 à 999).'], 422);
        }

        $this->json([
            'success' => true,
            'data' => MenuPricing::quote($menu->getBasePrice(), $menu->getMinPeople(), $people)
                + ['min_people' => $menu->getMinPeople()],
        ]);
    }

    /** ?people=N dans l'URL, entre 1 et 999, sinon null. */
    private function requestedPeople(): ?int
    {
        $people = filter_var(
            $_GET['people'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 999]]
        );

        return $people === false ? null : $people;
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
    private function serializeMenus(array $menus, ?int $people = null): array
    {
        $dishes = $this->menuService->getDishesForMenus($menus);
        $covers = $this->menuService->getCovers($menus);

        return array_map(
            fn (Menu $menu): array => $this->serializeMenu(
                $menu,
                $dishes[$menu->getId()] ?? [],
                isset($covers[$menu->getId()]) ? [$covers[$menu->getId()]] : [],
                null,
                $people
            ),
            $menus
        );
    }

    private function serializeMenu(
        Menu $menu,
        array $dishes,
        array $images,
        ?array $allergens = null,
        ?int $people = null
    ): array {
        $data = [
            'id' => $menu->getId(),
            'title' => $menu->getTitle(),
            'description' => $menu->getDescription(),
            'theme' => $menu->getTheme(),
            'dietary_regime' => $menu->getDietaryRegime(),
            'dietary_regime_label' => Labels::dietaryRegime($menu->getDietaryRegime()),
            'min_people' => $menu->getMinPeople(),
            'base_price' => $menu->getBasePrice(),
            'price_per_person' => MenuPricing::perPerson($menu->getBasePrice(), $menu->getMinPeople()),
            'discount_from' => MenuPricing::discountFrom($menu->getMinPeople()),
            'composition' => menu_composition($dishes),
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

        if ($people !== null) {
            $data['quote'] = MenuPricing::quote($menu->getBasePrice(), $menu->getMinPeople(), $people);
        }

        return $data;
    }
}
