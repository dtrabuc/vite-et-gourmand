<?php
declare(strict_types=1);

namespace App\Service;

/**
 * Limitation de débit par adresse IP et par point d'entrée sensible.
 *
 * Pour les connexions, seuls les échecs sont comptés (enregistrés par les
 * contrôleurs) et le compteur est remis à zéro après une connexion réussie.
 * Pour les autres formulaires, chaque envoi est compté par le middleware.
 */
final readonly class RateLimiter
{
    private const RULES = [
        '/login' => ['limit' => 5, 'window' => 300, 'failures_only' => true],
        '/admin/login' => ['limit' => 5, 'window' => 300, 'failures_only' => true],
        '/register' => ['limit' => 3, 'window' => 300, 'failures_only' => false],
        '/password' => ['limit' => 3, 'window' => 300, 'failures_only' => false],
        '/forgot-password' => ['limit' => 3, 'window' => 300, 'failures_only' => false],
        '/reset-password' => ['limit' => 3, 'window' => 300, 'failures_only' => false],
    ];

    public function __construct(
        private CacheService $cacheService
    ) {
    }

    /**
     * Point d'entrée limité correspondant au chemin, ou null.
     */
    public function endpointFor(string $path): ?string
    {
        foreach (array_keys(self::RULES) as $endpoint) {
            if ($path === $endpoint || str_starts_with($path, $endpoint . '/')) {
                return $endpoint;
            }
        }

        return null;
    }

    public function countsFailuresOnly(string $endpoint): bool
    {
        return self::RULES[$endpoint]['failures_only'] ?? false;
    }

    public function tooManyAttempts(string $endpoint): bool
    {
        if (!isset(self::RULES[$endpoint])) {
            return false;
        }

        return (int) $this->cacheService->get($this->key($endpoint), 0) >= self::RULES[$endpoint]['limit'];
    }

    public function window(string $endpoint): int
    {
        return self::RULES[$endpoint]['window'] ?? 0;
    }

    public function hit(string $endpoint): void
    {
        if (!isset(self::RULES[$endpoint])) {
            return;
        }

        $key = $this->key($endpoint);

        $this->cacheService->set(
            $key,
            (int) $this->cacheService->get($key, 0) + 1,
            self::RULES[$endpoint]['window']
        );
    }

    public function clear(string $endpoint): void
    {
        $this->cacheService->delete($this->key($endpoint));
    }

    private function key(string $endpoint): string
    {
        return 'rate_limit:' . $endpoint . ':' . (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    }
}
