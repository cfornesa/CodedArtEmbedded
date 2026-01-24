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
 * Determine if an IP address is public.
 *
 * @param string $ip IP address
 * @return bool True if IP is public
 */
function isPublicIp($ip) {
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return false;
    }

    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
}

/**
 * Resolve host to IPs and ensure they are public (no private/reserved ranges).
 *
 * @param string $host Hostname to resolve
 * @return array ['allowed' => bool, 'ips' => array]
 */
function resolvePublicIps($host) {
    $allowPrivate = defined('CORS_PROXY_ALLOW_PRIVATE_IPS') && CORS_PROXY_ALLOW_PRIVATE_IPS;

    if (filter_var($host, FILTER_VALIDATE_IP)) {
        return [
            'allowed' => $allowPrivate ? true : isPublicIp($host),
            'ips' => [$host]
        ];
    }

    $records = @dns_get_record($host, DNS_A + DNS_AAAA);
    if (!$records) {
        return ['allowed' => false, 'ips' => []];
    }

    $ips = [];
    foreach ($records as $record) {
        if (!empty($record['ip'])) {
            $ips[] = $record['ip'];
        } elseif (!empty($record['ipv6'])) {
            $ips[] = $record['ipv6'];
        }
    }

    if (empty($ips)) {
        return ['allowed' => false, 'ips' => []];
    }

    if ($allowPrivate) {
        return ['allowed' => true, 'ips' => $ips];
    }

    foreach ($ips as $ip) {
        if (!isPublicIp($ip)) {
            return ['allowed' => false, 'ips' => $ips];
        }
    }

    return ['allowed' => true, 'ips' => $ips];
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

    $parsedUrl = parse_url($imageUrl);
    $scheme = $parsedUrl['scheme'] ?? '';
    $host = $parsedUrl['host'] ?? '';

    $allowInsecure = defined('CORS_PROXY_ALLOW_INSECURE_HTTP') && CORS_PROXY_ALLOW_INSECURE_HTTP;
    if (!in_array($scheme, ['https', 'http'], true) || (!$allowInsecure && $scheme !== 'https')) {
        header('HTTP/1.1 400 Bad Request');
        die('Invalid URL scheme');
    }

    if (empty($host)) {
        header('HTTP/1.1 400 Bad Request');
        die('Invalid URL host');
    }

    $resolved = resolvePublicIps($host);
    if (!$resolved['allowed']) {
        header('HTTP/1.1 400 Bad Request');
        die('URL host not allowed');
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
    $maxFileSize = defined('CORS_MAX_FILE_SIZE') ? CORS_MAX_FILE_SIZE : 10485760;

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

    $headers = @get_headers($imageUrl, 1);
    if ($headers && isset($headers['Content-Length'])) {
        $contentLength = is_array($headers['Content-Length'])
            ? end($headers['Content-Length'])
            : $headers['Content-Length'];
        if (is_numeric($contentLength) && (int) $contentLength > $maxFileSize) {
            header('HTTP/1.1 413 Payload Too Large');
            die('Image exceeds max size');
        }
    }

    // Fetch image from remote URL
    $sslVerify = !defined('CORS_PROXY_SSL_VERIFY') || CORS_PROXY_SSL_VERIFY;
    $maxRedirects = defined('CORS_PROXY_MAX_REDIRECTS') ? CORS_PROXY_MAX_REDIRECTS : 3;
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'User-Agent: CodedArt CORS Proxy/1.0\r\n',
            'timeout' => 30,
            'follow_location' => 1,
            'max_redirects' => $maxRedirects
        ],
        'ssl' => [
            'verify_peer' => $sslVerify,
            'verify_peer_name' => $sslVerify
        ]
    ]);

    $handle = @fopen($imageUrl, 'rb', false, $context);
    if (!$handle) {
        header('HTTP/1.1 404 Not Found');
        die('Failed to fetch image');
    }

    $imageData = stream_get_contents($handle, $maxFileSize + 1);
    fclose($handle);

    if ($imageData === false) {
        header('HTTP/1.1 404 Not Found');
        die('Failed to fetch image');
    }

    if (strlen($imageData) > $maxFileSize) {
        header('HTTP/1.1 413 Payload Too Large');
        die('Image exceeds max size');
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
