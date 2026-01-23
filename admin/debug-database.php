<?php
require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/includes/auth.php');

requireAuth();

$db = getDBConnection();

echo "<h2>Database Contents (Web Server View)</h2>";

// Three.js pieces
echo "<h3>Three.js Pieces</h3>";
$threejs = $db->query("SELECT id, slug, title, background_color, deleted_at FROM threejs_art ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Slug</th><th>Title</th><th>Background Color</th><th>Deleted At</th></tr>";
foreach ($threejs as $piece) {
    echo "<tr>";
    echo "<td>" . $piece['id'] . "</td>";
    echo "<td>" . htmlspecialchars($piece['slug']) . "</td>";
    echo "<td>" . htmlspecialchars($piece['title']) . "</td>";
    echo "<td>" . htmlspecialchars($piece['background_color'] ?? 'NULL') . "</td>";
    echo "<td>" . htmlspecialchars($piece['deleted_at'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

// P5.js pieces
echo "<h3>P5.js Pieces</h3>";
$p5 = $db->query("SELECT id, slug, title, deleted_at FROM p5_art ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Slug</th><th>Title</th><th>Deleted At</th></tr>";
foreach ($p5 as $piece) {
    echo "<tr>";
    echo "<td>" . $piece['id'] . "</td>";
    echo "<td>" . htmlspecialchars($piece['slug']) . "</td>";
    echo "<td>" . htmlspecialchars($piece['title']) . "</td>";
    echo "<td>" . htmlspecialchars($piece['deleted_at'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Database file location
echo "<h3>Database File</h3>";
echo "<p>DB_PATH: " . DB_PATH . "</p>";
echo "<p>File exists: " . (file_exists(DB_PATH) ? 'YES' : 'NO') . "</p>";
echo "<p>File size: " . (file_exists(DB_PATH) ? filesize(DB_PATH) . ' bytes' : 'N/A') . "</p>";
