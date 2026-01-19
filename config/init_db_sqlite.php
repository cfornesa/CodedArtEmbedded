<?php
/**
 * SQLite Database Initialization Script
 *
 * Simplified version of init_db.php for SQLite testing.
 * Creates all 7 tables with SQLite-compatible syntax.
 *
 * @package CodedArt
 * @subpackage Config
 */

// Load configuration
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/environment.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';

// Output helper
function output($message, $type = 'info') {
    echo $message . "\n";
}

output("🚀 Starting SQLite database initialization...");
output("Database file: " . DB_NAME);

// Test connection
try {
    $pdo = getDBConnection();
    output("✅ Database connection successful\n");
} catch (Exception $e) {
    output("❌ Database connection failed: " . $e->getMessage());
    exit(1);
}

// Create tables
try {
    // TABLE 1: aframe_art
    output("📋 Creating table: aframe_art");
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS aframe_art (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        description TEXT,
        file_path TEXT NOT NULL,
        thumbnail_url TEXT,
        texture_urls TEXT,
        scene_type TEXT DEFAULT 'custom' CHECK(scene_type IN ('space', 'alt', 'custom')),
        configuration TEXT,
        tags TEXT,
        created_by INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        status TEXT DEFAULT 'active' CHECK(status IN ('active', 'draft', 'archived')),
        sort_order INTEGER DEFAULT 0
    )");
    output("  ✅ aframe_art created");

    // TABLE 2: c2_art
    output("\n📋 Creating table: c2_art");
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS c2_art (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        description TEXT,
        file_path TEXT NOT NULL,
        thumbnail_url TEXT,
        image_urls TEXT,
        canvas_count INTEGER DEFAULT 1,
        js_files TEXT,
        configuration TEXT,
        tags TEXT,
        created_by INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        status TEXT DEFAULT 'active' CHECK(status IN ('active', 'draft', 'archived')),
        sort_order INTEGER DEFAULT 0
    )");
    output("  ✅ c2_art created");

    // TABLE 3: p5_art
    output("\n📋 Creating table: p5_art");
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS p5_art (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        description TEXT,
        file_path TEXT NOT NULL,
        piece_path TEXT,
        thumbnail_url TEXT,
        screenshot_url TEXT,
        image_urls TEXT,
        configuration TEXT,
        tags TEXT,
        created_by INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        status TEXT DEFAULT 'active' CHECK(status IN ('active', 'draft', 'archived')),
        sort_order INTEGER DEFAULT 0
    )");
    output("  ✅ p5_art created");

    // TABLE 4: threejs_art
    output("\n📋 Creating table: threejs_art");
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS threejs_art (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        description TEXT,
        file_path TEXT NOT NULL,
        embedded_path TEXT,
        js_file TEXT,
        thumbnail_url TEXT,
        texture_urls TEXT,
        configuration TEXT,
        tags TEXT,
        created_by INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        status TEXT DEFAULT 'active' CHECK(status IN ('active', 'draft', 'archived')),
        sort_order INTEGER DEFAULT 0
    )");
    output("  ✅ threejs_art created");

    // TABLE 5: users
    output("\n📋 Creating table: users");
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        first_name TEXT,
        last_name TEXT,
        status TEXT DEFAULT 'pending' CHECK(status IN ('active', 'inactive', 'pending')),
        email_verified INTEGER DEFAULT 0,
        verification_token TEXT,
        reset_token TEXT,
        reset_token_expiry DATETIME,
        last_login DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    output("  ✅ users created");

    // TABLE 6: site_config
    output("\n📋 Creating table: site_config");
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS site_config (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        setting_key TEXT NOT NULL UNIQUE,
        setting_value TEXT,
        setting_type TEXT DEFAULT 'string' CHECK(setting_type IN ('string', 'int', 'bool', 'json')),
        description TEXT,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    output("  ✅ site_config created");

    // TABLE 7: activity_log
    output("\n📋 Creating table: activity_log");
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS activity_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        action_type TEXT NOT NULL CHECK(action_type IN ('create', 'update', 'delete')),
        art_type TEXT NOT NULL CHECK(art_type IN ('aframe', 'c2', 'p5', 'threejs')),
        art_id INTEGER NOT NULL,
        configuration_snapshot TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    output("  ✅ activity_log created");

    // Insert default settings
    output("\n⚙️  Inserting default site settings...");
    $defaultSettings = [
        ['site_name', 'CodedArt', 'string', 'Site name'],
        ['site_description', 'Code and code-generated art', 'string', 'Site description'],
        ['items_per_page', '12', 'int', 'Number of items per page in galleries'],
        ['allow_registration', 'true', 'bool', 'Allow new user registration'],
        ['maintenance_mode', 'false', 'bool', 'Enable maintenance mode'],
        ['email_notifications', 'true', 'bool', 'Send email notifications']
    ];

    $stmt = $pdo->prepare("INSERT OR IGNORE INTO site_config (setting_key, setting_value, setting_type, description) VALUES (?, ?, ?, ?)");
    foreach ($defaultSettings as $setting) {
        $stmt->execute($setting);
        output("  ✓ Added setting: {$setting[0]}");
    }

    output("\n" . str_repeat('=', 60));
    output("✅ SQLite database initialization complete!");
    output(str_repeat('=', 60));

    // Show statistics
    output("\n📊 Database Statistics:");
    $tables = ['aframe_art', 'c2_art', 'p5_art', 'threejs_art', 'users', 'site_config', 'activity_log'];
    foreach ($tables as $table) {
        $result = $pdo->query("SELECT COUNT(*) as count FROM {$table}")->fetch();
        output("  - {$table}: {$result['count']} records");
    }

    output("\n✅ Database is ready for seeding!");
    output("Next step: Run seed_data.php to populate art pieces");

} catch (PDOException $e) {
    output("❌ Error: " . $e->getMessage());
    exit(1);
}
