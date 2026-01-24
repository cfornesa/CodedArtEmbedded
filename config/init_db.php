<?php
/**
 * Database Initialization Script
 *
 * Creates all required database tables for the CodedArt application.
 * Run this script once to set up the database schema.
 *
 * Tables created:
 * 1. aframe_art - A-Frame WebVR art pieces
 * 2. c2_art - c2.js art pieces
 * 3. p5_art - p5.js art pieces
 * 4. threejs_art - Three.js art pieces
 * 5. users - User accounts for admin access
 * 6. site_config - Global site settings
 * 7. activity_log - Activity tracking for email notifications
 *
 * @package CodedArt
 * @subpackage Config
 */

// Load configuration
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/environment.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';

// ==========================================
// HELPER FUNCTIONS
// ==========================================

/**
 * Output message
 */
function output($message, $type = 'info') {
    $colors = [
        'success' => '#28a745',
        'error' => '#dc3545',
        'warning' => '#ffc107',
        'info' => '#17a2b8'
    ];
    $color = $colors[$type] ?? $colors['info'];

    if (PHP_SAPI === 'cli') {
        echo $message . "\n";
    } else {
        echo "<div style='padding: 10px; margin: 5px 0; background: " . $color . "20; border-left: 4px solid {$color}; color: #333;'>";
        echo htmlspecialchars($message);
        echo "</div>";
    }
}

/**
 * Create a table
 */
function createTable($tableName, $sql) {
    try {
        $pdo = getDBConnection();

        // Check if table already exists
        if (dbTableExists($tableName)) {
            output("⚠️  Table '{$tableName}' already exists - skipping", 'warning');
            return true;
        }

        // Create table
        $pdo->exec($sql);
        output("✅ Table '{$tableName}' created successfully", 'success');
        return true;

    } catch (PDOException $e) {
        output("❌ Error creating table '{$tableName}': " . $e->getMessage(), 'error');
        return false;
    }
}

// ==========================================
// START INITIALIZATION
// ==========================================

if (PHP_SAPI !== 'cli') {
    echo "<!DOCTYPE html><html><head><title>Database Initialization</title></head><body>";
    echo "<h1>CodedArt Database Initialization</h1>";
    echo "<div style='font-family: monospace; max-width: 800px; margin: 20px;'>";
}

output("🚀 Starting database initialization (MySQL)...", 'info');
output("Environment: " . getEnvironment(), 'info');
output("Database: " . DB_NAME, 'info');

// SAFEGUARD: Prevent running MySQL init on SQLite configuration
if (defined('DB_TYPE') && DB_TYPE !== 'mysql') {
    output("❌ CONFIGURATION ERROR!", 'error');
    output("This script is for MySQL only, but DB_TYPE is set to: " . DB_TYPE, 'error');
    output("", 'info');
    output("Solutions:", 'info');
    output("  - For Hostinger (MySQL): Set DB_TYPE='mysql' in config.php", 'info');
    output("  - For Replit (SQLite): Run init_db_sqlite.php instead (NOT this script)", 'info');
    exit(1);
}

// SAFEGUARD: Warn if running in development environment
if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
    output("⚠️  INFO: Running MySQL initialization in DEVELOPMENT environment.", 'warning');
    output("This is OK if you're testing MySQL locally on Replit.", 'warning');
    output("For typical Replit development, use init_db_sqlite.php instead.", 'warning');
    output("", 'info');
}

// Check system requirements
$sysCheck = checkSystemRequirements();
if (!$sysCheck['status']) {
    output("❌ System requirements not met:", 'error');
    foreach ($sysCheck['errors'] as $error) {
        output("  - " . $error, 'error');
    }
    exit(1);
}

// Test database connection
try {
    $pdo = getDBConnection();
    output("✅ Database connection successful", 'success');
} catch (Exception $e) {
    output("❌ Database connection failed: " . $e->getMessage(), 'error');
    exit(1);
}

// ==========================================
// TABLE 1: aframe_art
// ==========================================

output("\n📋 Creating table: aframe_art", 'info');

