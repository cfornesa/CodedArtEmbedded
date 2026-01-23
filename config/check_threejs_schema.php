<?php
/**
 * Diagnostic script to check threejs_art table schema
 */

// Minimal config for database connection
$db_path = __DIR__ . '/codedart.db';

try {
    $pdo = new PDO('sqlite:' . $db_path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== THREEJS_ART TABLE SCHEMA ===" . PHP_EOL . PHP_EOL;

    $stmt = $pdo->query('PRAGMA table_info(threejs_art)');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $col) {
        echo sprintf(
            "%-20s %-15s %s %s" . PHP_EOL,
            $col['name'],
            $col['type'],
            $col['notnull'] ? 'NOT NULL' : 'NULL',
            $col['dflt_value'] ? '(default: ' . $col['dflt_value'] . ')' : ''
        );
    }

    echo PHP_EOL . "=== CHECKING FOR EXPECTED COLUMNS ===" . PHP_EOL . PHP_EOL;

    $columnNames = array_column($columns, 'name');

    $expectedColumns = [
        'background_color' => 'Added in v1.0.21 for scene background color control',
        'background_image_url' => 'Added in v1.0.20 for single background image',
        'configuration' => 'JSON storage for geometry configurations including scale min/max'
    ];

    foreach ($expectedColumns as $col => $purpose) {
        $exists = in_array($col, $columnNames);
        echo ($exists ? '✓' : '✗') . " $col - $purpose" . PHP_EOL;
    }

    echo PHP_EOL . "=== DIAGNOSTIC SUMMARY ===" . PHP_EOL . PHP_EOL;

    if (!in_array('background_color', $columnNames)) {
        echo "❌ CRITICAL: background_color column is MISSING!" . PHP_EOL;
        echo "   Backend code expects this column (admin/includes/functions.php line 445)" . PHP_EOL;
        echo "   This causes background color saves to fail silently." . PHP_EOL . PHP_EOL;
    }

    if (!in_array('background_image_url', $columnNames)) {
        echo "⚠️  WARNING: background_image_url column is MISSING!" . PHP_EOL;
        echo "   Using old texture_urls field instead." . PHP_EOL . PHP_EOL;
    }

    if (in_array('configuration', $columnNames)) {
        echo "✓ configuration column exists - scale min/max should be stored here" . PHP_EOL . PHP_EOL;
    }

} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
