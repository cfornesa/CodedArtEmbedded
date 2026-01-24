<?php
/**
 * CodedArt Configuration File Template
 *
 * INSTRUCTIONS:
 * 1. Copy this file to config.php in the same directory
 * 2. Replace all placeholder values with your actual credentials
 * 3. NEVER commit config.php to Git (it's in .gitignore)
 *
 * This file is safe to commit as it contains no real credentials
 */

// ==========================================
// ENVIRONMENT DETECTION
// ==========================================
// Auto-detect environment (Replit vs Production)
define('ENVIRONMENT', getenv('REPL_ID') ? 'development' : 'production');

// ==========================================
// DATABASE CONFIGURATION
// ==========================================

/**
 * DATABASE TYPE AUTO-DETECTION:
 *
 * DB_TYPE is automatically detected based on your domain:
 * - codedart.org (and www.codedart.org) → MySQL
 * - codedart.cfornesa.com → MySQL
 * - codedart.fornesus.com → MySQL
 * - localhost, Replit, or any other domain → SQLite
 *
 * You can manually override auto-detection by uncommenting this line:
 * define('DB_TYPE', 'mysql');  // or 'sqlite'
 *
 * Leave commented to use automatic domain-based detection (recommended).
 */

// Uncomment to manually set database type (overrides auto-detection):
// define('DB_TYPE', 'mysql'); // or 'sqlite'

if (ENVIRONMENT === 'production') {
    // Hostinger MySQL Credentials (FILL THESE IN)
    define('DB_HOST', 'localhost'); // Or your MySQL host from cPanel
    define('DB_NAME', 'your_database_name'); // Database name from cPanel
    define('DB_USER', 'your_database_user'); // Database username
    define('DB_PASS', 'your_database_password'); // Database password
    define('DB_PORT', 3306); // Default MySQL port
    define('DB_CHARSET', 'utf8mb4');

    // For SQLite in production (NOT RECOMMENDED - only for testing):
    // define('DB_PATH', __DIR__ . '/../codedart.db');
    // define('FORCE_SQLITE_IN_PRODUCTION', true); // Required to use SQLite in production

} else {
    // Replit Development Environment
    // For SQLite (recommended and auto-detected on Replit/localhost):
    define('DB_PATH', __DIR__ . '/../codedart.db'); // SQLite database file path

    // MySQL credentials (OPTIONAL - only needed if manually forcing MySQL in development)
    // Leave these as-is if using SQLite (they won't be used)
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'codedart_db');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_PORT', 3306);
    define('DB_CHARSET', 'utf8mb4');
}

// ==========================================
// SMTP / EMAIL CONFIGURATION
// ==========================================
// Get these from your Hostinger email settings or external SMTP provider
// Hostinger SMTP supports both TLS (port 587) and SSL (port 465)
define('SMTP_HOST', 'mail.augmenthumankind.com'); // Your SMTP server hostname
define('SMTP_PORT', 587); // 587 for TLS (recommended), 465 for SSL
define('SMTP_SECURE', 'tls'); // 'tls' (recommended) or 'ssl'
define('SMTP_USERNAME', 'contact@augmenthumankind.com'); // Your email address
define('SMTP_PASSWORD', 'your_email_password_here'); // Your email password
define('SMTP_FROM_EMAIL', 'contact@augmenthumankind.com'); // Email address for "From" field
define('SMTP_FROM_NAME', 'CodedArt Admin'); // Name for "From" field

// ==========================================
// GOOGLE RECAPTCHA CONFIGURATION
// ==========================================
// Get these from https://www.google.com/recaptcha/admin
// IMPORTANT: Create reCAPTCHA v3 keys (NOT v2) - Select "Score based (v3)" during setup
define('RECAPTCHA_SITE_KEY', 'your_recaptcha_site_key_here'); // Public site key (v3)
define('RECAPTCHA_SECRET_KEY', 'your_recaptcha_secret_key_here'); // Secret key (v3)
define('RECAPTCHA_MIN_SCORE', 0.5); // Minimum score (0.0-1.0): 0.0 = likely bot, 1.0 = likely human

// ==========================================
// SECURITY SETTINGS
// ==========================================
define('SESSION_LIFETIME', 3600); // Session timeout in seconds (1 hour)
define('SESSION_NAME', 'codedart_session'); // Custom session name
define('PASSWORD_MIN_LENGTH', 8); // Minimum password length
define('PASSWORD_REQUIRE_UPPERCASE', true); // Require uppercase letter
define('PASSWORD_REQUIRE_LOWERCASE', true); // Require lowercase letter
define('PASSWORD_REQUIRE_NUMBER', true); // Require number
define('PASSWORD_REQUIRE_SPECIAL', false); // Require special character
define('MAX_LOGIN_ATTEMPTS', 5); // Max failed login attempts before lockout
define('LOGIN_LOCKOUT_TIME', 900); // Lockout duration in seconds (15 minutes)
define('MAX_REGISTRATION_ATTEMPTS', 3); // Max registration attempts before lockout
define('REGISTRATION_LOCKOUT_TIME', 1800); // Registration lockout duration in seconds (30 minutes)
define('MAX_PASSWORD_RESET_ATTEMPTS', 5); // Max password reset attempts before lockout
define('PASSWORD_RESET_LOCKOUT_TIME', 900); // Password reset lockout duration in seconds (15 minutes)

// CSRF Token Settings
define('CSRF_TOKEN_LENGTH', 32); // Length of CSRF tokens
define('CSRF_TOKEN_LIFETIME', 3600); // Token lifetime in seconds

