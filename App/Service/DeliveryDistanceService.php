<?php
declare(strict_types=1);

namespace App\Service;

use InvalidArgumentException;
use RuntimeException;

/**
 * Vérification de l'adresse et frais de livraison (données OpenStreetMap, voir GeoService).
 *
 * - Bordeaux : livraison offerte ;
 * - jusqu'à 25 km à vol d'oiseau : 5 € + 0,59 € par km de route ;
 * - au-delà : hors zone. Le client choisit le retrait (gratuit) ou accepte
 *   la livraison hors zone, au même tarif kilométrique ;
 * - adresse introuvable ou service indisponible : saisie manuelle possible,
 *   les frais sont alors estimés depuis la commune, ou confirmés par l'équipe.
 */
final readonly class DeliveryDistanceService
{
    /** Adresse, ville et code postal de l'entreprise (point de départ des livraisons). */
    public const COMPANY_ADDRESS = ['12 Quai des Chartrons', 'Bordeaux', '33000'];
    /** Coordonnées OpenStreetMap du 12 quai des Chartrons. */
    public const COMPANY_COORDS = [44.8494341, -0.5705344];

    public const ZONE_RADIUS_KM = 25.0;
    public const BASE_FEE = 5.00;
    public const PRICE_PER_KM = 0.59;
    /** Route ≈ vol d'oiseau × 1,3 quand OSRM ne répond pas. */
    private const ROAD_FACTOR = 1.3;

    private const BORDEAUX_POSTAL_CODES = ['33000', '33100', '33200', '33300', '33800'];

    public function __construct(
        private GeoService $geoService
    ) {
    }

    public function isBordeaux(string $city, string $postalCode): bool
    {
        return $this->normalize($city) === 'bordeaux'
            || in_array(preg_replace('/\s+/', '', $postalCode) ?? '', self::BORDEAUX_POSTAL_CODES, true);
    }

    /**
     * Vérifie une adresse pour l'affichage (sans rien refuser).
     *
     * @return array{status: string, label: ?string, street: ?string, postal_code: ?string, city: ?string,
     *               zone: ?string, crow_km: ?float, distance_km: ?float, estimated: bool, cost: ?float}
     *         status : verified (adresse trouvée), approximate (seule la commune est trouvée),
     *                  not_found, unavailable (service injoignable).
     */
    public function check(string $street, string $postalCode, string $city): array
    {
        $empty = ['label' => null, 'street' => null, 'postal_code' => null, 'city' => null,
            'zone' => null, 'crow_km' => null, 'distance_km' => null, 'estimated' => false, 'cost' => null];

        try {
            $place = $this->geoService->geocode($street, $postalCode, $city);
            $status = 'verified';
            // l'adresse exacte est inconnue : on se rabat sur la commune
            if ($place === null || $place['precision'] === 'city') {
                $place = $place ?? $this->geoService->geocode('', $postalCode, $city);
                $status = $place === null ? 'not_found' : 'approximate';
            }
        } catch (RuntimeException) {
            return ['status' => 'unavailable'] + $empty;
        }

        if ($place === null) {
            return ['status' => 'not_found'] + $empty;
        }

        return ['status' => $status] + $this->price($place);
    }

    /**
     * Frais de livraison retenus pour une commande. Refuse (exception) une adresse
     * non vérifiée sans saisie manuelle, ou une adresse hors zone non acceptée.
     *
     * @return array{distance_km: ?float, cost: float, zone: ?string, address_status: string, cost_pending: bool}
     */
    public function quote(string $street, string $postalCode, string $city, bool $manual = false, bool $acceptOutside = false): array
    {
        $street = trim($street);
        $postalCode = trim($postalCode);
        $city = trim($city);

        if ($street === '' || $postalCode === '' || $city === '') {
            throw new InvalidArgumentException('L’adresse, le code postal et la ville de livraison sont requis.');
        }

        $check = $this->check($street, $postalCode, $city);

        if ($check['status'] !== 'verified' && !$manual) {
            throw new InvalidArgumentException(match ($check['status']) {
                'unavailable' => 'La vérification des adresses est indisponible pour le moment. Cochez « Mon adresse est correcte » pour la saisir manuellement.',
                default => 'Adresse introuvable sur la carte. Corrigez-la, choisissez une suggestion, ou cochez « Mon adresse est correcte ».',
            });
        }

        // service injoignable ou commune inconnue : Bordeaux reste gratuit, sinon frais à confirmer
        if ($check['zone'] === null) {
            $bordeaux = $this->isBordeaux($city, $postalCode);

            return [
                'distance_km' => null,
                'cost' => 0.00,
                'zone' => $bordeaux ? 'bordeaux' : null,
                'address_status' => 'manual',
                'cost_pending' => !$bordeaux,
            ];
        }

        if ($check['zone'] === 'outside' && !$acceptOutside) {
            throw new InvalidArgumentException(sprintf(
                'Cette adresse est à %s km de Bordeaux, hors de notre zone de livraison (%d km). '
                . 'Choisissez le retrait à Bordeaux (gratuit) ou acceptez la livraison hors zone (%s).',
                number_format((float) $check['crow_km'], 0, ',', ' '),
                (int) self::ZONE_RADIUS_KM,
                MenuPricing::format((float) $check['cost'])
            ));
        }

        return [
            'distance_km' => $check['distance_km'],
            'cost' => (float) $check['cost'],
            'zone' => $check['zone'],
            'address_status' => $check['status'] === 'verified' ? 'verified' : 'manual',
            'cost_pending' => false,
        ];
    }

    /** Zone et frais pour un lieu trouvé sur la carte. */
    private function price(array $place): array
    {
        [$lat0, $lon0] = self::COMPANY_COORDS;
        $crow = GeoService::crowKm($lat0, $lon0, $place['lat'], $place['lon']);
        $base = [
            'label' => $place['label'],
            'street' => $place['street'],
            'postal_code' => $place['postal_code'],
            'city' => $place['city'],
            'crow_km' => $crow,
        ];

        if ($this->isBordeaux($place['city'], $place['postal_code'])) {
            return $base + ['zone' => 'bordeaux', 'distance_km' => null, 'estimated' => false, 'cost' => 0.00];
        }

        try {
            $road = $this->geoService->drivingKm($lat0, $lon0, $place['lat'], $place['lon']);
        } catch (RuntimeException) {
            $road = null;
        }
        $estimated = $road === null;
        $road ??= round($crow * self::ROAD_FACTOR, 2);

        return $base + [
            'zone' => $crow <= self::ZONE_RADIUS_KM ? 'zone' : 'outside',
            'distance_km' => $road,
            'estimated' => $estimated,
            'cost' => round(self::BASE_FEE + self::PRICE_PER_KM * $road, 2),
        ];
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');
        $value = strtr($value, [
            'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'î' => 'i', 'ï' => 'i', 'í' => 'i',
            'ô' => 'o', 'ö' => 'o', 'ó' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u',
            'ç' => 'c', 'ÿ' => 'y', 'œ' => 'oe', 'æ' => 'ae',
        ]);

        return trim(preg_replace('/[\s\-\'’,]+/u', ' ', $value) ?? $value);
    }
}
