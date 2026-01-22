<?php
/**
 * Environment Detection and Configuration
 *
 * Automatically detects the runtime environment (development vs production)
 * and sets appropriate constants and configurations.
 *
 * Supports:
 * - Replit development environment
 * - Hostinger production environment
 * - Local development (XAMPP, MAMP, etc.)
 *
 * @package CodedArt
 * @subpackage Config
 */

/**
 * Detect if running on Replit
 *
 * @return bool True if running on Replit
 */
function isReplit() {
    return isset($_ENV['REPL_ID']) ||
           isset($_SERVER['REPL_ID']) ||
           getenv('REPL_ID') !== false ||
           isset($_ENV['REPLIT_DB_URL']);
}

/**
 * Detect if running on localhost
 *
 * @return bool True if running on localhost
 */
function isLocalhost() {
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
    return in_array($host, ['localhost', '127.0.0.1', '::1']) ||
           strpos($host, 'localhost:') === 0;
}

/**
 * Detect if running in production
 *
 * @return bool True if running in production
 */
function isProduction() {
    // Check if explicitly set via environment variable
    if (getenv('ENVIRONMENT') === 'production') {
        return true;
    }

    // Not production if on Replit or localhost
    if (isReplit() || isLocalhost()) {
        return false;
    }

    // Check for production domains
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $productionDomains = [
        'codedart.org',
        'www.codedart.org',
        'codedart.cfornesa.com',
        'codedart.fornesus.com'
    ];

    return in_array($host, $productionDomains);
}

/**
 * Auto-detect database type based on domain
 *
 * @return string 'mysql' for production domains, 'sqlite' for development
 */
function autoDetectDatabaseType() {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // Production domains use MySQL
    $mysqlDomains = [
        'codedart.org',
        'www.codedart.org',
        'codedart.cfornesa.com',
        'codedart.fornesus.com'
    ];

    if (in_array($host, $mysqlDomains)) {
        return 'mysql';
    }

    // Development/Replit/localhost use SQLite
    return 'sqlite';
}

/**
 * Check if current domain should use MySQL
 *
 * @return bool True if domain should use MySQL
 */
function shouldUseMysql() {
    return autoDetectDatabaseType() === 'mysql';
}

/**
 * Get current environment name
 *
 * @return string 'production', 'development', or 'replit'
 */
function getEnvironment() {
    if (defined('ENVIRONMENT')) {
        return ENVIRONMENT;
    }

    if (isReplit()) {
        return 'replit';
    }

    if (isProduction()) {
        return 'production';
    }

    return 'development';
}

/**
 * Get current domain/host
 *
 * @return string Current domain
 */
function getCurrentDomain() {
    return $_SERVER['HTTP_HOST'] ?? 'localhost';
}

/**
 * Check if HTTPS is enabled
 *
 * @return bool True if using HTTPS
 */
function isHttps() {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
           ($_SERVER['SERVER_PORT'] ?? 0) == 443 ||
           (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
}

/**
 * Get base URL of the application
 *
 * @return string Base URL (with protocol and domain)
 */
function getBaseUrl() {
    if (defined('SITE_URL')) {
        return SITE_URL;
    }

    $protocol = isHttps() ? 'https' : 'http';
    $host = getCurrentDomain();
    return "{$protocol}://{$host}";
}

/**
 * Get server information
 *
 * @return array Server environment details
 */
function getServerInfo() {
    return [
        'environment' => getEnvironment(),
        'is_replit' => isReplit(),
        'is_localhost' => isLocalhost(),
        'is_production' => isProduction(),
        'is_https' => isHttps(),
        'domain' => getCurrentDomain(),
        'base_url' => getBaseUrl(),
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? '',
        'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? ''
    ];
}

/**
 * Check if debugging should be enabled
 *
 * @return bool True if debug mode should be on
 */
function shouldDebug() {
    if (defined('DEBUG_MODE')) {
        return DEBUG_MODE;
    }

    return !isProduction();
}

/**
 * Configure PHP settings based on environment
 */
function configureEnvironment() {
    $env = getEnvironment();

    if ($env === 'production') {
        // Production settings - hide errors
        error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
        ini_set('display_errors', '0');
        ini_set('display_startup_errors', '0');
        ini_set('log_errors', '1');

        // Set error log location if defined
        if (defined('LOGS_PATH')) {
            ini_set('error_log', LOGS_PATH . '/php_errors.log');
        }
    } else {
        // Development settings - show errors
        error_reporting(E_ALL);
        ini_set('display_errors', '1');
        ini_set('display_startup_errors', '1');
        ini_set('log_errors', '1');
    }

    // Session configuration (only if session not already started)
    // These settings must be configured BEFORE session_start() is called
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', '1'); // Prevent JavaScript access
        ini_set('session.use_only_cookies', '1'); // Only use cookies for sessions

        if ($env === 'production') {
            ini_set('session.cookie_secure', '1'); // HTTPS only in production
        }

        ini_set('session.cookie_samesite', 'Strict'); // CSRF protection
    }

    // Set timezone if defined
    if (defined('TIMEZONE')) {
        date_default_timezone_set(TIMEZONE);
    } else {
        date_default_timezone_set('America/New_York'); // Default timezone
    }
}

