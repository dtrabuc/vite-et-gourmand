<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\Menu;
use App\Repository\DishRepository;
use App\Repository\MenuRepository;
use InvalidArgumentException;

/**
 * Choix du client à l'intérieur d'un menu (prix inchangé) :
 * - répartir les convives entre les options d'une catégorie (ex. 10 kirs, 5 cocktails) ;
 * - remplacer un plat par un autre de la même catégorie, compatible avec le régime
 *   du menu (et sans alcool si le menu n'en contient pas) ;
 * - demander un plat sans tel allergène, pour une partie des convives (« variante »).
 *
 * Une ligne = un « emplacement » (plat d'origine du menu), le plat servi, un nombre
 * de convives et les allergènes à retirer. Chaque convive a une part de chaque
 * catégorie : la somme des lignes d'une catégorie vaut le nombre de convives,
 * sauf pour les accompagnements, tous servis (la somme vaut le nombre de convives
 * pour chaque accompagnement).
 */
final readonly class OrderCompositionService
{
    public const CATEGORY_LABELS = DishService::CATEGORIES;

    public function __construct(
        private MenuRepository $menuRepository,
        private DishRepository $dishRepository
    ) {
    }

    /**
     * Options proposées pour un menu (formulaire de commande).
     *
     * @return list<array{key: string, label: string, per_slot: bool, slots: list<array>, alternatives: list<array>}>
     */
    public function options(Menu $menu): array
    {
        [$slotsByCategory, $dishes] = $this->menuSlots($menu);
        $menuHasAlcohol = $this->menuHasAlcohol($slotsByCategory, $dishes);
        $result = [];

        foreach (self::CATEGORY_LABELS as $key => $label) {
            if (empty($slotsByCategory[$key])) {
                continue;
            }
            $inMenu = $slotsByCategory[$key];
            $alternatives = array_values(array_filter(
                $dishes,
                fn (array $dish): bool => $dish['category'] === $key
                    && !in_array($dish['id'], $inMenu, true)
                    && $this->compatible($menu, $dish, $menuHasAlcohol)
            ));

            $result[] = [
                'key' => $key,
                'label' => $label,
                'per_slot' => $key === 'side',
                'slots' => array_map(fn (int $id): array => $this->publicDish($dishes[$id]), $inMenu),
                'alternatives' => array_map(fn (array $dish): array => $this->publicDish($dish), $alternatives),
            ];
        }

        return $result;
    }

    /** Composition par défaut : convives répartis également entre les options. */
    public function defaults(Menu $menu, int $people): array
    {
        [$slotsByCategory, $dishes] = $this->menuSlots($menu);
        $rows = [];

        foreach ($slotsByCategory as $category => $slots) {
            $shares = $category === 'side'
                ? array_fill(0, count($slots), $people)
                : $this->split($people, count($slots));
            foreach ($slots as $index => $slot) {
                if ($shares[$index] > 0) {
                    $rows[] = $this->row($slot, $slot, $category, $shares[$index], []);
                }
            }
        }

        return $rows;
    }

    /**
     * Valide les choix envoyés par le formulaire. Sans choix : composition par défaut.
     *
     * @param mixed $raw items[n][slot|dish|quantity|without][] du formulaire
     * @return list<array{slot_dish_id: int, dish_id: int, category: string, quantity: int, without_allergens: list<int>}>
     */
    public function normalize(Menu $menu, int $people, mixed $raw): array
    {
        if (!is_array($raw) || $raw === []) {
            return $this->defaults($menu, $people);
        }

        [$slotsByCategory, $dishes] = $this->menuSlots($menu);
        $menuHasAlcohol = $this->menuHasAlcohol($slotsByCategory, $dishes);
        $slotCategory = [];
        foreach ($slotsByCategory as $category => $slots) {
            foreach ($slots as $slot) {
                $slotCategory[$slot] = $category;
            }
        }

        $rows = [];
        foreach (array_slice($raw, 0, 60) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $slot = (int) ($item['slot'] ?? 0);
            $dishId = (int) ($item['dish'] ?? $slot);
            $quantity = filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 999]]);

            if (!isset($slotCategory[$slot])) {
                throw new InvalidArgumentException('Un choix ne correspond pas au menu commandé.');
            }
            if ($quantity === false) {
                throw new InvalidArgumentException('Le nombre de convives d’un choix est invalide.');
            }
            if ($quantity === 0) {
                continue;
            }

            $category = $slotCategory[$slot];
            $dish = $dishes[$dishId] ?? null;
            if ($dish === null || $dish['category'] !== $category) {
                throw new InvalidArgumentException('Un plat de remplacement doit être de la même catégorie et disponible.');
            }
            if ($dishId !== $slot && !in_array($dishId, $slotsByCategory[$category], true) && !$this->compatible($menu, $dish, $menuHasAlcohol)) {
                throw new InvalidArgumentException('« ' . $dish['name'] . ' » ne peut pas être servi dans ce menu (régime alimentaire, ou alcool dans un menu qui n’en contient pas).');
            }

            $without = is_array($item['without'] ?? null) ? array_map('intval', $item['without']) : [];
            $without = array_values(array_unique(array_intersect($without, $dish['allergen_ids'])));
            sort($without);

            $rows[] = $this->row($slot, $dishId, $category, $quantity, $without);
        }

        // chaque convive a une part de chaque catégorie
        foreach ($slotsByCategory as $category => $slots) {
            if ($category === 'side') {
                foreach ($slots as $slot) {
                    $sum = array_sum(array_column(array_filter($rows, static fn (array $r): bool => $r['slot_dish_id'] === $slot), 'quantity'));
                    if ($sum !== $people) {
                        throw new InvalidArgumentException(sprintf(
                            'Accompagnement « %s » : %d part(s) pour %d convive(s).',
                            $dishes[$slot]['name'],
                            $sum,
                            $people
                        ));
                    }
                }
                continue;
            }

            $sum = array_sum(array_column(array_filter($rows, static fn (array $r): bool => $r['category'] === $category), 'quantity'));
            if ($sum !== $people) {
                throw new InvalidArgumentException(sprintf(
                    '%s : %d choix pour %d convive(s). Répartissez tous les convives.',
                    self::CATEGORY_LABELS[$category],
                    $sum,
                    $people
                ));
            }
        }

        return $rows;
    }

    /**
     * Nouveau nombre de convives : les choix sont gardés et les parts recalculées
     * au prorata (méthode du plus fort reste).
     */
    public function rescale(array $rows, int $oldPeople, int $people): array
    {
        if ($rows === [] || $oldPeople === $people || $oldPeople < 1) {
            return $rows;
        }

        $groups = [];
        foreach ($rows as $index => $row) {
            $key = $row['category'] === 'side' ? 'side:' . $row['slot_dish_id'] : $row['category'];
            $groups[$key][] = $index;
        }

        foreach ($groups as $indexes) {
            $exact = [];
            foreach ($indexes as $index) {
                $exact[$index] = $rows[$index]['quantity'] * $people / $oldPeople;
                $rows[$index]['quantity'] = (int) floor($exact[$index]);
            }
            $missing = $people - array_sum(array_map(static fn (int $i): int => $rows[$i]['quantity'], $indexes));
            uasort($exact, static fn (float $a, float $b): int => ($b - floor($b)) <=> ($a - floor($a)));
            foreach (array_keys($exact) as $index) {
                if ($missing-- <= 0) {
                    break;
                }
                $rows[$index]['quantity']++;
            }
        }

        return array_values(array_filter($rows, static fn (array $row): bool => $row['quantity'] > 0));
    }

    /**
     * Texte lisible, une ligne par catégorie (récapitulatifs, e-mails).
     *
     * @param list<array{category: string, quantity: int, dish_name: string, without_names?: list<string>}> $items
     * @return list<string>
     */
    public static function describe(array $items): array
    {
        $lines = [];
        foreach (self::CATEGORY_LABELS as $key => $label) {
            $parts = [];
            foreach ($items as $item) {
                if ($item['category'] !== $key) {
                    continue;
                }
                $without = $item['without_names'] ?? [];
                $parts[] = $item['quantity'] . ' × ' . $item['dish_name']
                    . ($without !== [] ? ' (sans ' . mb_strtolower(implode(', ', $without), 'UTF-8') . ')' : '');
            }
            if ($parts !== []) {
                $lines[] = $label . ' : ' . implode(', ', $parts);
            }
        }

        return $lines;
    }

    /** Ajoute le nom des plats et des allergènes retirés aux lignes validées. */
    public function withNames(array $rows): array
    {
        $dishes = $this->allDishes();
        $allergens = [];
        foreach ($this->dishRepository->findAllAllergens() as $allergen) {
            $allergens[(int) $allergen['id']] = (string) $allergen['name'];
        }

        return array_map(static fn (array $row): array => $row + [
            'dish_name' => $dishes[$row['dish_id']]['name'] ?? '?',
            'without_names' => array_map(static fn (int $id): string => $allergens[$id] ?? '?', $row['without_allergens']),
        ], $rows);
    }

    /** @return array{0: array<string, list<int>>, 1: array<int, array>} emplacements par catégorie, plats actifs par id */
    private function menuSlots(Menu $menu): array
    {
        $dishes = $this->allDishes();
        $slotsByCategory = [];
        foreach ($this->menuRepository->findDishIds($menu->getId()) as $id) {
            if (isset($dishes[$id])) {
                $slotsByCategory[$dishes[$id]['category']][] = $id;
            }
        }

        // ordre des catégories : apéritif → digestif
        $ordered = [];
        foreach (array_keys(self::CATEGORY_LABELS) as $key) {
            if (isset($slotsByCategory[$key])) {
                $ordered[$key] = $slotsByCategory[$key];
            }
        }

        return [$ordered, $dishes];
    }

    private function allDishes(): array
    {
        $dishes = [];
        foreach ($this->dishRepository->findAll() as $dish) {
            $dishes[(int) $dish['id']] = $dish;
        }

        return $dishes;
    }

    private function menuHasAlcohol(array $slotsByCategory, array $dishes): bool
    {
        foreach ($slotsByCategory as $slots) {
            foreach ($slots as $slot) {
                if (!empty($dishes[$slot]['contains_alcohol'])) {
                    return true;
                }
            }
        }

        return false;
    }

    /** Remplacement possible : même régime (ou plus strict) et pas d'alcool ajouté à un menu qui n'en a pas. */
    private function compatible(Menu $menu, array $dish, bool $menuHasAlcohol): bool
    {
        if (!$menuHasAlcohol && !empty($dish['contains_alcohol'])) {
            return false;
        }

        return match ($menu->getDietaryRegime()) {
            'vegan' => $dish['dietary_regime'] === 'vegan',
            'vegetarian' => in_array($dish['dietary_regime'], ['vegetarian', 'vegan'], true),
            default => true,
        };
    }

    private function publicDish(array $dish): array
    {
        return [
            'id' => (int) $dish['id'],
            'name' => (string) $dish['name'],
            'description' => (string) $dish['description'],
            'allergens' => array_map(
                static fn (int $id, string $name): array => ['id' => $id, 'name' => $name],
                $dish['allergen_ids'],
                $dish['allergens']
            ),
        ];
    }

    private function row(int $slot, int $dishId, string $category, int $quantity, array $without): array
    {
        return [
            'slot_dish_id' => $slot,
            'dish_id' => $dishId,
            'category' => $category,
            'quantity' => $quantity,
            'without_allergens' => $without,
        ];
    }

    /** @return list<int> $people réparti en $parts parts (les premières reçoivent le reste) */
    private function split(int $people, int $parts): array
    {
        $base = intdiv($people, $parts);
        $rest = $people % $parts;

        return array_map(static fn (int $i): int => $base + ($i < $rest ? 1 : 0), range(0, $parts - 1));
    }
}
