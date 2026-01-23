#!/usr/bin/env php
<?php
/**
 * CLI Database Migration: Add background_color column to threejs_art
 *
 * Run from Replit shell: php config/migrate_threejs_background_color.php
 *
 * This script:
 * 1. Adds background_color column to threejs_art table
 * 2. Sets default value to '#000000' (black) for existing pieces
 */

// Include configuration
require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/database.php');

echo "\n";
echo "=====================================\n";
echo "Three.js Background Color Migration\n";
echo "=====================================\n\n";

try {
    $pdo = getDBConnection();

    echo "Checking threejs_art table...\n";

    $columns = $pdo->query("PRAGMA table_info(threejs_art)")->fetchAll(PDO::FETCH_ASSOC);
    $hasColumn = false;
    foreach ($columns as $col) {
        if ($col['name'] === 'background_color') {
            $hasColumn = true;
            break;
        }
    }

    if ($hasColumn) {
        echo "  ✓ Column 'background_color' already exists\n";
    } else {
        echo "  + Adding 'background_color' column...\n";
        $pdo->exec("ALTER TABLE threejs_art ADD COLUMN background_color VARCHAR(20) DEFAULT '#000000'");
        echo "  ✓ Column added successfully!\n";

        // Update existing pieces to have default background color
        echo "  + Setting default background color for existing pieces...\n";
        $stmt = $pdo->exec("UPDATE threejs_art SET background_color = '#000000' WHERE background_color IS NULL");
        echo "  ✓ Updated existing pieces!\n";
    }

    echo "\n";
    echo "=====================================\n";
    echo "✓ Migration Complete!\n";
    echo "=====================================\n\n";
    echo "Next steps:\n";
    echo "1. Restart your web server (Replit: stop and restart run)\n";
    echo "2. Try creating or editing a Three.js piece\n";
    echo "3. Background color field should now be available\n\n";

} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n\n";
    exit(1);
}
