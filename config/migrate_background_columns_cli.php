#!/usr/bin/env php
<?php
/**
 * CLI Database Migration: Add background_image_url columns
 *
 * Run from Replit shell: php config/migrate_background_columns_cli.php
 *
 * This script:
 * 1. Adds background_image_url column to p5_art table
 * 2. Adds background_image_url column to threejs_art table
 * 3. Migrates data from old fields (image_urls, texture_urls)
 * 4. Provides clear output of what was done
 */

// Include configuration
require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/database.php');

echo "\n";
echo "=====================================\n";
echo "Background Image URL Migration Tool\n";
echo "=====================================\n\n";

try {
    $pdo = getDBConnection();

    // ==========================================
    // P5.js Table (p5_art)
    // ==========================================
    echo "Checking P5.js table (p5_art)...\n";

    $p5Columns = $pdo->query("PRAGMA table_info(p5_art)")->fetchAll(PDO::FETCH_ASSOC);
    $p5HasColumn = false;
    foreach ($p5Columns as $col) {
        if ($col['name'] === 'background_image_url') {
            $p5HasColumn = true;
            break;
        }
    }

    if ($p5HasColumn) {
        echo "  ✓ Column 'background_image_url' already exists\n";
    } else {
        echo "  + Adding 'background_image_url' column...\n";
        $pdo->exec("ALTER TABLE p5_art ADD COLUMN background_image_url VARCHAR(500)");
        echo "  ✓ Column added successfully!\n";

        // Migrate data
        echo "  + Migrating data from image_urls...\n";
        $pieces = $pdo->query("SELECT id, image_urls FROM p5_art WHERE deleted_at IS NULL")->fetchAll(PDO::FETCH_ASSOC);
        $migrated = 0;

        foreach ($pieces as $piece) {
            if (empty($piece['image_urls'])) continue;

            $imageUrls = json_decode($piece['image_urls'], true);
            if (is_array($imageUrls) && !empty($imageUrls)) {
                $stmt = $pdo->prepare("UPDATE p5_art SET background_image_url = ? WHERE id = ?");
                $stmt->execute([$imageUrls[0], $piece['id']]);
                $migrated++;
            }
        }

        echo "  ✓ Migrated {$migrated} piece(s)\n";
    }

    echo "\n";

    // ==========================================
    // Three.js Table (threejs_art)
    // ==========================================
    echo "Checking Three.js table (threejs_art)...\n";

    $threeColumns = $pdo->query("PRAGMA table_info(threejs_art)")->fetchAll(PDO::FETCH_ASSOC);
    $threeHasColumn = false;
    foreach ($threeColumns as $col) {
        if ($col['name'] === 'background_image_url') {
            $threeHasColumn = true;
            break;
        }
    }

    if ($threeHasColumn) {
        echo "  ✓ Column 'background_image_url' already exists\n";
    } else {
        echo "  + Adding 'background_image_url' column...\n";
        $pdo->exec("ALTER TABLE threejs_art ADD COLUMN background_image_url VARCHAR(500)");
        echo "  ✓ Column added successfully!\n";

        // Migrate data
        echo "  + Migrating data from texture_urls...\n";
        $pieces = $pdo->query("SELECT id, texture_urls FROM threejs_art WHERE deleted_at IS NULL")->fetchAll(PDO::FETCH_ASSOC);
        $migrated = 0;

        foreach ($pieces as $piece) {
            if (empty($piece['texture_urls'])) continue;

            $textureUrls = json_decode($piece['texture_urls'], true);
            if (is_array($textureUrls) && !empty($textureUrls)) {
                $stmt = $pdo->prepare("UPDATE threejs_art SET background_image_url = ? WHERE id = ?");
                $stmt->execute([$textureUrls[0], $piece['id']]);
                $migrated++;
            }
        }

        echo "  ✓ Migrated {$migrated} piece(s)\n";
    }

    echo "\n";
    echo "=====================================\n";
    echo "✓ Migration Complete!\n";
    echo "=====================================\n\n";
    echo "Next steps:\n";
    echo "1. Restart your web server (Replit: stop and restart run)\n";
    echo "2. Try saving a P5.js or Three.js piece again\n";
    echo "3. Configurations should now save without errors\n\n";

} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n\n";
    exit(1);
}
