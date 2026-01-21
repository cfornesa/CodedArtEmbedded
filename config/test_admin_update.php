<?php
/**
 * Test Admin Update Flow
 * Simulate the exact update flow the admin uses
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../admin/includes/functions.php';
require_once __DIR__ . '/../admin/includes/slug_functions.php';
require_once __DIR__ . '/../admin/includes/auth.php';

echo "=== Testing Admin Update Flow ===\n\n";

// Simulate authenticated user (bypass session issues)
$GLOBALS['test_user'] = [
    'id' => 1,
    'email' => 'admin@localhost',
    'first_name' => 'Admin',
    'last_name' => 'User'
];

// Override getCurrentUser for testing
function getCurrentUser() {
    return $GLOBALS['test_user'];
}

try {
    // Get existing piece
    echo "1. Fetching existing piece...\n";
    $piece = getArtPiece('aframe', 1);
    if (!$piece) {
        echo "   ❌ Piece not found\n";
        exit(1);
    }
    echo "   ✓ Found piece: {$piece['title']}\n";
    echo "   Current slug: {$piece['slug']}\n\n";

    // Simulate form data (exactly as admin form would send)
    echo "2. Preparing update data...\n";
    $data = [
        'title' => $piece['title'],
        'slug' => $piece['slug'],
        'description' => $piece['description'],
        'thumbnail_url' => $piece['thumbnail_url'],
        'scene_type' => $piece['scene_type'] ?? 'custom',
        'sky_color' => '#FF0000',  // NEW VALUE
        'sky_texture' => 'https://example.com/new-sky.jpg',  // NEW VALUE
        'ground_color' => '#00FF00',  // NEW VALUE
        'ground_texture' => 'https://example.com/new-ground.jpg',  // NEW VALUE
        'tags' => $piece['tags'],
        'status' => $piece['status'],
        'sort_order' => $piece['sort_order']
    ];

    echo "   ✓ Data prepared\n";
    echo "   Sky Color: {$data['sky_color']}\n";
    echo "   Sky Texture: {$data['sky_texture']}\n";
    echo "   Ground Color: {$data['ground_color']}\n";
    echo "   Ground Texture: {$data['ground_texture']}\n\n";

    // Call the update function
    echo "3. Calling updateArtPieceWithSlug()...\n";
    $result = updateArtPieceWithSlug('aframe', 1, $data);

    if ($result['success']) {
        echo "   ✅ SUCCESS: {$result['message']}\n\n";

        // Verify the update
        echo "4. Verifying update...\n";
        $updated = getArtPiece('aframe', 1);
        echo "   Sky Color: {$updated['sky_color']}\n";
        echo "   Sky Texture: {$updated['sky_texture']}\n";
        echo "   Ground Color: {$updated['ground_color']}\n";
        echo "   Ground Texture: {$updated['ground_texture']}\n";

        if ($updated['sky_color'] === $data['sky_color']
            && $updated['sky_texture'] === $data['sky_texture']
            && $updated['ground_color'] === $data['ground_color']
            && $updated['ground_texture'] === $data['ground_texture']) {
            echo "\n✅ All values updated correctly!\n";
        } else {
            echo "\n⚠️ Values don't match expected\n";
        }
    } else {
        echo "   ❌ FAILED: {$result['message']}\n";
        echo "\n   This is the error the user is seeing!\n";
    }

} catch (Exception $e) {
    echo "❌ Exception caught: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "   Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}
