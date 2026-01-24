<?php
/**
 * Database Initialization Check
 * Include this at the top of admin pages to ensure database is initialized
 */

/**
 * Check if database tables are initialized
 *
 * @return array ['initialized' => bool, 'missing_tables' => array]
 */
function checkDatabaseInitialized() {
    $requiredTables = [
        'users',
        'aframe_art',
        'c2_art',
        'p5_art',
        'threejs_art',
        'site_config',
        'activity_log',
        'slug_redirects',
        'auth_log',
        'auth_rate_limits'
    ];

    $pdo = getDBConnection();
    $missingTables = [];

    foreach ($requiredTables as $table) {
        try {
            $result = $pdo->query("SELECT 1 FROM {$table} LIMIT 1");
        } catch (PDOException $e) {
            $missingTables[] = $table;
        }
    }

    return [
        'initialized' => empty($missingTables),
        'missing_tables' => $missingTables
    ];
}

/**
 * Require database to be initialized, or show setup instructions
 */
function requireDatabaseInitialized() {
    $status = checkDatabaseInitialized();

    if (!$status['initialized']) {
        // Database not initialized
        showDatabaseSetupPage($status['missing_tables']);
        exit;
    }
}

/**
 * Show database setup instructions page
 *
 * @param array $missingTables List of missing tables
 */
function showDatabaseSetupPage($missingTables) {
    $dbType = defined('DB_TYPE') ? DB_TYPE : 'unknown';
    $initScript = $dbType === 'sqlite' ? 'init_db_sqlite.php' : 'init_db.php';

    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Database Setup Required - CodedArt Admin</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }

            .setup-container {
                background: white;
                border-radius: 12px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                max-width: 700px;
                width: 100%;
                overflow: hidden;
            }

            .setup-header {
                background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
                color: white;
                padding: 40px 30px;
                text-align: center;
            }

            .setup-header h1 {
                font-size: 32px;
                margin-bottom: 10px;
                font-weight: 700;
            }

            .setup-header p {
                font-size: 16px;
                opacity: 0.95;
            }

            .setup-body {
                padding: 40px 30px;
            }

            .alert {
                padding: 16px 20px;
                border-radius: 8px;
                margin-bottom: 24px;
                border-left: 4px solid;
            }

            .alert-warning {
                background: #fff3cd;
                border-color: #ffc107;
                color: #856404;
            }

            .alert-info {
                background: #d1ecf1;
                border-color: #17a2b8;
                color: #0c5460;
            }

            .alert h3 {
                font-size: 18px;
                margin-bottom: 8px;
                font-weight: 600;
            }

            .missing-tables {
                background: #f8f9fa;
                border: 1px solid #dee2e6;
                border-radius: 6px;
                padding: 16px;
                margin: 16px 0;
            }

            .missing-tables h4 {
                font-size: 14px;
                color: #6c757d;
                margin-bottom: 10px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .missing-tables ul {
                list-style: none;
                padding: 0;
            }

            .missing-tables li {
                padding: 6px 0;
                color: #495057;
                font-family: 'Courier New', monospace;
                font-size: 14px;
            }

            .missing-tables li:before {
                content: "✗ ";
                color: #dc3545;
                font-weight: bold;
                margin-right: 8px;
            }

            .code-block {
                background: #282c34;
                color: #abb2bf;
                padding: 20px;
                border-radius: 8px;
                margin: 16px 0;
                overflow-x: auto;
                font-family: 'Courier New', monospace;
                font-size: 14px;
                line-height: 1.6;
            }

            .code-block code {
                color: #98c379;
            }

            .step {
                margin-bottom: 24px;
                padding-bottom: 24px;
                border-bottom: 1px solid #e9ecef;
            }

            .step:last-child {
                border-bottom: none;
                margin-bottom: 0;
                padding-bottom: 0;
            }

            .step-number {
                display: inline-block;
                width: 32px;
                height: 32px;
                background: #667eea;
                color: white;
                border-radius: 50%;
                text-align: center;
                line-height: 32px;
                font-weight: bold;
                margin-right: 12px;
                font-size: 16px;
            }

            .step h3 {
                display: inline-block;
                font-size: 18px;
                color: #333;
                margin-bottom: 12px;
            }

            .step p {
                color: #6c757d;
                line-height: 1.6;
                margin-left: 44px;
            }

            .db-type {
                background: #e7f3ff;
                color: #004085;
                padding: 4px 12px;
                border-radius: 4px;
                font-weight: 600;
                font-size: 14px;
                display: inline-block;
                margin-bottom: 16px;
            }

            a {
                color: #667eea;
                text-decoration: none;
                font-weight: 500;
            }

            a:hover {
                text-decoration: underline;
            }
        </style>
    </head>
    <body>
        <div class="setup-container">
            <div class="setup-header">
                <h1>⚙️ Database Setup Required</h1>
                <p>Your database needs to be initialized before you can use the admin panel</p>
            </div>

            <div class="setup-body">
                <div class="alert alert-warning">
                    <h3>Database Not Initialized</h3>
                    <p>The database tables have not been created yet. This is a one-time setup process.</p>
                </div>

                <div class="missing-tables">
                    <h4>Missing Tables (<?php echo count($missingTables); ?>)</h4>
                    <ul>
                        <?php foreach ($missingTables as $table): ?>
                        <li><?php echo htmlspecialchars($table); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="alert alert-info">
                    <p><strong>Detected Database Type:</strong></p>
                    <div class="db-type"><?php echo strtoupper($dbType); ?></div>
                </div>

                <div class="step">
                    <span class="step-number">1</span>
                    <h3>Open Terminal/Shell</h3>
                    <p>
                        <?php if (defined('ENVIRONMENT') && ENVIRONMENT === 'development'): ?>
                        In Replit: Click the <strong>Shell</strong> tab at the bottom of the screen.
                        <?php else: ?>
                        Access your server terminal via SSH or hosting control panel.
                        <?php endif; ?>
                    </p>
                </div>

                <div class="step">
                    <span class="step-number">2</span>
                    <h3>Run Database Setup</h3>
                    <p>Execute this command to initialize the database:</p>
                    <div class="code-block">
                        <code>php setup-database.php</code>
                    </div>
                    <p style="margin-top: 12px;">Or run the init script directly:</p>
                    <div class="code-block">
                        <code>php config/<?php echo htmlspecialchars($initScript); ?></code>
                    </div>
                </div>

                <div class="step">
                    <span class="step-number">3</span>
                    <h3>Refresh This Page</h3>
                    <p>After the database is initialized, refresh this page or click the link below:</p>
                    <p style="margin-top: 12px;">
                        <a href="<?php echo $_SERVER['REQUEST_URI']; ?>">🔄 Refresh Page</a>
                    </p>
                </div>

                <?php if ($dbType === 'sqlite'): ?>
                <div class="alert alert-info" style="margin-top: 24px;">
                    <h3>ℹ️ SQLite Note</h3>
                    <p>You're using SQLite (file-based database). This is perfect for development on Replit!</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </body>
    </html>
    <?php
}
