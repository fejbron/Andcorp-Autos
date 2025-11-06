<?php

/**
 * Simple cache implementation for query results
 * In production, consider using Redis or Memcached
 */
class Cache {
    private static $cacheDir = __DIR__ . '/../storage/cache/';
    private static $defaultTTL = 3600; // 1 hour
    
    /**
     * Get cached value
     */
    public static function get($key) {
        $file = self::getCacheFile($key);
        
        if (!file_exists($file)) {
            return null;
        }
        
        $data = unserialize(file_get_contents($file));
        
        // Check if expired
        if ($data['expires'] < time()) {
            self::delete($key);
            return null;
        }
        
        return $data['value'];
    }
    
    /**
     * Set cached value
     */
    public static function set($key, $value, $ttl = null) {
        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0755, true);
        }
        
        $file = self::getCacheFile($key);
        $ttl = $ttl ?? self::$defaultTTL;
        
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        
        file_put_contents($file, serialize($data));
        chmod($file, 0644);
    }
    
    /**
     * Delete cached value
     */
    public static function delete($key) {
        $file = self::getCacheFile($key);
        if (file_exists($file)) {
            unlink($file);
        }
    }
    
    /**
     * Clear all cache
     */
    public static function clear() {
        if (is_dir(self::$cacheDir)) {
            $files = glob(self::$cacheDir . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }
    
    /**
     * Get cache file path
     */
    private static function getCacheFile($key) {
        return self::$cacheDir . md5($key) . '.cache';
    }
    
    /**
     * Remember pattern: get from cache or execute callback and cache result
     */
    public static function remember($key, $callback, $ttl = null) {
        $value = self::get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        self::set($key, $value, $ttl);
        
        return $value;
    }
}

