<?php
namespace Src\Middleware;

use Src\Service\CacheService;

class Security
{
    private CacheService $cacheService;
    private array $securityHeaders = [
        'X-Frame-Options' => 'DENY',
        'X-Content-Type-Options' => 'nosniff',
        'X-XSS-Protection' => '1; mode=block',
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
        'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'",
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()'
    ];
    private array $rateLimitRules = [
        'login' => ['limit' => 5, 'window' => 300], // 5 attempts per 5 minutes
        'register' => ['limit' => 3, 'window' => 300], // 3 attempts per 5 minutes
        'password' => ['limit' => 3, 'window' => 300], // 3 attempts per 5 minutes
    ];

    public function __construct(CacheService $cacheService = null)
    {
        $this->cacheService = $cacheService ?? new CacheService();
    }

    public function __invoke(): void
    {
        // Apply security headers
        $this->applySecurityHeaders();

        // Handle CSRF protection for state-changing methods
        $this->handleCsrfProtection();

        // Handle rate limiting for authentication endpoints
        $this->handleRateLimiting();
    }

    private function applySecurityHeaders(): void
    {
        foreach ($this->securityHeaders as $header => $value) {
            header("$header: $value");
        }
    }

    private function handleCsrfProtection(): void
    {
        // Only apply CSRF protection to state-changing methods
        $stateChangingMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];
        if (!in_array($_SERVER['REQUEST_METHOD'] ?? '', $stateChangingMethods, true)) {
            return;
        }

        // Skip CSRF check for certain public endpoints if needed
        $skipCsrfCheck = [
            '/public/catalog',
            '/public/reviews',
            '/menus',
            '/menu/',
            '/reviews/homepage'
        ];

        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        foreach ($skipCsrfCheck as $skipEndpoint) {
            if (strpos($uri, $skipEndpoint) === 0) {
                return; // Skip CSRF check for these endpoints
            }
        }

        // For AJAX requests, check header; for form posts, check POST data
        $csrfToken = null;
        if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'];
        } elseif (!empty($_POST['csrf_token'])) {
            $csrfToken = $_POST['csrf_token'];
        }

        $storedToken = $_SESSION['csrf_token'] ?? null;

        if (!$csrfToken || !$storedToken || !hash_equals($storedToken, $csrfToken)) {
            http_response_code(403);
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
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
        // Only apply rate limiting to specific authentication endpoints
        $rateLimitedPaths = ['/login', '/register', '/password', '/forgot-password', '/reset-password'];
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);

        $endpoint = null;
        foreach ($rateLimitedPaths as $path) {
            if (strpos($uri, $path) === 0) {
                $endpoint = $path;
                break;
            }
        }

        if (!$endpoint) {
            return; // Not a rate-limited endpoint
        }

        // Get client identifier (IP address)
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $clientIp = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        }

        $rateLimitKey = "rate_limit:{$endpoint}:{$clientIp}";
        $rule = $this->rateLimitRules[$endpoint] ?? null;

        if (!$rule) {
            return; // No rule defined for this endpoint
        }

        $currentCount = (int)$this->cacheService->get($rateLimitKey, 0);

        if ($currentCount >= $rule['limit']) {
            http_response_code(429); // Too Many Requests
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                $retryAfter = $rule['window'];
                header("Retry-After: $retryAfter");
                echo json_encode([
                    'success' => false,
                    'error' => 'Too many requests. Please try again later.',
                    'retry_after' => $retryAfter
                ]);
            } else {
                header("Retry-After: {$rule['window']}");
                echo 'Too many requests. Please try again later.';
            }
            exit;
        }

        // Increment the counter
        $this->cacheService->set($rateLimitKey, $currentCount + 1, $rule['window']);
    }
}