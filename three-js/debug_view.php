<?php
/**
 * Debug version of Three.js view to diagnose the issue
 */

require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../config/helpers.php');

// Get slug from query parameter
$slug = $_GET['slug'] ?? null;

echo "=== DEBUG OUTPUT ===\n\n";
echo "1. Slug from URL: " . ($slug ? "'" . $slug . "'" : "NULL") . "\n\n";

if (!$slug) {
    die('No slug provided in URL');
}

// Test database connection
try {
    $db = getDBConnection();
    echo "2. Database connection: OK\n\n";

    // Show all pieces
    $allPieces = $db->query("SELECT id, slug, title FROM threejs_art WHERE deleted_at IS NULL")->fetchAll(PDO::FETCH_ASSOC);
    echo "3. All Three.js pieces in database:\n";
    foreach ($allPieces as $p) {
        echo "   - ID " . $p['id'] . ": slug='" . $p['slug'] . "'\n";
    }
    echo "\n";

    // Try to fetch the specific piece
    echo "4. Querying for slug='" . $slug . "'...\n";
    $piece = dbFetchOne(
        "SELECT * FROM threejs_art WHERE slug = ? AND deleted_at IS NULL",
        [$slug]
    );

    if ($piece) {
        echo "   ✓ FOUND!\n";
        echo "   - ID: " . $piece['id'] . "\n";
        echo "   - Title: " . $piece['title'] . "\n";
        echo "   - Background Color: " . ($piece['background_color'] ?? 'NULL') . "\n";
        echo "   - Configuration length: " . strlen($piece['configuration'] ?? '') . " chars\n";
    } else {
        echo "   ✗ NOT FOUND\n";

        // Try without deleted_at check
        $pieceWithoutDeletedCheck = dbFetchOne(
            "SELECT * FROM threejs_art WHERE slug = ?",
            [$slug]
        );

        if ($pieceWithoutDeletedCheck) {
            echo "   (But it exists with deleted_at = " . ($pieceWithoutDeletedCheck['deleted_at'] ?? 'NULL') . ")\n";
        }
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
