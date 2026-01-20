<?php
/**
 * Database Migration: Add Slug System (SQLite Compatible)
 *
 * This migration adds:
 * 1. Slug columns to all art tables (unique, indexed)
 * 2. Soft delete functionality (deleted_at column)
 * 3. Slug redirects table for URL management
 * 4. Indexes for performance
 *
 * SQLite-specific approach: ALTER TABLE only supports adding columns one at a time
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/environment.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/slug_utils.php';

// ==========================================
// HELPER FUNCTIONS
// ==========================================

function output($message, $type = 'info') {
    $colors = [
        'success' => "\033[32m", // Green
        'error' => "\033[31m",   // Red
        'warning' => "\033[33m", // Yellow
        'info' => "\033[36m"     // Cyan
    ];
    $reset = "\033[0m";

    $prefix = [
        'success' => '✓',
        'error' => '✗',
        'warning' => '⚠',
        'info' => 'ℹ'
    ];

    if (PHP_SAPI === 'cli') {
        echo ($colors[$type] ?? $colors['info']) . ($prefix[$type] ?? '') . " " . $message . $reset . "\n";
    } else {
        echo "<div style='padding: 10px; margin: 5px 0; border-left: 4px solid " . ($colors[$type] ?? '#17a2b8') . ";'>";
        echo htmlspecialchars($message);
        echo "</div>";
    }
}

function runMigration($description, $sql) {
    try {
        $pdo = getDBConnection();
        $pdo->exec($sql);
        output($description, 'success');
        return true;
    } catch (PDOException $e) {
        output("$description - Error: " . $e->getMessage(), 'error');
        return false;
    }
}

function columnExists($table, $column) {
    $pdo = getDBConnection();
    $result = $pdo->query("PRAGMA table_info($table)");
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $col) {
        if ($col['name'] === $column) {
            return true;
        }
    }
    return false;
}

// ==========================================
// START MIGRATION
// ==========================================

if (PHP_SAPI !== 'cli') {
    echo "<!DOCTYPE html><html><head><title>Slug Migration</title></head><body>";
    echo "<h1>CodedArt Slug System Migration (SQLite)</h1>";
    echo "<div style='font-family: monospace; max-width: 800px; margin: 20px;'>";
}

output("🚀 Starting slug system migration (SQLite)...", 'info');
$pdo = getDBConnection();
output("Database: " . ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite' ? 'SQLite' : 'Other'), 'info');
output("", 'info');

$success = true;

// ==========================================
// STEP 1: Add columns to aframe_art
// ==========================================
output("📋 Step 1/13: Adding slug columns to aframe_art table...", 'info');

if (!columnExists('aframe_art', 'slug')) {
    $sql = "ALTER TABLE aframe_art ADD COLUMN slug VARCHAR(255)";
    $success = runMigration("  → Added slug column to aframe_art", $sql) && $success;
} else {
    output("  → slug column already exists in aframe_art", 'warning');
}

if (!columnExists('aframe_art', 'deleted_at')) {
    $sql = "ALTER TABLE aframe_art ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL";
    $success = runMigration("  → Added deleted_at column to aframe_art", $sql) && $success;
} else {
    output("  → deleted_at column already exists in aframe_art", 'warning');
}

// Create indexes (unique index enforces uniqueness)
try {
    $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_aframe_slug ON aframe_art(slug)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_aframe_deleted_at ON aframe_art(deleted_at)");
    output("  → Created indexes on aframe_art", 'success');
} catch (PDOException $e) {
    output("  → Error creating indexes: " . $e->getMessage(), 'error');
    $success = false;
}

// ==========================================
// STEP 2: Add columns to c2_art
// ==========================================
output("📋 Step 2/13: Adding slug columns to c2_art table...", 'info');

if (!columnExists('c2_art', 'slug')) {
    $sql = "ALTER TABLE c2_art ADD COLUMN slug VARCHAR(255)";
    $success = runMigration("  → Added slug column to c2_art", $sql) && $success;
} else {
    output("  → slug column already exists in c2_art", 'warning');
}

if (!columnExists('c2_art', 'deleted_at')) {
    $sql = "ALTER TABLE c2_art ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL";
    $success = runMigration("  → Added deleted_at column to c2_art", $sql) && $success;
} else {
    output("  → deleted_at column already exists in c2_art", 'warning');
}

// Create indexes (unique index enforces uniqueness)
try {
    $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_c2_slug ON c2_art(slug)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_c2_deleted_at ON c2_art(deleted_at)");
    output("  → Created indexes on c2_art", 'success');
} catch (PDOException $e) {
    output("  → Error creating indexes: " . $e->getMessage(), 'error');
    $success = false;
}

// ==========================================
// STEP 3: Add columns to p5_art
// ==========================================
output("📋 Step 3/13: Adding slug columns to p5_art table...", 'info');

if (!columnExists('p5_art', 'slug')) {
    $sql = "ALTER TABLE p5_art ADD COLUMN slug VARCHAR(255)";
    $success = runMigration("  → Added slug column to p5_art", $sql) && $success;
} else {
    output("  → slug column already exists in p5_art", 'warning');
}

if (!columnExists('p5_art', 'deleted_at')) {
    $sql = "ALTER TABLE p5_art ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL";
    $success = runMigration("  → Added deleted_at column to p5_art", $sql) && $success;
} else {
    output("  → deleted_at column already exists in p5_art", 'warning');
}

// Create indexes (unique index enforces uniqueness)
try {
    $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_p5_slug ON p5_art(slug)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_p5_deleted_at ON p5_art(deleted_at)");
    output("  → Created indexes on p5_art", 'success');
} catch (PDOException $e) {
    output("  → Error creating indexes: " . $e->getMessage(), 'error');
    $success = false;
}

// ==========================================
// STEP 4: Add columns to threejs_art
// ==========================================
output("📋 Step 4/13: Adding slug columns to threejs_art table...", 'info');

if (!columnExists('threejs_art', 'slug')) {
    $sql = "ALTER TABLE threejs_art ADD COLUMN slug VARCHAR(255)";
    $success = runMigration("  → Added slug column to threejs_art", $sql) && $success;
} else {
    output("  → slug column already exists in threejs_art", 'warning');
}

if (!columnExists('threejs_art', 'deleted_at')) {
    $sql = "ALTER TABLE threejs_art ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL";
    $success = runMigration("  → Added deleted_at column to threejs_art", $sql) && $success;
} else {
    output("  → deleted_at column already exists in threejs_art", 'warning');
}

// Create indexes (unique index enforces uniqueness)
try {
    $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_threejs_slug ON threejs_art(slug)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_threejs_deleted_at ON threejs_art(deleted_at)");
    output("  → Created indexes on threejs_art", 'success');
} catch (PDOException $e) {
    output("  → Error creating indexes: " . $e->getMessage(), 'error');
    $success = false;
}

// ==========================================
// STEP 5: Create slug_redirects table
// ==========================================
output("📋 Step 5/13: Creating slug_redirects table...", 'info');

$sql = "CREATE TABLE IF NOT EXISTS slug_redirects (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    art_type VARCHAR(20) NOT NULL,
    old_slug VARCHAR(255) NOT NULL,
    new_slug VARCHAR(255) NOT NULL,
    art_id INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    redirect_count INTEGER DEFAULT 0,
    UNIQUE(art_type, old_slug)
)";

$success = runMigration("Created slug_redirects table", $sql) && $success;

// Create indexes
try {
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_redirects_old_slug ON slug_redirects(old_slug)");
    output("  → Created index on slug_redirects", 'success');
} catch (PDOException $e) {
    output("  → Error creating index: " . $e->getMessage(), 'error');
    $success = false;
}

output("", 'info');

// ==========================================
// STEP 6-9: Generate slugs for existing pieces
// ==========================================
$artTypes = ['aframe', 'c2', 'p5', 'threejs'];
$stepNum = 6;

foreach ($artTypes as $type) {
    $table = $type . '_art';
    $typeLabel = strtoupper(str_replace('_', '.', $type));

    output("📋 Step $stepNum/13: Generating slugs for existing $typeLabel pieces...", 'info');

    try {
        // Get all pieces without slugs
        $stmt = $pdo->query("SELECT id, title FROM $table WHERE slug IS NULL OR slug = ''");
        $pieces = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $generated = 0;
        foreach ($pieces as $piece) {
            $slug = generateUniqueSlug($piece['title'], $type, $piece['id']);
            $updateStmt = $pdo->prepare("UPDATE $table SET slug = ? WHERE id = ?");
            $updateStmt->execute([$slug, $piece['id']]);
            $generated++;
        }

        output("  → Generated $generated slug(s) for $typeLabel pieces", 'success');
    } catch (PDOException $e) {
        output("  → Error generating slugs: " . $e->getMessage(), 'error');
        $success = false;
    }

    $stepNum++;
}

// ==========================================
// STEP 10-11: Add configuration settings
// ==========================================
output("📋 Step 10/13: Adding slug_reservation_days config...", 'info');

try {
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO site_config (setting_key, setting_value, setting_type, description)
                           VALUES (?, ?, ?, ?)");
    $stmt->execute([
        'slug_reservation_days',
        '30',
        'int',
        'Number of days to reserve slugs after deletion'
    ]);
    output("Added slug_reservation_days configuration", 'success');
} catch (PDOException $e) {
    output("Error adding slug_reservation_days: " . $e->getMessage(), 'error');
    $success = false;
}

output("📋 Step 11/13: Adding last_slug_cleanup config...", 'info');

try {
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO site_config (setting_key, setting_value, setting_type, description)
                           VALUES (?, ?, ?, ?)");
    $stmt->execute([
        'last_slug_cleanup',
        null,
        'string',
        'Timestamp of last slug cleanup job'
    ]);
    output("Added last_slug_cleanup configuration", 'success');
} catch (PDOException $e) {
    output("Error adding last_slug_cleanup: " . $e->getMessage(), 'error');
    $success = false;
}

// ==========================================
// STEP 12: Verify all slugs are unique
// ==========================================
output("📋 Step 12/13: Verifying slug uniqueness...", 'info');

$duplicates = 0;
foreach ($artTypes as $type) {
    $table = $type . '_art';
    $stmt = $pdo->query("SELECT slug, COUNT(*) as count FROM $table WHERE slug IS NOT NULL GROUP BY slug HAVING count > 1");
    $dups = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($dups)) {
        foreach ($dups as $dup) {
            output("  → Duplicate slug found in $table: {$dup['slug']} ({$dup['count']} times)", 'error');
            $duplicates++;
        }
    }
}

if ($duplicates === 0) {
    output("All slugs are unique", 'success');
} else {
    output("Found $duplicates duplicate slugs - please fix manually", 'error');
    $success = false;
}

// ==========================================
// STEP 13: Display summary
// ==========================================
output("", 'info');
output("📋 Step 13/13: Migration summary...", 'info');

foreach ($artTypes as $type) {
    $table = $type . '_art';
    $typeLabel = strtoupper(str_replace('_', '.', $type));

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM $table");
    $total = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) as with_slugs FROM $table WHERE slug IS NOT NULL AND slug != ''");
    $withSlugs = $stmt->fetchColumn();

    output("  → $typeLabel: $withSlugs/$total pieces have slugs", $withSlugs === $total ? 'success' : 'warning');
}

output("", 'info');

// ==========================================
// FINAL STATUS
// ==========================================
if ($success) {
    output("========================================", 'success');
    output("✓ MIGRATION COMPLETED SUCCESSFULLY", 'success');
    output("========================================", 'success');
    output("", 'info');
    output("Next steps:", 'info');
    output("1. Test slug generation: php test_slug_system.php", 'info');
    output("2. Review admin UI at /admin/aframe.php", 'info');
    output("3. Check deleted items at /admin/deleted.php", 'info');
    output("4. Set up cron job: config/cleanup_old_slugs.php", 'info');
} else {
    output("========================================", 'error');
    output("✗ MIGRATION COMPLETED WITH ERRORS", 'error');
    output("========================================", 'error');
    output("Please review errors above and fix manually.", 'error');
}

if (PHP_SAPI !== 'cli') {
    echo "</div></body></html>";
}

exit($success ? 0 : 1);
