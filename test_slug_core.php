<?php
/**
 * Core Slug System Test - Tests slug utilities and database integration
 * Simplified version that doesn't require full admin system
 */

require_once(__DIR__ . '/config/config.php');
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/config/slug_utils.php');

echo "==============================================\n";
echo "Phase 7: Slug System Core Tests\n";
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
// Part 2: Database Tests
// =====================================================
echo "\n--- Part 2: Database Integration ---\n\n";

test('Database connection works', function() {
    $pdo = getDBConnection();
    return $pdo !== null;
});

test('All art tables have slug column', function() {
    $pdo = getDBConnection();
    $tables = ['aframe_art', 'c2_art', 'p5_art', 'threejs_art'];

    foreach ($tables as $table) {
        $result = $pdo->query("PRAGMA table_info($table)");
        $columns = $result->fetchAll(PDO::FETCH_ASSOC);

        $hasSlug = false;
        foreach ($columns as $col) {
            if ($col['name'] === 'slug') {
                $hasSlug = true;
                break;
            }
        }

        if (!$hasSlug) {
            echo "  → Missing slug column in $table\n";
            return false;
        }
    }

    echo "  → All tables have slug column\n";
    return true;
});

test('All art tables have deleted_at column', function() {
    $pdo = getDBConnection();
    $tables = ['aframe_art', 'c2_art', 'p5_art', 'threejs_art'];

    foreach ($tables as $table) {
        $result = $pdo->query("PRAGMA table_info($table)");
        $columns = $result->fetchAll(PDO::FETCH_ASSOC);

        $hasDeletedAt = false;
        foreach ($columns as $col) {
            if ($col['name'] === 'deleted_at') {
                $hasDeletedAt = true;
                break;
            }
        }

        if (!$hasDeletedAt) {
            echo "  → Missing deleted_at column in $table\n";
            return false;
        }
    }

    echo "  → All tables have deleted_at column\n";
    return true;
});

test('slug_redirects table exists', function() {
    $pdo = getDBConnection();
    $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='slug_redirects'");
    $exists = $result->fetch() !== false;
    echo "  → slug_redirects table exists: " . ($exists ? 'yes' : 'no') . "\n";
    return $exists;
});

test('Existing pieces have slugs generated', function() {
    $pdo = getDBConnection();
    $tables = ['aframe_art', 'c2_art', 'p5_art', 'threejs_art'];

    $allHaveSlugs = true;
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM $table");
        $total = $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) as with_slugs FROM $table WHERE slug IS NOT NULL AND slug != ''");
        $withSlugs = $stmt->fetchColumn();

        $typeLabel = strtoupper(str_replace('_art', '', $table));
        echo "  → $typeLabel: $withSlugs/$total pieces have slugs\n";

        if ($total > 0 && $withSlugs !== $total) {
            $allHaveSlugs = false;
        }
    }

    return $allHaveSlugs;
});

// =====================================================
// Part 3: Slug Availability Tests
// =====================================================
echo "\n--- Part 3: Slug Availability ---\n\n";

test('Check availability of unused slug', function() {
    $slug = 'test-unique-slug-' . time();
    $available = isSlugAvailable($slug, 'aframe', null);
    echo "  → Slug '$slug' available: " . ($available ? 'yes' : 'no') . "\n";
    return $available;
});

test('Check availability of existing slug', function() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT slug FROM aframe_art WHERE slug IS NOT NULL LIMIT 1");
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        echo "  → No existing slugs to test\n";
        return true;
    }

    $available = isSlugAvailable($existing['slug'], 'aframe', null);
    echo "  → Existing slug '{$existing['slug']}' available: " . ($available ? 'yes (FAIL)' : 'no (PASS)') . "\n";
    return !$available;
});

test('Generate unique slug with counter', function() {
    $baseSlug = 'test-piece-' . time();
    $slug1 = makeSlugUnique($baseSlug, 'aframe', null);
    echo "  → First slug: '$slug1'\n";

    // Insert a test piece with this slug
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("INSERT INTO aframe_art (title, slug, file_path, status) VALUES (?, ?, ?, ?)");
    $stmt->execute(['Test Piece', $slug1, '/test/test.php', 'draft']);
    $testId = $pdo->lastInsertId();

    // Try to get unique slug again - should add -2
    $slug2 = makeSlugUnique($baseSlug, 'aframe', null);
    echo "  → Second slug: '$slug2'\n";

    // Cleanup
    $stmt = $pdo->prepare("DELETE FROM aframe_art WHERE id = ?");
    $stmt->execute([$testId]);

    return $slug2 === $baseSlug . '-2';
});

// =====================================================
// Part 4: Configuration Tests
// =====================================================
echo "\n--- Part 4: Configuration ---\n\n";

test('Slug reservation period configured', function() {
    $days = getSiteConfig('slug_reservation_days', null);
    echo "  → Reservation period: " . ($days ?? 'not set') . " days\n";
    return $days !== null && is_numeric($days);
});

test('URL generation works', function() {
    $url = getUrlFromSlug('aframe', 'test-piece');
    echo "  → Generated URL: $url\n";
    return strpos($url, '/a-frame/') !== false || strpos($url, 'a-frame') !== false;
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
if ($testsPassed + $testsFailed > 0) {
    echo "Success Rate: " . round(($testsPassed / ($testsPassed + $testsFailed)) * 100) . "%\n";
}
echo "==============================================\n";

if ($testsFailed === 0) {
    echo "\n✓ All tests passed! Core slug system is functional.\n\n";
    echo "Note: Full CRUD testing requires the admin system.\n";
    echo "Test the complete functionality via the admin UI:\n";
    echo "  - Create piece: /admin/aframe.php?action=create\n";
    echo "  - Edit piece: /admin/aframe.php?action=edit&id=1\n";
    echo "  - Delete/restore: /admin/deleted.php\n\n";
    exit(0);
} else {
    echo "\n✗ Some tests failed. Please review the errors above.\n\n";
    exit(1);
}
