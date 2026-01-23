<?php
/**
 * Test Direct Save (Bypass Auth)
 * Tests the core save functionality without authentication
 */

require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/database.php');

echo "=== TESTING DIRECT SAVE FUNCTIONALITY ===\n\n";

$pdo = getDBConnection();

// Test 1: P5.js Direct Save
echo "TEST 1: P5.js Direct Insert\n";
echo "----------------------------\n";

$p5Config = json_encode([
    'canvas' => [
        'width' => 800,
        'height' => 600,
        'background' => '#FFFFFF',
        'renderer' => 'P2D'
    ],
    'drawing' => [
        'shapeType' => 'ellipse',
        'shapeSize' => 50,
        'fillColor' => '#FF6B6B'
    ],
    'shapes' => [
        ['shape' => 'ellipse', 'color' => '#FF6B6B'],
        ['shape' => 'ellipse', 'color' => '#4ECDC4'],
        ['shape' => 'triangle', 'color' => '#45B7D1']
    ],
    'pattern' => ['type' => 'grid', 'spacing' => 100],
    'animation' => ['animated' => true, 'speed' => 1],
    'usePalette' => true
]);

try {
    $stmt = $pdo->prepare("
        INSERT INTO p5_art (title, slug, description, configuration, status, created_at)
        VALUES (?, ?, ?, ?, ?, datetime('now'))
    ");

    $slug = 'colorful-pattern-' . time();
    $stmt->execute([
        'Colorful Pattern Test',
        $slug,
        'Test piece with colorful shapes',
        $p5Config,
        'active'
    ]);

    $id = $pdo->lastInsertId();
    echo "✓ P5.js piece inserted (ID: $id, Slug: $slug)\n";

    // Verify
    $verify = $pdo->prepare("SELECT configuration FROM p5_art WHERE id = ?");
    $verify->execute([$id]);
    $piece = $verify->fetch(PDO::FETCH_ASSOC);

    $config = json_decode($piece['configuration'], true);
    if ($config && isset($config['shapes']) && count($config['shapes']) === 3) {
        echo "✓ Configuration verified (3 shapes found)\n";
        echo "  First shape: " . $config['shapes'][0]['shape'] . " (" . $config['shapes'][0]['color'] . ")\n";
        echo "✓ View at: /p5/view.php?slug=$slug\n";
    } else {
        echo "✗ Configuration issue\n";
    }
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Three.js Direct Save with Background Color
echo "TEST 2: Three.js Direct Insert with Background Color\n";
echo "------------------------------------------------------\n";

$threejsConfig = json_encode([
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
            'animation' => [
                'rotation' => ['enabled' => false],
                'position' => [
                    'x' => ['enabled' => false],
                    'y' => ['enabled' => false],
                    'z' => ['enabled' => false]
                ],
                'scale' => ['enabled' => true, 'min' => 0.5, 'max' => 2.0, 'duration' => 5000]
            ]
        ]
    ],
    'sceneSettings' => ['background' => '#FF5733'],
    'lighting' => ['ambient' => ['enabled' => true, 'color' => '#FFFFFF', 'intensity' => 0.5]]
]);

try {
    $stmt = $pdo->prepare("
        INSERT INTO threejs_art (title, slug, description, background_color, configuration, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, datetime('now'))
    ");

    $slug = 'scale-animation-test-' . time();
    $bgColor = '#FF5733';

    $stmt->execute([
        'Scale Animation Test',
        $slug,
        'Test piece with background color and scale animation',
        $bgColor,
        $threejsConfig,
        'active'
    ]);

    $id = $pdo->lastInsertId();
    echo "✓ Three.js piece inserted (ID: $id, Slug: $slug)\n";

    // Verify
    $verify = $pdo->prepare("SELECT background_color, configuration FROM threejs_art WHERE id = ?");
    $verify->execute([$id]);
    $piece = $verify->fetch(PDO::FETCH_ASSOC);

    if ($piece['background_color'] === $bgColor) {
        echo "✓ Background color verified: " . $piece['background_color'] . "\n";
    } else {
        echo "✗ Background color mismatch: " . ($piece['background_color'] ?: 'NULL') . "\n";
    }

    $config = json_decode($piece['configuration'], true);
    if ($config && isset($config['geometries'][0]['animation']['scale'])) {
        $scale = $config['geometries'][0]['animation']['scale'];
        echo "✓ Scale animation verified: min=" . $scale['min'] . ", max=" . $scale['max'] . ", duration=" . $scale['duration'] . "ms\n";
        echo "✓ View at: /three-js/view.php?slug=$slug\n";
    } else {
        echo "✗ Scale animation not found\n";
    }
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== SUMMARY ===\n";
$p5Count = $pdo->query("SELECT COUNT(*) FROM p5_art WHERE deleted_at IS NULL")->fetchColumn();
$threejsCount = $pdo->query("SELECT COUNT(*) FROM threejs_art WHERE deleted_at IS NULL")->fetchColumn();
echo "Total P5.js pieces: $p5Count\n";
echo "Total Three.js pieces: $threejsCount\n";
