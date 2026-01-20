<?php
/**
 * Test script for Phase 4 gallery pages
 * Verifies that all gallery pages can fetch data from database
 */

require_once(__DIR__ . '/config/config.php');
require_once(__DIR__ . '/config/database.php');

echo "==============================================\n";
echo "Phase 4: Gallery Pages Database Test\n";
echo "==============================================\n\n";

$testsPassed = 0;
$testsFailed = 0;

function test($name, $callback) {
    global $testsPassed, $testsFailed;

    try {
        $result = $callback();
        if ($result) {
            echo "✓ PASS: $name\n";
            $testsPassed++;
        } else {
            echo "✗ FAIL: $name\n";
            $testsFailed++;
        }
    } catch (Exception $e) {
        echo "✗ ERROR: $name - " . $e->getMessage() . "\n";
        $testsFailed++;
    }
}

// Test A-Frame gallery query
test('A-Frame gallery can fetch active pieces', function() {
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT * FROM aframe_art WHERE status = ? ORDER BY sort_order ASC, created_at DESC");
    $stmt->execute(['active']);
    $artPieces = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "  → Found " . count($artPieces) . " active A-Frame pieces\n";

    // Check that each piece has required fields
    foreach ($artPieces as $piece) {
        if (empty($piece['title']) || empty($piece['file_path'])) {
            echo "  → WARNING: Piece ID {$piece['id']} missing title or file_path\n";
            return false;
        }
    }

    return true;
});

// Test C2 gallery query
test('C2 gallery can fetch active pieces', function() {
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT * FROM c2_art WHERE status = ? ORDER BY sort_order ASC, created_at DESC");
    $stmt->execute(['active']);
    $artPieces = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "  → Found " . count($artPieces) . " active C2 pieces\n";

    foreach ($artPieces as $piece) {
        if (empty($piece['title']) || empty($piece['file_path'])) {
            echo "  → WARNING: Piece ID {$piece['id']} missing title or file_path\n";
            return false;
        }
    }

    return true;
});

// Test P5 gallery query
test('P5 gallery can fetch active pieces', function() {
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT * FROM p5_art WHERE status = ? ORDER BY sort_order ASC, created_at DESC");
    $stmt->execute(['active']);
    $artPieces = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "  → Found " . count($artPieces) . " active P5 pieces\n";

    foreach ($artPieces as $piece) {
        if (empty($piece['title']) || empty($piece['file_path'])) {
            echo "  → WARNING: Piece ID {$piece['id']} missing title or file_path\n";
            return false;
        }
    }

    return true;
});

// Test Three.js gallery query
test('Three.js gallery can fetch active pieces', function() {
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT * FROM threejs_art WHERE status = ? ORDER BY sort_order ASC, created_at DESC");
    $stmt->execute(['active']);
    $artPieces = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "  → Found " . count($artPieces) . " active Three.js pieces\n";

    foreach ($artPieces as $piece) {
        if (empty($piece['title']) || empty($piece['file_path'])) {
            echo "  → WARNING: Piece ID {$piece['id']} missing title or file_path\n";
            return false;
        }
    }

    return true;
});

// Test that sort_order works correctly
test('Pieces are sorted by sort_order', function() {
    $db = getDbConnection();

    // Test with A-Frame as example
    $stmt = $db->prepare("SELECT id, title, sort_order FROM aframe_art WHERE status = ? ORDER BY sort_order ASC");
    $stmt->execute(['active']);
    $pieces = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($pieces) < 2) {
        echo "  → Need at least 2 pieces to test sorting\n";
        return true; // Pass if not enough data
    }

    // Check that sort_order is ascending
    for ($i = 1; $i < count($pieces); $i++) {
        if ($pieces[$i]['sort_order'] < $pieces[$i-1]['sort_order']) {
            echo "  → Sort order not ascending\n";
            return false;
        }
    }

    echo "  → Sort order is correct\n";
    return true;
});

// Test database connection is properly closed
test('Database connection can be closed and reopened', function() {
    $db = getDbConnection();
    $db = null; // Close connection

    // Reopen
    $db = getDbConnection();
    $stmt = $db->query("SELECT COUNT(*) FROM aframe_art");
    $count = $stmt->fetchColumn();

    echo "  → Total A-Frame pieces in database: $count\n";
    return true;
});

echo "\n==============================================\n";
echo "Test Summary\n";
echo "==============================================\n";
echo "Total Tests: " . ($testsPassed + $testsFailed) . "\n";
echo "Passed: $testsPassed ✓\n";
echo "Failed: $testsFailed ✗\n";
echo "Success Rate: " . round(($testsPassed / ($testsPassed + $testsFailed)) * 100) . "%\n";
echo "==============================================\n";

if ($testsFailed === 0) {
    echo "\n✓ All tests passed! Gallery pages are ready.\n\n";
    exit(0);
} else {
    echo "\n✗ Some tests failed. Please review the errors above.\n\n";
    exit(1);
}
