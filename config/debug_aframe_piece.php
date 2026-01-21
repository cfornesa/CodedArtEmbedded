<?php
/**
 * Debug A-Frame Piece Data
 * Check what's actually stored in the database for sky/ground fields
 */

// Try to load config from environment if config.php doesn't exist
if (file_exists(__DIR__ . '/config.php')) {
    require_once(__DIR__ . '/config.php');
} else {
    // Fallback to environment-based config
    require_once(__DIR__ . '/environment.php');

    // Define minimal config for database connection
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
}

require_once(__DIR__ . '/database.php');

echo "=== A-Frame Piece Diagnostic ===\n\n";

try {
    $db = getDbConnection();

    // Check if sky/ground columns exist
    echo "1. Checking if sky/ground columns exist...\n";

    // SQLite-compatible query
    if (defined('DB_TYPE') && DB_TYPE === 'sqlite') {
        $columns = $db->query("PRAGMA table_info(aframe_art)")->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_column($columns, 'name');
    } else {
        $columns = $db->query("DESCRIBE aframe_art")->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_column($columns, 'Field');
    }

    $requiredColumns = ['sky_color', 'sky_texture', 'ground_color', 'ground_texture'];
    $missingColumns = [];

    foreach ($requiredColumns as $col) {
        if (in_array($col, $columnNames)) {
            echo "   ✓ Column '$col' exists\n";
        } else {
            echo "   ✗ Column '$col' MISSING!\n";
            $missingColumns[] = $col;
        }
    }

    if (!empty($missingColumns)) {
        echo "\n❌ ERROR: Missing columns: " . implode(', ', $missingColumns) . "\n";
        echo "   Run the migration: php config/migrate_sky_ground.php\n";
        exit(1);
    }

    // Get the first piece
    echo "\n2. Fetching first A-Frame piece...\n";
    $stmt = $db->query("SELECT id, title, slug, sky_color, sky_texture, ground_color, ground_texture FROM aframe_art ORDER BY id LIMIT 1");
    $piece = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$piece) {
        echo "   ⚠ No pieces found in database\n";
        exit(0);
    }

    echo "   Piece ID: {$piece['id']}\n";
    echo "   Title: {$piece['title']}\n";
    echo "   Slug: {$piece['slug']}\n";
    echo "   Sky Color: " . ($piece['sky_color'] ?? 'NULL') . "\n";
    echo "   Sky Texture: " . ($piece['sky_texture'] ?? 'NULL') . "\n";
    echo "   Ground Color: " . ($piece['ground_color'] ?? 'NULL') . "\n";
    echo "   Ground Texture: " . ($piece['ground_texture'] ?? 'NULL') . "\n";

    // Check if defaults are being used
    echo "\n3. Analysis:\n";
    if ($piece['sky_color'] === '#ECECEC') {
        echo "   ℹ Sky color is using default value (#ECECEC)\n";
    }
    if ($piece['ground_color'] === '#7BC8A4') {
        echo "   ℹ Ground color is using default value (#7BC8A4)\n";
    }
    if (empty($piece['sky_texture'])) {
        echo "   ℹ No sky texture set\n";
    }
    if (empty($piece['ground_texture'])) {
        echo "   ℹ No ground texture set\n";
    }

    echo "\n✅ Diagnostic complete!\n";

} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
