<?php

/**
 * Security headers and HTTP response headers
 */
class Headers {
    
    /**
     * Set security headers
     */
    public static function setSecurityHeaders() {
        // Prevent clickjacking
        header('X-Frame-Options: SAMEORIGIN');
        
        // XSS Protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Content Security Policy (adjust based on your needs)
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; " .
               "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; " .
               "font-src 'self' https://cdn.jsdelivr.net https://fonts.gstatic.com; " .
               "img-src 'self' data: https:; " .
               "connect-src 'self' https://cdn.jsdelivr.net https://fonts.googleapis.com https://fonts.gstatic.com; " .
               "frame-ancestors 'self';";
        header("Content-Security-Policy: $csp");
        
        // Permissions Policy (formerly Feature Policy)
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    }
    
    /**
     * Set caching headers
     */
    public static function setCacheHeaders($public = false, $maxAge = 3600) {
        if ($public) {
            header("Cache-Control: public, max-age={$maxAge}");
        } else {
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
        }
    }
    
    /**
     * Set JSON response headers
     */
    public static function setJsonHeaders() {
        header('Content-Type: application/json; charset=utf-8');
        self::setSecurityHeaders();
        self::setCacheHeaders(false);
    }
    
    /**
     * Set HTML response headers
     */
    public static function setHtmlHeaders() {
        header('Content-Type: text/html; charset=utf-8');
        self::setSecurityHeaders();
        self::setCacheHeaders(false);
    }
    
    /**
     * Enable GZIP compression
     */
    public static function enableCompression() {
        if (extension_loaded('zlib') && !ob_get_level() && !ini_get('zlib.output_compression')) {
            ob_start('ob_gzhandler');
        }
    }
}

