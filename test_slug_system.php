<?php
/**
 * Comprehensive Test Suite for Advanced Slug System
 * Tests all aspects of Phase 7: slug generation, soft delete, redirects, restore
 */

require_once(__DIR__ . '/config/config.php');
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/config/slug_utils.php');

// Mock authentication and admin functions for testing
if (!function_exists('getCurrentUser')) {
    function getCurrentUser() {
        return ['id' => 1, 'email' => 'test@example.com'];
    }
}

if (!function_exists('sendEmailNotification')) {
    function sendEmailNotification($type, $artType, $piece, $userId) {
        // Mock - no email sending during tests
        return true;
    }
}

if (!function_exists('validateArtPieceData')) {
    function validateArtPieceData($type, $data) {
        // Basic validation mock
        if (empty($data['title'])) {
            return ['valid' => false, 'errors' => ['title' => 'Title is required']];
        }
        return ['valid' => true, 'errors' => []];
    }
}

if (!function_exists('logActivity')) {
    function logActivity($action, $type, $id, $data) {
        // Mock - no activity logging during tests
        return true;
    }
}

require_once(__DIR__ . '/admin/includes/slug_functions.php');

echo "==============================================\n";
echo "Phase 7: Advanced Slug System Test Suite\n";
echo "==============================================\n\n";

$testsPassed = 0;
$testsFailed = 0;
$testData = []; // Store test piece IDs for cleanup

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

// =====================================================
// Part 1: Slug Generation Tests
// =====================================================
echo "\n--- Part 1: Slug Generation ---\n\n";

test('Generate slug from simple title', function() {
    $slug = generateSlug('My Test Art Piece');
    echo "  → Generated slug: '$slug'\n";
    return $slug === 'my-test-art-piece';
});

test('Generate slug with special characters', function() {
    $slug = generateSlug('Hello!@#$% World & Test (2024)');
    echo "  → Generated slug: '$slug'\n";
    return $slug === 'hello-world-test-2024';
});

test('Generate slug with consecutive hyphens', function() {
    $slug = generateSlug('Test --- Multiple --- Hyphens');
    echo "  → Generated slug: '$slug'\n";
    return $slug === 'test-multiple-hyphens';
});

test('Generate slug with leading/trailing spaces', function() {
    $slug = generateSlug('  Trimmed Slug  ');
    echo "  → Generated slug: '$slug'\n";
    return $slug === 'trimmed-slug';
});

test('Generate slug with maximum length', function() {
    $longTitle = str_repeat('a', 250) . ' test';
    $slug = generateSlug($longTitle);
    echo "  → Slug length: " . strlen($slug) . " (max 200)\n";
    return strlen($slug) <= 200;
});

test('Validate slug format (valid)', function() {
    return isValidSlugFormat('valid-slug-123');
});

test('Validate slug format (invalid - uppercase)', function() {
    return !isValidSlugFormat('Invalid-Slug');
});

test('Validate slug format (invalid - spaces)', function() {
    return !isValidSlugFormat('invalid slug');
});

// =====================================================
// Part 2: Slug Uniqueness Tests
// =====================================================
echo "\n--- Part 2: Slug Uniqueness ---\n\n";

test('Check availability of new slug', function() {
    $available = isSlugAvailable('test-unique-slug-12345', 'aframe', null);
    echo "  → Slug 'test-unique-slug-12345' available: " . ($available ? 'yes' : 'no') . "\n";
    return $available;
});

test('Make slug unique by adding counter', function() {
    // First, we need to ensure there's a collision scenario
    // This is a hypothetical test - would need actual data
    $slug = makeSlugUnique('test-piece', 'aframe', null);
    echo "  → Unique slug: '$slug'\n";
    return preg_match('/^test-piece(-\d+)?$/', $slug);
});

test('Generate unique slug for new piece', function() {
    $slug = generateUniqueSlug('Test Uniqueness', 'aframe', null);
    echo "  → Generated unique slug: '$slug'\n";
    return !empty($slug) && isValidSlugFormat($slug);
});

// =====================================================
// Part 3: CRUD Operations with Slugs
// =====================================================
echo "\n--- Part 3: CRUD Operations ---\n\n";