// ==========================================
// CORS PROXY SETTINGS
// ==========================================
define('CORS_PROXY_ENABLED', true); // Enable/disable CORS proxy
define('CORS_CACHE_DIR', __DIR__ . '/../cache/cors/'); // Cache directory for proxied images
define('CORS_CACHE_LIFETIME', 86400); // Cache lifetime in seconds (24 hours)
define('CORS_MAX_FILE_SIZE', 10485760); // Max file size in bytes (10 MB)
define('CORS_PROXY_ALLOW_INSECURE_HTTP', false); // Allow http:// images (not recommended)
define('CORS_PROXY_ALLOW_PRIVATE_IPS', false); // Allow private/reserved IP ranges
define('CORS_PROXY_SSL_VERIFY', true); // Verify SSL certificates for proxied images
define('CORS_PROXY_MAX_REDIRECTS', 3); // Max redirects when fetching images
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/jpg']);
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);

// ==========================================
// APPLICATION SETTINGS
// ==========================================
// Site URLs
if (ENVIRONMENT === 'production') {
    define('SITE_URL', 'https://codedart.org'); // Your production URL
    define('SITE_DOMAIN', 'codedart.org');
} else {
    define('SITE_URL', 'http://localhost:8000'); // Replit dev URL
    define('SITE_DOMAIN', 'localhost');
}

define('ADMIN_URL', SITE_URL . '/admin'); // Admin panel URL
define('TIMEZONE', 'America/New_York'); // Your timezone (see: https://www.php.net/manual/en/timezones.php)

// ==========================================
// NOTIFICATION SETTINGS
// ==========================================
define('SEND_EMAIL_NOTIFICATIONS', true); // Enable/disable email notifications
define('ADMIN_EMAIL', 'contact@augmenthumankind.com'); // Admin email (receives all notifications)
define('NOTIFICATION_BCC', ''); // Optional BCC address for all emails
define('EMAIL_TEMPLATE_DIR', __DIR__ . '/../resources/email-templates/'); // Email template directory

// ==========================================
// FILE & UPLOAD SETTINGS
// ==========================================
define('MAX_IMAGE_URL_LENGTH', 500); // Maximum length for image URLs
define('IMAGE_URL_VALIDATION', true); // Validate image URLs before saving
define('ALLOW_EXTERNAL_IMAGES', true); // Allow images from external domains

// ==========================================
// DATABASE TABLE NAMES
// ==========================================
// Define table names (allows for custom prefixes if needed)
define('TABLE_AFRAME_ART', 'aframe_art');
define('TABLE_C2_ART', 'c2_art');
define('TABLE_P5_ART', 'p5_art');
define('TABLE_THREEJS_ART', 'threejs_art');
define('TABLE_USERS', 'users');
define('TABLE_SITE_CONFIG', 'site_config');
define('TABLE_ACTIVITY_LOG', 'activity_log');
define('TABLE_AUTH_LOG', 'auth_log');
define('TABLE_AUTH_RATE_LIMITS', 'auth_rate_limits');

// ==========================================
// ERROR HANDLING & LOGGING
// ==========================================
if (ENVIRONMENT === 'development') {
    // Development: Show all errors
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    define('DEBUG_MODE', true);
} else {
    // Production: Log errors but don't display
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
    define('DEBUG_MODE', false);
}

// ==========================================
// RATE LIMITING
// ==========================================
define('RATE_LIMIT_ENABLED', true); // Enable rate limiting
define('RATE_LIMIT_REQUESTS', 100); // Max requests per time window
define('RATE_LIMIT_WINDOW', 3600); // Time window in seconds (1 hour)

// ==========================================
// APPLICATION INITIALIZATION
// ==========================================
// Set timezone
date_default_timezone_set(TIMEZONE);

// Start session configuration
ini_set('session.cookie_httponly', 1); // Prevent JavaScript access to session cookie
ini_set('session.use_only_cookies', 1); // Only use cookies for sessions
ini_set('session.cookie_secure', ENVIRONMENT === 'production' ? 1 : 0); // HTTPS only in production
ini_set('session.cookie_samesite', 'Strict'); // CSRF protection

// ==========================================
// OPTIONAL: MULTI-DOMAIN SUPPORT
// ==========================================
// If you have multiple domains pointing to this installation
$allowed_domains = [
    'codedart.org',
    'codedart.cfornesa.com',
    'codedart.fornesus.com',
    'localhost'
];

// Validate current domain
$current_domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
if (!in_array($current_domain, $allowed_domains) && ENVIRONMENT === 'production') {
    // Optionally redirect to primary domain or show error
    // header('Location: https://codedart.org');
    // exit;
}

define('CURRENT_DOMAIN', $current_domain);

// ==========================================
// CONSTANTS FOR PATHS
// ==========================================
define('ROOT_PATH', dirname(__DIR__)); // Root directory of application
define('CONFIG_PATH', __DIR__); // Config directory
define('ADMIN_PATH', ROOT_PATH . '/admin'); // Admin directory
define('RESOURCES_PATH', ROOT_PATH . '/resources'); // Resources directory
define('CACHE_PATH', ROOT_PATH . '/cache'); // Cache directory
define('LOGS_PATH', ROOT_PATH . '/logs'); // Logs directory

// Create necessary directories if they don't exist
$directories = [CACHE_PATH, LOGS_PATH, CORS_CACHE_DIR];
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// ==========================================
// HELPER FUNCTIONS
// ==========================================

/**
 * Check if we're in development mode
 */
function is_dev() {
    return ENVIRONMENT === 'development';
}

/**
 * Check if we're in production mode
 */
function is_prod() {
    return ENVIRONMENT === 'production';
}

/**
 * Get full URL for a path
 */
function url($path = '') {
    return SITE_URL . '/' . ltrim($path, '/');
}

/**
 * Get admin URL for a path
 */
function admin_url($path = '') {
    return ADMIN_URL . '/' . ltrim($path, '/');
}
