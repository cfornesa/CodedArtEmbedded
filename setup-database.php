<?php
/**
 * Database Setup Helper
 * Checks if database is initialized and runs the appropriate init script
 */

require_once(__DIR__ . '/config/config.php');
require_once(__DIR__ . '/config/database.php');

// ANSI color codes for terminal output
$colors = [
    'reset' => "\033[0m",
    'red' => "\033[31m",
    'green' => "\033[32m",
    'yellow' => "\033[33m",
    'blue' => "\033[34m",
    'cyan' => "\033[36m",
    'bold' => "\033[1m",
];

function output($message, $color = 'reset') {
    global $colors;
    echo $colors[$color] . $message . $colors['reset'] . PHP_EOL;
}

output("==========================================", 'cyan');
output("       CodedArt Database Setup", 'bold');
output("==========================================", 'cyan');
echo PHP_EOL;

// Detect database type
$dbType = defined('DB_TYPE') ? DB_TYPE : 'unknown';
output("Database Type: " . strtoupper($dbType), 'blue');

if ($dbType === 'sqlite') {
    $dbPath = defined('DB_PATH') ? DB_PATH : (defined('DB_NAME') ? DB_NAME : 'unknown');
    output("Database Path: " . $dbPath, 'blue');
} elseif ($dbType === 'mysql') {
    $dbHost = defined('DB_HOST') ? DB_HOST : 'unknown';
    $dbName = defined('DB_NAME') ? DB_NAME : 'unknown';
    output("Database Host: " . $dbHost, 'blue');
    output("Database Name: " . $dbName, 'blue');
}

echo PHP_EOL;

// Check if tables exist
output("Checking database tables...", 'yellow');

$requiredTables = function_exists('getRequiredTables')
    ? getRequiredTables()
    : [
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
$existingTables = [];

foreach ($requiredTables as $table) {
    try {
        $result = $pdo->query("SELECT 1 FROM {$table} LIMIT 1");
        $existingTables[] = $table;
        output("  ✓ {$table}", 'green');
    } catch (PDOException $e) {
        $missingTables[] = $table;
        output("  ✗ {$table} (missing)", 'red');
    }
}

echo PHP_EOL;

// Determine what to do
if (empty($missingTables)) {
    output("==========================================", 'green');
    output("✓ Database is fully initialized!", 'green');
    output("==========================================", 'green');
    echo PHP_EOL;
    output("All " . count($requiredTables) . " tables exist.", 'green');
    echo PHP_EOL;

    // Show table counts
    output("Table Statistics:", 'cyan');
    foreach ($requiredTables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM {$table}");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $count = $result['count'];
            output("  {$table}: {$count} records", 'blue');
        } catch (PDOException $e) {
            // Skip
        }
    }

    echo PHP_EOL;
    output("Next steps:", 'cyan');
    output("  1. Visit /admin/register.php to create your first admin account", 'blue');
    output("  2. Or visit /admin/login.php if you already have an account", 'blue');

    exit(0);
}

// Database needs initialization
output("==========================================", 'red');
output("⚠️  Database Needs Initialization", 'red');
output("==========================================", 'red');
echo PHP_EOL;

output("Missing " . count($missingTables) . " table(s):", 'yellow');
foreach ($missingTables as $table) {
    output("  - {$table}", 'yellow');
}

echo PHP_EOL;

// Determine which script to run based on what's missing
$totalTables = count($requiredTables);
$existingTablesCount = count($existingTables);

// Check if this is a partial initialization (most tables exist but some are missing)
$needsMigration = false;
if ($existingTablesCount > 0 && $existingTablesCount >= ($totalTables - 2)) {
    // Most tables exist - likely needs migration, not full init
    // Check if the missing table is slug_redirects (added via migration)
    if (in_array('slug_redirects', $missingTables)) {
        $needsMigration = true;
    }
}

// Determine which script to run
if ($needsMigration && $dbType === 'sqlite') {
    $initScript = __DIR__ . '/config/migrate_add_slugs_sqlite.php';
    $scriptName = 'migrate_add_slugs_sqlite.php';
    output("Detected partial database - will run migration to add missing tables.", 'cyan');
} elseif ($needsMigration && $dbType === 'mysql') {
    $initScript = __DIR__ . '/config/migrate_add_slugs.php';
    $scriptName = 'migrate_add_slugs.php';
    output("Detected partial database - will run migration to add missing tables.", 'cyan');
} elseif ($dbType === 'sqlite') {
    $initScript = __DIR__ . '/config/init_db_sqlite.php';
    $scriptName = 'init_db_sqlite.php';
} elseif ($dbType === 'mysql') {
    $initScript = __DIR__ . '/config/init_db.php';
    $scriptName = 'init_db.php';
} else {
    output("Error: Unknown database type: {$dbType}", 'red');
    exit(1);
}

// Check if init script exists
if (!file_exists($initScript)) {
    output("Error: Init script not found: {$initScript}", 'red');
    exit(1);
}

// Offer to run initialization
if ($needsMigration) {
    output("Would you like to run the migration now?", 'cyan');
    output("This will run: config/{$scriptName}", 'blue');
} else {
    output("Would you like to initialize the database now?", 'cyan');
    output("This will run: config/{$scriptName}", 'blue');
}
echo PHP_EOL;

// Check if running in CLI
if (php_sapi_name() === 'cli') {
    output("Press Y to continue, or N to cancel: ", 'yellow');
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    fclose($handle);

    $response = trim(strtolower($line));

    if ($response === 'y' || $response === 'yes') {
        echo PHP_EOL;
        output("==========================================", 'cyan');
        output("Running database initialization...", 'cyan');
        output("==========================================", 'cyan');
        echo PHP_EOL;

        // Run the init script as a separate process to avoid function conflicts
        $command = 'php ' . escapeshellarg($initScript);
        passthru($command, $exitCode);

        if ($exitCode === 0) {
            echo PHP_EOL;
            output("==========================================", 'green');
            if ($needsMigration) {
                output("✓ Migration complete!", 'green');
            } else {
                output("✓ Database initialization complete!", 'green');
            }
            output("==========================================", 'green');
            echo PHP_EOL;

            output("Next steps:", 'cyan');
            output("  1. Visit /admin/register.php to create your first admin account", 'blue');
            output("  2. Or run: php setup-database.php (to verify setup)", 'blue');
        } else {
            echo PHP_EOL;
            output("==========================================", 'red');
            if ($needsMigration) {
                output("✗ Migration failed!", 'red');
            } else {
                output("✗ Database initialization failed!", 'red');
            }
            output("==========================================", 'red');
            echo PHP_EOL;
            output("Please check the error messages above.", 'yellow');
            exit(1);
        }

    } else {
        echo PHP_EOL;
        output("Initialization cancelled.", 'yellow');
        echo PHP_EOL;
        output("To initialize manually, run:", 'cyan');
        output("  php config/{$scriptName}", 'blue');
    }
} else {
    // Running via web browser
    output("To initialize the database, run this command in your terminal:", 'cyan');
    output("  php config/{$scriptName}", 'blue');
    echo PHP_EOL;
    output("Or run this setup script:", 'cyan');
    output("  php setup-database.php", 'blue');
}

echo PHP_EOL;
