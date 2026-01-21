<?php
/**
 * Helper Functions
 *
 * Collection of utility functions used throughout the application
 * for paths, URLs, sanitization, validation, and common operations.
 *
 * @package CodedArt
 * @subpackage Config
 */

// ==========================================
// PATH & URL HELPERS
// ==========================================

/**
 * Get absolute path to root directory
 *
 * @return string Absolute path to root
 */
function rootPath() {
    return defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__);
}

/**
 * Resolve path relative to root
 *
 * @param string $path Relative path
 * @return string Absolute path
 */
function resolvePath($path) {
    return rootPath() . '/' . ltrim($path, '/');
}

/**
 * Get base URL from server variables
 *
 * @return string Base URL
 */
function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
                (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ?
                'https://' : 'http://';

    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';

    // Get the directory path (remove trailing index.php or script name)
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $basePath = dirname($scriptName);

    // Clean up base path
    if ($basePath === '/' || $basePath === '\\') {
        $basePath = '';
    }

    return rtrim($protocol . $host . $basePath, '/');
}

/**
 * Get URL for a path
 *
 * @param string $path Path relative to root
 * @return string Full URL
 */
function url($path = '') {
    $base = defined('SITE_URL') ? SITE_URL : getBaseUrl();
    return $base . '/' . ltrim($path, '/');
}

/**
 * Get admin URL for a path
 *
 * @param string $path Path relative to admin directory
 * @return string Full admin URL
 */
function adminUrl($path = '') {
    $base = defined('ADMIN_URL') ? ADMIN_URL : url('admin');
    return $base . '/' . ltrim($path, '/');
}

/**
 * Get asset URL (CSS, JS, images)
 *
 * @param string $asset Asset path
 * @return string Asset URL
 */
function asset($asset) {
    return url($asset);
}

/**
 * Redirect to a URL
 *
 * @param string $url URL to redirect to
 * @param int $statusCode HTTP status code (default 302)
 */
function redirect($url, $statusCode = 302) {
    header('Location: ' . $url, true, $statusCode);
    exit;
}

/**
 * Redirect back to previous page
 */
function redirectBack() {
    $referer = $_SERVER['HTTP_REFERER'] ?? url();
    redirect($referer);
}

// ==========================================
// INPUT SANITIZATION & VALIDATION
// ==========================================

/**
 * Sanitize string input
 *
 * @param string $input Input string
 * @return string Sanitized string
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize email address
 *
 * @param string $email Email address
 * @return string|false Sanitized email or false if invalid
 */
function sanitizeEmail($email) {
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
}

/**
 * Validate email address
 *
 * @param string $email Email address
 * @return bool True if valid
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Sanitize URL
 *
 * @param string $url URL
 * @return string|false Sanitized URL or false if invalid
 */
function sanitizeUrl($url) {
    return filter_var(trim($url), FILTER_SANITIZE_URL);
}

/**
 * Validate URL
 *
 * @param string $url URL to validate
 * @return bool True if valid
 */
function isValidUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Validate image URL
 *
 * @param string $url Image URL
 * @return bool True if valid image URL
 */
function isValidImageUrl($url) {
    if (!isValidUrl($url)) {
        return false;
    }

    $allowedExtensions = defined('ALLOWED_IMAGE_EXTENSIONS')
        ? ALLOWED_IMAGE_EXTENSIONS
        : ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
    return in_array($extension, $allowedExtensions);
}

/**
 * Sanitize integer
 *
 * @param mixed $input Input value
 * @return int Sanitized integer
 */
function sanitizeInt($input) {
    return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
}

/**
 * Get POST value safely
 *
 * @param string $key POST key
 * @param mixed $default Default value if not set
 * @return mixed POST value or default
 */
function post($key, $default = null) {
    return $_POST[$key] ?? $default;
}

/**
 * Get GET value safely
 *
 * @param string $key GET key
 * @param mixed $default Default value if not set
 * @return mixed GET value or default
 */
function get($key, $default = null) {
    return $_GET[$key] ?? $default;
}

