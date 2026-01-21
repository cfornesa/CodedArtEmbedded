<?php
/**
 * Ensure Database Schema is Correct
 * NON-DESTRUCTIVE: Only adds missing columns, never drops data
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

echo "=== Ensuring Database Schema is Correct ===\n\n";

try {
    $db = getDbConnection();
    $isSQLite = defined('DB_TYPE') && DB_TYPE === 'sqlite';

    echo "Database Type: " . ($isSQLite ? 'SQLite' : 'MySQL') . "\n\n";

    // Check if aframe_art table exists
    echo "1. Checking if aframe_art table exists...\n";
    if ($isSQLite) {
        $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='aframe_art'");
        $tableExists = $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    } else {
        $stmt = $db->query("SHOW TABLES LIKE 'aframe_art'");
        $tableExists = $stmt->rowCount() > 0;
    }

    if (!$tableExists) {
        echo "   ✗ Table doesn't exist\n";
        echo "   Creating aframe_art table with full schema...\n\n";

        if ($isSQLite) {
            $sql = "CREATE TABLE aframe_art (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) UNIQUE NOT NULL,
                description TEXT,
                file_path VARCHAR(255),
                thumbnail_url VARCHAR(500),
                scene_type TEXT CHECK(scene_type IN ('space', 'alt', 'custom')) DEFAULT 'custom',
                sky_color VARCHAR(20) DEFAULT '#ECECEC',
                sky_texture VARCHAR(500),
                ground_color VARCHAR(20) DEFAULT '#7BC8A4',
                ground_texture VARCHAR(500),
                configuration TEXT,
                tags TEXT,
                created_by INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                deleted_at DATETIME DEFAULT NULL,
                status TEXT CHECK(status IN ('active', 'draft', 'archived')) DEFAULT 'draft',
                sort_order INTEGER DEFAULT 0
            )";
        } else {
            $sql = "CREATE TABLE aframe_art (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) UNIQUE NOT NULL,
                description TEXT,
                file_path VARCHAR(255),
                thumbnail_url VARCHAR(500),
                scene_type ENUM('space', 'alt', 'custom') DEFAULT 'custom',
                sky_color VARCHAR(20) DEFAULT '#ECECEC',
                sky_texture VARCHAR(500),
                ground_color VARCHAR(20) DEFAULT '#7BC8A4',
                ground_texture VARCHAR(500),
                configuration TEXT,
                tags TEXT,
                created_by INT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at DATETIME DEFAULT NULL,
                status ENUM('active', 'draft', 'archived') DEFAULT 'draft',
                sort_order INT DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        }

        $db->exec($sql);
        echo "   ✓ Table created successfully\n\n";
    } else {
        echo "   ✓ Table exists\n\n";

        // Check for missing columns
        echo "2. Checking for missing columns...\n";
        if ($isSQLite) {
            $columns = $db->query("PRAGMA table_info(aframe_art)")->fetchAll(PDO::FETCH_ASSOC);
            $columnNames = array_column($columns, 'name');
        } else {
            $columns = $db->query("DESCRIBE aframe_art")->fetchAll(PDO::FETCH_ASSOC);
            $columnNames = array_column($columns, 'Field');
        }

        $requiredColumns = [
            'sky_color' => "VARCHAR(20) DEFAULT '#ECECEC'",
            'sky_texture' => "VARCHAR(500)",
            'ground_color' => "VARCHAR(20) DEFAULT '#7BC8A4'",
            'ground_texture' => "VARCHAR(500)"
        ];

        $missingColumns = [];
        foreach ($requiredColumns as $col => $type) {
            if (!in_array($col, $columnNames)) {
                $missingColumns[$col] = $type;
                echo "   ✗ Missing: $col\n";
            } else {
                echo "   ✓ Present: $col\n";
            }
        }

        if (!empty($missingColumns)) {
            echo "\n3. Adding missing columns...\n";
            foreach ($missingColumns as $col => $type) {
                try {
                    if ($isSQLite) {
                        // SQLite doesn't support AFTER clause
                        $db->exec("ALTER TABLE aframe_art ADD COLUMN $col $type");
                    } else {
                        // MySQL supports AFTER clause for better column ordering
                        $afterClause = match($col) {
                            'sky_color' => 'AFTER scene_type',
                            'sky_texture' => 'AFTER sky_color',
                            'ground_color' => 'AFTER sky_texture',
                            'ground_texture' => 'AFTER ground_color',
                            default => ''
                        };
                        $db->exec("ALTER TABLE aframe_art ADD COLUMN $col $type $afterClause");
                    }
                    echo "   ✓ Added: $col\n";
                } catch (PDOException $e) {
                    echo "   ✗ Failed to add $col: " . $e->getMessage() . "\n";
                }
            }
        } else {
            echo "\n✅ All required columns present!\n";
        }
    }

    // Final verification
    echo "\n4. Final schema verification...\n";
    if ($isSQLite) {
        $columns = $db->query("PRAGMA table_info(aframe_art)")->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_column($columns, 'name');
    } else {
        $columns = $db->query("DESCRIBE aframe_art")->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_column($columns, 'Field');
    }

    $allPresent = true;
    foreach (['sky_color', 'sky_texture', 'ground_color', 'ground_texture'] as $col) {
        if (!in_array($col, $columnNames)) {
            echo "   ✗ $col still missing!\n";
            $allPresent = false;
        }
    }

    if ($allPresent) {
        echo "   ✅ All sky/ground columns verified!\n\n";
        echo "✅ Database schema is correct and ready to use!\n";
    } else {
        echo "\n❌ Some columns are still missing. Manual intervention required.\n";
        exit(1);
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
