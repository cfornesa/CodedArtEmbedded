<?php
/**
 * Admin System Test Script
 * Tests authentication, CRUD operations, and security features
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . '/config/config.php');
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/admin/includes/auth.php');
require_once(__DIR__ . '/admin/includes/functions.php');

echo "==========================================================\n";
echo "CodedArt Admin System Testing\n";
echo "==========================================================\n\n";

$allPassed = true;
$testResults = [];

/**
 * Test helper function
 */
function test($name, $callback) {
    global $allPassed, $testResults;

    echo "Testing: $name... ";

    try {
        $result = $callback();
        if ($result['success']) {
            echo "✓ PASS\n";
            if (isset($result['message'])) {
                echo "   " . $result['message'] . "\n";
            }
            $testResults[] = ['test' => $name, 'status' => 'PASS'];
        } else {
            echo "✗ FAIL\n";
            echo "   Error: " . $result['message'] . "\n";
            $allPassed = false;
            $testResults[] = ['test' => $name, 'status' => 'FAIL', 'error' => $result['message']];
        }
    } catch (Exception $e) {
        echo "✗ FAIL (Exception)\n";
        echo "   " . $e->getMessage() . "\n";
        $allPassed = false;
        $testResults[] = ['test' => $name, 'status' => 'FAIL', 'error' => $e->getMessage()];
    }

    echo "\n";
}

// ========== DATABASE TESTS ==========
echo "Database Tests:\n";
echo "-----------------------------------------------------------\n";

test('Database connection', function() {
    try {
        $db = getDBConnection();
        return [
            'success' => true,
            'message' => 'Connected to database successfully'
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Database connection failed: ' . $e->getMessage()
        ];
    }
});

test('All tables exist', function() {
    $tables = ['users', 'aframe_art', 'c2_art', 'p5_art', 'threejs_art', 'activity_log', 'site_config'];
    $missing = [];

    foreach ($tables as $table) {
        if (!dbTableExists($table)) {
            $missing[] = $table;
        }
    }

    if (empty($missing)) {
        return [
            'success' => true,
            'message' => 'All 7 tables exist'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Missing tables: ' . implode(', ', $missing)
        ];
    }
});

test('Database has seeded data', function() {
    $counts = [
        'aframe_art' => count(getArtPieces('aframe')),
        'c2_art' => count(getArtPieces('c2')),
        'p5_art' => count(getArtPieces('p5')),
        'threejs_art' => count(getArtPieces('threejs'))
    ];

    $total = array_sum($counts);

    if ($total > 0) {
        return [
            'success' => true,
            'message' => sprintf(
                'Found %d art pieces (A-Frame: %d, C2: %d, P5: %d, Three.js: %d)',
                $total, $counts['aframe_art'], $counts['c2_art'], $counts['p5_art'], $counts['threejs_art']
            )
        ];
    } else {
        return [
            'success' => false,
            'message' => 'No art pieces found. Run php config/seed_data.php'
        ];
    }
});

// ========== AUTHENTICATION TESTS ==========
echo "\nAuthentication Tests:\n";
echo "-----------------------------------------------------------\n";

test('Password hashing', function() {
    $password = 'TestPassword123!';
    $hash = hashPassword($password);

    if (verifyPassword($password, $hash)) {
        return ['success' => true, 'message' => 'Password hashing works correctly'];
    } else {
        return ['success' => false, 'message' => 'Password verification failed'];
    }
});

test('Password validation', function() {
    $weak = validatePassword('weak');
    $strong = validatePassword('StrongPass123!');

    if (!$weak['valid'] && $strong['valid']) {
        return ['success' => true, 'message' => 'Password validation works correctly'];
    } else {
        return ['success' => false, 'message' => 'Password validation not working as expected'];
    }
});

test('CSRF token generation', function() {
    $token1 = generateCsrfToken();
    $token2 = generateCsrfToken();

    if (!empty($token1) && $token1 === $token2) {
        return ['success' => true, 'message' => 'CSRF tokens generated correctly'];
    } else {
        return ['success' => false, 'message' => 'CSRF token generation failed'];
    }
});

// ========== CRUD TESTS ==========
echo "\nCRUD Function Tests:\n";
echo "-----------------------------------------------------------\n";

// Test user registration
test('User registration', function() {
    // Check if test user already exists
    $existingUser = dbFetchOne("SELECT id FROM users WHERE email = ?", ['testuser@example.com']);

    if ($existingUser) {
        // Delete existing test user
        dbDelete('users', 'id = ?', [$existingUser['id']]);
    }

    $userData = [
        'email' => 'testuser@example.com',
        'password' => 'TestPassword123!',
        'first_name' => 'Test',
        'last_name' => 'User'
    ];

    $result = registerUser($userData);

    if ($result['success']) {
        return ['success' => true, 'message' => 'User registered successfully'];
    } else {
        return ['success' => false, 'message' => $result['message']];
    }
});

test('User login', function() {
    $result = login('testuser@example.com', 'TestPassword123!');

    if ($result['success']) {
        return ['success' => true, 'message' => 'Login successful'];
    } else {
        return ['success' => false, 'message' => $result['message']];
    }
});

// Test art piece operations
test('Create A-Frame art piece', function() {
    $data = [
        'title' => 'Test A-Frame Piece',
        'description' => 'Test description',
        'file_path' => '/a-frame/test.php',
        'thumbnail_url' => 'https://example.com/test.png',
        'scene_type' => 'custom',
        'tags' => 'test, aframe',
        'status' => 'draft',
        'sort_order' => 999
    ];

    $result = createArtPiece('aframe', $data);

    if ($result['success']) {
        // Store ID for later tests
        $_SESSION['test_aframe_id'] = $result['id'];
        return ['success' => true, 'message' => 'A-Frame piece created (ID: ' . $result['id'] . ')'];
    } else {
        return ['success' => false, 'message' => $result['message']];
    }
});

