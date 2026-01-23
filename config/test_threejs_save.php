<?php
/**
 * Test script to verify Three.js can save background_color and scale animation configuration
 */

$db_path = __DIR__ . '/codedart.db';

try {
    $pdo = new PDO('sqlite:' . $db_path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== TESTING THREE.JS SAVE FUNCTIONALITY ===" . PHP_EOL . PHP_EOL;

    // Test configuration with scale animation min/max
    $testConfig = [
        'geometries' => [
            [
                'id' => 1,
                'type' => 'box',
                'animation' => [
                    'scale' => [
                        'enabled' => true,
                        'min' => 0.5,
                        'max' => 2.0,
                        'duration' => 5000
                    ]
                ]
            ]
        ]
    ];

    $configuration = json_encode($testConfig);
    $background_color = '#FF5733';

    echo "Test Data:" . PHP_EOL;
    echo "- background_color: $background_color" . PHP_EOL;
    echo "- configuration: $configuration" . PHP_EOL . PHP_EOL;

    // Insert test piece
    echo "Inserting test Three.js piece..." . PHP_EOL;
    $stmt = $pdo->prepare("
        INSERT INTO threejs_art (
            title, slug, description,
            background_color, configuration, status, created_by
        ) VALUES (
            :title, :slug, :description,
            :background_color, :configuration, :status, :created_by
        )
    ");

    $stmt->execute([
        ':title' => 'Test Three.js Piece',
        ':slug' => 'test-threejs-' . time(),
        ':description' => 'Test piece for background_color and scale animation',
        ':background_color' => $background_color,
        ':configuration' => $configuration,
        ':status' => 'draft',
        ':created_by' => 1
    ]);

    $insertId = $pdo->lastInsertId();
    echo "✓ Inserted with ID: $insertId" . PHP_EOL . PHP_EOL;

    // Retrieve and verify
    echo "Retrieving piece..." . PHP_EOL;
    $stmt = $pdo->prepare("SELECT background_color, configuration FROM threejs_art WHERE id = ?");
    $stmt->execute([$insertId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "Retrieved Data:" . PHP_EOL;
    echo "- background_color: " . $row['background_color'] . PHP_EOL;
    echo "- configuration: " . $row['configuration'] . PHP_EOL . PHP_EOL;

    // Parse configuration to verify scale min/max
    $config = json_decode($row['configuration'], true);
    $scaleConfig = $config['geometries'][0]['animation']['scale'];

    echo "=== VERIFICATION ===" . PHP_EOL . PHP_EOL;

    $checks = [
        'background_color saved' => $row['background_color'] === $background_color,
        'configuration is JSON' => !is_null($config),
        'scale enabled saved' => $scaleConfig['enabled'] === true,
        'scale min saved' => $scaleConfig['min'] === 0.5,
        'scale max saved' => $scaleConfig['max'] === 2.0,
        'scale duration saved' => $scaleConfig['duration'] === 5000
    ];

    foreach ($checks as $check => $passed) {
        echo ($passed ? '✓' : '✗') . " $check" . PHP_EOL;
    }

    $allPassed = !in_array(false, $checks, true);

    echo PHP_EOL;
    if ($allPassed) {
        echo "✅ ALL CHECKS PASSED - Three.js save functionality working correctly!" . PHP_EOL;
    } else {
        echo "❌ SOME CHECKS FAILED - Review output above" . PHP_EOL;
    }

} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
