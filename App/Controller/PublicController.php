<?php
namespace App\Controller;

use App\Service\MenuService;
use App\Service\CommentService;
use App\Repository\MenuRepository;
use App\Repository\CommentRepository;

class PublicController extends BaseController
{
    private MenuService $menuService;
    private CommentService $commentService;

    public function __construct()
    {
        $this->menuService = new MenuService(new MenuRepository());
        $this->commentService = new CommentService(new CommentRepository());
    }

    public function index(): void
    {
        try {
            $reviews = $this->commentService->getHomepageReviews();
        } catch (\Throwable $exception) {
            error_log('Impossible de charger les avis : ' . $exception->getMessage());
            $reviews = [];
        }
        try {
            $menus = array_slice($this->menuService->getAllMenus(), 0, 3);
        } catch (\Throwable $exception) {
            error_log('Impossible de charger les menus : ' . $exception->getMessage());
            $menus = [];
        }
        $this->render('home/index', ['reviews' => $reviews, 'menus' => $menus]);
    }

    public function menusPage(): void
    {
        $menus = $this->menuService->getAllMenus();
        $this->render('home/menus', ['menus' => $menus]);
    }

    public function menuDetail(int $id): void
    {
        $menu = $id > 0 ? $this->menuService->getMenuById($id) : null;
        if ($menu === null) {
            http_response_code(404);
            $this->render('home/menu_detail', ['menu' => null]);
            return;
        }
        $details = (new MenuRepository())->findDetails($id);
        $this->render('home/menu_detail', ['menu' => $menu, 'details' => $details]);
    }

    public function getMenus(): void
    {
        $this->jsonMenus($this->menuService->getAllMenus());
    }

    public function getMenuById(int $id): void
    {
        if ($id <= 0) { http_response_code(400); echo $this->jsonError('Identifiant invalide'); return; }
        $menu = $this->menuService->getMenuById($id);
        if ($menu === null) { http_response_code(404); echo $this->jsonError('Menu introuvable'); return; }
        header('Content-Type: application/json');
        echo $this->jsonSuccess($this->menuToArray($menu));
    }

    public function filterMenus(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo $this->jsonError('Method Not Allowed');
            return;
        }

        $filters = [];
        foreach (['max_price', 'min_price', 'theme', 'dietary_regime', 'min_people'] as $key) {
            if (isset($_GET[$key]) && $_GET[$key] !== '') {
                $filters[$key] = $_GET[$key];
            }
        }

        $menus = $this->menuService->filterMenus($filters);
        $this->jsonMenus($menus);
    }

    private function jsonMenus(array $menus): void
    {
        header('Content-Type: application/json');
        echo $this->jsonSuccess(array_map([$this, 'menuToArray'], $menus));
    }

    public function legal(): void
    {
        $this->render('home/legal');
    }

    public function cgv(): void
    {
        $this->render('home/cgv');
    }

    private function menuToArray(\App\Entity\Menu $menu): array
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
        ];
    }
}
