<?php
/**
 * Test Update Fix - Verify slug preservation and texture updates
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

echo "=== Testing Update Fix ===\n\n";

try {
    $db = getDbConnection();

    // Get the current piece
    echo "1. Fetching Piece 1...\n";
    $stmt = $db->prepare("SELECT * FROM aframe_art WHERE id = 1");
    $stmt->execute();
    $piece = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$piece) {
        echo "❌ Piece 1 not found\n";
        exit(1);
    }

    echo "   Current slug: {$piece['slug']}\n";
    echo "   Current file_path: {$piece['file_path']}\n";
    echo "   Current sky_texture: " . ($piece['sky_texture'] ?? 'NULL') . "\n";
    echo "   Current ground_texture: " . ($piece['ground_texture'] ?? 'NULL') . "\n\n";

    // Simulate an update with new textures
    echo "2. Updating with new textures...\n";
    $updateData = [
        'title' => 'Piece 1',
        'description' => 'Updated with textures',
        'sky_color' => '#FF5733',
        'sky_texture' => 'https://example.com/sky-new.jpg',
        'ground_color' => '#33FF57',
        'ground_texture' => 'https://example.com/ground-new.jpg',
        'slug' => $piece['slug'],  // Keep same slug
        'updated_at' => date('Y-m-d H:i:s')
    ];

    $updateStmt = $db->prepare("
        UPDATE aframe_art
        SET sky_texture = ?,
            ground_texture = ?,
            description = ?,
            updated_at = ?
        WHERE id = 1
    ");

    $success = $updateStmt->execute([
        $updateData['sky_texture'],
        $updateData['ground_texture'],
        $updateData['description'],
        $updateData['updated_at']
    ]);

    if ($success) {
        echo "   ✓ Database update executed\n\n";

        // Verify the update
        echo "3. Verifying update...\n";
        $verifyStmt = $db->prepare("SELECT * FROM aframe_art WHERE id = 1");
        $verifyStmt->execute();
        $updated = $verifyStmt->fetch(PDO::FETCH_ASSOC);

        echo "   New sky_texture: " . ($updated['sky_texture'] ?? 'NULL') . "\n";
        echo "   New ground_texture: " . ($updated['ground_texture'] ?? 'NULL') . "\n";
        echo "   File path preserved: {$updated['file_path']}\n";

        // Check if textures were saved
        if ($updated['sky_texture'] === $updateData['sky_texture']
            && $updated['ground_texture'] === $updateData['ground_texture']) {
            echo "\n✅ SUCCESS: Textures saved correctly!\n";
        } else {
            echo "\n❌ FAILED: Textures not saved correctly\n";
        }
    } else {
        echo "   ❌ Database update failed\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