/**
 * Get REQUEST value safely (POST, then GET)
 *
 * @param string $key Request key
 * @param mixed $default Default value if not set
 * @return mixed Request value or default
 */
function request($key, $default = null) {
    return $_REQUEST[$key] ?? $default;
}

// ==========================================
// SESSION HELPERS
// ==========================================

/**
 * Start session if not already started
 */
function sessionStart() {
    if (session_status() === PHP_SESSION_NONE) {
        if (defined('SESSION_NAME')) {
            session_name(SESSION_NAME);
        }
        session_start();
    }
}

/**
 * Set session value
 *
 * @param string $key Session key
 * @param mixed $value Value to store
 */
function sessionSet($key, $value) {
    sessionStart();
    $_SESSION[$key] = $value;
}

/**
 * Get session value
 *
 * @param string $key Session key
 * @param mixed $default Default value if not set
 * @return mixed Session value or default
 */
function sessionGet($key, $default = null) {
    sessionStart();
    return $_SESSION[$key] ?? $default;
}

/**
 * Check if session key exists
 *
 * @param string $key Session key
 * @return bool True if exists
 */
function sessionHas($key) {
    sessionStart();
    return isset($_SESSION[$key]);
}

/**
 * Remove session key
 *
 * @param string $key Session key
 */
function sessionDelete($key) {
    sessionStart();
    unset($_SESSION[$key]);
}

/**
 * Destroy entire session
 */
function sessionDestroy() {
    sessionStart();
    session_unset();
    session_destroy();
}

/**
 * Set flash message
 *
 * @param string $message Message text
 * @param string $type Type: success, error, warning, info
 */
function setFlash($message, $type = 'info') {
    sessionSet('flash_message', $message);
    sessionSet('flash_type', $type);
}

/**
 * Get and clear flash message
 *
 * @return array|null ['message' => string, 'type' => string] or null
 */
function getFlash() {
    if (sessionHas('flash_message')) {
        $flash = [
            'message' => sessionGet('flash_message'),
            'type' => sessionGet('flash_type', 'info')
        ];
        sessionDelete('flash_message');
        sessionDelete('flash_type');
        return $flash;
    }
    return null;
}

// ==========================================
// CSRF PROTECTION
// ==========================================

/**
 * Generate CSRF token
 *
 * @return string CSRF token
 */
function generateCsrfToken() {
    sessionStart();
    $token = bin2hex(random_bytes(defined('CSRF_TOKEN_LENGTH') ? CSRF_TOKEN_LENGTH : 32));
    $_SESSION['csrf_token'] = $token;
    $_SESSION['csrf_token_time'] = time();
    return $token;
}

/**
 * Get current CSRF token (generate if not exists)
 *
 * @return string CSRF token
 */
