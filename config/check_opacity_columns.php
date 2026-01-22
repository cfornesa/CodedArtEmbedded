<?php
require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/database.php');

$db = getDBConnection();

echo "=== Checking aframe_art columns ===\n\n";

$stmt = $db->query("PRAGMA table_info(aframe_art)");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "All columns:\n";
foreach ($columns as $col) {
    echo "  {$col['name']} - {$col['type']}\n";
}

echo "\n";

// Check specifically for opacity columns
$hasSky = false;
$hasGround = false;

foreach ($columns as $col) {
    if ($col['name'] === 'sky_opacity') $hasSky = true;
    if ($col['name'] === 'ground_opacity') $hasGround = true;
}

echo "sky_opacity present: " . ($hasSky ? 'YES' : 'NO') . "\n";
echo "ground_opacity present: " . ($hasGround ? 'YES' : 'NO') . "\n";
