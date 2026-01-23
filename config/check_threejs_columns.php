<?php
/**
 * Check if threejs_art table has background_color column
 */

require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/database.php');

try {
    $db = getDBConnection();
    $stmt = $db->query('PRAGMA table_info(threejs_art)');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Columns in threejs_art table:\n";
    foreach ($columns as $col) {
        echo "  - " . $col['name'] . " (" . $col['type'] . ")" . ($col['notnull'] ? ' NOT NULL' : '') . "\n";
    }

    // Check specifically for background_color
    $hasBackgroundColor = false;
    foreach ($columns as $col) {
        if ($col['name'] === 'background_color') {
            $hasBackgroundColor = true;
            break;
        }
    }

    echo "\n" . ($hasBackgroundColor ? '✓' : '✗') . " background_color column" . ($hasBackgroundColor ? ' EXISTS' : ' MISSING') . "\n";

    // If missing, provide migration instructions
    if (!$hasBackgroundColor) {
        echo "\nTO FIX: Run this SQL command:\n";
        echo "ALTER TABLE threejs_art ADD COLUMN background_color VARCHAR(20) DEFAULT '#000000';\n";
    }

    // Test an actual piece
    echo "\n--- Testing piece-1 ---\n";
    $piece = $db->query("SELECT id, slug, background_color FROM threejs_art WHERE slug = 'piece-1' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($piece) {
        echo "✓ Found piece-1\n";
        echo "  ID: " . $piece['id'] . "\n";
        echo "  Slug: " . $piece['slug'] . "\n";
        echo "  Background Color: " . ($piece['background_color'] ?? 'NULL') . "\n";
    } else {
        echo "✗ piece-1 not found\n";
    }

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