/**
 * Check if required PHP extensions are loaded
 *
 * @return array Missing extensions (empty if all present)
 */
function checkRequiredExtensions() {
    $required = ['pdo', 'pdo_mysql', 'curl', 'mbstring', 'json', 'openssl'];
    $missing = [];

    foreach ($required as $ext) {
        if (!extension_loaded($ext)) {
            $missing[] = $ext;
        }
    }

    return $missing;
}

/**
 * Check if system meets minimum requirements
 *
 * @return array Array with 'status' (bool) and 'errors' (array)
 */
function checkSystemRequirements() {
    $errors = [];

    // Check PHP version (minimum 7.4, recommended 8.0+)
    if (version_compare(PHP_VERSION, '7.4.0', '<')) {
        $errors[] = 'PHP version 7.4.0 or higher required. Current: ' . PHP_VERSION;
    }

    // Check required extensions
    $missingExtensions = checkRequiredExtensions();
    if (!empty($missingExtensions)) {
        $errors[] = 'Missing PHP extensions: ' . implode(', ', $missingExtensions);
    }

    // Check if config file exists
    if (!file_exists(__DIR__ . '/config.php')) {
        $errors[] = 'Configuration file (config.php) not found. Please copy config.example.php to config.php and configure.';
    }

    // Check write permissions for cache and logs
    $writableDirs = [
        __DIR__ . '/../cache',
        __DIR__ . '/../logs'
    ];

    foreach ($writableDirs as $dir) {
        if (file_exists($dir) && !is_writable($dir)) {
            $errors[] = "Directory not writable: {$dir}";
        }
    }

    return [
        'status' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Create required directories if they don't exist
 */
function createRequiredDirectories() {
    $dirs = [
        __DIR__ . '/../cache' => 0755,
        __DIR__ . '/../cache/cors' => 0755,
        __DIR__ . '/../logs' => 0755
    ];

    foreach ($dirs as $dir => $permissions) {
        if (!file_exists($dir)) {
            @mkdir($dir, $permissions, true);
        }
    }
}

/**
 * Display environment information (for debugging)
 */
function displayEnvironmentInfo() {
    if (!shouldDebug()) {
        return;
    }

    echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px; border: 1px solid #ccc; font-family: monospace;'>";
    echo "<strong>Environment Information:</strong><br>";

    $info = getServerInfo();
    foreach ($info as $key => $value) {
        echo htmlspecialchars($key) . ": " . htmlspecialchars($value) . "<br>";
    }

    $reqs = checkSystemRequirements();
    if (!$reqs['status']) {
        echo "<br><strong style='color: red;'>System Requirements Issues:</strong><br>";
        foreach ($reqs['errors'] as $error) {
            echo "❌ " . htmlspecialchars($error) . "<br>";
        }
    } else {
        echo "<br>✅ All system requirements met<br>";
    }

    echo "</div>";
}

// Auto-configure environment when this file is included
configureEnvironment();

// Create required directories
createRequiredDirectories();
