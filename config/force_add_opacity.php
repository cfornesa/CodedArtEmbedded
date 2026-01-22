<?php
/**
 * Force add opacity columns to aframe_art table
 * Run this via CLI or web to ensure columns exist
 */

require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/database.php');

$db = getDBConnection();

echo "=== Force Add Opacity Columns ===\n\n";

try {
    // Check current columns
    $stmt = $db->query("PRAGMA table_info(aframe_art)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $columnNames = array_column($columns, 'name');

    echo "Current columns: " . implode(', ', $columnNames) . "\n\n";

    // Add sky_opacity if missing
    if (!in_array('sky_opacity', $columnNames)) {
        echo "Adding sky_opacity column...\n";
        $db->exec("ALTER TABLE aframe_art ADD COLUMN sky_opacity REAL DEFAULT 1.0");
        echo "✓ Added sky_opacity\n";
    } else {
        echo "✓ sky_opacity already exists\n";
    }

    // Add ground_opacity if missing
    if (!in_array('ground_opacity', $columnNames)) {
        echo "Adding ground_opacity column...\n";
        $db->exec("ALTER TABLE aframe_art ADD COLUMN ground_opacity REAL DEFAULT 1.0");
        echo "✓ Added ground_opacity\n";
    } else {
        echo "✓ ground_opacity already exists\n";
    }

    echo "\n✅ All opacity columns present!\n";

    // Verify by selecting from table
    $stmt = $db->query("SELECT id, sky_opacity, ground_opacity FROM aframe_art LIMIT 1");
    $test = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($test) {
        echo "\n✓ Successfully queried opacity columns:\n";
        echo "  ID: {$test['id']}\n";
        echo "  sky_opacity: {$test['sky_opacity']}\n";
        echo "  ground_opacity: {$test['ground_opacity']}\n";
    }

    echo "\n🔄 IMPORTANT: Restart your web server now!\n";
    echo "   - If using Apache: sudo service apache2 restart\n";
    echo "   - If using PHP-FPM: sudo service php-fpm restart\n";
    echo "   - Then visit /admin/clear-cache.php in your browser\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