$sql_aframe = "
CREATE TABLE IF NOT EXISTS aframe_art (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    file_path VARCHAR(255),
    thumbnail_url VARCHAR(500),
    scene_type ENUM('space', 'alt', 'custom') DEFAULT 'custom',
    sky_color VARCHAR(20) DEFAULT '#ECECEC' COMMENT 'Sky/background color',
    sky_texture VARCHAR(500) COMMENT 'Optional sky texture URL',
    ground_color VARCHAR(20) DEFAULT '#7BC8A4' COMMENT 'Ground/foreground color',
    ground_texture VARCHAR(500) COMMENT 'Optional ground texture URL',
    configuration TEXT COMMENT 'JSON with full piece configuration details',
    tags TEXT COMMENT 'Comma-separated tags',
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL COMMENT 'Soft delete timestamp',
    status ENUM('active', 'draft', 'archived') DEFAULT 'draft',
    sort_order INT DEFAULT 0,
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_deleted (deleted_at),
    INDEX idx_sort (sort_order),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

createTable('aframe_art', $sql_aframe);

// ==========================================
// TABLE 2: c2_art
// ==========================================

output("\n📋 Creating table: c2_art", 'info');

$sql_c2 = "
CREATE TABLE IF NOT EXISTS c2_art (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file_path VARCHAR(255) NOT NULL,
    thumbnail_url VARCHAR(500),
    image_urls TEXT COMMENT 'JSON array of image URLs used in piece',
    canvas_count INT DEFAULT 1,
    js_files TEXT COMMENT 'JSON array of JS file paths',
    configuration TEXT COMMENT 'JSON with full piece configuration details',
    tags TEXT COMMENT 'Comma-separated tags',
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('active', 'draft', 'archived') DEFAULT 'active',
    sort_order INT DEFAULT 0,
    INDEX idx_status (status),
    INDEX idx_sort (sort_order),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

createTable('c2_art', $sql_c2);

// ==========================================
// TABLE 3: p5_art
// ==========================================

output("\n📋 Creating table: p5_art", 'info');

$sql_p5 = "
CREATE TABLE IF NOT EXISTS p5_art (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file_path VARCHAR(255) NOT NULL,
    piece_path VARCHAR(255) COMMENT 'Path to piece/*.php file',
    thumbnail_url VARCHAR(500),
    screenshot_url VARCHAR(500) COMMENT 'PNG screenshot URL',
    image_urls TEXT COMMENT 'JSON array of image URLs used in piece',
    configuration TEXT COMMENT 'JSON with full piece configuration details',
    tags TEXT COMMENT 'Comma-separated tags',
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('active', 'draft', 'archived') DEFAULT 'active',
    sort_order INT DEFAULT 0,
    INDEX idx_status (status),
    INDEX idx_sort (sort_order),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

createTable('p5_art', $sql_p5);

// ==========================================
// TABLE 4: threejs_art
// ==========================================

output("\n📋 Creating table: threejs_art", 'info');

$sql_threejs = "
CREATE TABLE IF NOT EXISTS threejs_art (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file_path VARCHAR(255) NOT NULL,
    embedded_path VARCHAR(255) COMMENT '*-whole.php version',
    js_file VARCHAR(255),
    thumbnail_url VARCHAR(500),
    texture_urls TEXT COMMENT 'JSON array of texture image URLs',
    configuration TEXT COMMENT 'JSON with full piece configuration details',
    tags TEXT COMMENT 'Comma-separated tags',
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('active', 'draft', 'archived') DEFAULT 'active',
    sort_order INT DEFAULT 0,
    INDEX idx_status (status),
    INDEX idx_sort (sort_order),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

createTable('threejs_art', $sql_threejs);

// ==========================================
// TABLE 5: users
// ==========================================

output("\n📋 Creating table: users", 'info');

$sql_users = "
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    status ENUM('active', 'inactive', 'pending') DEFAULT 'pending',
    email_verified BOOLEAN DEFAULT FALSE,
    verification_token VARCHAR(255),
    reset_token VARCHAR(255),
    reset_token_expiry DATETIME,
    last_login DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_verification_token (verification_token),
    INDEX idx_reset_token (reset_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

createTable('users', $sql_users);

// ==========================================
// TABLE 6: site_config
// ==========================================

output("\n📋 Creating table: site_config", 'info');

$sql_config = "
CREATE TABLE IF NOT EXISTS site_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type ENUM('string', 'int', 'bool', 'json') DEFAULT 'string',
    description TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

createTable('site_config', $sql_config);

// ==========================================
// TABLE 7: activity_log
// ==========================================

output("\n📋 Creating table: activity_log", 'info');

$sql_activity = "
CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action_type ENUM('create', 'update', 'delete') NOT NULL,
    art_type ENUM('aframe', 'c2', 'p5', 'threejs') NOT NULL,
    art_id INT NOT NULL,
    configuration_snapshot TEXT COMMENT 'JSON of full configuration at time of action',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_art (art_type, art_id),
    INDEX idx_action (action_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

createTable('activity_log', $sql_activity);

// ==========================================
// TABLE 8: auth_log
// ==========================================

output("\n📋 Creating table: auth_log", 'info');

$sql_auth_log = "
CREATE TABLE IF NOT EXISTS auth_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    email VARCHAR(255) NULL,
    event_type VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    metadata TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_auth_user (user_id),
    INDEX idx_auth_email (email),
    INDEX idx_auth_event (event_type),
    INDEX idx_auth_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

createTable('auth_log', $sql_auth_log);

// ==========================================
// TABLE 9: auth_rate_limits
// ==========================================

output("\n📋 Creating table: auth_rate_limits", 'info');

$sql_auth_rate_limits = "
CREATE TABLE IF NOT EXISTS auth_rate_limits (
    identifier VARCHAR(255) PRIMARY KEY,
    attempt_count INT NOT NULL DEFAULT 0,
    first_attempt DATETIME NULL,
    last_attempt DATETIME NULL,
    locked_until DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_locked_until (locked_until),
    INDEX idx_last_attempt (last_attempt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

createTable('auth_rate_limits', $sql_auth_rate_limits);

// ==========================================
// CREATE FOREIGN KEYS
// ==========================================

output("\n🔗 Creating foreign key constraints...", 'info');

try {
    $pdo = getDBConnection();

    // Add foreign keys for created_by in art tables
    $foreignKeys = [
        "ALTER TABLE aframe_art ADD CONSTRAINT fk_aframe_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL",
        "ALTER TABLE c2_art ADD CONSTRAINT fk_c2_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL",
        "ALTER TABLE p5_art ADD CONSTRAINT fk_p5_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL",
        "ALTER TABLE threejs_art ADD CONSTRAINT fk_threejs_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL",
        "ALTER TABLE activity_log ADD CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE",
        "ALTER TABLE auth_log ADD CONSTRAINT fk_auth_log_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL"
    ];

    foreach ($foreignKeys as $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            // Foreign key might already exist, that's okay
            if (strpos($e->getMessage(), 'already exists') === false) {
                output("⚠️  Foreign key warning: " . $e->getMessage(), 'warning');
            }
        }
    }

    output("✅ Foreign keys created successfully", 'success');

} catch (Exception $e) {
    output("⚠️  Foreign key creation had issues (non-critical): " . $e->getMessage(), 'warning');
}

// ==========================================
// INSERT DEFAULT SETTINGS
// ==========================================

output("\n⚙️  Inserting default site settings...", 'info');

try {
    $defaultSettings = [
        ['site_name', 'CodedArt', 'string', 'Site name'],
        ['site_description', 'Code and code-generated art', 'string', 'Site description'],
        ['items_per_page', '12', 'int', 'Number of items per page in galleries'],
        ['allow_registration', 'true', 'bool', 'Allow new user registration'],
        ['maintenance_mode', 'false', 'bool', 'Enable maintenance mode'],
        ['email_notifications', 'true', 'bool', 'Send email notifications']
    ];

    foreach ($defaultSettings as $setting) {
        try {
            // Check if setting already exists
            $existing = dbFetchOne(
                "SELECT id FROM site_config WHERE setting_key = ?",
                [$setting[0]]
            );

            if (!$existing) {
                dbInsert('site_config', [
                    'setting_key' => $setting[0],
                    'setting_value' => $setting[1],
                    'setting_type' => $setting[2],
                    'description' => $setting[3]
                ]);
                output("  ✓ Added setting: {$setting[0]}", 'success');
            }
        } catch (Exception $e) {
            output("  ⚠️  Could not add setting {$setting[0]}: " . $e->getMessage(), 'warning');
        }
    }

} catch (Exception $e) {
    output("⚠️  Error inserting default settings: " . $e->getMessage(), 'warning');
}

// ==========================================
// SUMMARY
// ==========================================

output("\n" . str_repeat('=', 60), 'info');
output("✅ Database initialization complete!", 'success');
output(str_repeat('=', 60), 'info');

// Get table statistics
output("\n📊 Database Statistics:", 'info');
$stats = dbGetStats();
foreach ($stats as $table => $count) {
    output("  - {$table}: {$count} records", 'info');
}

output("\n🎉 You can now use the application!", 'success');
output("📝 Next steps:", 'info');
output("  1. Create an admin user via /admin/register.php", 'info');
output("  2. Populate art pieces via admin interface", 'info');
output("  3. Delete or restrict access to this init_db.php file", 'info');

if (PHP_SAPI !== 'cli') {
    echo "</div></body></html>";
}