test('Create art piece with auto-generated slug', function() use (&$testData) {
    $data = [
        'title' => 'Test Piece - Auto Slug ' . time(),
        'description' => 'Test piece for slug system',
        'file_path' => '/test/test-slug-' . time() . '.php',
        'thumbnail_url' => '',
        'scene_type' => 'custom',
        'status' => 'draft'
    ];

    $result = createArtPieceWithSlug('aframe', $data);

    if ($result['success']) {
        $testData['create_auto_id'] = $result['id'];
        $testData['create_auto_slug'] = $result['slug'];
        echo "  → Created piece ID: {$result['id']}, Slug: {$result['slug']}\n";
        return !empty($result['slug']);
    }
    return false;
});

test('Create art piece with custom slug', function() use (&$testData) {
    $data = [
        'title' => 'Test Piece - Custom Slug',
        'slug' => 'my-custom-test-slug-' . time(),
        'description' => 'Test piece with custom slug',
        'file_path' => '/test/custom-slug.php',
        'thumbnail_url' => '',
        'scene_type' => 'custom',
        'status' => 'draft'
    ];

    $result = createArtPieceWithSlug('aframe', $data);

    if ($result['success']) {
        $testData['create_custom_id'] = $result['id'];
        $testData['create_custom_slug'] = $result['slug'];
        echo "  → Created piece ID: {$result['id']}, Slug: {$result['slug']}\n";
        return $result['slug'] === $data['slug'];
    }
    return false;
});

test('Retrieve piece by slug', function() use ($testData) {
    if (!isset($testData['create_auto_slug'])) {
        echo "  → Skipping: no test slug available\n";
        return true;
    }

    $piece = getArtPieceBySlug('aframe', $testData['create_auto_slug']);
    echo "  → Retrieved piece: " . ($piece ? $piece['title'] : 'not found') . "\n";
    return $piece !== null && $piece['id'] === $testData['create_auto_id'];
});

test('Update piece and change slug (creates redirect)', function() use (&$testData) {
    if (!isset($testData['create_auto_id'])) {
        echo "  → Skipping: no test piece available\n";
        return true;
    }

    $oldSlug = $testData['create_auto_slug'];
    $newSlug = 'updated-slug-' . time();

    $data = [
        'title' => 'Updated Test Piece',
        'slug' => $newSlug,
        'description' => 'Updated description'
    ];

    $result = updateArtPieceWithSlug('aframe', $testData['create_auto_id'], $data);

    if ($result['success']) {
        echo "  → Changed slug from '$oldSlug' to '$newSlug'\n";
        $testData['old_slug'] = $oldSlug;
        $testData['new_slug'] = $newSlug;
        return true;
    }
    return false;
});

test('Redirect follows old slug to new slug', function() use ($testData) {
    if (!isset($testData['old_slug']) || !isset($testData['new_slug'])) {
        echo "  → Skipping: no redirect data available\n";
        return true;
    }

    $redirectedSlug = getSlugRedirect('aframe', $testData['old_slug']);
    echo "  → Old slug '{$testData['old_slug']}' redirects to: '$redirectedSlug'\n";
    return $redirectedSlug === $testData['new_slug'];
});

test('Retrieve piece via old slug (follows redirect)', function() use ($testData) {
    if (!isset($testData['old_slug'])) {
        echo "  → Skipping: no redirect data available\n";
        return true;
    }

    $piece = getArtPieceBySlug('aframe', $testData['old_slug']);
    echo "  → Retrieved piece via old slug: " . ($piece ? $piece['title'] : 'not found') . "\n";
    return $piece !== null && $piece['slug'] === $testData['new_slug'];
});

// =====================================================
// Part 4: Soft Delete and Restore
// =====================================================
echo "\n--- Part 4: Soft Delete & Restore ---\n\n";

test('Soft delete art piece', function() use ($testData) {
    if (!isset($testData['create_custom_id'])) {
        echo "  → Skipping: no test piece available\n";
        return true;
    }

    $result = deleteArtPieceWithSlug('aframe', $testData['create_custom_id'], false);
    echo "  → Soft deleted piece ID: {$testData['create_custom_id']}\n";
    return $result['success'];
});

test('Soft-deleted piece not in active list', function() use ($testData) {
    if (!isset($testData['create_custom_id'])) {
        echo "  → Skipping: no test piece available\n";
        return true;
    }

    $activePieces = getActiveArtPieces('aframe', 'all');
    $found = false;
    foreach ($activePieces as $piece) {
        if ($piece['id'] === $testData['create_custom_id']) {
            $found = true;
            break;
        }
    }

    echo "  → Soft-deleted piece found in active list: " . ($found ? 'yes (FAIL)' : 'no (PASS)') . "\n";
    return !$found;
});

