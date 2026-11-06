<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Service\RateLimiter;

class Security
{
    private readonly RateLimiter $rateLimiter;

    private array $securityHeaders = [
        'X-Frame-Options' => 'DENY',
        'X-Content-Type-Options' => 'nosniff',
        'X-XSS-Protection' => '1; mode=block',
        'Content-Security-Policy' => "default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline'; img-src 'self' data: https://images.unsplash.com; font-src 'self' data:",
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
    ];

    public function __construct(RateLimiter $rateLimiter)
    {
        $this->rateLimiter = $rateLimiter;
    }

    public function __invoke(): void
    {
        $this->applySecurityHeaders();
        $this->handleCsrfProtection();
        $this->handleRateLimiting();
    }

    private function applySecurityHeaders(): void
    {
        foreach ($this->securityHeaders as $header => $value) {
            header("$header: $value");
        }

        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }

    private function handleCsrfProtection(): void
    {
        if (!in_array($_SERVER['REQUEST_METHOD'] ?? '', ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }

        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? null);
        $storedToken = $_SESSION['csrf_token'] ?? null;

        if (!is_string($csrfToken) || !is_string($storedToken) || !hash_equals($storedToken, $csrfToken)) {
            http_response_code(403);
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
            } else {
                echo 'Invalid CSRF token';
            }
            exit;
        }
    }

    private function handleRateLimiting(): void
    {
        if (!in_array($_SERVER['REQUEST_METHOD'] ?? '', ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }

        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $endpoint = $this->rateLimiter->endpointFor($uri);

        if ($endpoint === null) {
            return;
        }

        if ($this->rateLimiter->tooManyAttempts($endpoint)) {
            http_response_code(429);
            header('Retry-After: ' . $this->rateLimiter->window($endpoint));
            echo 'Trop de requêtes, réessayez plus tard.';
            exit;
        }

        // Connexions : seuls les échecs sont comptés, par les contrôleurs.
        if (!$this->rateLimiter->countsFailuresOnly($endpoint)) {
            $this->rateLimiter->hit($endpoint);
        }
    }
}