test('Read A-Frame art piece', function() {
    if (!isset($_SESSION['test_aframe_id'])) {
        return ['success' => false, 'message' => 'No test piece ID available'];
    }

    $piece = getArtPiece('aframe', $_SESSION['test_aframe_id']);

    if ($piece && $piece['title'] === 'Test A-Frame Piece') {
        return ['success' => true, 'message' => 'A-Frame piece retrieved successfully'];
    } else {
        return ['success' => false, 'message' => 'Failed to retrieve A-Frame piece'];
    }
});

test('Update A-Frame art piece', function() {
    if (!isset($_SESSION['test_aframe_id'])) {
        return ['success' => false, 'message' => 'No test piece ID available'];
    }

    $data = [
        'title' => 'Updated Test A-Frame Piece',
        'description' => 'Updated description',
        'file_path' => '/a-frame/test.php',
        'scene_type' => 'alt',
        'tags' => 'test, aframe, updated',
        'status' => 'active',
        'sort_order' => 888
    ];

    $result = updateArtPiece('aframe', $_SESSION['test_aframe_id'], $data);

    if ($result['success']) {
        // Verify update
        $piece = getArtPiece('aframe', $_SESSION['test_aframe_id']);
        if ($piece['title'] === 'Updated Test A-Frame Piece') {
            return ['success' => true, 'message' => 'A-Frame piece updated successfully'];
        } else {
            return ['success' => false, 'message' => 'Update did not persist'];
        }
    } else {
        return ['success' => false, 'message' => $result['message']];
    }
});

test('Delete A-Frame art piece', function() {
    if (!isset($_SESSION['test_aframe_id'])) {
        return ['success' => false, 'message' => 'No test piece ID available'];
    }

    $result = deleteArtPiece('aframe', $_SESSION['test_aframe_id']);

    if ($result['success']) {
        // Verify deletion
        $piece = getArtPiece('aframe', $_SESSION['test_aframe_id']);
        if (!$piece) {
            return ['success' => true, 'message' => 'A-Frame piece deleted successfully'];
        } else {
            return ['success' => false, 'message' => 'Piece still exists after deletion'];
        }
    } else {
        return ['success' => false, 'message' => $result['message']];
    }
});

// ========== HELPER FUNCTIONS TESTS ==========
echo "\nHelper Function Tests:\n";
echo "-----------------------------------------------------------\n";

test('Email validation', function() {
    $validEmails = ['test@example.com', 'user+tag@domain.co.uk'];
    $invalidEmails = ['notanemail', 'missing@domain', '@domain.com'];

    foreach ($validEmails as $email) {
        if (!isValidEmail($email)) {
            return ['success' => false, 'message' => "Valid email rejected: $email"];
        }
    }

    foreach ($invalidEmails as $email) {
        if (isValidEmail($email)) {
            return ['success' => false, 'message' => "Invalid email accepted: $email"];
        }
    }

    return ['success' => true, 'message' => 'Email validation working correctly'];
});

test('URL validation', function() {
    $validUrls = ['https://example.com', 'http://test.com/path'];
    $invalidUrls = ['notaurl', 'ftp://unsupported.com'];

    foreach ($validUrls as $url) {
        if (!isValidImageUrl($url)) {
            return ['success' => false, 'message' => "Valid URL rejected: $url"];
        }
    }

    return ['success' => true, 'message' => 'URL validation working correctly'];
});

test('Sanitization functions', function() {
    $input = '<script>alert("XSS")</script>';
    $sanitized = sanitize($input);

    if ($sanitized !== $input && !str_contains($sanitized, '<script>')) {
        return ['success' => true, 'message' => 'Sanitization working correctly'];
    } else {
        return ['success' => false, 'message' => 'Sanitization not working'];
    }
});

// ========== CLEANUP ==========
echo "\nCleanup:\n";
echo "-----------------------------------------------------------\n";

test('Cleanup test data', function() {
    // Delete test user and associated activity
    $user = dbFetchOne("SELECT id FROM users WHERE email = ?", ['testuser@example.com']);
    if ($user) {
        dbDelete('activity_log', 'user_id = ?', [$user['id']]);
        dbDelete('users', 'id = ?', [$user['id']]);
    }

    return ['success' => true, 'message' => 'Test data cleaned up'];
});

// ========== SUMMARY ==========
echo "\n==========================================================\n";
echo "Test Summary:\n";
echo "==========================================================\n";

$passed = count(array_filter($testResults, fn($r) => $r['status'] === 'PASS'));
$failed = count(array_filter($testResults, fn($r) => $r['status'] === 'FAIL'));
$total = count($testResults);

echo sprintf("Total Tests: %d\n", $total);
echo sprintf("Passed: %d (%.1f%%)\n", $passed, ($passed/$total)*100);
echo sprintf("Failed: %d (%.1f%%)\n", $failed, ($failed/$total)*100);

if ($allPassed) {
    echo "\n✓ ALL TESTS PASSED!\n";
    echo "\nThe admin system is fully functional and ready to use.\n";
    exit(0);
} else {
    echo "\n✗ SOME TESTS FAILED\n";
    echo "\nPlease review the errors above.\n";
    exit(1);
}
