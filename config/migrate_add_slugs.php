<?php
/**
 * Database Migration: Add Slug System
 *
 * This migration adds:
 * 1. Slug columns to all art tables (unique, indexed)
 * 2. Soft delete functionality (deleted_at column)
 * 3. Slug redirects table for URL management
 * 4. Indexes for performance
 *
 * Run this once to upgrade the database schema.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/environment.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';

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

// ==========================================
// START MIGRATION
// ==========================================

if (PHP_SAPI !== 'cli') {
    echo "<!DOCTYPE html><html><head><title>Slug Migration</title></head><body>";
    echo "<h1>CodedArt Slug System Migration</h1>";
    echo "<div style='font-family: monospace; max-width: 800px; margin: 20px;'>";
}

output("🚀 Starting slug system migration...", 'info');
output("Database: " . DB_NAME, 'info');
output("", 'info');

$success = true;

// ==========================================
// STEP 1: Add slug column to aframe_art
// ==========================================
output("📋 Step 1/13: Adding slug to aframe_art table...", 'info');

$sql = "ALTER TABLE aframe_art
        ADD COLUMN slug VARCHAR(255) UNIQUE AFTER title,
        ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL AFTER updated_at,
        ADD INDEX idx_slug (slug),
        ADD INDEX idx_deleted_at (deleted_at)";

$success = runMigration("Added slug and soft delete to aframe_art", $sql) && $success;

// ==========================================
// STEP 2: Add slug column to c2_art
// ==========================================
output("📋 Step 2/13: Adding slug to c2_art table...", 'info');

$sql = "ALTER TABLE c2_art
        ADD COLUMN slug VARCHAR(255) UNIQUE AFTER title,
        ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL AFTER updated_at,
        ADD INDEX idx_slug (slug),
        ADD INDEX idx_deleted_at (deleted_at)";

$success = runMigration("Added slug and soft delete to c2_art", $sql) && $success;

// ==========================================
// STEP 3: Add slug column to p5_art
// ==========================================
output("📋 Step 3/13: Adding slug to p5_art table...", 'info');

$sql = "ALTER TABLE p5_art
        ADD COLUMN slug VARCHAR(255) UNIQUE AFTER title,
        ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL AFTER updated_at,
        ADD INDEX idx_slug (slug),
        ADD INDEX idx_deleted_at (deleted_at)";

$success = runMigration("Added slug and soft delete to p5_art", $sql) && $success;

// ==========================================
// STEP 4: Add slug column to threejs_art
// ==========================================
output("📋 Step 4/13: Adding slug to threejs_art table...", 'info');

$sql = "ALTER TABLE threejs_art
        ADD COLUMN slug VARCHAR(255) UNIQUE AFTER title,
        ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL AFTER updated_at,
        ADD INDEX idx_slug (slug),
        ADD INDEX idx_deleted_at (deleted_at)";

$success = runMigration("Added slug and soft delete to threejs_art", $sql) && $success;

// ==========================================
// STEP 5: Create slug_redirects table
// ==========================================
output("📋 Step 5/13: Creating slug_redirects table...", 'info');

$sql = "CREATE TABLE IF NOT EXISTS slug_redirects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    art_type ENUM('aframe', 'c2', 'p5', 'threejs') NOT NULL,
    old_slug VARCHAR(255) NOT NULL,
    new_slug VARCHAR(255) NOT NULL,
    art_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    redirect_count INT DEFAULT 0 COMMENT 'Track how many times this redirect was used',
    INDEX idx_old_slug (old_slug),
    INDEX idx_art_type_id (art_type, art_id),
    UNIQUE KEY unique_redirect (art_type, old_slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$success = runMigration("Created slug_redirects table", $sql) && $success;

// ==========================================
// STEP 6-9: Generate slugs for existing data
// ==========================================
output("", 'info');
output("📋 Step 6/13: Generating slugs for existing A-Frame pieces...", 'info');

require_once __DIR__ . '/slug_utils.php';

$pdo = getDBConnection();

// A-Frame
$pieces = $pdo->query("SELECT id, title FROM aframe_art WHERE slug IS NULL OR slug = ''")->fetchAll(PDO::FETCH_ASSOC);
foreach ($pieces as $piece) {
    $slug = generateUniqueSlug($piece['title'], 'aframe', null);
    $stmt = $pdo->prepare("UPDATE aframe_art SET slug = ? WHERE id = ?");
    $stmt->execute([$slug, $piece['id']]);
    output("  Generated slug for '{$piece['title']}': {$slug}", 'info');
}
output("Generated " . count($pieces) . " slugs for A-Frame", 'success');

// C2
output("📋 Step 7/13: Generating slugs for existing C2 pieces...", 'info');
$pieces = $pdo->query("SELECT id, title FROM c2_art WHERE slug IS NULL OR slug = ''")->fetchAll(PDO::FETCH_ASSOC);
foreach ($pieces as $piece) {
    $slug = generateUniqueSlug($piece['title'], 'c2', null);
    $stmt = $pdo->prepare("UPDATE c2_art SET slug = ? WHERE id = ?");
    $stmt->execute([$slug, $piece['id']]);
    output("  Generated slug for '{$piece['title']}': {$slug}", 'info');
}
output("Generated " . count($pieces) . " slugs for C2", 'success');

// P5
output("📋 Step 8/13: Generating slugs for existing P5 pieces...", 'info');
$pieces = $pdo->query("SELECT id, title FROM p5_art WHERE slug IS NULL OR slug = ''")->fetchAll(PDO::FETCH_ASSOC);
foreach ($pieces as $piece) {
    $slug = generateUniqueSlug($piece['title'], 'p5', null);
    $stmt = $pdo->prepare("UPDATE p5_art SET slug = ? WHERE id = ?");
    $stmt->execute([$slug, $piece['id']]);
    output("  Generated slug for '{$piece['title']}': {$slug}", 'info');
}
output("Generated " . count($pieces) . " slugs for P5", 'success');

// Three.js
output("📋 Step 9/13: Generating slugs for existing Three.js pieces...", 'info');
$pieces = $pdo->query("SELECT id, title FROM threejs_art WHERE slug IS NULL OR slug = ''")->fetchAll(PDO::FETCH_ASSOC);
foreach ($pieces as $piece) {
    $slug = generateUniqueSlug($piece['title'], 'threejs', null);
    $stmt = $pdo->prepare("UPDATE threejs_art SET slug = ? WHERE id = ?");
    $stmt->execute([$slug, $piece['id']]);
    output("  Generated slug for '{$piece['title']}': {$slug}", 'info');
}
output("Generated " . count($pieces) . " slugs for Three.js", 'success');

// ==========================================
// STEP 10: Update gallery pages to use slugs
// ==========================================
output("", 'info');
output("📋 Step 10/13: Updating configuration...", 'info');

// Add slug configuration to site_config
$sql = "INSERT INTO site_config (setting_key, setting_value, setting_type, description)
        VALUES
        ('slug_reservation_days', '30', 'int', 'Days to reserve slug after soft delete'),
        ('enable_slug_redirects', '1', 'bool', 'Enable automatic slug redirects')
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";

$success = runMigration("Added slug configuration settings", $sql) && $success;

// ==========================================
// STEP 11: Create cleanup job entry
// ==========================================
output("📋 Step 11/13: Setting up cleanup job...", 'info');

$sql = "INSERT INTO site_config (setting_key, setting_value, setting_type, description)
        VALUES
        ('last_slug_cleanup', NOW(), 'string', 'Last time slug cleanup job ran')
        ON DUPLICATE KEY UPDATE setting_value = NOW()";

$success = runMigration("Created cleanup job entry", $sql) && $success;

// ==========================================
// STEP 12: Verify data integrity
// ==========================================
output("", 'info');
output("📋 Step 12/13: Verifying data integrity...", 'info');

$tables = ['aframe_art', 'c2_art', 'p5_art', 'threejs_art'];
$total = 0;
$withSlugs = 0;

foreach ($tables as $table) {
    $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
    $slugCount = $pdo->query("SELECT COUNT(*) FROM $table WHERE slug IS NOT NULL AND slug != ''")->fetchColumn();
    $total += $count;
    $withSlugs += $slugCount;
    output("  {$table}: {$slugCount}/{$count} pieces have slugs", $count == $slugCount ? 'success' : 'warning');
}

output("Total: {$withSlugs}/{$total} pieces have slugs", $withSlugs == $total ? 'success' : 'warning');

// ==========================================
// STEP 13: Summary
// ==========================================
output("", 'info');
output("📋 Step 13/13: Migration summary...", 'info');

if ($success && $withSlugs == $total) {
    output("", 'info');
    output("✅ MIGRATION COMPLETED SUCCESSFULLY!", 'success');
    output("", 'info');
    output("What was added:", 'info');
    output("  • slug column to all 4 art tables (unique, indexed)", 'success');
    output("  • deleted_at column for soft delete functionality", 'success');
    output("  • slug_redirects table for URL management", 'success');
    output("  • Slugs generated for all {$total} existing art pieces", 'success');
    output("  • Configuration settings for slug management", 'success');
    output("", 'info');
    output("Next steps:", 'info');
    output("  1. Test slug generation with new art pieces", 'info');
    output("  2. Test soft delete and restore functionality", 'info');
    output("  3. Set up cron job for cleanup: php config/cleanup_old_slugs.php", 'info');
    output("", 'info');
} else {
    output("", 'info');
    output("⚠️  MIGRATION COMPLETED WITH WARNINGS", 'warning');
    output("Some pieces may not have slugs. Please review manually.", 'warning');
}

if (PHP_SAPI !== 'cli') {
    echo "</div></body></html>";
}

exit($success ? 0 : 1);
