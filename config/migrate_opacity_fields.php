<?php
/**
 * Database Migration: Add Opacity Fields to A-Frame
 *
 * This migration adds opacity controls for sky and ground colors.
 * Per-shape opacity is stored in the configuration JSON.
 *
 * Usage: php migrate_opacity_fields.php
 */

// Try to load config from environment if config.php doesn't exist
if (file_exists(__DIR__ . '/config.php')) {
    require_once(__DIR__ . '/config.php');
} else {
    // Fallback to environment-based config
    require_once(__DIR__ . '/environment.php');

    // Define minimal constants for database connection
    if (!defined('DB_HOST')) {
        define('DB_HOST', 'localhost');
    }
    if (!defined('DB_NAME')) {
        define('DB_NAME', 'codedart_db');
    }
    if (!defined('DB_USER')) {
        define('DB_USER', '');
    }
    if (!defined('DB_PASS')) {
        define('DB_PASS', '');
    }
}

require_once(__DIR__ . '/database.php');

echo "🔄 Starting database migration: Opacity fields for A-Frame\n\n";

try {
    $db = getDbConnection();
    $isSQLite = defined('DB_TYPE') && DB_TYPE === 'sqlite';

    echo "Database type: " . ($isSQLite ? 'SQLite' : 'MySQL') . "\n\n";

    // Check if columns already exist
    if ($isSQLite) {
        $result = $db->query("PRAGMA table_info(aframe_art)");
        $columns = $result->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_column($columns, 'name');
        $skyOpacityExists = in_array('sky_opacity', $columnNames);
        $groundOpacityExists = in_array('ground_opacity', $columnNames);
    } else {
        $skyOpacityStmt = $db->query("SHOW COLUMNS FROM aframe_art LIKE 'sky_opacity'");
        $skyOpacityExists = $skyOpacityStmt->rowCount() > 0;
        $groundOpacityStmt = $db->query("SHOW COLUMNS FROM aframe_art LIKE 'ground_opacity'");
        $groundOpacityExists = $groundOpacityStmt->rowCount() > 0;
    }

    if ($skyOpacityExists && $groundOpacityExists) {
        echo "✓ Migration already applied. Opacity columns exist.\n";
        exit(0);
    }

    echo "Adding new opacity columns to aframe_art table...\n";

    if (!$skyOpacityExists) {
        if ($isSQLite) {
            // SQLite: DECIMAL stored as REAL
            $db->exec("ALTER TABLE aframe_art ADD COLUMN sky_opacity REAL DEFAULT 1.0");
        } else {
            // MySQL: Use DECIMAL(3,2) for precise 0.00-1.00 range
            $db->exec("ALTER TABLE aframe_art ADD COLUMN sky_opacity DECIMAL(3,2) DEFAULT 1.00 COMMENT 'Sky color opacity (0.00-1.00)' AFTER sky_texture");
        }
        echo "✓ Added sky_opacity column\n";
    } else {
        echo "⊙ sky_opacity column already exists\n";
    }

    if (!$groundOpacityExists) {
        if ($isSQLite) {
            $db->exec("ALTER TABLE aframe_art ADD COLUMN ground_opacity REAL DEFAULT 1.0");
        } else {
            $db->exec("ALTER TABLE aframe_art ADD COLUMN ground_opacity DECIMAL(3,2) DEFAULT 1.00 COMMENT 'Ground color opacity (0.00-1.00)' AFTER ground_texture");
        }
        echo "✓ Added ground_opacity column\n";
    } else {
        echo "⊙ ground_opacity column already exists\n";
    }

    echo "\n✅ Migration completed successfully!\n";
    echo "\nThe following columns have been added to aframe_art:\n";
    echo "  - sky_opacity (default: 1.00) - Controls sky color transparency\n";
    echo "  - ground_opacity (default: 1.00) - Controls ground color transparency\n";
    echo "\nNote: Per-shape opacity is stored in the configuration JSON.\n";

} catch (PDOException $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    echo "\nIf you see 'Duplicate column name' errors, the migration was already applied.\n";
    exit(1);
}
