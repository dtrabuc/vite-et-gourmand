<?php
namespace App\Middleware;

use App\Service\CacheService;

class Security
{
    private CacheService $cacheService;

    private array $securityHeaders = [
        'X-Frame-Options' => 'DENY',
        'X-Content-Type-Options' => 'nosniff',
        'X-XSS-Protection' => '1; mode=block',
        'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'",
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
    ];

    private array $rateLimitRules = [
        '/login' => ['limit' => 5, 'window' => 300],
        '/register' => ['limit' => 3, 'window' => 300],
        '/password' => ['limit' => 3, 'window' => 300],
        '/forgot-password' => ['limit' => 3, 'window' => 300],
        '/reset-password' => ['limit' => 3, 'window' => 300],
    ];

    public function __construct(?CacheService $cacheService = null)
    {
        $this->cacheService = $cacheService ?? new CacheService();
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

        if (!$csrfToken || !$storedToken || !hash_equals($storedToken, $csrfToken)) {
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
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $endpoint = null;

        foreach (array_keys($this->rateLimitRules) as $path) {
            if ($uri === $path || strpos($uri, $path . '/') === 0) {
                $endpoint = $path;
                break;
            }
        }

        if ($endpoint === null) {
            return;
        }

        $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $rateLimitKey = "rate_limit:{$endpoint}:{$clientIp}";
        $rule = $this->rateLimitRules[$endpoint];
        $currentCount = (int) $this->cacheService->get($rateLimitKey, 0);

        if ($currentCount >= $rule['limit']) {
            http_response_code(429);
            header("Retry-After: {$rule['window']}");
            echo 'Trop de requêtes, réessayez plus tard.';
            exit;
        }

        $this->cacheService->set($rateLimitKey, $currentCount + 1, $rule['window']);
    }
}
