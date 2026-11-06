<?php
declare(strict_types=1);

namespace App\Service;

final readonly class CacheService
{
    public function __construct(
        private ?string $cacheDir = null,
        private int $defaultTtl = 3600
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->cacheFile($key);

        if (!is_file($file)) {
            return $default;
        }

        try {
            $data = file_get_contents($file);

            if ($data === false) {
                return $default;
            }

            // Aucun objet n'est reconstruit : le cache ne contient que des
            // scalaires et des tableaux (les appelants convertissent leurs objets).
            $cache = unserialize($data, ['allowed_classes' => false]);

            if (
                !is_array($cache)
                || !isset($cache['expires'], $cache['value'])
                || (int) $cache['expires'] < time()
            ) {
                $this->delete($key);
                return $default;
            }

            return $cache['value'];
        } catch (\Throwable) {
            $this->delete($key);
            return $default;
        }
    }

    public function set(
        string $key,
        mixed $value,
        ?int $ttl = null
    ): bool {
        $ttl ??= $this->defaultTtl;
        $directory = $this->directory();

        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            return false;
        }

        $payload = serialize([
            'expires' => time() + max(0, $ttl),
            'value' => $value,
        ]);

        return file_put_contents(
            $this->cacheFile($key),
            $payload,
            LOCK_EX
        ) !== false;
    }

    public function delete(string $key): bool
    {
        $file = $this->cacheFile($key);

        return !is_file($file) || unlink($file);
    }

    private function directory(): string
    {
        // Dossier propre au projet (storage/ est ignoré par git), et non le
        // répertoire temporaire du système partagé avec d'autres applications.
        return $this->cacheDir
            ?? (dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache');
    }

    private function cacheFile(string $key): string
    {
        return $this->directory()
            . DIRECTORY_SEPARATOR
            . hash('sha256', $key)
            . '.cache';
    }
}
