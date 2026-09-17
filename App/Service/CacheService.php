<?php
namespace App\Service;

class CacheService
{
    private string $cacheDir;
    private int $defaultTtl;

    public function __construct(string $cacheDir = null, int $defaultTtl = 3600)
    {
        // Use sys_get_temp_dir() if no cache directory specified
        $this->cacheDir = $cacheDir ?? sys_get_temp_dir() . '/viteetgourmand_cache';
        $this->defaultTtl = $defaultTtl;

        // Create cache directory if it doesn't exist
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Get a value from cache
     * 
     * @param string $key Cache key
     * @param mixed $default Default value if key not found or expired
     * @return mixed Cached value or default
     */
    public function get(string $key, $default = null)
    {
        $file = $this->getCacheFile($key);
        
        if (!file_exists($file)) {
            return $default;
        }
        
        try {
            $data = unserialize(file_get_contents($file));
            
            // Check if expired
            if ($data['expires'] < time()) {
                // Delete expired cache
                unlink($file);
                return $default;
            }
            
            return $data['value'];
        } catch (\Exception $e) {
            // If we can't read/unserialize the cache, treat as miss
            return $default;
        }
    }

    /**
     * Set a value in cache
     * 
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $ttl Time to live in seconds (null uses default)
     * @return bool True on success
     */
    public function set(string $key, $value, int $ttl = null): bool
    {
        if ($ttl === null) {
            $ttl = $this->defaultTtl;
        }
        
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        
        $file = $this->getCacheFile($key);
        $serialized = serialize($data);
        
        // Use file_put_contents with LOCK_EX to prevent race conditions
        return file_put_contents($file, $serialized, LOCK_EX) !== false;
    }

    /**
     * Delete a value from cache
     * 
     * @param string $key Cache key
     * @return bool True on success
     */
    public function delete(string $key): bool
    {
        $file = $this->getCacheFile($key);
        if (file_exists($file)) {
            return unlink($file);
        }
        return true; // Already deleted
    }

    /**
     * Clear all cache
     * 
     * @return bool True on success
     */
    public function clear(): bool
    {
        $files = glob($this->cacheDir . '/*.cache');
        $success = true;
        
        foreach ($files as $file) {
            if (is_file($file) && !unlink($file)) {
                $success = false;
            }
        }
        
        return $success;
    }

    /**
     * Get the cache file path for a key
     * 
     * @param string $key Cache key
     * @return string Cache file path
     */
    private function getCacheFile(string $key): string
    {
        // Create a safe filename from the key
        $safeKey = md5($key);
        return $this->cacheDir . '/' . $safeKey . '.cache';
    }

    /**
     * Check if a key exists in cache and is not expired
     * 
     * @param string $key Cache key
     * @return bool True if key exists and is not expired
     */
    public function has(string $key): bool
    {
        $file = $this->getCacheFile($key);
        
        if (!file_exists($file)) {
            return false;
        }
        
        try {
            $data = unserialize(file_get_contents($file));
            return $data['expires'] >= time();
        } catch (\Exception $e) {
            return false;
        }
    }
}
