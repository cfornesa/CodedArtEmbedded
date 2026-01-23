<?php
/**
 * Comprehensive Save Testing Using Admin Functions
 * This test uses the ACTUAL admin workflow (createArtPieceWithSlug)
 */

// Start output buffering to capture session warnings
ob_start();

require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/database.php');

// Mock session for testing (bypass authentication)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = 1; // Mock user ID
$_SESSION['authenticated'] = true;

require_once(__DIR__ . '/../admin/includes/functions.php');
require_once(__DIR__ . '/../admin/includes/slug_functions.php');

// Clear output buffer (suppress session warnings)
ob_end_clean();

echo "=== TESTING WITH ACTUAL ADMIN FUNCTIONS ===\n\n";

// ==================================================
// TEST 1: P5.js Save via createArtPieceWithSlug
// ==================================================
echo "TEST 1: P5.js Save (Using Admin Functions)\n";
echo "--------------------------------------------\n";

$p5Data = [
    'title' => 'Admin Function Test P5',
    'slug' => '', // Let it auto-generate
    'description' => 'Testing with admin functions',
    'thumbnail_url' => '',
    'background_image_url' => '',
    'tags' => 'test, admin',
    'status' => 'active',
    'sort_order' => 0,
    'configuration' => [
        'canvas' => [
            'width' => 800,
            'height' => 600,
            'background' => '#FFFFFF',
            'renderer' => 'P2D'
        ],
        'drawing' => [
            'shapeType' => 'ellipse',
            'shapeSize' => 50,
            'shapeCount' => 5,
            'fillColor' => '#FF6B6B',
            'fillOpacity' => 255,
            'noStroke' => false,
            'strokeColor' => '#000000',
            'strokeWeight' => 1
        ],
        'shapes' => [
            ['shape' => 'ellipse', 'color' => '#FF6B6B'],
            ['shape' => 'ellipse', 'color' => '#4ECDC4'],
            ['shape' => 'triangle', 'color' => '#45B7D1']
        ],
        'pattern' => [
            'type' => 'grid',
            'spacing' => 100
        ],
        'animation' => [
            'animated' => true,
            'speed' => 1,
            'loop' => true
        ],
        'usePalette' => true
    ]
];

