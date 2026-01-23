<?php
/**
 * Test P5.js Save Functionality
 * Verifies P5.js pieces can be saved and retrieved
 */

$db_path = __DIR__ . '/codedart.db';

try {
    $pdo = new PDO('sqlite:' . $db_path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== TESTING P5.JS SAVE FUNCTIONALITY ===\n\n";

    // Test configuration
    $testConfig = [
        'canvas' => [
            'width' => 800,
            'height' => 600,
            'renderer' => 'P2D',
            'background' => '#FFFFFF'
        ],
        'drawing' => [
            'shapeType' => 'ellipse',
            'shapeCount' => 5,
            'shapeSize' => 50
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
    ];

    $configJson = json_encode($testConfig);

    echo "Test Configuration:\n";
    echo "- Has canvas config: Yes\n";
    echo "- Has shapes palette: " . count($testConfig['shapes']) . " shapes\n";
    echo "- Pattern type: " . $testConfig['pattern']['type'] . "\n";
    echo "- Shape types: " . implode(', ', array_column($testConfig['shapes'], 'shape')) . "\n\n";

    // Insert test piece
    echo "Inserting test P5.js piece...\n";
    $stmt = $pdo->prepare("INSERT INTO p5_art (
        title, slug, description,
        background_image_url, configuration,
        status, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, datetime('now'))");

    $stmt->execute([
        'Test P5 Piece',
        'test-p5-piece-' . time(),
        'Diagnostic test piece',
        null, // No background image
        $configJson,
        'active'
    ]);

    $pieceId = $pdo->lastInsertId();
    echo "✓ Inserted with ID: $pieceId\n\n";

    // Retrieve and verify
    echo "Retrieving piece...\n";
    $stmt = $pdo->prepare("SELECT * FROM p5_art WHERE id = ?");
    $stmt->execute([$pieceId]);
    $piece = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$piece) {
        echo "❌ FAILED to retrieve piece!\n";
        exit(1);
    }

    echo "Retrieved Data:\n";
    echo "- Title: " . $piece['title'] . "\n";
    echo "- Slug: " . $piece['slug'] . "\n";
    echo "- Configuration length: " . strlen($piece['configuration']) . " bytes\n\n";

    // Verify configuration
    $savedConfig = json_decode($piece['configuration'], true);

    if (!$savedConfig) {
        echo "❌ Configuration is not valid JSON!\n";
        exit(1);
    }

    echo "=== VERIFICATION ===\n\n";

    $checks = [
        'Canvas config exists' => isset($savedConfig['canvas']),
        'Drawing config exists' => isset($savedConfig['drawing']),
        'Shapes palette exists' => isset($savedConfig['shapes']),
        'Pattern config exists' => isset($savedConfig['pattern']),
        'Animation config exists' => isset($savedConfig['animation']),
        'Shape type is correct' => ($savedConfig['drawing']['shapeType'] ?? null) === 'ellipse',
        'Shapes count is 3' => count($savedConfig['shapes'] ?? []) === 3,
        'First shape is ellipse' => ($savedConfig['shapes'][0]['shape'] ?? null) === 'ellipse',
        'Second shape is ellipse' => ($savedConfig['shapes'][1]['shape'] ?? null) === 'ellipse',
        'Third shape is triangle' => ($savedConfig['shapes'][2]['shape'] ?? null) === 'triangle',
        'usePalette is true' => ($savedConfig['usePalette'] ?? false) === true
    ];

    $passCount = 0;
    $failCount = 0;

    foreach ($checks as $check => $result) {
        echo ($result ? '✓' : '✗') . " $check\n";
        if ($result) {
            $passCount++;
        } else {
            $failCount++;
        }
    }

    echo "\n=== RESULTS ===\n\n";
    echo "Passed: $passCount/" . count($checks) . "\n";
    echo "Failed: $failCount/" . count($checks) . "\n\n";

    if ($failCount > 0) {
        echo "❌ SOME CHECKS FAILED\n\n";
        echo "Saved configuration:\n";
        echo json_encode($savedConfig, JSON_PRETTY_PRINT) . "\n";
        exit(1);
    }

    echo "✅ ALL CHECKS PASSED\n\n";
    echo "P5.js save functionality is working correctly!\n";
    echo "Piece can be viewed at: /p5/view.php?slug=" . $piece['slug'] . "\n\n";

    // Cleanup
    echo "Cleaning up test piece...\n";
    $stmt = $pdo->prepare("DELETE FROM p5_art WHERE id = ?");
    $stmt->execute([$pieceId]);
    echo "✓ Test piece deleted\n";

} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
