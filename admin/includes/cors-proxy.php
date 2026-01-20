<?php
/**
 * CORS Proxy for Images
 * Proxies non-CORS-compliant image URLs
 *
 * Features:
 * - Automatic CORS detection
 * - Only proxies when needed
 * - Caching for performance
 * - Supports WEBP, JPG, JPEG, PNG
 * - Security validation
 */

// Load configuration
require_once(__DIR__ . '/../../config/config.php');
require_once(__DIR__ . '/../../config/helpers.php');

/**
 * Check if an image URL needs CORS proxying
 * @param string $url Image URL
 * @return bool True if proxy needed
 */
function needsCorsProxy($url) {
    if (empty($url)) {
        return false;
    }

    // If it's already a local URL, no proxy needed
    if (strpos($url, url()) === 0) {
        return false;
    }

    // Try to detect CORS support by checking headers
    $headers = @get_headers($url, 1);

    if (!$headers) {
        // If we can't get headers, assume proxy might be needed
        return true;
    }

    // Check for CORS headers
    if (isset($headers['Access-Control-Allow-Origin'])) {
        $allowOrigin = is_array($headers['Access-Control-Allow-Origin'])
            ? end($headers['Access-Control-Allow-Origin'])
            : $headers['Access-Control-Allow-Origin'];

        // If it allows all origins or our origin, no proxy needed
        if ($allowOrigin === '*' || strpos($allowOrigin, $_SERVER['HTTP_HOST']) !== false) {
            return false;
        }
    }

    // Assume proxy is needed if no CORS headers found
    return true;
}

/**
 * Get proxied image URL
 * @param string $url Original image URL
 * @return string Proxied URL or original if proxy not needed
 */
function getProxiedImageUrl($url) {
    if (!needsCorsProxy($url)) {
        return $url;
    }

    // Return proxy URL
    return url('admin/includes/cors-proxy.php') . '?url=' . urlencode($url);
}

/**
 * Serve proxied image
 * This function is called when cors-proxy.php is accessed directly
 */
function serveProxiedImage() {
    // Get URL parameter
    if (!isset($_GET['url']) || empty($_GET['url'])) {
        header('HTTP/1.1 400 Bad Request');
        die('Missing URL parameter');
    }

    $imageUrl = $_GET['url'];

    // Validate URL format
    if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
        header('HTTP/1.1 400 Bad Request');
        die('Invalid URL format');
    }

    // Check if URL points to an allowed image type
    if (!isValidImageUrl($imageUrl)) {
        header('HTTP/1.1 400 Bad Request');
        die('Invalid image URL');
    }

    // Check cache first
    $cacheEnabled = defined('CORS_PROXY_ENABLED') && CORS_PROXY_ENABLED;
    $cacheDir = defined('CORS_CACHE_DIR') ? CORS_CACHE_DIR : __DIR__ . '/../../../cache/cors/';
    $cacheLifetime = defined('CORS_CACHE_LIFETIME') ? CORS_CACHE_LIFETIME : 86400; // 24 hours

    if ($cacheEnabled) {
        $cacheKey = md5($imageUrl);
        $cacheFile = $cacheDir . $cacheKey;

        // Create cache directory if it doesn't exist
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        // Check if cached file exists and is still valid
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheLifetime)) {
            $imageData = file_get_contents($cacheFile);
            $mimeType = getMimeTypeFromData($imageData);

            header('Content-Type: ' . $mimeType);
            header('Cache-Control: public, max-age=' . $cacheLifetime);
            header('Access-Control-Allow-Origin: *');
            echo $imageData;
            exit;
        }
    }

    // Fetch image from remote URL
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'User-Agent: CodedArt CORS Proxy/1.0\r\n',
            'timeout' => 30
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);

    $imageData = @file_get_contents($imageUrl, false, $context);

    if ($imageData === false) {
        header('HTTP/1.1 404 Not Found');
        die('Failed to fetch image');
    }

    // Validate that we actually got an image
    $mimeType = getMimeTypeFromData($imageData);
    $allowedTypes = defined('ALLOWED_IMAGE_TYPES')
        ? ALLOWED_IMAGE_TYPES
        : ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];

    if (!in_array($mimeType, $allowedTypes)) {
        header('HTTP/1.1 400 Bad Request');
        die('Invalid image type');
    }

    // Cache the image
    if ($cacheEnabled) {
        file_put_contents($cacheFile, $imageData);
    }

    // Serve the image with CORS headers
    header('Content-Type: ' . $mimeType);
    header('Cache-Control: public, max-age=' . $cacheLifetime);
    header('Access-Control-Allow-Origin: *');
    echo $imageData;
    exit;
}

/**
 * Get MIME type from image data
 * @param string $data Image data
 * @return string MIME type
 */
function getMimeTypeFromData($data) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_buffer($finfo, $data);
    finfo_close($finfo);
    return $mimeType;
}

/**
 * Clear CORS proxy cache
 * @param int $maxAge Maximum age in seconds (delete files older than this)
 * @return array ['success' => bool, 'deleted' => int]
 */
function clearCorsCache($maxAge = null) {
    $cacheDir = defined('CORS_CACHE_DIR') ? CORS_CACHE_DIR : __DIR__ . '/../../../cache/cors/';

    if (!is_dir($cacheDir)) {
        return [
            'success' => true,
            'deleted' => 0
        ];
    }

    $deleted = 0;
    $files = glob($cacheDir . '*');

    foreach ($files as $file) {
        if (is_file($file)) {
            $shouldDelete = false;

            if ($maxAge === null) {
                // Delete all
                $shouldDelete = true;
            } else {
                // Delete only old files
                if (time() - filemtime($file) > $maxAge) {
                    $shouldDelete = true;
                }
            }

            if ($shouldDelete && unlink($file)) {
                $deleted++;
            }
        }
    }

    return [
        'success' => true,
        'deleted' => $deleted
    ];
}

// If this file is accessed directly, serve the proxied image
if (basename($_SERVER['SCRIPT_FILENAME']) === 'cors-proxy.php') {
    serveProxiedImage();
}
