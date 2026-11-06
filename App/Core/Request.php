<?php
declare(strict_types=1);

namespace App\Core;

final class Request
{
    /**
     * Vrai pour les appels d'API (fetch avec Accept JSON, XHR, routes /api/ et /public/) :
     * les erreurs leur sont alors renvoyées en JSON plutôt qu'en page HTML.
     */
    public static function wantsJson(): bool
    {
        $path = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

        if (str_starts_with($path, '/api/') || str_starts_with($path, '/public/')) {
            return true;
        }

        if (strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest') {
            return true;
        }

        $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));

        return str_contains($accept, 'application/json') && !str_contains($accept, 'text/html');
    }
}
