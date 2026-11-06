<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Session;
use App\Service\DishService;
use InvalidArgumentException;
use Throwable;

/**
 * Gestion des plats et de leurs allergènes (employé et administrateur).
 */
final class DishController extends BaseController
{
    public function __construct(
        private readonly DishService $dishService
    ) {
    }

    public function index(): void
    {
        $this->render('admin/dishes', [
            'dishes' => $this->dishService->getAllDishes(),
            'allergens' => $this->dishService->getAllergens(),
            'categories' => DishService::CATEGORIES,
            'regimes' => DishService::DIETARY_REGIMES,
            'oldInput' => (array) Session::pullFlash('dish_old_input', []),
        ]);
    }

    public function create(): never
    {
        try {
            $this->dishService->createDish($_POST);
            Session::flash('admin_success', 'Plat créé.');
        } catch (InvalidArgumentException $exception) {
            Session::flash('admin_error', $exception->getMessage());
            Session::flash('dish_old_input', $_POST);
        } catch (Throwable $exception) {
            error_log('Création de plat : ' . $exception->getMessage());
            Session::flash('admin_error', 'Le plat n’a pas pu être enregistré.');
            Session::flash('dish_old_input', $_POST);
        }

        $this->redirect('/admin/dishes');
    }

    public function edit(int $id): void
    {
        $dish = $this->dishService->getDish($id);

        if ($dish === null) {
            Session::flash('admin_error', 'Plat introuvable.');
            $this->redirect('/admin/dishes');
        }

        $this->render('admin/dish_edit', [
            'dish' => $dish,
            'allergens' => $this->dishService->getAllergens(),
            'categories' => DishService::CATEGORIES,
            'regimes' => DishService::DIETARY_REGIMES,
            'title' => 'Modifier un plat - Vite & Gourmand',
        ]);
    }

    public function update(int $id): never
    {
        try {
            $this->dishService->updateDish($id, $_POST);
            Session::flash('admin_success', 'Plat mis à jour.');
            $this->redirect('/admin/dishes');
        } catch (InvalidArgumentException $exception) {
            Session::flash('admin_error', $exception->getMessage());
        } catch (Throwable $exception) {
            error_log('Modification de plat : ' . $exception->getMessage());
            Session::flash('admin_error', 'Le plat n’a pas pu être modifié.');
        }

        $this->redirect('/admin/dishes/' . $id . '/edit');
    }

    public function delete(int $id): never
    {
        try {
            $menus = $this->dishService->deleteDish($id);
            Session::flash(
                'admin_success',
                'Plat supprimé.' . ($menus !== []
                    ? ' Il n’apparaît plus dans : ' . implode(', ', $menus) . '. Vérifiez la composition de ces menus.'
                    : '')
            );
        } catch (InvalidArgumentException $exception) {
            Session::flash('admin_error', $exception->getMessage());
        } catch (Throwable $exception) {
            error_log('Suppression de plat : ' . $exception->getMessage());
            Session::flash('admin_error', 'Le plat n’a pas pu être supprimé.');
        }

        $this->redirect('/admin/dishes');
    }
}
