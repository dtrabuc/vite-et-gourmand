<?php
declare(strict_types=1);

namespace App\Service;

use RuntimeException;

/**
 * Adresses et distances à partir d'OpenStreetMap, sans clé d'API :
 * - Photon (photon.komoot.io) : suggestions et recherche d'adresse ;
 * - OSRM (router.project-osrm.org) : distance par la route.
 *
 * Les réponses sont gardées en cache pour ménager ces services publics.
 * Un service injoignable lève une RuntimeException (la saisie manuelle prend alors le relais).
 */
final readonly class GeoService
{
    private const PHOTON = 'https://photon.komoot.io/api/';
    private const OSRM = 'https://router.project-osrm.org/route/v1/driving/';
    // les services OSM demandent un User-Agent qui identifie l'application
    private const USER_AGENT = 'ViteEtGourmand/1.0 (site de demonstration, +https://dylantrabuc.alwaysdata.net)';

    public function __construct(
        private CacheService $cacheService
    ) {
    }

    /**
     * Suggestions d'adresses en France, les plus proches de Bordeaux d'abord.
     *
     * @return list<array{label: string, street: string, postal_code: string, city: string, lat: float, lon: float, precision: string}>
     */
    public function suggest(string $query): array
    {
        $key = 'geo_suggest:' . md5(mb_strtolower($query));
        $cached = $this->cacheService->get($key);
        if (is_array($cached)) {
            return $cached;
        }

        $results = $this->photon($query, 6);
        $this->cacheService->set($key, $results, 24 * 3600);

        return $results;
    }

    /**
     * Recherche d'une adresse précise. Renvoie null si OpenStreetMap ne la connaît pas.
     *
     * @return array{label: string, street: string, postal_code: string, city: string, lat: float, lon: float, precision: string}|null
     */
    public function geocode(string $street, string $postalCode, string $city): ?array
    {
        $query = trim($street . ', ' . $postalCode . ' ' . $city, ' ,');
        $key = 'geo_geocode_v3:' . md5(mb_strtolower($query));
        $cached = $this->cacheService->get($key);
        if (is_array($cached)) {
            return $cached['result'];
        }

        $best = null;
        foreach ($this->photon($query, 5) as $candidate) {
            // même code postal que celui saisi, quand il y en a un
            if ($postalCode !== '' && $candidate['postal_code'] !== '' && $candidate['postal_code'] !== $postalCode) {
                continue;
            }
            // une autre rue de la même commune ne vaut pas vérification
            if ($street !== '' && $candidate['precision'] !== 'city' && !self::sameStreet($street, $candidate['street'])) {
                continue;
            }
            $best = $candidate;
            break;
        }

        $this->cacheService->set($key, ['result' => $best], 30 * 24 * 3600);

        return $best;
    }

    /** Distance par la route en km, ou null si OSRM ne trouve pas d'itinéraire. */
    public function drivingKm(float $fromLat, float $fromLon, float $toLat, float $toLon): ?float
    {
        $key = sprintf('geo_route:%.5f,%.5f;%.5f,%.5f', $fromLat, $fromLon, $toLat, $toLon);
        $cached = $this->cacheService->get($key);
        if (is_float($cached) || is_int($cached)) {
            return (float) $cached;
        }

        $url = self::OSRM . sprintf('%.6F,%.6F;%.6F,%.6F', $fromLon, $fromLat, $toLon, $toLat) . '?overview=false';
        $data = $this->getJson($url);
        $meters = $data['routes'][0]['distance'] ?? null;

        if (($data['code'] ?? '') !== 'Ok' || !is_numeric($meters)) {
            return null;
        }

        $km = round(((float) $meters) / 1000, 2);
        $this->cacheService->set($key, $km, 30 * 24 * 3600);

        return $km;
    }

    /**
     * Même rue ? Compare les mots significatifs (sans numéro, type de voie ni articles) :
     * « 1 rue de la Mairie » et « Rue de la Mairie » oui, « Place Géraud Lavergne » non.
     */
    public static function sameStreet(string $typed, string $found): bool
    {
        $words = static function (string $value): array {
            $value = mb_strtolower($value, 'UTF-8');
            $value = strtr($value, ['é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'à' => 'a', 'â' => 'a',
                'î' => 'i', 'ï' => 'i', 'ô' => 'o', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ç' => 'c', 'œ' => 'oe']);
            $stop = ['rue', 'avenue', 'av', 'bd', 'boulevard', 'chemin', 'ch', 'allee', 'place', 'pl', 'impasse',
                'imp', 'route', 'rte', 'quai', 'cours', 'square', 'lotissement', 'residence', 'voie', 'passage',
                'de', 'du', 'des', 'la', 'le', 'les', 'l', 'd', 'et', 'bis', 'ter', 'a', 'au', 'aux'];
            $parts = preg_split('/[^a-z0-9]+/', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];

            return array_values(array_filter($parts, static fn (string $w): bool => !ctype_digit($w) && !in_array($w, $stop, true)));
        };

        $a = $words($typed);
        $b = $words($found);
        if ($a === [] || $b === []) {
            return false;
        }

        // chaque mot significatif saisi doit se retrouver (une faute de frappe tolérée)
        foreach ($a as $word) {
            $match = false;
            foreach ($b as $other) {
                if ($word === $other || (strlen($word) > 4 && levenshtein($word, $other) <= 1)) {
                    $match = true;
                    break;
                }
            }
            if (!$match) {
                return false;
            }
        }

        return true;
    }

    /** Distance à vol d'oiseau (formule de haversine), en km. */
    public static function crowKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $rad = M_PI / 180;
        $a = sin(($lat2 - $lat1) * $rad / 2) ** 2
            + cos($lat1 * $rad) * cos($lat2 * $rad) * sin(($lon2 - $lon1) * $rad / 2) ** 2;

        return round(6371 * 2 * atan2(sqrt($a), sqrt(1 - $a)), 2);
    }

    /** @return list<array{label: string, street: string, postal_code: string, city: string, lat: float, lon: float, precision: string}> */
    private function photon(string $query, int $limit): array
    {
        $url = self::PHOTON . '?' . http_build_query([
            'q' => $query,
            'lang' => 'fr',
            'limit' => $limit + 4,
            // priorité aux résultats proches de Bordeaux
            'lat' => DeliveryDistanceService::COMPANY_COORDS[0],
            'lon' => DeliveryDistanceService::COMPANY_COORDS[1],
        ]);

        $data = $this->getJson($url);
        $results = [];

        foreach ($data['features'] ?? [] as $feature) {
            $p = $feature['properties'] ?? [];
            $coords = $feature['geometry']['coordinates'] ?? null;
            if (($p['countrycode'] ?? '') !== 'FR' || !is_array($coords) || count($coords) < 2) {
                continue;
            }

            $type = (string) ($p['type'] ?? '');
            $precision = match ($type) {
                'house' => 'house',
                'street' => 'street',
                default => 'city',
            };
            $street = match ($precision) {
                'house' => trim(($p['housenumber'] ?? '') . ' ' . ($p['street'] ?? $p['name'] ?? '')),
                'street' => (string) ($p['name'] ?? $p['street'] ?? ''),
                default => '',
            };
            $city = (string) ($p['city'] ?? ($type === 'city' ? ($p['name'] ?? '') : ($p['locality'] ?? '')));
            $postalCode = (string) ($p['postcode'] ?? '');
            if ($city === '') {
                continue;
            }

            $label = trim(($street !== '' ? $street . ', ' : '') . $postalCode . ' ' . $city);
            $results[$label] = [
                'label' => $label,
                'street' => $street,
                'postal_code' => $postalCode,
                'city' => $city,
                'lat' => (float) $coords[1],
                'lon' => (float) $coords[0],
                'precision' => $precision,
            ];
        }

        return array_slice(array_values($results), 0, $limit);
    }

    private function getJson(string $url): array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'User-Agent: ' . self::USER_AGENT . "\r\nAccept: application/json",
                'timeout' => 4,
                'ignore_errors' => true,
                'follow_location' => 0,
            ],
        ]);

        // une seconde tentative si le service public est momentanément saturé (429, 503)
        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $response = @file_get_contents($url, false, $context);
            $status = isset($http_response_header[0]) && preg_match('/\s(\d{3})\s?/', $http_response_header[0], $m) ? (int) $m[1] : 0;
            if (!in_array($status, [429, 502, 503], true)) {
                break;
            }
            usleep(400000);
        }
        $decoded = is_string($response) ? json_decode($response, true) : null;

        if ($status !== 200 || !is_array($decoded)) {
            error_log(sprintf('Service OpenStreetMap injoignable (HTTP %d) : %s', $status, parse_url($url, PHP_URL_HOST)));
            throw new RuntimeException('Service de cartographie indisponible.');
        }

        return $decoded;
    }
}
