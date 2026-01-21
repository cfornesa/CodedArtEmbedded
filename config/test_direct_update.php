<?php
/**
 * Test Direct Database Update
 * Bypass all admin logic and test database directly
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

echo "=== Testing Direct Database Update ===\n\n";

try {
    $db = getDbConnection();

    // Verify columns exist
    echo "1. Verifying sky/ground columns...\n";
    $columns = $db->query("PRAGMA table_info(aframe_art)")->fetchAll(PDO::FETCH_ASSOC);
    $columnNames = array_column($columns, 'name');

    foreach (['sky_color', 'sky_texture', 'ground_color', 'ground_texture'] as $col) {
        if (in_array($col, $columnNames)) {
            echo "   ✓ $col exists\n";
        } else {
            echo "   ✗ $col MISSING\n";
        }
    }

    // Get current piece
    echo "\n2. Getting current piece data...\n";
    $stmt = $db->prepare("SELECT * FROM aframe_art WHERE id = 1");
    $stmt->execute();
    $piece = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$piece) {
        echo "   ❌ Piece not found\n";
        exit(1);
    }

    echo "   ✓ Found piece: {$piece['title']}\n";
    echo "   Current sky_color: {$piece['sky_color']}\n";
    echo "   Current sky_texture: " . ($piece['sky_texture'] ?? 'NULL') . "\n\n";

    // Try direct UPDATE
    echo "3. Attempting direct UPDATE with sky/ground fields...\n";

    $updateData = [
        'sky_color' => '#FF0000',
        'sky_texture' => 'https://example.com/test-sky.jpg',
        'ground_color' => '#00FF00',
        'ground_texture' => 'https://example.com/test-ground.jpg',
        'updated_at' => date('Y-m-d H:i:s')
    ];

    $updateStmt = $db->prepare("
        UPDATE aframe_art
        SET sky_color = ?,
            sky_texture = ?,
            ground_color = ?,
            ground_texture = ?,
            updated_at = ?
        WHERE id = 1
    ");

    $success = $updateStmt->execute([
        $updateData['sky_color'],
        $updateData['sky_texture'],
        $updateData['ground_color'],
        $updateData['ground_texture'],
        $updateData['updated_at']
    ]);

    if ($success) {
        echo "   ✓ UPDATE executed successfully\n\n";

        // Verify
        echo "4. Verifying update...\n";
        $verifyStmt = $db->prepare("SELECT * FROM aframe_art WHERE id = 1");
        $verifyStmt->execute();
        $updated = $verifyStmt->fetch(PDO::FETCH_ASSOC);

        echo "   New sky_color: {$updated['sky_color']}\n";
        echo "   New sky_texture: {$updated['sky_texture']}\n";
        echo "   New ground_color: {$updated['ground_color']}\n";
        echo "   New ground_texture: {$updated['ground_texture']}\n";

        if ($updated['sky_color'] === $updateData['sky_color']) {
            echo "\n✅ Direct database update works correctly!\n";
        }
    } else {
        $errorInfo = $updateStmt->errorInfo();
        echo "   ❌ UPDATE failed\n";
        echo "   Error: " . implode(' - ', $errorInfo) . "\n";
    }

} catch (PDOException $e) {
    echo "❌ PDO Exception: " . $e->getMessage() . "\n";
    echo "   Error Code: " . $e->getCode() . "\n";
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}
