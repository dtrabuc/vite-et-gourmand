<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Response;
use App\Service\DeliveryDistanceService;
use App\Service\GeoService;
use App\Service\MenuPricing;
use App\Service\RateLimiter;
use RuntimeException;

/**
 * Adresses de livraison (OpenStreetMap) : suggestions pendant la saisie,
 * puis vérification de l'adresse choisie avec la zone et les frais.
 */
final class AddressController extends BaseController
{
    public function __construct(
        private readonly RateLimiter $rateLimiter,
        private readonly GeoService $geoService,
        private readonly DeliveryDistanceService $deliveryDistanceService
    ) {
    }

    public function autocomplete(): never
    {
        $query = trim((string) ($_GET['q'] ?? ''));
        $this->limit('/api/address/autocomplete');

        if (mb_strlen($query) < 4 || mb_strlen($query) > 200) {
            Response::json(['success' => true, 'data' => []]);
        }

        try {
            $items = $this->geoService->suggest($query);
        } catch (RuntimeException) {
            // la page propose alors la saisie manuelle
            Response::json(['success' => true, 'data' => [], 'unavailable' => true]);
        }

        Response::json(['success' => true, 'data' => $items]);
    }

    public function check(): never
    {
        $street = trim((string) ($_GET['address'] ?? ''));
        $postalCode = trim((string) ($_GET['postal_code'] ?? ''));
        $city = trim((string) ($_GET['city'] ?? ''));
        $this->limit('/api/address/check');

        if ($street === '' || $postalCode === '' || $city === '' || mb_strlen($street . $postalCode . $city) > 300) {
            Response::json(['success' => false, 'error' => 'Renseignez l’adresse, le code postal et la ville.'], 422);
        }

        $result = $this->deliveryDistanceService->check($street, $postalCode, $city);
        $result['message'] = $this->message($result);
        $result['zone_radius_km'] = DeliveryDistanceService::ZONE_RADIUS_KM;

        Response::json(['success' => true, 'data' => $result]);
    }

    private function limit(string $route): void
    {
        if ($this->rateLimiter->tooManyAttempts($route)) {
            Response::json(['success' => false, 'error' => 'Trop de recherches, réessayez dans quelques minutes.'], 429);
        }
        $this->rateLimiter->hit($route);
    }

    private function message(array $r): string
    {
        $km = static fn (?float $value): string => number_format((float) $value, 0, ',', ' ') . ' km';

        return match (true) {
            $r['status'] === 'unavailable' => 'Vérification impossible pour le moment : vous pouvez confirmer votre adresse manuellement.',
            $r['status'] === 'not_found' => 'Adresse introuvable sur la carte. Corrigez-la ou confirmez-la manuellement.',
            $r['zone'] === 'bordeaux' => 'Adresse vérifiée à Bordeaux : livraison offerte.',
            $r['zone'] === 'zone' => sprintf('Adresse vérifiée, à %s de Bordeaux : livraison %s.', $km($r['crow_km']), MenuPricing::format((float) $r['cost'])),
            default => sprintf(
                'Adresse à %s de Bordeaux, hors de notre zone de livraison (%d km). Retrait gratuit à Bordeaux, ou livraison hors zone : %s.',
                $km($r['crow_km']),
                (int) DeliveryDistanceService::ZONE_RADIUS_KM,
                MenuPricing::format((float) $r['cost'])
            ),
        } . ($r['status'] === 'approximate' ? ' Rue non trouvée : frais calculés depuis la commune.' : '');
    }
}
