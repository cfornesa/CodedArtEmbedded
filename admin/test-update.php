<?php
/**
 * Test update to diagnose database error
 * Access via browser
 */

session_start();
require_once(__DIR__ . '/includes/auth.php');
requireAuth();

require_once(__DIR__ . '/../config/database.php');
$db = getDBConnection();

echo "<pre>";
echo "=== Testing Database Update ===\n\n";

try {
    // Check columns
    $stmt = $db->query("PRAGMA table_info(aframe_art)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $columnNames = array_column($columns, 'name');

    echo "Columns visible to web server:\n";
    echo implode(', ', $columnNames) . "\n\n";

    $hasSky = in_array('sky_opacity', $columnNames);
    $hasGround = in_array('ground_opacity', $columnNames);

    echo "sky_opacity: " . ($hasSky ? "✓ PRESENT" : "✗ MISSING") . "\n";
    echo "ground_opacity: " . ($hasGround ? "✓ PRESENT" : "✗ MISSING") . "\n\n";

    if (!$hasSky || !$hasGround) {
        echo "❌ COLUMNS MISSING FROM WEB SERVER VIEW!\n";
        echo "This is a PHP process cache issue.\n\n";
        echo "Solutions:\n";
        echo "1. Restart web server\n";
        echo "2. Clear PHP opcache\n";
        echo "3. Reconnect to database\n";
        exit;
    }

    // Try a test update
    echo "Attempting test update on piece ID 1...\n";

    $testData = [
        'sky_opacity' => 1.0,
        'ground_opacity' => 1.0,
        'updated_at' => date('Y-m-d H:i:s')
    ];

    $setParts = [];
    $values = [];
    foreach ($testData as $column => $value) {
        $setParts[] = "{$column} = ?";
        $values[] = $value;
    }
    $values[] = 1; // WHERE id = 1

    $sql = "UPDATE aframe_art SET " . implode(', ', $setParts) . " WHERE id = ?";
    echo "\nSQL: $sql\n";
    echo "Values: " . implode(', ', $values) . "\n\n";

    $stmt = $db->prepare($sql);
    $stmt->execute($values);

    echo "✓ Update successful!\n";
    echo "Rows affected: " . $stmt->rowCount() . "\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "</pre>";
