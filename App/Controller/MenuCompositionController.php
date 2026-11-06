<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Session;
use App\Service\DishService;
use App\Service\MenuService;
use InvalidArgumentException;
use Throwable;

/**
 * Composition d'un menu à partir des plats et gestion de sa galerie d'images
 * (employé et administrateur).
 */
final class MenuCompositionController extends BaseController
{
    public function __construct(
        private readonly MenuService $menuService,
        private readonly DishService $dishService
    ) {
    }

    public function show(int $id): void
    {
        $menu = $this->menuService->getMenuById($id);

        if ($menu === null) {
            Session::flash('admin_error', 'Menu introuvable.');
            $this->redirect('/admin/menus');
        }

        $this->render('admin/menu_content', [
            'menu' => $menu,
            'dishes' => $this->dishService->getAllDishes(),
            'selectedDishIds' => $this->menuService->getDishIds($id),
            'images' => $this->menuService->getImages($id),
            'categories' => DishService::CATEGORIES,
            'title' => 'Composition et galerie - Vite & Gourmand',
        ]);
    }

    public function saveDishes(int $id): never
    {
        $this->attempt(
            fn () => $this->menuService->saveComposition($id, $_POST['dishes'] ?? []),
            'Composition du menu enregistrée.',
            'Composition du menu'
        );

        $this->redirect('/admin/menus/' . $id . '/content');
    }

    public function addImage(int $id): never
    {
        $this->attempt(
            fn () => $this->menuService->addImage($id, (string) ($_POST['alt_text'] ?? '')),
            'Image ajoutée à la galerie.',
            'Ajout d’image'
        );

        $this->redirect('/admin/menus/' . $id . '/content#gallery');
    }

    public function moveImage(int $id, string $imageId): never
    {
        $this->attempt(
            fn () => $this->menuService->moveImage($id, $imageId, (string) ($_POST['direction'] ?? '')),
            'Ordre de la galerie mis à jour.',
            'Déplacement d’image'
        );

        $this->redirect('/admin/menus/' . $id . '/content#gallery');
    }

    public function deleteImage(int $id, string $imageId): never
    {
        $this->attempt(
            fn () => $this->menuService->deleteImage($id, $imageId),
            'Image supprimée de la galerie.',
            'Suppression d’image'
        );

        $this->redirect('/admin/menus/' . $id . '/content#gallery');
    }

    private function attempt(callable $action, string $success, string $context): void
    {
        try {
            $action();
            Session::flash('admin_success', $success);
        } catch (InvalidArgumentException $exception) {
            Session::flash('admin_error', $exception->getMessage());
        } catch (Throwable $exception) {
            error_log($context . ' : ' . $exception->getMessage());
            Session::flash('admin_error', 'L’opération a échoué. Réessayez plus tard.');
        }
    }
}
