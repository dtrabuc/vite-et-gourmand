<?php
namespace Src\Controller;

use Src\Service\MenuService;
use Src\Service\CommentService;
use Src\Repository\MenuRepository;
use Src\Repository\CommentRepository;

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
        // Get featured/recent reviews for homepage
        $reviews = $this->commentService->getHomepageReviews();
        // Get some menus to display on homepage (e.g., 3 menus)
        $allMenus = $this->menuService->getAllMenus();
        $menus = array_slice($allMenus, 0, 3); // Take first 3 menus

        $this->render('home/index', ['reviews' => $reviews, 'menus' => $menus]);
    }

    public function getMenus(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo $this->jsonError('Method Not Allowed');
            return;
        }

        $menus = $this->menuService->getAllMenus();

        header('Content-Type: application/json');
        echo $this->jsonSuccess($menus);
    }

    public function getMenuById(array $params): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo $this->jsonError('Method Not Allowed');
            return;
        }

        $id = (int)$params[0] ?? 0;
        if ($id <= 0) {
            http_response_code(400);
            echo $this->jsonError('Invalid menu ID');
            return;
        }

        $menu = $this->menuService->getMenuById($id);

        if ($menu === null) {
            http_response_code(404);
            echo $this->jsonError('Menu not found');
            return;
        }

        header('Content-Type: application/json');
        echo $this->jsonSuccess($menu);
    }

    public function filterMenus(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo $this->jsonError('Method Not Allowed');
            return;
        }

        $filters = [];

        // Price filter
        if (!empty($_GET['max_price']) && is_numeric($_GET['max_price'])) {
            $filters['max_price'] = (float)$_GET['max_price'];
        }

        // Theme filter
        if (!empty($_GET['theme'])) {
            $filters['theme'] = $_GET['theme'];
        }

        // Minimum people filter
        if (!empty($_GET['min_people']) && is_numeric($_GET['min_people'])) {
            $filters['min_people'] = (int)$_GET['min_people'];
        }

        // Add other filters as needed

        $menus = $this->menuService->filterMenus($filters);

        header('Content-Type: application/json');
        echo $this->jsonSuccess($menus);
    }

    public function getHomepageReviews(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo $this->jsonError('Method Not Allowed');
            return;
        }

        $reviews = $this->commentService->getHomepageReviews();

        header('Content-Type: application/json');
        echo $this->jsonSuccess($reviews);
    }

    // NEW METHOD: Get catalog for frontend
    public function getCatalog(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo $this->jsonError('Method Not Allowed');
            return;
        }

        // Get all menus
        $allMenus = $this->menuService->getAllMenus();
        
        // Categorize menus
        $menus = [];
        $aLaCarte = [];
        $boissonsSans = [];
        $boissonsAvec = [];
        $digestifs = [];

        foreach ($allMenus as $menu) {
            // Assuming menu has a 'category' or 'type' field
            $category = strtolower($menu['category'] ?? $menu['type'] ?? '');
            
            switch ($category) {
                case 'entree':
                case 'plat':
                case 'dessert':
                    $menus[] = $menu;
                    break;
                case 'boisson':
                    // Check if it's alcoholic or not
                    if (isset($menu['alcool']) && $menu['alcool'] === true) {
                        $boissonsAvec[] = $menu;
                    } else {
                        $boissonsSans[] = $menu;
                    }
                    break;
                case 'digestif':
                    $digestifs[] = $menu;
                    break;
                default:
                    // Default to main menu if category not recognized
                    $menus[] = $menu;
                    break;
            }
        }

        $catalogData = [
            'menus' => $menus,
            'aLaCarte' => $aLaCarte, // Assuming this is for special menu items
            'boissons' => [
                'sans' => $boissonsSans,
                'avec' => $boissonsAvec
            ],
            'digestifs' => $digestifs
        ];

        header('Content-Type: application/json');
        echo $this->jsonSuccess($catalogData);
    }

    // NEW METHOD: Get reviews for frontend
    public function getReviews(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo $this->jsonError('Method Not Allowed');
            return;
        }

        // Get all approved reviews
        $reviews = $this->commentService->getApprovedReviews();
        
        // Format reviews for frontend
        $formattedReviews = [];
        foreach ($reviews as $review) {
            $formattedReviews[] = [
                'auteur' => $review['user_name'] ?? $review['firstname'] . ' ' . $review['lastname'],
                'note' => (int)$review['rating'],
                'commentaire' => $review['comment'] ?? $review['content']
            ];
        }

        $reviewsData = [
            'avis' => $formattedReviews
        ];

        header('Content-Type: application/json');
        echo $this->jsonSuccess($reviewsData);
    }

}
