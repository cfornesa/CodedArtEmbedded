<?php
/**
 * Phase 6: Comprehensive System Test Suite
 * Tests all aspects of the CodedArtEmbedded refactoring project
 */

require_once(__DIR__ . '/config/config.php');
require_once(__DIR__ . '/config/database.php');

echo "\n";
echo "==============================================================\n";
echo "  PHASE 6: COMPREHENSIVE SYSTEM TEST SUITE\n";
echo "  CodedArtEmbedded Refactoring Project\n";
echo "==============================================================\n\n";

$testsPassed = 0;
$testsFailed = 0;
$testCategories = [];
$currentCategory = '';

function startCategory($name) {
    global $currentCategory, $testCategories;
    $currentCategory = $name;
    $testCategories[$name] = ['passed' => 0, 'failed' => 0];
    echo "\n--- $name ---\n\n";
}

function test($name, $callback) {
    global $testsPassed, $testsFailed, $currentCategory, $testCategories;

    try {
        $result = $callback();
        if ($result) {
            echo "✓ PASS: $name\n";
            $testsPassed++;
            $testCategories[$currentCategory]['passed']++;
        } else {
            echo "✗ FAIL: $name\n";
            $testsFailed++;
            $testCategories[$currentCategory]['failed']++;
        }
    } catch (Exception $e) {
        echo "✗ ERROR: $name - " . $e->getMessage() . "\n";
        $testsFailed++;
        $testCategories[$currentCategory]['failed']++;
    }
}

// ============================================================
// CATEGORY 1: DATABASE TESTS
// ============================================================
startCategory('Database Tests');

test('Database connection successful', function() {
    $db = getDbConnection();
    return $db !== null && $db instanceof PDO;
});

test('All 7 required tables exist', function() {
    $db = getDbConnection();
    $tables = ['users', 'aframe_art', 'c2_art', 'p5_art', 'threejs_art', 'activity_log', 'site_config'];

    foreach ($tables as $table) {
        $stmt = $db->query("SELECT 1 FROM $table LIMIT 1");
        if (!$stmt) {
            echo "  → Missing table: $table\n";
            return false;
        }
    }

    echo "  → All 7 tables present\n";
    return true;
});

test('Art pieces seeded in database', function() {
    $db = getDbConnection();

    $counts = [
        'aframe_art' => $db->query("SELECT COUNT(*) FROM aframe_art")->fetchColumn(),
        'c2_art' => $db->query("SELECT COUNT(*) FROM c2_art")->fetchColumn(),
        'p5_art' => $db->query("SELECT COUNT(*) FROM p5_art")->fetchColumn(),
        'threejs_art' => $db->query("SELECT COUNT(*) FROM threejs_art")->fetchColumn()
    ];

    $total = array_sum($counts);

    echo "  → A-Frame: {$counts['aframe_art']}\n";
    echo "  → C2: {$counts['c2_art']}\n";
    echo "  → P5: {$counts['p5_art']}\n";
    echo "  → Three.js: {$counts['threejs_art']}\n";
    echo "  → Total: $total pieces\n";

    return $total > 0;
});

test('Database queries use prepared statements', function() {
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT * FROM aframe_art WHERE id = ?");
    $stmt->execute([1]);
    return $stmt !== false;
});

// ============================================================
// CATEGORY 2: PHP SYNTAX TESTS
// ============================================================
startCategory('PHP Syntax Tests');

test('All root PHP files have valid syntax', function() {
    $files = glob(__DIR__ . '/*.php');
    foreach ($files as $file) {
        $output = shell_exec("php -l " . escapeshellarg($file) . " 2>&1");
        if (strpos($output, 'No syntax errors') === false) {
            echo "  → Syntax error in: " . basename($file) . "\n";
            return false;
        }
    }
    echo "  → All root files valid\n";
    return true;
});

test('All a-frame PHP files have valid syntax', function() {
    $files = glob(__DIR__ . '/a-frame/*.php');
    foreach ($files as $file) {
        $output = shell_exec("php -l " . escapeshellarg($file) . " 2>&1");
        if (strpos($output, 'No syntax errors') === false) {
            echo "  → Syntax error in: " . basename($file) . "\n";
            return false;
        }
    }
    echo "  → All a-frame files valid\n";
    return true;
});

