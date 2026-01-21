<?php
/**
 * Quick Check: Sky/Ground Columns
 *
 * This script checks if the new sky/ground columns exist in the aframe_art table.
 * Run this to verify if you need to run the migration.
 */

require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/database.php');

echo "🔍 Checking for sky/ground columns in aframe_art table...\n\n";

try {
    $db = getDbConnection();

    // Get column information
    $result = $db->query("SHOW COLUMNS FROM aframe_art");
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);

    // Check for each required column
    $requiredColumns = ['sky_color', 'sky_texture', 'ground_color', 'ground_texture'];
    $existingColumns = array_column($columns, 'Field');

    $allExist = true;

    foreach ($requiredColumns as $col) {
        if (in_array($col, $existingColumns)) {
            echo "✅ Column '{$col}' exists\n";
        } else {
            echo "❌ Column '{$col}' is MISSING\n";
            $allExist = false;
        }
    }

    echo "\n";

    if ($allExist) {
        echo "✅ All sky/ground columns exist! Your database is up to date.\n";
        echo "\nIf your changes aren't showing, the issue is elsewhere.\n";
    } else {
        echo "⚠️  Missing columns detected!\n";
        echo "\n📋 Action Required:\n";
        echo "   Run the migration script:\n";
        echo "   php config/migrate_sky_ground.php\n";
    }

} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    echo "\nMake sure your database connection is configured correctly.\n";
    exit(1);
}
