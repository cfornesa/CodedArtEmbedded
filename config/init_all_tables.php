<?php
/**
 * Complete Database Initialization - All Tables with Current Schema
 * Creates all tables: aframe_art, c2_art, p5_art, threejs_art, users
 *
 * CRITICAL: Includes background_color column for Three.js (added in v1.0.21)
 *
 * Run with: php config/init_all_tables.php
 */

$db_path = __DIR__ . '/codedart.db';

try {
    $pdo = new PDO('sqlite:' . $db_path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "🚀 Initializing SQLite database with ALL tables...\n\n";

    // ==================== AFRAME_ART TABLE ====================
    echo "Creating aframe_art table...\n";
    $pdo->exec("DROP TABLE IF EXISTS aframe_art");
    $pdo->exec("CREATE TABLE aframe_art (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) UNIQUE NOT NULL,
        description TEXT,
        file_path VARCHAR(255),
        thumbnail_url VARCHAR(500),
        scene_type TEXT CHECK(scene_type IN ('space', 'alt', 'custom')) DEFAULT 'custom',
        sky_color VARCHAR(20) DEFAULT '#ECECEC',
        sky_texture VARCHAR(500),
        sky_opacity DECIMAL(3,2) DEFAULT 1.00,
        ground_color VARCHAR(20) DEFAULT '#7BC8A4',
        ground_texture VARCHAR(500),
        ground_opacity DECIMAL(3,2) DEFAULT 1.00,
        configuration TEXT,
        tags TEXT,
        created_by INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        deleted_at DATETIME DEFAULT NULL,
        status TEXT CHECK(status IN ('active', 'draft', 'archived')) DEFAULT 'draft',
        sort_order INTEGER DEFAULT 0
    )");
    $pdo->exec("CREATE INDEX idx_aframe_slug ON aframe_art(slug)");
    $pdo->exec("CREATE INDEX idx_aframe_status ON aframe_art(status)");
    echo "✓ aframe_art created\n\n";

    // ==================== C2_ART TABLE ====================
    echo "Creating c2_art table...\n";
    $pdo->exec("DROP TABLE IF EXISTS c2_art");
    $pdo->exec("CREATE TABLE c2_art (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) UNIQUE NOT NULL,
        description TEXT,
        file_path VARCHAR(255),
        thumbnail_url VARCHAR(500),
        background_image_url VARCHAR(500),
        canvas_count INTEGER DEFAULT 1,
        js_files TEXT,
        configuration TEXT,
        tags TEXT,
        created_by INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        deleted_at DATETIME DEFAULT NULL,
        status TEXT CHECK(status IN ('active', 'draft', 'archived')) DEFAULT 'draft',
        sort_order INTEGER DEFAULT 0
    )");
    $pdo->exec("CREATE INDEX idx_c2_slug ON c2_art(slug)");
    $pdo->exec("CREATE INDEX idx_c2_status ON c2_art(status)");
    echo "✓ c2_art created\n\n";

    // ==================== P5_ART TABLE ====================
    echo "Creating p5_art table...\n";
    $pdo->exec("DROP TABLE IF EXISTS p5_art");
    $pdo->exec("CREATE TABLE p5_art (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) UNIQUE NOT NULL,
        description TEXT,
        file_path VARCHAR(255),
        piece_path VARCHAR(255),
        thumbnail_url VARCHAR(500),
        screenshot_url VARCHAR(500),
        background_image_url VARCHAR(500),
        configuration TEXT,
        tags TEXT,
        created_by INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        deleted_at DATETIME DEFAULT NULL,
        status TEXT CHECK(status IN ('active', 'draft', 'archived')) DEFAULT 'draft',
        sort_order INTEGER DEFAULT 0
    )");
    $pdo->exec("CREATE INDEX idx_p5_slug ON p5_art(slug)");
    $pdo->exec("CREATE INDEX idx_p5_status ON p5_art(status)");
    echo "✓ p5_art created\n\n";

    // ==================== THREEJS_ART TABLE ====================
    echo "Creating threejs_art table...\n";
    $pdo->exec("DROP TABLE IF EXISTS threejs_art");
    $pdo->exec("CREATE TABLE threejs_art (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) UNIQUE NOT NULL,
        description TEXT,
        file_path VARCHAR(255),
        embedded_path VARCHAR(255),
        js_file VARCHAR(255),
        thumbnail_url VARCHAR(500),
        background_color VARCHAR(20) DEFAULT '#000000',
        background_image_url VARCHAR(500),
        texture_urls TEXT,
        configuration TEXT,
        tags TEXT,
        created_by INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        deleted_at DATETIME DEFAULT NULL,
        status TEXT CHECK(status IN ('active', 'draft', 'archived')) DEFAULT 'draft',
        sort_order INTEGER DEFAULT 0
    )");
    $pdo->exec("CREATE INDEX idx_threejs_slug ON threejs_art(slug)");
    $pdo->exec("CREATE INDEX idx_threejs_status ON threejs_art(status)");
    echo "✓ threejs_art created with background_color column!\n\n";

    // ==================== USERS TABLE ====================
    echo "Creating users table...\n";
    $pdo->exec("DROP TABLE IF EXISTS users");
    $pdo->exec("CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email VARCHAR(255) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        first_name VARCHAR(100),
        last_name VARCHAR(100),
        status TEXT CHECK(status IN ('active', 'inactive', 'pending')) DEFAULT 'pending',
        email_verified BOOLEAN DEFAULT 0,
        verification_token VARCHAR(255),
        reset_token VARCHAR(255),
        reset_token_expiry DATETIME,
        last_login DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE INDEX idx_users_email ON users(email)");
    $pdo->exec("CREATE INDEX idx_users_status ON users(status)");
    echo "✓ users created\n\n";

    // ==================== AUTH LOG TABLE ====================
    echo "Creating auth_log table...\n";
    $pdo->exec("DROP TABLE IF EXISTS auth_log");
    $pdo->exec("CREATE TABLE auth_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        email VARCHAR(255),
        event_type VARCHAR(50) NOT NULL,
        ip_address VARCHAR(45),
        user_agent VARCHAR(255),
        metadata TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE INDEX idx_auth_user ON auth_log(user_id)");
    $pdo->exec("CREATE INDEX idx_auth_email ON auth_log(email)");
    $pdo->exec("CREATE INDEX idx_auth_event ON auth_log(event_type)");
    echo "✓ auth_log created\n\n";

    // ==================== AUTH RATE LIMITS TABLE ====================
    echo "Creating auth_rate_limits table...\n";
    $pdo->exec("DROP TABLE IF EXISTS auth_rate_limits");
    $pdo->exec("CREATE TABLE auth_rate_limits (
        identifier VARCHAR(255) PRIMARY KEY,
        attempt_count INTEGER NOT NULL DEFAULT 0,
        first_attempt DATETIME,
        last_attempt DATETIME,
        locked_until DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE INDEX idx_auth_rate_last ON auth_rate_limits(last_attempt)");
    $pdo->exec("CREATE INDEX idx_auth_rate_locked ON auth_rate_limits(locked_until)");
    echo "✓ auth_rate_limits created\n\n";

    // ==================== VERIFICATION ====================
    echo "=== VERIFICATION ===" . PHP_EOL . PHP_EOL;

    $tables = ['aframe_art', 'c2_art', 'p5_art', 'threejs_art', 'users', 'auth_log', 'auth_rate_limits'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='$table'");
        $exists = $stmt->fetchColumn();
        echo ($exists ? '✓' : '✗') . " $table" . PHP_EOL;
    }

    echo PHP_EOL . "=== THREE.JS CRITICAL COLUMNS ===" . PHP_EOL . PHP_EOL;
    $stmt = $pdo->query('PRAGMA table_info(threejs_art)');
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);

    $criticalColumns = ['background_color', 'background_image_url', 'configuration'];
    foreach ($criticalColumns as $col) {
        $exists = in_array($col, $columns);
        echo ($exists ? '✓' : '✗') . " $col" . PHP_EOL;
    }

    echo PHP_EOL . "✅ Database initialization complete!" . PHP_EOL;
    echo "All tables created with current schema including background_color for Three.js" . PHP_EOL;

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
