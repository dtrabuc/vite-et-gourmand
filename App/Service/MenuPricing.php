<?php
declare(strict_types=1);

namespace App\Service;

use InvalidArgumentException;

/**
 * Règles de prix des menus, au même endroit pour le catalogue, la fiche menu,
 * la commande et les e-mails.
 *
 * - base_price = prix du menu pour son nombre minimum de personnes ;
 * - prix par personne = base_price / min_people ;
 * - total = prix par personne × nombre de convives ;
 * - remise de 10 % à partir de min_people + 5 convives.
 */
final class MenuPricing
{
    public const DISCOUNT_RATE = 10.0;
    public const DISCOUNT_EXTRA_PEOPLE = 5;

    public static function perPerson(float $basePrice, int $minPeople): float
    {
        if ($basePrice < 0 || $minPeople < 1) {
            throw new InvalidArgumentException('Paramètres de tarification invalides.');
        }

        return round($basePrice / $minPeople, 2);
    }

    /** Nombre de convives à partir duquel la remise s'applique. */
    public static function discountFrom(int $minPeople): int
    {
        return $minPeople + self::DISCOUNT_EXTRA_PEOPLE;
    }

    /**
     * @return array{people: int, orderable: bool, per_person: float, gross: float,
     *               discount_rate: float, discount_amount: float, total: float}
     */
    public static function quote(float $basePrice, int $minPeople, int $people): array
    {
        if ($people < 1) {
            throw new InvalidArgumentException('Paramètres de tarification invalides.');
        }

        $perPerson = self::perPerson($basePrice, $minPeople);
        $gross = round(($basePrice / $minPeople) * $people, 2);
        $rate = $people >= self::discountFrom($minPeople) ? self::DISCOUNT_RATE : 0.0;
        $total = round($gross * (1 - $rate / 100), 2);

        return [
            'people' => $people,
            'orderable' => $people >= $minPeople,
            'per_person' => $perPerson,
            'gross' => $gross,
            'discount_rate' => $rate,
            'discount_amount' => round($gross - $total, 2),
            'total' => $total,
        ];
    }

    public static function format(float $amount): string
    {
        return number_format($amount, 2, ',', ' ') . ' €';
    }
}
