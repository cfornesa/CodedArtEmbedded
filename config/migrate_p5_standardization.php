<?php
/**
 * P5.js Standardization Migration
 *
 * Purpose: Add background_image_url column and migrate data from image_urls array
 * Version: v1.0.19
 * Date: 2026-01-22
 *
 * Changes:
 * - Add background_image_url column (single URL, matches C2.js pattern)
 * - Migrate first image from image_urls array to background_image_url
 * - Keep old columns for backward compatibility (piece_path, screenshot_url, image_urls)
 *
 * NON-DESTRUCTIVE: Old columns remain in database but will be hidden from admin forms
 */

// Try to load config from environment if config.php doesn't exist
if (file_exists(__DIR__ . '/config.php')) {
    require_once(__DIR__ . '/config.php');
} else {
    // Fallback to environment-based config
    require_once(__DIR__ . '/environment.php');

    // Define minimal constants for database connection
    if (!defined('DB_HOST')) {
        define('DB_HOST', 'localhost');
    }
    if (!defined('DB_NAME')) {
        define('DB_NAME', 'codedart_db');
    }
    if (!defined('DB_USER')) {
        define('DB_USER', 'root');
    }
    if (!defined('DB_PASS')) {
        define('DB_PASS', '');
    }
    if (!defined('DB_CHARSET')) {
        define('DB_CHARSET', 'utf8mb4');
    }
}

require_once __DIR__ . '/database.php';

echo "P5.js Standardization Migration (v1.0.19)\n";
echo "==========================================\n\n";

try {
    // Get database connection
    $pdo = getDBConnection();
    // Check if background_image_url column already exists
    $checkColumn = $pdo->query("PRAGMA table_info(p5_art)");
    $columns = $checkColumn->fetchAll(PDO::FETCH_ASSOC);
    $hasBackgroundImageUrl = false;

    foreach ($columns as $column) {
        if ($column['name'] === 'background_image_url') {
            $hasBackgroundImageUrl = true;
            break;
        }
    }

    if ($hasBackgroundImageUrl) {
        echo "✓ Column 'background_image_url' already exists in p5_art table\n";
    } else {
        echo "Adding 'background_image_url' column to p5_art table...\n";

        // SQLite: Add column after thumbnail_url
        $pdo->exec("ALTER TABLE p5_art ADD COLUMN background_image_url VARCHAR(500)");

        echo "✓ Column 'background_image_url' added successfully\n";
    }

    // Migrate data: Copy first image from image_urls array to background_image_url
    echo "\nMigrating data from image_urls to background_image_url...\n";

    $pieces = $pdo->query("SELECT id, image_urls, background_image_url FROM p5_art WHERE deleted_at IS NULL")->fetchAll(PDO::FETCH_ASSOC);
    $migratedCount = 0;

    foreach ($pieces as $piece) {
        // Only migrate if background_image_url is empty and image_urls has data
        if (empty($piece['background_image_url']) && !empty($piece['image_urls'])) {
            $imageUrls = json_decode($piece['image_urls'], true);

            if (is_array($imageUrls) && !empty($imageUrls)) {
                $firstImageUrl = $imageUrls[0];

                $stmt = $pdo->prepare("UPDATE p5_art SET background_image_url = ? WHERE id = ?");
                $stmt->execute([$firstImageUrl, $piece['id']]);

                $migratedCount++;
                echo "  ✓ Migrated piece #{$piece['id']}: {$firstImageUrl}\n";
            }
        }
    }

    if ($migratedCount === 0) {
        echo "  ℹ No data migration needed (all pieces already have background_image_url or no image_urls)\n";
    } else {
        echo "\n✓ Migrated {$migratedCount} piece(s) successfully\n";
    }

    // Verify final state
    echo "\nVerifying final schema...\n";
    $checkColumn = $pdo->query("PRAGMA table_info(p5_art)");
    $columns = $checkColumn->fetchAll(PDO::FETCH_ASSOC);

    $requiredColumns = ['thumbnail_url', 'background_image_url', 'configuration'];
    $deprecatedColumns = ['piece_path', 'screenshot_url', 'image_urls']; // Still in DB, hidden from forms

    echo "\nRequired columns:\n";
    foreach ($requiredColumns as $colName) {
        $exists = false;
        foreach ($columns as $column) {
            if ($column['name'] === $colName) {
                $exists = true;
                break;
            }
        }
        echo "  " . ($exists ? '✓' : '✗') . " {$colName}\n";
    }

    echo "\nDeprecated columns (kept for backward compatibility):\n";
    foreach ($deprecatedColumns as $colName) {
        $exists = false;
        foreach ($columns as $column) {
            if ($column['name'] === $colName) {
                $exists = true;
                break;
            }
        }
        echo "  " . ($exists ? '✓' : '✗') . " {$colName} (hidden from admin forms)\n";
    }

    echo "\n" . str_repeat("=", 50) . "\n";
    echo "✓ Migration completed successfully!\n";
    echo "\nNext steps:\n";
    echo "1. Update admin/p5.php to remove deprecated fields\n";
    echo "2. Update admin/includes/functions.php (prepareArtPieceData)\n";
    echo "3. Update p5/view.php to use background_image_url\n";
    echo "4. Update admin/includes/preview.php for P5.js section\n";
    echo "5. Test saves and view pages\n";

} catch (PDOException $e) {
    echo "\n✗ Migration failed: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
}
