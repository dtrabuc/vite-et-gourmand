<?php
declare(strict_types=1);

namespace App\Service;

use InvalidArgumentException;

/**
 * Frais de livraison : gratuits dans Bordeaux, sinon 5 € + 0,59 € par kilomètre.
 * La distance est calculée par le serveur (API Google Distance Matrix) depuis
 * l'adresse de l'entreprise ; elle n'est jamais saisie par le client.
 */
final readonly class DeliveryDistanceService
{
    /** Adresse, ville et code postal de l'entreprise (point de départ des livraisons). */
    public const COMPANY_ADDRESS = ['12 Quai des Chartrons', 'Bordeaux', '33000'];

    private const BORDEAUX_POSTAL_CODES = ['33000', '33100', '33200', '33300', '33800'];

    private const BASE_FEE = 5.00;
    private const PRICE_PER_KM = 0.59;

    private const ENDPOINT = 'https://maps.googleapis.com/maps/api/distancematrix/json';

    public function __construct(
        private string $apiKey,
        private CacheService $cacheService
    ) {
    }

    /**
     * Bordeaux est reconnu par le nom de la ville (sans tenir compte de la casse,
     * des accents et des espaces) ou par l'un de ses codes postaux.
     */
    public function isBordeaux(string $city, string $postalCode): bool
    {
        return $this->normalize($city) === 'bordeaux'
            || in_array(preg_replace('/\s+/', '', $postalCode) ?? '', self::BORDEAUX_POSTAL_CODES, true);
    }

    /**
     * @return array{distance_km: ?float, cost: float} distance_km vaut null dans Bordeaux.
     *
     * @throws InvalidArgumentException si l'adresse est incomplète ou si la distance
     *                                  ne peut pas être calculée (la commande est alors refusée).
     */
    public function quote(string $address, string $postalCode, string $city): array
    {
        $address = trim($address);
        $postalCode = trim($postalCode);
        $city = trim($city);

        if ($address === '' || $postalCode === '' || $city === '') {
            throw new InvalidArgumentException('L’adresse, le code postal et la ville de livraison sont requis.');
        }

        if ($this->isBordeaux($city, $postalCode)) {
            return ['distance_km' => null, 'cost' => 0.00];
        }

        $distanceKm = $this->distanceKm($address . ', ' . $postalCode . ' ' . $city . ', France');

        return [
            'distance_km' => $distanceKm,
            'cost' => round(self::BASE_FEE + (self::PRICE_PER_KM * $distanceKm), 2),
        ];
    }

    private function distanceKm(string $destination): float
    {
        $cacheKey = 'delivery_distance:' . $this->normalize($destination);
        $cached = $this->cacheService->get($cacheKey);

        if (is_float($cached) || is_int($cached)) {
            return (float) $cached;
        }

        $failure = 'Impossible de calculer la distance de livraison pour cette adresse. '
            . 'Vérifiez l’adresse ou contactez-nous : la commande ne peut pas être enregistrée sans ce calcul.';

        if ($this->apiKey === '') {
            error_log('Distance Matrix : GOOGLE_MAPS_API_KEY absente.');
            throw new InvalidArgumentException($failure);
        }

        [$street, $city, $postalCode] = self::COMPANY_ADDRESS;
        $url = self::ENDPOINT . '?' . http_build_query([
            'origins' => $street . ', ' . $postalCode . ' ' . $city . ', France',
            'destinations' => $destination,
            'mode' => 'driving',
            'units' => 'metric',
            'language' => 'fr',
            'key' => $this->apiKey,
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 5,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        $decoded = is_string($response) ? json_decode($response, true) : null;
        $element = $decoded['rows'][0]['elements'][0] ?? null;

        if (
            !is_array($decoded)
            || ($decoded['status'] ?? '') !== 'OK'
            || !is_array($element)
            || ($element['status'] ?? '') !== 'OK'
            || !isset($element['distance']['value'])
        ) {
            error_log(sprintf(
                'Distance Matrix en échec (statut %s / %s) pour « %s ».',
                is_array($decoded) ? (string) ($decoded['status'] ?? '?') : 'réponse invalide',
                is_array($element) ? (string) ($element['status'] ?? '?') : '?',
                $destination
            ));
            throw new InvalidArgumentException($failure);
        }

        $distanceKm = round(((float) $element['distance']['value']) / 1000, 2);
        $this->cacheService->set($cacheKey, $distanceKm, 30 * 24 * 3600);

        return $distanceKm;
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
