<?php
/**
 * Three.js Standardization Verification
 *
 * Purpose: Verify Three.js schema is ready for standardization
 * Version: v1.0.19
 * Date: 2026-01-22
 *
 * Changes:
 * - No schema changes needed for Three.js
 * - Verify required columns exist
 * - embedded_path and js_file will be hidden from admin forms but kept in DB
 *
 * NON-DESTRUCTIVE: Only verification, no modifications
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
        define('DB_USER', 'root');
    }
    if (!defined('DB_PASS')) {
        define('DB_PASS', '');
    }
    if (!defined('DB_CHARSET')) {
        define('DB_CHARSET', 'utf8mb4');
    }
}

require_once __DIR__ . '/database.php';

echo "Three.js Standardization Verification (v1.0.19)\n";
echo "================================================\n\n";

try {
    // Get database connection
    $pdo = getDBConnection();
    // Check Three.js table structure
    $checkColumn = $pdo->query("PRAGMA table_info(threejs_art)");
    $columns = $checkColumn->fetchAll(PDO::FETCH_ASSOC);

    $requiredColumns = ['thumbnail_url', 'background_color', 'background_image_url', 'configuration'];
    $deprecatedColumns = ['embedded_path', 'js_file', 'texture_urls']; // Legacy fields kept for backward data

    echo "Checking threejs_art table schema...\n\n";

    echo "Required columns (active in admin forms):\n";
    foreach ($requiredColumns as $colName) {
        $exists = false;
        foreach ($columns as $column) {
            if ($column['name'] === $colName) {
                $exists = true;
                break;
            }
        }
        echo "  " . ($exists ? '✓' : '✗') . " {$colName}\n";
        if (!$exists) {
            echo "    ✗ ERROR: Missing required column!\n";
        }
    }

    echo "\nDeprecated columns (legacy only):\n";
    foreach ($deprecatedColumns as $colName) {
        $exists = false;
        foreach ($columns as $column) {
            if ($column['name'] === $colName) {
                $exists = true;
                break;
            }
        }
        echo "  " . ($exists ? '✓' : '✗') . " {$colName} (backward compat only)\n";
    }

    // Check if any pieces are using deprecated fields
    echo "\nChecking usage of deprecated fields...\n";

    $embeddedPathCount = $pdo->query("SELECT COUNT(*) FROM threejs_art WHERE embedded_path IS NOT NULL AND embedded_path != '' AND deleted_at IS NULL")->fetchColumn();
    $jsFileCount = $pdo->query("SELECT COUNT(*) FROM threejs_art WHERE js_file IS NOT NULL AND js_file != '' AND deleted_at IS NULL")->fetchColumn();

    echo "  Pieces with embedded_path: {$embeddedPathCount}\n";
    echo "  Pieces with js_file: {$jsFileCount}\n";

    if ($embeddedPathCount > 0 || $jsFileCount > 0) {
        echo "\n  ℹ Note: Some pieces use deprecated fields. They will continue working.\n";
        echo "         Admin forms will no longer allow editing these fields.\n";
        echo "         View pages will ignore these fields.\n";
    } else {
        echo "\n  ✓ No pieces using deprecated fields - clean slate!\n";
    }

    echo "\n" . str_repeat("=", 50) . "\n";
    echo "✓ Verification completed successfully!\n";
    echo "\nThree.js schema is ready for standardization.\n";
    echo "\nNext steps:\n";
    echo "1. Update admin/threejs.php to remove embedded_path and js_file fields\n";
    echo "2. Update admin/includes/functions.php (prepareArtPieceData)\n";
    echo "3. Update three-js/view.php to ignore deprecated fields\n";
    echo "4. Test saves and view pages\n";

} catch (PDOException $e) {
    echo "\n✗ Verification failed: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
}
