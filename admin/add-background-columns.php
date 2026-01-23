<?php
/**
 * Add background_image_url columns to P5.js and Three.js
 * Web-accessible tool to fix database schema
 */

session_start();
require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/includes/auth.php');

// Require authentication
requireAuth();

$pdo = getDBConnection();
$results = [];

echo "<!DOCTYPE html><html><head><title>Add Background Image Columns</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#f5f5f5;}";
echo ".success{color:green;}.error{color:red;}.info{color:blue;}</style></head><body>";

echo "<h1>Add background_image_url Columns</h1>";

echo "<div style='background: #fff3cd; border: 2px solid #ffc107; padding: 15px; margin-bottom: 20px; border-radius: 4px;'>";
echo "<h3 style='margin-top:0;'>⚠️ Important: Clear Web Server Cache</h3>";
echo "<p>After running this migration, you MUST restart your web server or clear PHP opcache:</p>";
echo "<ul>";
echo "<li><strong>Replit:</strong> Stop and restart the run</li>";
echo "<li><strong>Apache:</strong> <code>sudo service apache2 restart</code></li>";
echo "<li><strong>PHP-FPM:</strong> <code>sudo service php-fpm restart</code></li>";
echo "</ul>";
echo "</div>";

// Clear opcache if available
echo "<h2>Clearing PHP Cache</h2>";
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "<p class='success'>✓ Opcache cleared</p>";
} else {
    echo "<p class='info'>ℹ️ Opcache not available or not enabled</p>";
}

if (function_exists('apc_clear_cache')) {
    apc_clear_cache();
    echo "<p class='success'>✓ APC cache cleared</p>";
} else {
    echo "<p class='info'>ℹ️ APC cache not available</p>";
}

try {
    // Check and add to p5_art
    echo "<h2>P5.js Table (p5_art)</h2>";

    $p5Columns = $pdo->query("PRAGMA table_info(p5_art)")->fetchAll(PDO::FETCH_ASSOC);
    $p5HasColumn = false;
    foreach ($p5Columns as $col) {
        if ($col['name'] === 'background_image_url') {
            $p5HasColumn = true;
            break;
        }
    }

    if ($p5HasColumn) {
        echo "<p class='info'>✓ Column 'background_image_url' already exists</p>";
    } else {
        echo "<p class='info'>Adding 'background_image_url' column...</p>";
        $pdo->exec("ALTER TABLE p5_art ADD COLUMN background_image_url VARCHAR(500)");
        echo "<p class='success'>✓ Column 'background_image_url' added successfully!</p>";

        // Migrate data from image_urls
        echo "<p class='info'>Migrating data from image_urls...</p>";
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

        echo "<p class='success'>✓ Migrated {$migrated} piece(s)</p>";
    }

    // Check and add to threejs_art
    echo "<h2>Three.js Table (threejs_art)</h2>";

    $threeColumns = $pdo->query("PRAGMA table_info(threejs_art)")->fetchAll(PDO::FETCH_ASSOC);
    $threeHasColumn = false;
    foreach ($threeColumns as $col) {
        if ($col['name'] === 'background_image_url') {
            $threeHasColumn = true;
            break;
        }
    }

    if ($threeHasColumn) {
        echo "<p class='info'>✓ Column 'background_image_url' already exists</p>";
    } else {
        echo "<p class='info'>Adding 'background_image_url' column...</p>";
        $pdo->exec("ALTER TABLE threejs_art ADD COLUMN background_image_url VARCHAR(500)");
        echo "<p class='success'>✓ Column 'background_image_url' added successfully!</p>";

        // Migrate data from texture_urls
        echo "<p class='info'>Migrating data from texture_urls...</p>";
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

        echo "<p class='success'>✓ Migrated {$migrated} piece(s)</p>";
    }

    echo "<hr>";
    echo "<h2 class='success'>✓ Schema Update Complete!</h2>";
    echo "<p><a href='p5.php'>Go to P5.js Admin</a> | <a href='threejs.php'>Go to Three.js Admin</a></p>";

} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
