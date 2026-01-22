<?php
/**
 * Cache Clear Utility
 *
 * Clears PHP opcode cache and resets database connection
 * Access via: /admin/clear-cache.php
 *
 * Security: Only accessible when logged in as admin
 */

session_start();
require_once(__DIR__ . '/includes/auth.php');

// Require authentication
requireAuth();

$results = [];

// Clear PHP opcache if available
if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        $results[] = '✅ PHP opcache cleared successfully';
    } else {
        $results[] = '⚠️ Failed to clear PHP opcache';
    }
} else {
    $results[] = 'ℹ️ PHP opcache not available';
}

// Clear APCu cache if available
if (function_exists('apcu_clear_cache')) {
    if (apcu_clear_cache()) {
        $results[] = '✅ APCu cache cleared successfully';
    } else {
        $results[] = '⚠️ Failed to clear APCu cache';
    }
} else {
    $results[] = 'ℹ️ APCu cache not available';
}

// Reset database connection
require_once(__DIR__ . '/../config/database.php');
$db = getDBConnection();

// Verify schema
$stmt = $db->query("PRAGMA table_info(aframe_art)");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

$columnNames = array_column($columns, 'name');
$hasSkyOpacity = in_array('sky_opacity', $columnNames);
$hasGroundOpacity = in_array('ground_opacity', $columnNames);

$results[] = '';
$results[] = '📊 Database Schema Check:';
$results[] = ($hasSkyOpacity ? '✅' : '❌') . ' sky_opacity column';
$results[] = ($hasGroundOpacity ? '✅' : '❌') . ' ground_opacity column';

if (!$hasSkyOpacity || !$hasGroundOpacity) {
    $results[] = '';
    $results[] = '⚠️ MISSING COLUMNS DETECTED!';
    $results[] = 'Run migration: php config/migrate_opacity_fields.php';
    $results[] = 'Then restart your web server and clear cache again.';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clear Cache - CodedArt Admin</title>
    <link rel="stylesheet" href="<?php echo url('css/style.css'); ?>">
    <style>
        .cache-results {
            max-width: 600px;
            margin: 50px auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .cache-results h1 {
            color: #333;
            margin-bottom: 20px;
        }
        .cache-results pre {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 4px;
            line-height: 1.6;
            font-size: 14px;
        }
        .btn-back {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .btn-back:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="cache-results">
        <h1>🔄 Cache Cleared</h1>
        <pre><?php echo implode("\n", $results); ?></pre>

        <?php if ($hasSkyOpacity && $hasGroundOpacity): ?>
        <p style="color: #28a745; font-weight: 600; margin-top: 20px;">
            ✅ Database schema is correct. You can now update pieces with sky/ground opacity.
        </p>
        <?php else: ?>
        <p style="color: #dc3545; font-weight: 600; margin-top: 20px;">
            ❌ Schema issue detected. Follow the instructions above to fix.
        </p>
        <?php endif; ?>

        <a href="<?php echo url('admin/aframe.php'); ?>" class="btn-back">← Back to A-Frame Admin</a>
    </div>
</body>
</html>