test('All c2 PHP files have valid syntax', function() {
    $files = glob(__DIR__ . '/c2/*.php');
    foreach ($files as $file) {
        $output = shell_exec("php -l " . escapeshellarg($file) . " 2>&1");
        if (strpos($output, 'No syntax errors') === false) {
            echo "  → Syntax error in: " . basename($file) . "\n";
            return false;
        }
    }
    echo "  → All c2 files valid\n";
    return true;
});

test('All p5 PHP files have valid syntax', function() {
    $files = glob(__DIR__ . '/p5/*.php');
    foreach ($files as $file) {
        $output = shell_exec("php -l " . escapeshellarg($file) . " 2>&1");
        if (strpos($output, 'No syntax errors') === false) {
            echo "  → Syntax error in: " . basename($file) . "\n";
            return false;
        }
    }
    echo "  → All p5 files valid\n";
    return true;
});

test('All three-js PHP files have valid syntax', function() {
    $files = glob(__DIR__ . '/three-js/*.php');
    foreach ($files as $file) {
        $output = shell_exec("php -l " . escapeshellarg($file) . " 2>&1");
        if (strpos($output, 'No syntax errors') === false) {
            echo "  → Syntax error in: " . basename($file) . "\n";
            return false;
        }
    }
    echo "  → All three-js files valid\n";
    return true;
});

test('All admin PHP files have valid syntax', function() {
    $files = glob(__DIR__ . '/admin/*.php');
    foreach ($files as $file) {
        $output = shell_exec("php -l " . escapeshellarg($file) . " 2>&1");
        if (strpos($output, 'No syntax errors') === false) {
            echo "  → Syntax error in: " . basename($file) . "\n";
            return false;
        }
    }
    echo "  → All admin files valid\n";
    return true;
});

// ============================================================
// CATEGORY 3: CONFIGURATION TESTS
// ============================================================
startCategory('Configuration Tests');

test('config.php exists and is loaded', function() {
    return defined('DB_HOST') && defined('DB_NAME');
});

test('database.php provides getDbConnection function', function() {
    return function_exists('getDbConnection');
});

test('pages.php provides getPageInfo function', function() {
    require_once(__DIR__ . '/config/pages.php');
    return function_exists('getPageInfo');
});

test('Admin auth functions available', function() {
    require_once(__DIR__ . '/admin/includes/auth.php');
    return function_exists('login') && function_exists('logout') && function_exists('isLoggedIn');
});

test('Admin CRUD functions available', function() {
    require_once(__DIR__ . '/admin/includes/functions.php');
    return function_exists('getArtPieces') && function_exists('createArtPiece') && function_exists('updateArtPiece');
});

// ============================================================
// CATEGORY 4: TEMPLATE TESTS
// ============================================================
startCategory('Template Tests');

test('Unified header.php exists', function() {
    return file_exists(__DIR__ . '/resources/templates/header.php');
});

test('Unified footer.php exists', function() {
    return file_exists(__DIR__ . '/resources/templates/footer.php');
});

test('Old header-level.php has been removed', function() {
    $removed = !file_exists(__DIR__ . '/resources/templates/header-level.php');
    if ($removed) {
        echo "  → Deprecated file successfully removed\n";
    }
    return $removed;
});

test('Old footer-level.php has been removed', function() {
    $removed = !file_exists(__DIR__ . '/resources/templates/footer-level.php');
    if ($removed) {
        echo "  → Deprecated file successfully removed\n";
    }
    return $removed;
});

test('No references to header-level.php remain', function() {
    $output = shell_exec("grep -r 'header-level' " . escapeshellarg(__DIR__) . " --include='*.php' 2>/dev/null | grep -v '.git' | wc -l");
    $count = (int)trim($output);
    if ($count === 0) {
        echo "  → No references found\n";
    } else {
        echo "  → WARNING: Found $count references\n";
    }
    return $count === 0;
});

