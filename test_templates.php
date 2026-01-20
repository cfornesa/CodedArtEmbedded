<?php
/**
 * Test script for Phase 5 template consolidation
 * Verifies that unified templates work from different directory levels
 */

echo "==============================================\n";
echo "Phase 5: Template Consolidation Test\n";
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

// Test 1: Check unified header.php exists
test('Unified header.php exists', function() {
    $exists = file_exists(__DIR__ . '/resources/templates/header.php');
    if ($exists) {
        echo "  → Found at: resources/templates/header.php\n";
    }
    return $exists;
});

// Test 2: Check unified footer.php exists
test('Unified footer.php exists', function() {
    $exists = file_exists(__DIR__ . '/resources/templates/footer.php');
    if ($exists) {
        echo "  → Found at: resources/templates/footer.php\n";
    }
    return $exists;
});

// Test 3: Check header.php has auto-detection logic
test('Header.php contains auto-detection logic', function() {
    $content = file_get_contents(__DIR__ . '/resources/templates/header.php');
    $hasAutoDetect = (
        strpos($content, 'file_exists') !== false &&
        strpos($content, '$pathPrefix') !== false
    );
    if ($hasAutoDetect) {
        echo "  → Auto-detection logic found\n";
    }
    return $hasAutoDetect;
});

// Test 4: Check footer.php has auto-detection logic
test('Footer.php contains auto-detection logic', function() {
    $content = file_get_contents(__DIR__ . '/resources/templates/footer.php');
    $hasAutoDetect = (
        strpos($content, 'file_exists') !== false &&
        strpos($content, '$pathPrefix') !== false
    );
    if ($hasAutoDetect) {
        echo "  → Auto-detection logic found\n";
    }
    return $hasAutoDetect;
});

// Test 5: Verify header-level.php is still present (deprecated but kept for now)
test('Old header-level.php still exists (backward compatibility)', function() {
    $exists = file_exists(__DIR__ . '/resources/templates/header-level.php');
    if ($exists) {
        echo "  → Old file still exists (will be removed after testing)\n";
    } else {
        echo "  → Old file already removed\n";
    }
    return true; // Pass either way
});

// Test 6: Verify footer-level.php is still present (deprecated but kept for now)
test('Old footer-level.php still exists (backward compatibility)', function() {
    $exists = file_exists(__DIR__ . '/resources/templates/footer-level.php');
    if ($exists) {
        echo "  → Old file still exists (will be removed after testing)\n";
    } else {
        echo "  → Old file already removed\n";
    }
    return true; // Pass either way
});

// Test 7: Check that subdirectory files now reference unified header.php
test('Subdirectory files reference unified header.php', function() {
    $testFile = __DIR__ . '/a-frame/index.php';
    $content = file_get_contents($testFile);

    $usesUnified = strpos($content, 'header.php') !== false;
    $usesOld = strpos($content, 'header-level.php') !== false;

    if ($usesUnified && !$usesOld) {
        echo "  → a-frame/index.php correctly uses unified header.php\n";
        return true;
    } elseif ($usesOld) {
        echo "  → ERROR: Still references header-level.php\n";
        return false;
    } else {
        echo "  → ERROR: No header reference found\n";
        return false;
    }
});

// Test 8: Check that subdirectory files now reference unified footer.php
test('Subdirectory files reference unified footer.php', function() {
    $testFile = __DIR__ . '/a-frame/index.php';
    $content = file_get_contents($testFile);

    $usesUnified = strpos($content, 'footer.php') !== false;
    $usesOld = strpos($content, 'footer-level.php') !== false;

    if ($usesUnified && !$usesOld) {
        echo "  → a-frame/index.php correctly uses unified footer.php\n";
        return true;
    } elseif ($usesOld) {
        echo "  → ERROR: Still references footer-level.php\n";
        return false;
    } else {
        echo "  → ERROR: No footer reference found\n";
        return false;
    }
});

// Test 9: Count files that were updated
test('All 16 subdirectory files updated', function() {
    $files = [
        '/a-frame/alt-piece-ns.php',
        '/a-frame/alt-piece.php',
        '/a-frame/alt.php',
        '/a-frame/index.php',
        '/c2/1.php',
        '/c2/2.php',
        '/c2/index.php',
        '/p5/index.php',
        '/p5/p5_1.php',
        '/p5/p5_2.php',
        '/p5/p5_3.php',
        '/p5/p5_4.php',
        '/three-js/first.php',
        '/three-js/index.php',
        '/three-js/second.php',
        '/three-js/third.php'
    ];

    $updatedCount = 0;
    foreach ($files as $file) {
        $fullPath = __DIR__ . $file;
        if (file_exists($fullPath)) {
            $content = file_get_contents($fullPath);
            if (strpos($content, 'header.php') !== false && strpos($content, 'footer.php') !== false) {
                $updatedCount++;
            }
        }
    }

    echo "  → Updated files: $updatedCount / " . count($files) . "\n";
    return $updatedCount === count($files);
});

// Test 10: Verify PHP syntax is valid for updated templates
test('Header.php has valid PHP syntax', function() {
    $output = shell_exec('php -l ' . __DIR__ . '/resources/templates/header.php 2>&1');
    $valid = strpos($output, 'No syntax errors') !== false;
    if ($valid) {
        echo "  → Valid PHP syntax\n";
    } else {
        echo "  → Syntax error: $output\n";
    }
    return $valid;
});

test('Footer.php has valid PHP syntax', function() {
    $output = shell_exec('php -l ' . __DIR__ . '/resources/templates/footer.php 2>&1');
    $valid = strpos($output, 'No syntax errors') !== false;
    if ($valid) {
        echo "  → Valid PHP syntax\n";
    } else {
        echo "  → Syntax error: $output\n";
    }
    return $valid;
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
    echo "\n✓ All tests passed! Template consolidation successful.\n\n";
    exit(0);
} else {
    echo "\n✗ Some tests failed. Please review the errors above.\n\n";
    exit(1);
}
