<?php
/**
 * P5.js Piece Diagnostic Script
 * Checks configuration and database state for P5.js pieces
 */

$db_path = __DIR__ . '/codedart.db';

try {
    $pdo = new PDO('sqlite:' . $db_path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== P5.JS DIAGNOSTIC ===\n\n";

    // Check if p5_art table exists
    echo "Step 1: Checking if p5_art table exists...\n";
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='p5_art'");
    $tableExists = $stmt->fetchColumn();

    if (!$tableExists) {
        echo "❌ CRITICAL: p5_art table does NOT exist!\n";
        echo "   This means the database has never been initialized.\n";
        echo "   Run: bash config/fix_database.sh\n\n";
        exit(1);
    }

    echo "✓ p5_art table exists\n\n";

    // Check table schema
    echo "Step 2: Checking p5_art table schema...\n";
    $stmt = $pdo->query('PRAGMA table_info(p5_art)');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Columns found: " . count($columns) . "\n";
    foreach ($columns as $col) {
        echo "  - " . $col['name'] . " (" . $col['type'] . ")\n";
    }
    echo "\n";

    // Check for critical columns
    $columnNames = array_column($columns, 'name');
    $requiredColumns = ['configuration', 'background_image_url', 'title', 'slug'];

    echo "Step 3: Verifying required columns...\n";
    foreach ($requiredColumns as $col) {
        $exists = in_array($col, $columnNames);
        echo ($exists ? '✓' : '✗') . " $col\n";
    }
    echo "\n";

    // Check if there are any pieces
    echo "Step 4: Checking for P5.js pieces...\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM p5_art WHERE deleted_at IS NULL");
    $count = $stmt->fetchColumn();
    echo "Active pieces found: $count\n\n";

    if ($count > 0) {
        // Get first piece configuration
        echo "Step 5: Analyzing first piece configuration...\n";
        $stmt = $pdo->query("SELECT * FROM p5_art WHERE deleted_at IS NULL LIMIT 1");
        $piece = $stmt->fetch(PDO::FETCH_ASSOC);

        echo "Piece: " . $piece['title'] . " (slug: " . $piece['slug'] . ")\n";
        echo "Configuration length: " . strlen($piece['configuration'] ?? '') . " bytes\n\n";

        if (!empty($piece['configuration'])) {
            $config = json_decode($piece['configuration'], true);

            if ($config) {
                echo "Configuration structure:\n";
                echo "  - Canvas: " . (isset($config['canvas']) ? 'Yes' : 'No') . "\n";
                echo "  - Drawing: " . (isset($config['drawing']) ? 'Yes' : 'No') . "\n";
                echo "  - Pattern: " . (isset($config['pattern']) ? 'Yes' : 'No') . "\n";
                echo "  - Animation: " . (isset($config['animation']) ? 'Yes' : 'No') . "\n";
                echo "  - Shapes: " . (isset($config['shapes']) ? 'Yes' : 'No') . "\n";
                echo "\n";

                if (isset($config['drawing'])) {
                    echo "Drawing configuration:\n";
                    echo "  - shapeType: " . ($config['drawing']['shapeType'] ?? 'NOT SET') . "\n";
                    echo "  - shapeCount: " . ($config['drawing']['shapeCount'] ?? 'NOT SET') . "\n";
                    echo "  - shapeSize: " . ($config['drawing']['shapeSize'] ?? 'NOT SET') . "\n";
                    echo "\n";
                }

                if (isset($config['shapes'])) {
                    echo "Shapes palette:\n";
                    foreach ($config['shapes'] as $i => $shape) {
                        echo "  - Shape $i: " . ($shape['shape'] ?? 'UNKNOWN') . " - " . ($shape['color'] ?? 'NO COLOR') . "\n";
                    }
                    echo "\n";
                }

                if (isset($config['pattern'])) {
                    echo "Pattern configuration:\n";
                    echo "  - type: " . ($config['pattern']['type'] ?? 'NOT SET') . "\n";
                    echo "  - spacing: " . ($config['pattern']['spacing'] ?? 'NOT SET') . "\n";
                    echo "\n";
                }

                echo "Full configuration JSON:\n";
                echo json_encode($config, JSON_PRETTY_PRINT) . "\n\n";
            } else {
                echo "❌ Configuration is NOT valid JSON!\n\n";
            }
        } else {
            echo "❌ Configuration is EMPTY!\n\n";
        }

        // Check background image
        echo "Background image URL: " . ($piece['background_image_url'] ?? 'NONE') . "\n\n";
    }

    echo "=== DIAGNOSTIC COMPLETE ===\n";

    if (!in_array('configuration', $columnNames)) {
        echo "\n❌ CRITICAL: configuration column is missing!\n";
        echo "   Run: bash config/fix_database.sh\n";
    } elseif ($count === 0) {
        echo "\n⚠️  No P5.js pieces found in database.\n";
        echo "   Create a piece in the admin interface to test.\n";
    } else {
        echo "\n✓ Database schema looks OK\n";
        echo "   If view page shows only lines, check the configuration above.\n";
        echo "   The 'shapeType' field should NOT be 'line' unless intentional.\n";
    }

} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    exit(1);
}