try {
    $result = createArtPieceWithSlug('p5', $p5Data);

    if ($result['success']) {
        echo "✓ SUCCESS: " . $result['message'] . "\n";
        echo "  Slug: " . $result['slug'] . "\n";
        echo "  ID: " . $result['id'] . "\n";

        // Verify the piece
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT file_path, configuration FROM p5_art WHERE id = ?");
        $stmt->execute([$result['id']]);
        $piece = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($piece) {
            echo "✓ file_path auto-generated: " . $piece['file_path'] . "\n";

            $config = json_decode($piece['configuration'], true);
            if ($config && isset($config['shapes']) && count($config['shapes']) === 3) {
                echo "✓ Configuration saved correctly (3 shapes)\n";
                echo "✓ View URL: /p5/view.php?slug=" . $result['slug'] . "\n";
            } else {
                echo "✗ Configuration issue\n";
            }
        }
    } else {
        echo "✗ FAILED: " . $result['message'] . "\n";
        if (isset($result['error'])) {
            echo "  Error details: " . $result['error'] . "\n";
        }
    }
} catch (Exception $e) {
    echo "✗ EXCEPTION: " . $e->getMessage() . "\n";
    echo "  Stack trace:\n";
    echo "  " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n";

// ==================================================
// TEST 2: Three.js Save via createArtPieceWithSlug
// ==================================================
echo "TEST 2: Three.js Save (Using Admin Functions)\n";
echo "----------------------------------------------\n";

$threejsData = [
    'title' => 'Admin Function Test Three.js',
    'slug' => '', // Auto-generate
    'description' => 'Testing background color and scale animation',
    'thumbnail_url' => '',
    'background_color' => '#FF5733', // Orange-red
    'background_image_url' => '',
    'tags' => 'test, admin',
    'status' => 'active',
    'sort_order' => 0,
    'configuration' => [
        'geometries' => [
            [
                'id' => time(),
                'type' => 'BoxGeometry',
                'dimensions' => ['width' => 1, 'height' => 1, 'depth' => 1],
                'position' => ['x' => 0, 'y' => 0, 'z' => -5],
                'rotation' => ['x' => 0, 'y' => 0, 'z' => 0],
                'scale' => ['x' => 1, 'y' => 1, 'z' => 1],
                'color' => '#4CC3D9',
                'opacity' => 1.0,
                'texture' => '',
                'wireframe' => false,
                'metalness' => 0.5,
                'roughness' => 0.5,
                'animation' => [
                    'rotation' => ['enabled' => false, 'counterclockwise' => false, 'duration' => 10000],
                    'position' => [
                        'x' => ['enabled' => false, 'range' => 0, 'duration' => 10000],
                        'y' => ['enabled' => false, 'range' => 0, 'duration' => 10000],
                        'z' => ['enabled' => false, 'range' => 0, 'duration' => 10000]
                    ],
                    'scale' => ['enabled' => true, 'min' => 0.5, 'max' => 2.0, 'duration' => 5000]
                ]
            ]
        ],
        'sceneSettings' => [
            'background' => '#FF5733'
        ],
        'lighting' => [
            'ambient' => ['enabled' => true, 'color' => '#FFFFFF', 'intensity' => 0.5],
            'directional' => ['enabled' => true, 'color' => '#FFFFFF', 'intensity' => 1.0]
        ]
    ]
];

try {
    $result = createArtPieceWithSlug('threejs', $threejsData);

    if ($result['success']) {
        echo "✓ SUCCESS: " . $result['message'] . "\n";
        echo "  Slug: " . $result['slug'] . "\n";
        echo "  ID: " . $result['id'] . "\n";

        // Verify
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT file_path, background_color, configuration FROM threejs_art WHERE id = ?");
        $stmt->execute([$result['id']]);
        $piece = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($piece) {
            echo "✓ file_path auto-generated: " . $piece['file_path'] . "\n";

            if ($piece['background_color'] === '#FF5733') {
                echo "✓ background_color saved: " . $piece['background_color'] . "\n";
            } else {
                echo "✗ background_color wrong: " . ($piece['background_color'] ?: 'NULL') . " (expected #FF5733)\n";
            }

            $config = json_decode($piece['configuration'], true);
            if ($config && isset($config['geometries'][0]['animation']['scale'])) {
                $scale = $config['geometries'][0]['animation']['scale'];
                if ($scale['enabled'] && $scale['min'] == 0.5 && $scale['max'] == 2.0) {
                    echo "✓ Scale animation saved: min={$scale['min']}, max={$scale['max']}, duration={$scale['duration']}ms\n";
                } else {
                    echo "✗ Scale animation incorrect\n";
                }
                echo "✓ View URL: /three-js/view.php?slug=" . $result['slug'] . "\n";
            } else {
                echo "✗ Scale animation not found in configuration\n";
            }
        }
    } else {
        echo "✗ FAILED: " . $result['message'] . "\n";
        if (isset($result['error'])) {
            echo "  Error details: " . $result['error'] . "\n";
        }
    }
} catch (Exception $e) {
    echo "✗ EXCEPTION: " . $e->getMessage() . "\n";
    echo "  Stack trace:\n";
    echo "  " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n";

// ==================================================
// SUMMARY
// ==================================================
echo "=== SUMMARY ===\n";
$pdo = getDBConnection();
$p5Count = $pdo->query("SELECT COUNT(*) FROM p5_art WHERE deleted_at IS NULL")->fetchColumn();
$threejsCount = $pdo->query("SELECT COUNT(*) FROM threejs_art WHERE deleted_at IS NULL")->fetchColumn();

echo "Total P5.js pieces: $p5Count\n";
echo "Total Three.js pieces: $threejsCount\n\n";

echo "=== ALL P5.JS PIECES ===\n";
$stmt = $pdo->query("SELECT id, title, slug, file_path FROM p5_art WHERE deleted_at IS NULL ORDER BY id");
foreach ($stmt as $row) {
    echo "ID {$row['id']}: {$row['title']}\n";
    echo "  Slug: {$row['slug']}\n";
    echo "  File Path: {$row['file_path']}\n";
    echo "  View: /p5/view.php?slug={$row['slug']}\n\n";
}

echo "=== ALL THREE.JS PIECES ===\n";
$stmt = $pdo->query("SELECT id, title, slug, file_path, background_color FROM threejs_art WHERE deleted_at IS NULL ORDER BY id");
foreach ($stmt as $row) {
    echo "ID {$row['id']}: {$row['title']}\n";
    echo "  Slug: {$row['slug']}\n";
    echo "  File Path: {$row['file_path']}\n";
    echo "  Background Color: {$row['background_color']}\n";
    echo "  View: /three-js/view.php?slug={$row['slug']}\n\n";
}
