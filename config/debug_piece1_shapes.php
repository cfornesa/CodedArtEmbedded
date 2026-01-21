<?php
/**
 * Debug script to check Piece 1 shape configuration
 */

require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/database.php');

$db = getDBConnection();

echo "=== Piece 1 Shape Configuration Debug ===\n\n";

try {
    // Query Piece 1 - get ALL columns
    $stmt = $db->prepare("SELECT * FROM aframe_art WHERE id = 1 LIMIT 1");
    $stmt->execute();
    $piece = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$piece) {
        echo "❌ No piece found with ID 1\n";
        exit;
    }

    echo "✓ Found piece:\n";
    echo "  ID: {$piece['id']}\n";
    echo "  Title: {$piece['title']}\n";
    echo "  Slug: {$piece['slug']}\n\n";

    echo "All fields:\n";
    echo str_repeat("-", 50) . "\n";
    foreach ($piece as $key => $value) {
        if ($key === 'configuration') continue; // Skip, we'll handle below
        $displayValue = $value === null ? 'NULL' : (strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value);
        echo "  {$key}: {$displayValue}\n";
    }
    echo "\n";

    echo "Configuration field:\n";
    echo str_repeat("-", 50) . "\n";

    if (empty($piece['configuration'])) {
        echo "❌ Configuration is empty or NULL\n";
        exit;
    }

    echo "Raw configuration JSON:\n";
    echo $piece['configuration'] . "\n\n";

    echo "Parsed configuration:\n";
    echo str_repeat("-", 50) . "\n";

    $config = json_decode($piece['configuration'], true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "❌ JSON parsing error: " . json_last_error_msg() . "\n";
        exit;
    }

    echo "✓ JSON parsed successfully\n\n";

    if (isset($config['shapes'])) {
        echo "Number of shapes: " . count($config['shapes']) . "\n\n";

        foreach ($config['shapes'] as $index => $shape) {
            echo "Shape #{$index}:\n";
            echo "  Type: " . ($shape['type'] ?? 'MISSING') . "\n";
            echo "  Color: " . ($shape['color'] ?? 'MISSING') . "\n";
            echo "  Opacity: " . (isset($shape['opacity']) ? $shape['opacity'] : 'MISSING') . "\n";

            // Check animation structure
            if (isset($shape['animation'])) {
                echo "  Animation structure: ";

                // Check if it's old structure (enabled, property, dur)
                if (isset($shape['animation']['enabled'])) {
                    echo "OLD FORMAT (enabled: " . ($shape['animation']['enabled'] ? 'true' : 'false') . ")\n";
                    if (isset($shape['animation']['property'])) {
                        echo "    Property: {$shape['animation']['property']}\n";
                    }
                    if (isset($shape['animation']['dur'])) {
                        echo "    Duration: {$shape['animation']['dur']}\n";
                    }
                }
                // Check if it's new structure (rotation, position, scale)
                else if (isset($shape['animation']['rotation'])) {
                    echo "NEW FORMAT\n";
                    echo "    Rotation enabled: " . ($shape['animation']['rotation']['enabled'] ?? 'MISSING') . "\n";
                    echo "    Position enabled: " . ($shape['animation']['position']['enabled'] ?? 'MISSING') . "\n";
                    echo "    Scale enabled: " . ($shape['animation']['scale']['enabled'] ?? 'MISSING') . "\n";
                } else {
                    echo "UNKNOWN FORMAT\n";
                    print_r($shape['animation']);
                }
            } else {
                echo "  Animation: MISSING\n";
            }
            echo "\n";
        }
    } else {
        echo "❌ No 'shapes' key in configuration\n";
        echo "Configuration keys: " . implode(', ', array_keys($config)) . "\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
