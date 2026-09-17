<?php
namespace App\Service;

class RedisCacheService extends CacheService
{
    private $redis;
    private $useRedis = false;

    public function __construct(?int $defaultTtl = 3600)
    {
        parent::__construct(null, $defaultTtl);
        
        // Try to initialize Redis
        $this->initializeRedis();
    }

    private function initializeRedis(): void
    {
        // Try Redis extension first
        if (class_exists('Redis')) {
            try {
                $redis = new \Redis();
                // Try to connect - using localhost:6379 as default,
                // but this should be configurable via .env in a real implementation
                $redis->connect('127.0.0.1', 6379);
                
                // Test the connection
                if ($redis->ping() === '+PONG') {
                    $this->redis = $redis;
                    $this->useRedis = true;
                    error_log('RedisCacheService: Connected to Redis via extension');
                    return;
                }
            } catch (\Exception $e) {
                error_log('RedisCacheService: Failed to connect via Redis extension: ' . $e->getMessage());
            }
        }
        
        // Try Predis if Redis extension not available or failed
        if (class_exists('Predis\Client')) {
            try {
                $this->redis = new \Predis\Client([
                    'scheme' => 'tcp',
                    'host'   => '127.0.0.1',
                    'port'   => 6379,
                ]);
                
                // Test the connection
                if ($this->redis->ping() === 'PONG') {
                    $this->useRedis = true;
                    error_log('RedisCacheService: Connected to Redis via Predis');
                    return;
                }
            } catch (\Exception $e) {
                error_log('RedisCacheService: Failed to connect via Predis: ' . $e->getMessage());
            }
        }
        
        // If we get here, Redis is not available, use fallback (parent class methods)
        $this->useRedis = false;
        error_log('RedisCacheService: Redis not available, using file-based cache fallback');
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
        if ($this->useRedis && $this->redis !== null) {
            try {
                $value = $this->redis->get($key);
                if ($value !== false && $value !== null) {
                    return unserialize($value);
                }
            } catch (\Exception $e) {
                error_log('RedisCacheService::get error: ' . $e->getMessage());
                // Fall back to parent implementation (file-based cache) on error
            }
        }
        
        // Use parent implementation (file-based cache)
        return parent::get($key, $default);
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
        
        $serialized = serialize($value);
        $success = false;
        
        if ($this->useRedis && $this->redis !== null) {
            try {
                $success = $this->redis->setex($key, $ttl, $serialized);
                if ($success) {
                    return true;
                }
            } catch (\Exception $e) {
                error_log('RedisCacheService::set error: ' . $e->getMessage());
                // Fall through to parent implementation
            }
        }
        
        // Use parent implementation
        return parent::set($key, $value, $ttl);
    }

    /**
     * Delete a value from cache
     *
     * @param string $key Cache key
     * @return bool True on success
     */
    public function delete(string $key): bool
    {
        $success = false;
        
        if ($this->useRedis && $this->redis !== null) {
            try {
                $success = $this->redis->del($key);
                if ($success) {
                    return true;
                }
            } catch (\Exception $e) {
                error_log('RedisCacheService::delete error: ' . $e->getMessage());
                // Fall through to parent implementation
            }
        }
        
        // Use parent implementation
        return parent::delete($key);
    }

    /**
     * Clear all cache
     *
     * @return bool True on success
     */
    public function clear(): bool
    {
        $redisSuccess = false;
        $parentSuccess = false;
        
        if ($this->useRedis && $this->redis !== null) {
            try {
                $redisSuccess = $this->redis->flushDB();
            } catch (\Exception $e) {
                error_log('RedisCacheService::clear error: ' . $e->getMessage());
            }
        }
        
        $parentSuccess = parent::clear();
        
        return $redisSuccess || $parentSuccess;
    }

    /**
     * Check if a key exists in cache and is not expired
     *
     * @param string $key Cache key
     * @return bool True if key exists and is not expired
     */
    public function has(string $key): bool
    {
        if ($this->useRedis && $this->redis !== null) {
            try {
                return $this->redis->exists($key) === 1;
            } catch (\Exception $e) {
                error_log('RedisCacheService::has error: ' . $e->getMessage());
                // Fall through to parent implementation
            }
        }
        
        // Use parent implementation
        return parent::has($key);
    }

    /**
     * Check if using Redis backend
     *
     * @return bool True if using Redis, false if using fallback
     */
    public function isUsingRedis(): bool
    {
        return $this->useRedis;
    }
}