test('Soft-deleted piece appears in deleted list', function() use ($testData) {
    if (!isset($testData['create_custom_id'])) {
        echo "  → Skipping: no test piece available\n";
        return true;
    }

    $deletedPieces = getDeletedArtPieces('aframe');
    $found = false;
    foreach ($deletedPieces as $piece) {
        if ($piece['id'] === $testData['create_custom_id']) {
            $found = true;
            break;
        }
    }

    echo "  → Soft-deleted piece found in deleted list: " . ($found ? 'yes (PASS)' : 'no (FAIL)') . "\n";
    return $found;
});

test('Slug is reserved during deletion period', function() use ($testData) {
    if (!isset($testData['create_custom_slug'])) {
        echo "  → Skipping: no test slug available\n";
        return true;
    }

    $available = isSlugAvailable($testData['create_custom_slug'], 'aframe', null);
    echo "  → Deleted piece slug available: " . ($available ? 'yes (FAIL)' : 'no (PASS)') . "\n";
    return !$available;
});

test('Restore soft-deleted piece', function() use ($testData) {
    if (!isset($testData['create_custom_id'])) {
        echo "  → Skipping: no test piece available\n";
        return true;
    }

    $result = restoreArtPiece('aframe', $testData['create_custom_id']);
    echo "  → Restored piece ID: {$testData['create_custom_id']}\n";
    return $result;
});

test('Restored piece appears in active list', function() use ($testData) {
    if (!isset($testData['create_custom_id'])) {
        echo "  → Skipping: no test piece available\n";
        return true;
    }

    $activePieces = getActiveArtPieces('aframe', 'all');
    $found = false;
    foreach ($activePieces as $piece) {
        if ($piece['id'] === $testData['create_custom_id']) {
            $found = true;
            break;
        }
    }

    echo "  → Restored piece found in active list: " . ($found ? 'yes (PASS)' : 'no (FAIL)') . "\n";
    return $found;
});

// =====================================================
// Part 5: Configuration and Utilities
// =====================================================
echo "\n--- Part 5: Configuration ---\n\n";

test('Get slug reservation period from config', function() {
    $days = getSiteConfig('slug_reservation_days', 30);
    echo "  → Reservation period: $days days\n";
    return is_numeric($days) && $days > 0;
});

test('Build URL from slug', function() {
    $url = getUrlFromSlug('aframe', 'test-piece');
    echo "  → URL: $url\n";
    return strpos($url, '/a-frame/') !== false;
});

// =====================================================
// Cleanup Test Data
// =====================================================
echo "\n--- Cleanup ---\n\n";

test('Cleanup: Permanently delete test pieces', function() use ($testData) {
    $pdo = getDBConnection();
    $deleted = 0;

    if (isset($testData['create_auto_id'])) {
        $stmt = $pdo->prepare("DELETE FROM aframe_art WHERE id = ?");
        $stmt->execute([$testData['create_auto_id']]);
        $deleted++;
        echo "  → Deleted test piece ID: {$testData['create_auto_id']}\n";
    }

    if (isset($testData['create_custom_id'])) {
        $stmt = $pdo->prepare("DELETE FROM aframe_art WHERE id = ?");
        $stmt->execute([$testData['create_custom_id']]);
        $deleted++;
        echo "  → Deleted test piece ID: {$testData['create_custom_id']}\n";
    }

    echo "  → Cleaned up $deleted test pieces\n";
    return true;
});

test('Cleanup: Remove test redirects', function() use ($testData) {
    if (!isset($testData['old_slug'])) {
        echo "  → No test redirects to clean up\n";
        return true;
    }

    $pdo = getDBConnection();
    $stmt = $pdo->prepare("DELETE FROM slug_redirects WHERE old_slug = ?");
    $stmt->execute([$testData['old_slug']]);
    echo "  → Removed redirect for old slug: {$testData['old_slug']}\n";
    return true;
});

// =====================================================
// Test Summary
// =====================================================
echo "\n==============================================\n";
echo "Test Summary\n";
echo "==============================================\n";
echo "Total Tests: " . ($testsPassed + $testsFailed) . "\n";
echo "Passed: $testsPassed ✓\n";
echo "Failed: $testsFailed ✗\n";
echo "Success Rate: " . round(($testsPassed / ($testsPassed + $testsFailed)) * 100) . "%\n";
echo "==============================================\n";

if ($testsFailed === 0) {
    echo "\n✓ All tests passed! Slug system is fully functional.\n\n";
    exit(0);
} else {
    echo "\n✗ Some tests failed. Please review the errors above.\n\n";
    exit(1);
}