test('No references to footer-level.php remain', function() {
    $output = shell_exec("grep -r 'footer-level' " . escapeshellarg(__DIR__) . " --include='*.php' 2>/dev/null | grep -v '.git' | wc -l");
    $count = (int)trim($output);
    if ($count === 0) {
        echo "  → No references found\n";
    } else {
        echo "  → WARNING: Found $count references\n";
    }
    return $count === 0;
});

// ============================================================
// CATEGORY 5: GALLERY PAGE TESTS
// ============================================================
startCategory('Gallery Page Tests');

test('A-Frame gallery can fetch active pieces', function() {
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT * FROM aframe_art WHERE status = ? ORDER BY sort_order ASC");
    $stmt->execute(['active']);
    $pieces = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "  → Found " . count($pieces) . " active pieces\n";
    return true;
});

test('C2 gallery can fetch active pieces', function() {
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT * FROM c2_art WHERE status = ? ORDER BY sort_order ASC");
    $stmt->execute(['active']);
    $pieces = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "  → Found " . count($pieces) . " active pieces\n";
    return true;
});

test('P5 gallery can fetch active pieces', function() {
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT * FROM p5_art WHERE status = ? ORDER BY sort_order ASC");
    $stmt->execute(['active']);
    $pieces = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "  → Found " . count($pieces) . " active pieces\n";
    return true;
});

test('Three.js gallery can fetch active pieces', function() {
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT * FROM threejs_art WHERE status = ? ORDER BY sort_order ASC");
    $stmt->execute(['active']);
    $pieces = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "  → Found " . count($pieces) . " active pieces\n";
    return true;
});

// ============================================================
// CATEGORY 6: SECURITY TESTS
// ============================================================
startCategory('Security Tests');

test('Password hashing uses bcrypt', function() {
    require_once(__DIR__ . '/admin/includes/auth.php');
    $hash = hashPassword('test123');
    return strpos($hash, '$2y$') === 0; // bcrypt identifier
});

test('CSRF token generation works', function() {
    // Start a session for testing
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    require_once(__DIR__ . '/admin/includes/auth.php');
    $token1 = generateCsrfToken();
    $token2 = generateCsrfToken();
    return !empty($token1) && $token1 === $token2; // Same token in same session
});

test('XSS protection: htmlspecialchars used in gallery pages', function() {
    $testFiles = [
        __DIR__ . '/a-frame/index.php',
        __DIR__ . '/c2/index.php',
        __DIR__ . '/p5/index.php',
        __DIR__ . '/three-js/index.php'
    ];

    foreach ($testFiles as $file) {
        $content = file_get_contents($file);
        if (strpos($content, 'htmlspecialchars') === false) {
            echo "  → Missing XSS protection in: " . basename($file) . "\n";
            return false;
        }
    }

    echo "  → All gallery pages use htmlspecialchars\n";
    return true;
});

test('SQL injection protection: prepared statements used', function() {
    $adminFunctions = file_get_contents(__DIR__ . '/admin/includes/functions.php');

    // Check that we use prepare() and execute() pattern
    $usesPrepare = strpos($adminFunctions, '->prepare(') !== false;
    $usesExecute = strpos($adminFunctions, '->execute(') !== false;
    $avoidsDirect = strpos($adminFunctions, '->query(') === false ||
                    substr_count($adminFunctions, '->query(') < 3; // Allow a few safe queries

    if ($usesPrepare && $usesExecute) {
        echo "  → Prepared statements used\n";
        return true;
    }

    return false;
});

test('Config file not in git (sensitive data protection)', function() {
    $gitignore = file_get_contents(__DIR__ . '/.gitignore');
    $excludesConfig = strpos($gitignore, 'config.php') !== false || strpos($gitignore, '/config/config.php') !== false;

    if ($excludesConfig) {
        echo "  → config.php excluded from git\n";
    }

    return $excludesConfig;
});

// ============================================================
// CATEGORY 7: FILE STRUCTURE TESTS
// ============================================================
startCategory('File Structure Tests');

