<?php
/**
 * Web-Accessible Migration: Add background_color column to threejs_art
 *
 * Run from browser: /admin/migrate-background-color.php
 * Requires authentication
 */

require_once(__DIR__ . '/includes/auth.php');
require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/../config/database.php');

// Require authentication
requireAuth();

$success = false;
$error = null;
$messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migration'])) {
    try {
        $pdo = getDBConnection();

        $messages[] = "Checking threejs_art table schema...";

        // Check if column exists (SQLite)
        $columns = $pdo->query("PRAGMA table_info(threejs_art)")->fetchAll(PDO::FETCH_ASSOC);
        $hasColumn = false;
        foreach ($columns as $col) {
            if ($col['name'] === 'background_color') {
                $hasColumn = true;
                break;
            }
        }

        if ($hasColumn) {
            $messages[] = "✓ Column 'background_color' already exists - no migration needed";
            $success = true;
        } else {
            $messages[] = "+ Adding 'background_color' column...";
            $pdo->exec("ALTER TABLE threejs_art ADD COLUMN background_color VARCHAR(20) DEFAULT '#000000'");
            $messages[] = "✓ Column added successfully!";

            $messages[] = "+ Setting default background color for existing pieces...";
            $updated = $pdo->exec("UPDATE threejs_art SET background_color = '#000000' WHERE background_color IS NULL");
            $messages[] = "✓ Updated $updated existing piece(s)";

            $success = true;
        }

        // Try to clear opcache
        if (function_exists('opcache_reset')) {
            opcache_reset();
            $messages[] = "✓ Cleared opcache";
        }

        if (function_exists('apc_clear_cache')) {
            apc_clear_cache();
            $messages[] = "✓ Cleared APC cache";
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
        $messages[] = "✗ ERROR: " . $error;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Three.js Background Color Migration</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #764ba2;
            margin-bottom: 20px;
            border-bottom: 3px solid #764ba2;
            padding-bottom: 10px;
        }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .success-box {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .error-box {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .messages {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
            font-family: monospace;
            font-size: 14px;
        }
        .messages div {
            margin: 5px 0;
        }
        button {
            background: #764ba2;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background 0.2s;
        }
        button:hover {
            background: #5a3680;
        }
        button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .btn-secondary {
            background: #6c757d;
            margin-left: 10px;
        }
        .btn-secondary:hover {
            background: #545b62;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        ol, ul {
            margin-left: 20px;
            margin-top: 10px;
        }
        li {
            margin: 8px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛠️ Three.js Background Color Migration</h1>

        <div class="info-box">
            <strong>📋 What this migration does:</strong>
            <ul>
                <li>Adds <code>background_color</code> column to <code>threejs_art</code> table</li>
                <li>Sets default value to <code>#000000</code> (black) for existing pieces</li>
                <li>Safe to run multiple times (idempotent)</li>
                <li>Clears PHP opcache automatically</li>
            </ul>
        </div>

        <?php if (!$success && !$error): ?>
            <div class="warning-box">
                <strong>⚠️ IMPORTANT: After running this migration:</strong>
                <ol>
                    <li><strong>Restart your web server</strong>
                        <ul>
                            <li><strong>Replit:</strong> Stop and restart the run</li>
                            <li><strong>Apache:</strong> <code>sudo service apache2 restart</code></li>
                            <li><strong>PHP-FPM:</strong> <code>sudo service php-fpm restart</code></li>
                        </ul>
                    </li>
                    <li>Wait 10 seconds for server to fully restart</li>
                    <li>Try editing your Three.js piece again</li>
                </ol>
                <p style="margin-top: 10px;"><em>The restart is REQUIRED because PHP caches database schema information.</em></p>
            </div>

            <form method="POST">
                <button type="submit" name="run_migration">▶️ Run Migration</button>
                <a href="threejs.php" class="btn-secondary" style="display: inline-block; padding: 12px 24px; text-decoration: none; border-radius: 4px;">← Back to Three.js Admin</a>
            </form>
        <?php endif; ?>

        <?php if (!empty($messages)): ?>
            <div class="messages">
                <?php foreach ($messages as $message): ?>
                    <div><?php echo htmlspecialchars($message); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-box">
                <strong>✅ Migration Complete!</strong>
                <p style="margin-top: 10px;">The <code>background_color</code> column has been added to your database.</p>
            </div>

            <div class="warning-box">
                <strong>🔄 NEXT STEPS (REQUIRED):</strong>
                <ol>
                    <li><strong>Restart your web server NOW:</strong>
                        <ul>
                            <li><strong>Replit:</strong> Click "Stop" then "Run" again</li>
                            <li><strong>Apache:</strong> Run <code>sudo service apache2 restart</code></li>
                            <li><strong>PHP-FPM:</strong> Run <code>sudo service php-fpm restart</code></li>
                        </ul>
                    </li>
                    <li>Wait 10 seconds for the server to fully restart</li>
                    <li>Go back to <a href="threejs.php">Three.js Admin</a> and try editing your piece</li>
                    <li>The "no such column: background_color" error should now be gone</li>
                </ol>
            </div>

            <a href="threejs.php" class="btn-secondary" style="display: inline-block; padding: 12px 24px; text-decoration: none; border-radius: 4px; margin-top: 20px;">← Back to Three.js Admin</a>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-box">
                <strong>❌ Migration Failed</strong>
                <p style="margin-top: 10px;"><code><?php echo htmlspecialchars($error); ?></code></p>
                <p style="margin-top: 10px;">If the error persists, you can try the CLI migration:</p>
                <code>php config/migrate_threejs_background_color.php</code>
            </div>

            <button onclick="location.reload()">🔄 Try Again</button>
            <a href="threejs.php" class="btn-secondary" style="display: inline-block; padding: 12px 24px; text-decoration: none; border-radius: 4px; margin-left: 10px;">← Back to Three.js Admin</a>
        <?php endif; ?>
    </div>
</body>
</html>
