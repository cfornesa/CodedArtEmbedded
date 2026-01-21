<?php
/**
 * Database Migration: Add Sky/Ground Fields to A-Frame
 *
 * This migration adds separate sky and ground fields to replace the old
 * generic texture_urls system.
 *
 * Usage: Run this file once via browser or CLI: php migrate_sky_ground.php
 */

require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/database.php');

echo "🔄 Starting database migration: Sky/Ground fields for A-Frame\n\n";

try {
    $db = getDbConnection();

    // Check if columns already exist
    $result = $db->query("SHOW COLUMNS FROM aframe_art LIKE 'sky_color'");
    if ($result->rowCount() > 0) {
        echo "✓ Migration already applied. Columns exist.\n";
        exit(0);
    }

    echo "Adding new columns to aframe_art table...\n";

    // Add sky_color column
    $db->exec("ALTER TABLE aframe_art ADD COLUMN sky_color VARCHAR(20) DEFAULT '#ECECEC' COMMENT 'Sky/background color' AFTER scene_type");
    echo "✓ Added sky_color column\n";

    // Add sky_texture column
    $db->exec("ALTER TABLE aframe_art ADD COLUMN sky_texture VARCHAR(500) COMMENT 'Optional sky texture URL' AFTER sky_color");
    echo "✓ Added sky_texture column\n";

    // Add ground_color column
    $db->exec("ALTER TABLE aframe_art ADD COLUMN ground_color VARCHAR(20) DEFAULT '#7BC8A4' COMMENT 'Ground/foreground color' AFTER sky_texture");
    echo "✓ Added ground_color column\n";

    // Add ground_texture column
    $db->exec("ALTER TABLE aframe_art ADD COLUMN ground_texture VARCHAR(500) COMMENT 'Optional ground texture URL' AFTER ground_color");
    echo "✓ Added ground_texture column\n";

    echo "\n✅ Migration completed successfully!\n";
    echo "\nThe following columns have been added to aframe_art:\n";
    echo "  - sky_color (default: #ECECEC)\n";
    echo "  - sky_texture (optional)\n";
    echo "  - ground_color (default: #7BC8A4)\n";
    echo "  - ground_texture (optional)\n";

} catch (PDOException $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    echo "\nIf you see 'Duplicate column name' errors, the migration was already applied.\n";
    exit(1);
}
