<?php
/**
 * SQLite Database Initialization - Current Schema with Sky/Ground Fields
 * This script creates the database with the latest schema including sky/ground fields
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

echo "🚀 Initializing SQLite database with current schema...\n\n";

try {
    $db = getDBConnection();
    echo "✓ Database connection successful\n\n";

    // Drop existing table if it exists (for clean init)
    echo "Dropping existing aframe_art table (if exists)...\n";
    $db->exec("DROP TABLE IF EXISTS aframe_art");
    echo "✓ Table dropped\n\n";

    // Create aframe_art with sky/ground fields
    echo "Creating aframe_art table with sky/ground fields...\n";
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

    $db->exec($sql);
    echo "✓ Table aframe_art created successfully\n\n";

    // Create indexes
    echo "Creating indexes...\n";
    $db->exec("CREATE INDEX idx_aframe_slug ON aframe_art(slug)");
    $db->exec("CREATE INDEX idx_aframe_status ON aframe_art(status)");
    $db->exec("CREATE INDEX idx_aframe_deleted ON aframe_art(deleted_at)");
    $db->exec("CREATE INDEX idx_aframe_sort ON aframe_art(sort_order)");
    echo "✓ Indexes created\n\n";

    // Insert a test piece
    echo "Inserting test piece...\n";
    $stmt = $db->prepare("
        INSERT INTO aframe_art (
            title, slug, description, file_path,
            sky_color, ground_color, status, created_by
        ) VALUES (
            'Piece 1', 'piece-1', 'Test A-Frame piece', '/a-frame/view.php?slug=piece-1',
            '#FF5733', '#33FF57', 'active', 1
        )
    ");
    $stmt->execute();
    echo "✓ Test piece inserted\n\n";

    echo "✅ Database initialization complete!\n\n";
    echo "Columns in aframe_art:\n";

    $result = $db->query("PRAGMA table_info(aframe_art)");
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "  - {$row['name']} ({$row['type']})\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