function getCsrfToken() {
    sessionStart();
    if (!isset($_SESSION['csrf_token'])) {
        return generateCsrfToken();
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 *
 * @param string $token Token to verify
 * @return bool True if valid
 */
function verifyCsrfToken($token) {
    sessionStart();

    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }

    // Check token expiry if lifetime is defined
    if (defined('CSRF_TOKEN_LIFETIME') && isset($_SESSION['csrf_token_time'])) {
        $age = time() - $_SESSION['csrf_token_time'];
        if ($age > CSRF_TOKEN_LIFETIME) {
            return false;
        }
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate CSRF input field
 *
 * @return string HTML input field
 */
function csrfField() {
    $token = getCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

// ==========================================
// PASSWORD HELPERS
// ==========================================

/**
 * Hash password using bcrypt
 *
 * @param string $password Plain text password
 * @return string Hashed password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password against hash
 *
 * @param string $password Plain text password
 * @param string $hash Password hash
 * @return bool True if password matches
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Validate password strength
 *
 * @param string $password Password to validate
 * @return array ['valid' => bool, 'errors' => array]
 */
function validatePassword($password) {
    $errors = [];

    $minLength = defined('PASSWORD_MIN_LENGTH') ? PASSWORD_MIN_LENGTH : 8;
    if (strlen($password) < $minLength) {
        $errors[] = "Password must be at least {$minLength} characters";
    }

    if (defined('PASSWORD_REQUIRE_UPPERCASE') && PASSWORD_REQUIRE_UPPERCASE) {
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
    }

    if (defined('PASSWORD_REQUIRE_LOWERCASE') && PASSWORD_REQUIRE_LOWERCASE) {
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
    }

    if (defined('PASSWORD_REQUIRE_NUMBER') && PASSWORD_REQUIRE_NUMBER) {
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
    }

    if (defined('PASSWORD_REQUIRE_SPECIAL') && PASSWORD_REQUIRE_SPECIAL) {
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }
    }

    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

// ==========================================
// STRING HELPERS
// ==========================================

/**
 * Truncate string to specified length
 *
 * @param string $string String to truncate
 * @param int $length Maximum length
 * @param string $append String to append (e.g., '...')
 * @return string Truncated string
 */
function truncate($string, $length, $append = '...') {
    if (mb_strlen($string) <= $length) {
        return $string;
    }
    return mb_substr($string, 0, $length) . $append;
}

/**
 * Generate random string
 *
 * @param int $length Length of string
 * @return string Random string
 */
function randomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Generate slug from string
 *
 * @param string $string String to slugify
 * @return string Slug
 */
function slug($string) {
    $string = strtolower(trim($string));
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

// ==========================================
// DATE/TIME HELPERS
// ==========================================

/**
 * Format datetime for display
 *
 * @param string $datetime Datetime string
 * @param string $format Format string
 * @return string Formatted datetime
 */
function formatDate($datetime, $format = 'Y-m-d H:i:s') {
    if (empty($datetime)) {
        return '';
    }
    $dt = new DateTime($datetime);
    return $dt->format($format);
}

/**
 * Get relative time (e.g., "2 hours ago")
 *
 * @param string $datetime Datetime string
 * @return string Relative time
 */
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 2592000) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return formatDate($datetime, 'M j, Y');
    }
}

// ==========================================
// JSON HELPERS
// ==========================================

/**
 * Encode data as JSON
 *
 * @param mixed $data Data to encode
 * @param bool $pretty Pretty print
 * @return string JSON string
 */
function jsonEncode($data, $pretty = false) {
    $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
    if ($pretty) {
        $options |= JSON_PRETTY_PRINT;
    }
    return json_encode($data, $options);
}

/**
 * Decode JSON string
 *
 * @param string $json JSON string
 * @param bool $assoc Return associative array instead of object
 * @return mixed Decoded data
 */
function jsonDecode($json, $assoc = true) {
    return json_decode($json, $assoc);
}

/**
 * Send JSON response and exit
 *
 * @param mixed $data Data to send
 * @param int $statusCode HTTP status code
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo jsonEncode($data);
    exit;
}

// ==========================================
// DEBUG HELPERS
// ==========================================

/**
 * Dump variable and die (for debugging)
 *
 * @param mixed ...$vars Variables to dump
 */
function dd(...$vars) {
    echo '<pre style="background: #f0f0f0; padding: 20px; border: 2px solid #333;">';
    foreach ($vars as $var) {
        var_dump($var);
    }
    echo '</pre>';
    die;
}

/**
 * Dump variable without dying
 *
 * @param mixed ...$vars Variables to dump
 */
function dump(...$vars) {
    echo '<pre style="background: #f0f0f0; padding: 10px; border: 1px solid #ccc;">';
    foreach ($vars as $var) {
        var_dump($var);
    }
    echo '</pre>';
}

// ==========================================
// ARRAY HELPERS
// ==========================================

/**
 * Get value from array by key with default
 *
 * @param array $array Array
 * @param string $key Key
 * @param mixed $default Default value
 * @return mixed Value or default
 */
function arrayGet($array, $key, $default = null) {
    return $array[$key] ?? $default;
}

/**
 * Check if array is associative
 *
 * @param array $array Array to check
 * @return bool True if associative
 */
function isAssocArray($array) {
    if (!is_array($array) || empty($array)) {
        return false;
    }
    return array_keys($array) !== range(0, count($array) - 1);
}
