<?php
/**
 * Test Admin Save Workflow
 * Simulates saving from admin interface to diagnose issues
 */

require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/database.php');
require_once(__DIR__ . '/../admin/includes/functions.php');
require_once(__DIR__ . '/../admin/includes/slug_functions.php');

echo "=== TESTING ADMIN SAVE WORKFLOW ===\n\n";

// Test 1: P5.js Save
echo "TEST 1: P5.js Save\n";
echo "-------------------\n";

$p5Data = [
    'title' => 'Admin Test P5',
    'slug' => '',  // Let it auto-generate
    'description' => 'Testing admin save workflow',
    'thumbnail_url' => '',
    'background_image_url' => '',
    'tags' => 'test',
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

        // Verify it saved correctly
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT configuration FROM p5_art WHERE id = ?");
        $stmt->execute([$result['id']]);
        $piece = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($piece) {
            $config = json_decode($piece['configuration'], true);
            if ($config && isset($config['shapes']) && count($config['shapes']) === 3) {
                echo "✓ Configuration saved correctly (3 shapes found)\n";
            } else {
                echo "✗ Configuration issue: " . ($config ? 'Missing shapes' : 'Invalid JSON') . "\n";
            }
        }
    } else {
        echo "✗ FAILED: " . $result['message'] . "\n";
    }
} catch (Exception $e) {
    echo "✗ EXCEPTION: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Three.js Background Color Save
echo "TEST 2: Three.js Background Color Save\n";
echo "---------------------------------------\n";

$threejsData = [
    'title' => 'Admin Test Three.js',
    'slug' => '',
    'description' => 'Testing background color save',
    'thumbnail_url' => '',
    'background_color' => '#FF5733',  // Specific color
    'background_image_url' => '',
    'tags' => 'test',
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
                    'rotation' => ['enabled' => false],
                    'position' => ['x' => ['enabled' => false], 'y' => ['enabled' => false], 'z' => ['enabled' => false]],
                    'scale' => ['enabled' => true, 'min' => 0.5, 'max' => 2.0, 'duration' => 5000]
                ]
            ]
        ],
        'sceneSettings' => [
            'background' => '#FF5733'  // Should match background_color field
        ],
        'lighting' => [
            'ambient' => ['enabled' => true, 'color' => '#FFFFFF', 'intensity' => 0.5]
        ]
    ]
];

try {
    $result = createArtPieceWithSlug('threejs', $threejsData);

    if ($result['success']) {
        echo "✓ SUCCESS: " . $result['message'] . "\n";
        echo "  Slug: " . $result['slug'] . "\n";
        echo "  ID: " . $result['id'] . "\n";

        // Verify background_color saved
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT background_color, configuration FROM threejs_art WHERE id = ?");
        $stmt->execute([$result['id']]);
        $piece = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($piece) {
            if ($piece['background_color'] === '#FF5733') {
                echo "✓ background_color saved correctly: " . $piece['background_color'] . "\n";
            } else {
                echo "✗ background_color wrong: " . ($piece['background_color'] ?: 'NULL') . " (expected #FF5733)\n";
            }

            $config = json_decode($piece['configuration'], true);
            if ($config && isset($config['geometries'][0]['animation']['scale'])) {
                $scale = $config['geometries'][0]['animation']['scale'];
                echo "✓ Scale animation saved: min=" . $scale['min'] . ", max=" . $scale['max'] . "\n";
            } else {
                echo "✗ Scale animation not found in configuration\n";
            }
        }
    } else {
        echo "✗ FAILED: " . $result['message'] . "\n";
    }
} catch (Exception $e) {
    echo "✗ EXCEPTION: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: View URLs
echo "TEST 3: Generated View URLs\n";
echo "----------------------------\n";
echo "P5.js piece would be viewable at:\n";
echo "  http://your-domain/p5/view.php?slug=" . ($result['slug'] ?? 'piece-slug') . "\n";
echo "\nThree.js piece would be viewable at:\n";
echo "  http://your-domain/three-js/view.php?slug=" . ($result['slug'] ?? 'piece-slug') . "\n";

echo "\n=== TEST COMPLETE ===\n";