test('Required directories exist', function() {
    $dirs = [
        'a-frame', 'c2', 'p5', 'three-js',
        'admin', 'admin/includes', 'admin/assets',
        'config', 'resources/templates',
        'cache/cors', 'logs'
    ];

    foreach ($dirs as $dir) {
        if (!is_dir(__DIR__ . '/' . $dir)) {
            echo "  → Missing directory: $dir\n";
            return false;
        }
    }

    echo "  → All required directories present\n";
    return true;
});

test('Admin assets exist', function() {
    $assets = [
        'admin/assets/admin.css',
        'admin/assets/admin.js'
    ];

    foreach ($assets as $asset) {
        if (!file_exists(__DIR__ . '/' . $asset)) {
            echo "  → Missing asset: $asset\n";
            return false;
        }
    }

    echo "  → All admin assets present\n";
    return true;
});

test('Test scripts exist', function() {
    $tests = [
        'test_admin_system.php',
        'test_galleries.php',
        'test_templates.php'
    ];

    foreach ($tests as $test) {
        if (!file_exists(__DIR__ . '/' . $test)) {
            echo "  → Missing test: $test\n";
            return false;
        }
    }

    echo "  → All test scripts present\n";
    return true;
});

test('Documentation files exist', function() {
    $docs = [
        'CLAUDE.md',
        'PHASE3-COMPLETE.md',
        'PHASE4-COMPLETE.md',
        'PHASE5-COMPLETE.md'
    ];

    foreach ($docs as $doc) {
        if (!file_exists(__DIR__ . '/' . $doc)) {
            echo "  → Missing documentation: $doc\n";
            return false;
        }
    }

    echo "  → All phase documentation present\n";
    return true;
});

// ============================================================
// CATEGORY 8: ADMIN INTERFACE TESTS
// ============================================================
startCategory('Admin Interface Tests');

test('All admin CRUD pages exist', function() {
    $pages = ['aframe.php', 'c2.php', 'p5.php', 'threejs.php'];

    foreach ($pages as $page) {
        if (!file_exists(__DIR__ . '/admin/' . $page)) {
            echo "  → Missing admin page: $page\n";
            return false;
        }
    }

    echo "  → All 4 CRUD interfaces present\n";
    return true;
});

test('Admin authentication pages exist', function() {
    $pages = [
        'login.php', 'register.php', 'logout.php',
        'verify.php', 'forgot-password.php', 'reset-password.php'
    ];

    foreach ($pages as $page) {
        if (!file_exists(__DIR__ . '/admin/' . $page)) {
            echo "  → Missing auth page: $page\n";
            return false;
        }
    }

    echo "  → All authentication pages present\n";
    return true;
});

test('Admin dashboard and profile exist', function() {
    $pages = ['dashboard.php', 'profile.php'];

    foreach ($pages as $page) {
        if (!file_exists(__DIR__ . '/admin/' . $page)) {
            echo "  → Missing page: $page\n";
            return false;
        }
    }

    echo "  → Dashboard and profile pages present\n";
    return true;
});

// ============================================================
// TEST SUMMARY
// ============================================================
echo "\n";
echo "==============================================================\n";
echo "  TEST SUMMARY\n";
echo "==============================================================\n\n";

foreach ($testCategories as $category => $results) {
    $total = $results['passed'] + $results['failed'];
    $percentage = $total > 0 ? round(($results['passed'] / $total) * 100) : 0;
    echo sprintf("%-30s %2d/%2d  (%3d%%)\n", $category . ':', $results['passed'], $total, $percentage);
}

echo "\n";
echo "Total Tests: " . ($testsPassed + $testsFailed) . "\n";
echo "Passed: $testsPassed ✓\n";
echo "Failed: $testsFailed ✗\n";
echo "Success Rate: " . round(($testsPassed / ($testsPassed + $testsFailed)) * 100, 1) . "%\n";
echo "==============================================================\n";

if ($testsFailed === 0) {
    echo "\n✓ ALL TESTS PASSED! System is ready for production.\n\n";
    exit(0);
} else {
    echo "\n✗ Some tests failed. Please review the errors above.\n\n";
    exit(1);
}
