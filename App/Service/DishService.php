<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\Labels;
use App\Repository\DishRepository;
use InvalidArgumentException;

final readonly class DishService
{
    public const CATEGORIES = [
        'starter' => 'Entrée',
        'main' => 'Plat',
        'dessert' => 'Dessert',
    ];

    public const DIETARY_REGIMES = Labels::DIETARY_REGIME;

    public function __construct(
        private DishRepository $dishRepository
    ) {
    }

    public function getAllDishes(): array
    {
        return $this->dishRepository->findAll();
    }

    public function getDish(int $id): ?array
    {
        $dish = $this->dishRepository->findById($id);

        return $dish !== null && $dish['is_active'] ? $dish : null;
    }

    public function getAllergens(): array
    {
        return $this->dishRepository->findAllAllergens();
    }

    public function createDish(array $input): int
    {
        [$data, $allergenIds] = $this->validate($input);

        return $this->dishRepository->create($data, $allergenIds);
    }

    public function updateDish(int $id, array $input): void
    {
        if ($this->getDish($id) === null) {
            throw new InvalidArgumentException('Plat introuvable.');
        }

        [$data, $allergenIds] = $this->validate($input);
        $this->dishRepository->update($id, $data, $allergenIds);
    }

    /**
     * @return string[] Menus dont le plat a été retiré de l'affichage.
     */
    public function deleteDish(int $id): array
    {
        if ($this->getDish($id) === null) {
            throw new InvalidArgumentException('Plat introuvable.');
        }

        $menus = $this->dishRepository->findMenuTitlesUsingDish($id);
        $this->dishRepository->deactivate($id);

        return $menus;
    }

    /**
     * @return array{0: array<string, string>, 1: int[]}
     */
    private function validate(array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $category = (string) ($input['category'] ?? '');
        $regime = (string) ($input['dietary_regime'] ?? 'classic');
        $allergenIds = is_array($input['allergens'] ?? null)
            ? array_values(array_filter(array_map('intval', $input['allergens']), static fn (int $id): bool => $id > 0))
            : [];

        if ($name === '' || mb_strlen($name) > 180) {
            throw new InvalidArgumentException('Le nom du plat est requis (180 caractères maximum).');
        }

        if ($description === '') {
            throw new InvalidArgumentException('La description du plat est requise.');
        }

        if (!array_key_exists($category, self::CATEGORIES)) {
            throw new InvalidArgumentException('La catégorie doit être entrée, plat ou dessert.');
        }

        if (!array_key_exists($regime, self::DIETARY_REGIMES)) {
            throw new InvalidArgumentException('Régime alimentaire invalide.');
        }

        $existing = $this->dishRepository->existingAllergenIds($allergenIds);

        if (count($existing) !== count(array_unique($allergenIds))) {
            throw new InvalidArgumentException('Un des allergènes sélectionnés est inconnu.');
        }

        return [
            [
                'name' => $name,
                'description' => $description,
                'category' => $category,
                'dietary_regime' => $regime,
            ],
            $existing,
        ];
    }
}
