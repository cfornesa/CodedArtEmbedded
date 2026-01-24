<?php
/**
 * Web-accessible P5.js piece-1 configuration checker
 */

require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/includes/auth.php');

requireAuth();

try {
    $db = getDBConnection();

    $stmt = $db->prepare("SELECT id, slug, title, configuration FROM p5_art WHERE slug = ? AND deleted_at IS NULL");
    $stmt->execute(['piece-1']);
    $piece = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "<h2>P5.js Piece-1 Configuration (Web Server View)</h2>";

    if ($piece) {
        echo "<p><strong>ID:</strong> " . $piece['id'] . "</p>";
        echo "<p><strong>Slug:</strong> " . htmlspecialchars($piece['slug']) . "</p>";
        echo "<p><strong>Title:</strong> " . htmlspecialchars($piece['title']) . "</p>";

        $config = json_decode($piece['configuration'], true);

        if ($config) {
            echo "<h3>Canvas Settings</h3>";
            echo "<ul>";
            echo "<li>Width: " . ($config['canvas']['width'] ?? 'N/A') . "</li>";
            echo "<li>Height: " . ($config['canvas']['height'] ?? 'N/A') . "</li>";
            echo "<li>Background: " . ($config['canvas']['background'] ?? 'N/A') . "</li>";
            echo "<li>Renderer: " . ($config['canvas']['renderer'] ?? 'N/A') . "</li>";
            echo "</ul>";

            echo "<h3>Drawing Settings</h3>";
            echo "<ul>";
            echo "<li><strong>Shape Count: " . ($config['drawing']['shapeCount'] ?? 'N/A') . "</strong></li>";
            echo "<li>Shape Size: " . ($config['drawing']['shapeSize'] ?? 'N/A') . "</li>";
            echo "<li>Drawing Mode: " . ($config['drawing']['mode'] ?? 'N/A') . "</li>";
            echo "<li>Fill Color: " . ($config['drawing']['fillColor'] ?? 'N/A') . "</li>";
            echo "<li>Fill Opacity: " . ($config['drawing']['fillOpacity'] ?? 'N/A') . "</li>";
            echo "</ul>";

            echo "<h3>Shapes Palette</h3>";
            if (!empty($config['shapes'])) {
                echo "<p><strong>Count: " . count($config['shapes']) . "</strong></p>";
                echo "<ul>";
                foreach ($config['shapes'] as $idx => $shape) {
                    echo "<li>Shape " . ($idx + 1) . ": " . htmlspecialchars($shape['shape']) . " - " . htmlspecialchars($shape['color']) . "</li>";
                }
                echo "</ul>";
            } else {
                echo "<p>No shapes palette</p>";
            }

            echo "<h3>Animation Settings</h3>";
            echo "<ul>";
            echo "<li>Animated: " . (isset($config['animation']['animated']) ? ($config['animation']['animated'] ? 'Yes' : 'No') : 'N/A') . "</li>";
            echo "</ul>";

            echo "<h3>Full Configuration JSON</h3>";
            echo "<pre style='background: #f5f5f5; padding: 10px; overflow: auto;'>";
            echo htmlspecialchars(json_encode($config, JSON_PRETTY_PRINT));
            echo "</pre>";

            echo "<h3>Analysis</h3>";
            echo "<p><strong>PREVIEW rendering logic:</strong> Draws 5 shapes in fixed, evenly-spaced positions</p>";
            echo "<p><strong>VIEW PAGE rendering logic:</strong> Uses config.drawing.shapeCount (" . ($config['drawing']['shapeCount'] ?? '10') . ") with random positions</p>";

            $shapeCount = $config['drawing']['shapeCount'] ?? 10;
            if ($shapeCount != 5) {
                echo "<p style='color: red;'><strong>MISMATCH FOUND:</strong> Preview uses hardcoded 5 shapes, but configuration specifies " . $shapeCount . " shapes!</p>";
                echo "<p><strong>Solution:</strong> Update preview.php to use config.drawing.shapeCount instead of hardcoded 5</p>";
            }

        } else {
            echo "<p>Configuration is empty or invalid JSON</p>";
        }
    } else {
        echo "<p>Piece-1 not found in database (web server view)</p>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'><strong>ERROR:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
