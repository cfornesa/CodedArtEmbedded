<?php
/**
 * Test if shape configuration saves correctly
 */

require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/database.php');
require_once(__DIR__ . '/../admin/includes/functions.php');

$db = getDBConnection();

echo "=== Shape Configuration Save Test ===\n\n";

// Create a test configuration with 2 shapes
$testConfig = [
    'shapes' => [
        [
            'id' => 1706000001,
            'type' => 'sphere',
            'position' => ['x' => 0, 'y' => 1.5, 'z' => -5],
            'rotation' => ['x' => 0, 'y' => 0, 'z' => 0],
            'scale' => ['x' => 1, 'y' => 1, 'z' => 1],
            'color' => '#4CC3D9',
            'texture' => '',
            'opacity' => 1.0,
            'radius' => 1,
            'animation' => [
                'rotation' => [
                    'enabled' => false,
                    'degrees' => 360,
                    'duration' => 10000
                ],
                'position' => [
                    'enabled' => false,
                    'axis' => 'y',
                    'distance' => 0,
                    'duration' => 10000
                ],
                'scale' => [
                    'enabled' => false,
                    'min' => 1.0,
                    'max' => 1.0,
                    'duration' => 10000
                ]
            ]
        ],
        [
            'id' => 1706000002,
            'type' => 'box',
            'position' => ['x' => 2, 'y' => 0.5, 'z' => -3],
            'rotation' => ['x' => 0, 'y' => 45, 'z' => 0],
            'scale' => ['x' => 1, 'y' => 1, 'z' => 1],
            'color' => '#EF2D5E',
            'texture' => '',
            'opacity' => 1.0,
            'width' => 1,
            'height' => 1,
            'depth' => 1,
            'animation' => [
                'rotation' => [
                    'enabled' => true,
                    'degrees' => 180,
                    'duration' => 5000
                ],
                'position' => [
                    'enabled' => false,
                    'axis' => 'y',
                    'distance' => 0,
                    'duration' => 10000
                ],
                'scale' => [
                    'enabled' => false,
                    'min' => 1.0,
                    'max' => 1.0,
                    'duration' => 10000
                ]
            ]
        ]
    ],
    'sceneSettings' => [
        'background' => '#ECECEC',
        'fog' => 'type: linear; color: #AAA'
    ]
];

echo "Test configuration has " . count($testConfig['shapes']) . " shapes\n\n";

// Simulate form data
$data = [
    'title' => 'Piece 1',
    'slug' => 'piece-1',
    'description' => 'Test with 2 shapes',
    'sky_color' => '#FF0000',
    'sky_texture' => 'https://example.com/test-sky.jpg',
    'ground_color' => '#00FF00',
    'ground_texture' => 'https://example.com/test-ground.jpg',
    'configuration' => $testConfig,  // Pass as array, will be JSON encoded
    'status' => 'active'
];

echo "Calling prepareArtPieceData() for aframe...\n";

$prepared = prepareArtPieceData('aframe', $data, 1);

echo "Result:\n";
echo "  configuration type: " . gettype($prepared['configuration']) . "\n";
echo "  configuration value: " . ($prepared['configuration'] === null ? 'NULL' : 'SET') . "\n";

if ($prepared['configuration'] !== null) {
    echo "  configuration length: " . strlen($prepared['configuration']) . " bytes\n";

    // Try to decode it back
    $decoded = json_decode($prepared['configuration'], true);
    if ($decoded !== null && isset($decoded['shapes'])) {
        echo "  ✓ JSON valid, " . count($decoded['shapes']) . " shapes decoded\n";
    } else {
        echo "  ✗ JSON invalid or no shapes\n";
    }
} else {
    echo "  ✗ Configuration is NULL - THIS IS THE PROBLEM!\n";
    echo "\n  Debug info:\n";
    echo "    isset(data['configuration']): " . (isset($data['configuration']) ? 'YES' : 'NO') . "\n";
    echo "    empty(data['configuration']): " . (empty($data['configuration']) ? 'YES' : 'NO') . "\n";
}

// Now test with the real update function
echo "\n\nTrying actual database update...\n";

try {
    $stmt = $db->prepare("UPDATE aframe_art SET configuration = :config WHERE id = 1");
    $configJson = json_encode($testConfig, JSON_UNESCAPED_SLASHES);
    $stmt->execute([':config' => $configJson]);

    echo "✓ Direct database update successful\n";

    // Verify
    $stmt = $db->prepare("SELECT configuration FROM aframe_art WHERE id = 1");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result && !empty($result['configuration'])) {
        $decoded = json_decode($result['configuration'], true);
        if ($decoded && isset($decoded['shapes'])) {
            echo "✓ Verified: Configuration now has " . count($decoded['shapes']) . " shapes in database\n";
        }
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
