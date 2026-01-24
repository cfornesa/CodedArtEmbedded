<?php
/**
 * Check P5.js piece-1 configuration
 */

require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/database.php');

try {
    $db = getDBConnection();

    $stmt = $db->prepare("SELECT id, slug, title, configuration FROM p5_art WHERE slug = ? AND deleted_at IS NULL");
    $stmt->execute(['piece-1']);
    $piece = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($piece) {
        echo "=== P5.js Piece-1 Configuration ===\n\n";
        echo "ID: " . $piece['id'] . "\n";
        echo "Slug: " . $piece['slug'] . "\n";
        echo "Title: " . $piece['title'] . "\n\n";

        echo "Configuration JSON:\n";
        $config = json_decode($piece['configuration'], true);

        if ($config) {
            echo "\nCanvas Settings:\n";
            echo "  Width: " . ($config['canvas']['width'] ?? 'N/A') . "\n";
            echo "  Height: " . ($config['canvas']['height'] ?? 'N/A') . "\n";
            echo "  Background: " . ($config['canvas']['background'] ?? 'N/A') . "\n";
            echo "  Renderer: " . ($config['canvas']['renderer'] ?? 'N/A') . "\n";

            echo "\nDrawing Settings:\n";
            echo "  Shape Count: " . ($config['drawing']['shapeCount'] ?? 'N/A') . "\n";
            echo "  Shape Size: " . ($config['drawing']['shapeSize'] ?? 'N/A') . "\n";
            echo "  Drawing Mode: " . ($config['drawing']['mode'] ?? 'N/A') . "\n";
            echo "  Fill Color: " . ($config['drawing']['fillColor'] ?? 'N/A') . "\n";
            echo "  Fill Opacity: " . ($config['drawing']['fillOpacity'] ?? 'N/A') . "\n";

            echo "\nShapes Palette:\n";
            if (!empty($config['shapes'])) {
                echo "  Count: " . count($config['shapes']) . "\n";
                foreach ($config['shapes'] as $idx => $shape) {
                    echo "    Shape " . ($idx + 1) . ": " . $shape['shape'] . " - " . $shape['color'] . "\n";
                }
            } else {
                echo "  No shapes palette\n";
            }

            echo "\nAnimation Settings:\n";
            echo "  Animated: " . (isset($config['animation']['animated']) ? ($config['animation']['animated'] ? 'Yes' : 'No') : 'N/A') . "\n";

            echo "\n\nFull Configuration JSON:\n";
            echo json_encode($config, JSON_PRETTY_PRINT);
        } else {
            echo "Configuration is empty or invalid JSON\n";
        }
    } else {
        echo "Piece-1 not found in database\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
