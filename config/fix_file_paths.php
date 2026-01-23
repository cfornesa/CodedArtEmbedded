<?php
/**
 * Fix Missing file_path Values
 * Regenerates file_path for all pieces that are missing it
 */

require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/database.php');

echo "=== FIXING MISSING file_path VALUES ===\n\n";

$pdo = getDBConnection();

// Map art type to directory
$typeMap = [
    'aframe_art' => 'a-frame',
    'c2_art' => 'c2',
    'p5_art' => 'p5',
    'threejs_art' => 'three-js'
];

$totalFixed = 0;

foreach ($typeMap as $table => $directory) {
    echo "Checking $table...\n";

    // Find pieces with empty or NULL file_path
    $stmt = $pdo->prepare("SELECT id, slug FROM $table WHERE (file_path IS NULL OR file_path = '') AND deleted_at IS NULL");
    $stmt->execute();
    $pieces = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($pieces) === 0) {
        echo "  ✓ All pieces have file_path\n\n";
        continue;
    }

    echo "  Found " . count($pieces) . " pieces needing fix\n";

    foreach ($pieces as $piece) {
        $file_path = "/{$directory}/view.php?slug=" . $piece['slug'];

        $update = $pdo->prepare("UPDATE $table SET file_path = ? WHERE id = ?");
        $update->execute([$file_path, $piece['id']]);

        echo "  ✓ ID {$piece['id']}: {$file_path}\n";
        $totalFixed++;
    }

    echo "\n";
}

echo "=== SUMMARY ===\n";
echo "Total pieces fixed: $totalFixed\n\n";

// Verify
echo "=== VERIFICATION ===\n";
foreach ($typeMap as $table => $directory) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM $table WHERE (file_path IS NULL OR file_path = '') AND deleted_at IS NULL");
    $count = $stmt->fetchColumn();

    if ($count === 0) {
        echo "✓ $table: All pieces have file_path\n";
    } else {
        echo "✗ $table: Still has $count pieces without file_path\n";
    }
}

echo "\n=== P5.JS PIECES AFTER FIX ===\n";
$stmt = $pdo->query("SELECT id, title, slug, file_path FROM p5_art WHERE deleted_at IS NULL ORDER BY id");
foreach ($stmt as $row) {
    echo "ID {$row['id']}: {$row['title']}\n";
    echo "  View: {$row['file_path']}\n\n";
}

echo "=== THREE.JS PIECES AFTER FIX ===\n";
$stmt = $pdo->query("SELECT id, title, slug, file_path, background_color FROM threejs_art WHERE deleted_at IS NULL ORDER BY id");
foreach ($stmt as $row) {
    echo "ID {$row['id']}: {$row['title']}\n";
    echo "  Background: {$row['background_color']}\n";
    echo "  View: {$row['file_path']}\n\n";
}
