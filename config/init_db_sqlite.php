<?php
/**
 * SQLite-Compatible Database Initialization Script
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/environment.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';

function output($message, $type = 'info') {
    $icons = ['success' => '✅', 'error' => '❌', 'warning' => '⚠️', 'info' => 'ℹ️'];
    $icon = $icons[$type] ?? $icons['info'];
    if (PHP_SAPI === 'cli') {
        echo "$icon $message\n";
    }
}

function createTable($tableName, $sql) {
    try {
        $pdo = getDBConnection();
        $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$tableName'");
        if ($result->fetch()) {
            output("Table '{$tableName}' already exists - skipping", 'warning');
            return true;
        }
        $pdo->exec($sql);
        output("Table '{$tableName}' created successfully", 'success');
        return true;
    } catch (PDOException $e) {
        output("Error creating table '{$tableName}': " . $e->getMessage(), 'error');
        return false;
    }
}

output("🚀 Starting database initialization (SQLite)...", 'info');
$pdo = getDBConnection();
output("Database connection successful", 'success');

// Create aframe_art
output("\n📋 Creating table: aframe_art", 'info');
$sql = "CREATE TABLE IF NOT EXISTS aframe_art (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file_path VARCHAR(255) NOT NULL,
    thumbnail_url VARCHAR(500),
    texture_urls TEXT,
    scene_type TEXT CHECK(scene_type IN ('space', 'alt', 'custom')) DEFAULT 'custom',
    configuration TEXT,
    tags TEXT,
    created_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status TEXT CHECK(status IN ('active', 'draft', 'archived')) DEFAULT 'active',
    sort_order INTEGER DEFAULT 0
)";
createTable('aframe_art', $sql);
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_aframe_status ON aframe_art(status)");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_aframe_sort ON aframe_art(sort_order)");

// Create c2_art
output("\n📋 Creating table: c2_art", 'info');
$sql = "CREATE TABLE IF NOT EXISTS c2_art (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file_path VARCHAR(255) NOT NULL,
    thumbnail_url VARCHAR(500),
    image_urls TEXT,
    canvas_count INTEGER DEFAULT 1,
    js_files TEXT,
    configuration TEXT,
    tags TEXT,
    created_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status TEXT CHECK(status IN ('active', 'draft', 'archived')) DEFAULT 'active',
    sort_order INTEGER DEFAULT 0
)";
createTable('c2_art', $sql);
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_c2_status ON c2_art(status)");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_c2_sort ON c2_art(sort_order)");

// Create p5_art
output("\n📋 Creating table: p5_art", 'info');
$sql = "CREATE TABLE IF NOT EXISTS p5_art (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file_path VARCHAR(255) NOT NULL,
    piece_path VARCHAR(255),
    thumbnail_url VARCHAR(500),
    screenshot_url VARCHAR(500),
    image_urls TEXT,
    configuration TEXT,
    tags TEXT,
    created_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status TEXT CHECK(status IN ('active', 'draft', 'archived')) DEFAULT 'active',
    sort_order INTEGER DEFAULT 0
)";
createTable('p5_art', $sql);
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_p5_status ON p5_art(status)");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_p5_sort ON p5_art(sort_order)");

// Create threejs_art
output("\n📋 Creating table: threejs_art", 'info');
$sql = "CREATE TABLE IF NOT EXISTS threejs_art (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file_path VARCHAR(255) NOT NULL,
    embedded_path VARCHAR(255),
    js_file VARCHAR(255),
    thumbnail_url VARCHAR(500),
    texture_urls TEXT,
    configuration TEXT,
    tags TEXT,
    created_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status TEXT CHECK(status IN ('active', 'draft', 'archived')) DEFAULT 'active',
    sort_order INTEGER DEFAULT 0
)";
createTable('threejs_art', $sql);
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_threejs_status ON threejs_art(status)");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_threejs_sort ON threejs_art(sort_order)");

// Create users
output("\n📋 Creating table: users", 'info');
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    status TEXT CHECK(status IN ('active', 'inactive', 'pending')) DEFAULT 'pending',
    email_verified INTEGER DEFAULT 0,
    verification_token VARCHAR(255),
    reset_token VARCHAR(255),
    reset_token_expiry DATETIME,
    last_login DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";
createTable('users', $sql);
$pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_users_email ON users(email)");

// Create site_config
output("\n📋 Creating table: site_config", 'info');
$sql = "CREATE TABLE IF NOT EXISTS site_config (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type TEXT CHECK(setting_type IN ('string', 'int', 'bool', 'json')) DEFAULT 'string',
    description TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";
createTable('site_config', $sql);
$pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_config_key ON site_config(setting_key)");

// Create activity_log
output("\n📋 Creating table: activity_log", 'info');
$sql = "CREATE TABLE IF NOT EXISTS activity_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    action_type TEXT CHECK(action_type IN ('create', 'update', 'delete')),
    art_type TEXT CHECK(art_type IN ('aframe', 'c2', 'p5', 'threejs')),
    art_id INTEGER,
    configuration_snapshot TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";
createTable('activity_log', $sql);
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_activity_user ON activity_log(user_id)");

// Insert default settings
output("\n⚙️  Inserting default settings...", 'info');
$settings = [
    ['site_name', 'CodedArt', 'string', 'Website name'],
    ['site_description', 'Digital art gallery', 'string', 'Website description'],
    ['items_per_page', '12', 'int', 'Items per page'],
    ['allow_registration', '1', 'bool', 'Allow registrations'],
    ['maintenance_mode', '0', 'bool', 'Maintenance mode'],
    ['email_notifications', '1', 'bool', 'Email notifications']
];
foreach ($settings as $s) {
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO site_config (setting_key, setting_value, setting_type, description) VALUES (?, ?, ?, ?)");
    $stmt->execute($s);
}
output("  → Settings inserted", 'success');

output("\n✅ Database initialization complete!", 'success');
output("📝 Next: php config/migrate_add_slugs_sqlite.php", 'info');
