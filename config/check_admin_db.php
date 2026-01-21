<?php
/**
 * Admin Database Connection Diagnostic
 * Check what database the admin is actually using
 */

// Simulate admin environment
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/admin/aframe.php';

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

echo "=== Admin Database Connection Diagnostic ===\n\n";

try {
    $db = getDbConnection();

    echo "1. Database Connection Info:\n";
    echo "   DB_TYPE: " . (defined('DB_TYPE') ? DB_TYPE : 'undefined') . "\n";
    echo "   DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'undefined') . "\n";
    echo "   DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'undefined') . "\n\n";

    // Check if table exists
    echo "2. Checking if aframe_art table exists...\n";
    if (defined('DB_TYPE') && DB_TYPE === 'sqlite') {
        $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='aframe_art'");
        $tableExists = $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    } else {
        $stmt = $db->query("SHOW TABLES LIKE 'aframe_art'");
        $tableExists = $stmt->rowCount() > 0;
    }

    if ($tableExists) {
        echo "   ✓ Table 'aframe_art' exists\n\n";

        // Get column list
        echo "3. Checking columns in aframe_art table...\n";
        if (defined('DB_TYPE') && DB_TYPE === 'sqlite') {
            $columns = $db->query("PRAGMA table_info(aframe_art)")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($columns as $col) {
                echo "   - {$col['name']} ({$col['type']})\n";
            }
            $columnNames = array_column($columns, 'name');
        } else {
            $columns = $db->query("DESCRIBE aframe_art")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($columns as $col) {
                echo "   - {$col['Field']} ({$col['Type']})\n";
            }
            $columnNames = array_column($columns, 'Field');
        }

        // Check for sky/ground columns
        echo "\n4. Sky/Ground Column Status:\n";
        $requiredColumns = ['sky_color', 'sky_texture', 'ground_color', 'ground_texture'];
        $missingColumns = [];

        foreach ($requiredColumns as $col) {
            if (in_array($col, $columnNames)) {
                echo "   ✓ $col - EXISTS\n";
            } else {
                echo "   ✗ $col - MISSING\n";
                $missingColumns[] = $col;
            }
        }

        if (!empty($missingColumns)) {
            echo "\n❌ PROBLEM FOUND: Missing columns: " . implode(', ', $missingColumns) . "\n";
            echo "\nSOLUTION: Run migration script:\n";
            echo "   php config/migrate_sky_ground.php\n";
            echo "\nOR reinitialize database with:\n";
            echo "   php config/init_db_current.php\n";
        } else {
            echo "\n✅ All sky/ground columns present!\n";
        }

    } else {
        echo "   ✗ Table 'aframe_art' does NOT exist\n";
        echo "\n❌ CRITICAL: Database not initialized!\n";
        echo "   Run: php config/init_db_current.php\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
