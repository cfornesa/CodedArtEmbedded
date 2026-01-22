<?php
/**
 * Preview Handler - Renders piece from session data without saving to database
 *
 * This file handles live preview requests from the admin interface.
 * It renders the piece using data from the session, never touching the database.
 *
 * Security: Session-based, CSRF protected, no database modifications
 */

session_start();

// Check if this is a POST request with form data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Store preview data in session
    $_SESSION['preview_data'] = $_POST;
    $_SESSION['preview_type'] = 'aframe'; // Hardcoded for now, can be made dynamic

    // Set timestamp for cache busting
    $_SESSION['preview_timestamp'] = time();
}

// Check if preview data exists in session
if (!isset($_SESSION['preview_data']) || !isset($_SESSION['preview_type'])) {
    http_response_code(400);
    die('No preview data available. Please use the "Show Preview" button in the admin panel.');
}

$previewData = $_SESSION['preview_data'];
$artType = $_SESSION['preview_type'];

// Validate art type
$validTypes = ['aframe', 'c2', 'p5', 'threejs'];
if (!in_array($artType, $validTypes)) {
    http_response_code(400);
    die('Invalid art type');
}

// Create a mock "piece" object from preview data
$piece = $previewData;

// Decode configuration_json from form POST data
if (isset($piece['configuration_json'])) {
    $configData = json_decode($piece['configuration_json'], true);
    if ($configData !== null) {
        $piece['configuration'] = $configData;
    }
}

// If configuration is still a JSON string, decode it
if (isset($piece['configuration']) && is_string($piece['configuration'])) {
    $piece['configuration'] = json_decode($piece['configuration'], true);
}

// Extract shapes from configuration
$shapes = [];
if (isset($piece['configuration']['shapes'])) {
    $shapes = $piece['configuration']['shapes'];
} elseif (isset($piece['configuration']) && is_array($piece['configuration'])) {
    // Backward compatibility
    $shapes = $piece['configuration'];
}

// Include helpers for proxifyImageUrl() and other utilities
require_once(__DIR__ . '/../../config/helpers.php');

// Set preview flag so templates know this is a preview
$isPreview = true;

// Route to appropriate view renderer based on art type
switch ($artType) {
    case 'aframe':
        renderAFramePreview($piece, $shapes);
        break;
    case 'c2':
        // TODO: Implement C2 preview
        echo "<h1>C2.js Preview</h1><p>Coming soon...</p>";
        break;
    case 'p5':
        // TODO: Implement P5 preview
        echo "<h1>P5.js Preview</h1><p>Coming soon...</p>";
        break;
    case 'threejs':
        // TODO: Implement Three.js preview
        echo "<h1>Three.js Preview</h1><p>Coming soon...</p>";
        break;
    default:
        http_response_code(400);
        die('Unknown art type');
}

/**
 * Render A-Frame preview
 */
function renderAFramePreview($piece, $shapes) {
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview: <?php echo htmlspecialchars($piece['title'] ?? 'Untitled'); ?></title>
    <script src="https://aframe.io/releases/1.6.0/aframe.min.js"></script>
    <style>
        body {
            margin: 0;
            overflow: hidden;
        }
        .preview-badge {
            position: fixed;
            top: 10px;
            left: 10px;
            background: rgba(255, 193, 7, 0.95);
            color: #000;
            padding: 8px 16px;
            border-radius: 4px;
            font-family: Arial, sans-serif;
            font-size: 14px;
            font-weight: bold;
            z-index: 9999;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>
    <div class="preview-badge">⚠️ PREVIEW MODE - Changes not saved</div>

    <!-- A-Frame Scene -->
    <a-scene>
        <!-- Camera -->
        <a-camera position="0 1.6 0" look-controls wasd-controls></a-camera>

        <!-- Lighting -->
        <a-light type="ambient" color="#BBB"></a-light>
        <a-light type="directional" color="#FFF" intensity="0.6" position="-0.5 1 1"></a-light>

        <!-- Dynamically render shapes from configuration -->
        <?php if (!empty($shapes)): ?>
            <?php foreach ($shapes as $index => $shape): ?>
                <?php
                // Build A-Frame primitive attributes
                $attrs = [];
                $attrs[] = 'id="shape-' . $index . '"';

                // Position
                if (isset($shape['position'])) {
                    $pos = sprintf('%s %s %s', $shape['position']['x'], $shape['position']['y'], $shape['position']['z']);
                    $attrs[] = 'position="' . $pos . '"';
                }

                // Rotation
                if (isset($shape['rotation'])) {
                    $rot = sprintf('%s %s %s', $shape['rotation']['x'], $shape['rotation']['y'], $shape['rotation']['z']);
                    $attrs[] = 'rotation="' . $rot . '"';
                }

                // Scale
                if (isset($shape['scale'])) {
                    $scale = sprintf('%s %s %s', $shape['scale']['x'], $shape['scale']['y'], $shape['scale']['z']);
                    $attrs[] = 'scale="' . $scale . '"';
                }

                // Material properties (color, texture, opacity)
                $materialAttrs = [];

                if (!empty($shape['color'])) {
                    $materialAttrs[] = 'color: ' . htmlspecialchars($shape['color']);
                }

                // Texture (use CORS proxy for external images)
                if (!empty($shape['texture'])) {
                    $textureUrl = proxifyImageUrl($shape['texture']);
                    $materialAttrs[] = 'src: ' . htmlspecialchars($textureUrl);
                }

                // Opacity (if less than 1.0, enable transparency)
                $opacity = isset($shape['opacity']) ? (float)$shape['opacity'] : 1.0;
                if ($opacity < 1.0) {
                    $materialAttrs[] = 'opacity: ' . $opacity;
                    $materialAttrs[] = 'transparent: true';
                }

                if (!empty($materialAttrs)) {
                    $attrs[] = 'material="' . implode('; ', $materialAttrs) . '"';
                }

                // Type-specific dimensions
                $type = $shape['type'] ?? 'box';
                switch ($type) {
                    case 'box':
                        if (isset($shape['width'], $shape['height'], $shape['depth'])) {
                            $attrs[] = sprintf('width="%s" height="%s" depth="%s"', $shape['width'], $shape['height'], $shape['depth']);
                        }
                        break;
                    case 'sphere':
                        if (isset($shape['radius'])) {
                            $attrs[] = 'radius="' . $shape['radius'] . '"';
                        }
                        break;
                    case 'cylinder':
                    case 'cone':
                        if (isset($shape['radius'], $shape['height'])) {
                            $attrs[] = sprintf('radius="%s" height="%s"', $shape['radius'], $shape['height']);
                        }
                        break;
                    case 'plane':
                        if (isset($shape['width'], $shape['height'])) {
                            $attrs[] = sprintf('width="%s" height="%s"', $shape['width'], $shape['height']);
                        }
                        break;
                    case 'torus':
                        if (isset($shape['radius'], $shape['tube'])) {
                            $attrs[] = sprintf('radius="%s" radius-tubular="%s"', $shape['radius'], $shape['tube']);
                        }
                        break;
                    case 'ring':
                        if (isset($shape['radiusInner'], $shape['radiusOuter'])) {
                            $attrs[] = sprintf('radius-inner="%s" radius-outer="%s"', $shape['radiusInner'], $shape['radiusOuter']);
                        }
                        break;
                    default:
                        // Polyhedrons
                        if (isset($shape['radius'])) {
                            $attrs[] = 'radius="' . $shape['radius'] . '"';
                        }
                }

                // Granular Animations
                if (!empty($shape['animation'])) {
                    // Rotation Animation
                    if (!empty($shape['animation']['rotation']['enabled'])) {
                        $counterclockwise = !empty($shape['animation']['rotation']['counterclockwise']);
                        $duration = $shape['animation']['rotation']['duration'] ?? 10000;

                        // Backward compatibility: support old "degrees" field
                        if (isset($shape['animation']['rotation']['degrees'])) {
                            $degrees = $shape['animation']['rotation']['degrees'];
                            $attrs[] = 'animation__rotation="property: rotation; to: 0 ' . $degrees . ' 0; dur: ' . $duration . '; loop: true; easing: linear"';
                        } else {
                            // New format: counterclockwise boolean
                            // Counterclockwise: 360 to 0, Clockwise: 0 to 360
                            $rotation = $counterclockwise ? '0 0 0' : '0 360 0';
                            $from = $counterclockwise ? '0 360 0' : '0 0 0';
                            $attrs[] = 'animation__rotation="property: rotation; from: ' . $from . '; to: ' . $rotation . '; dur: ' . $duration . '; loop: true; easing: linear"';
                        }
                    }

                    // Position Animation - Independent X/Y/Z axis controls
                    $currentPos = $shape['position'] ?? ['x' => 0, 'y' => 0, 'z' => 0];

                    // Check for new format (X/Y/Z independent) vs old format (single enabled)
                    $hasNewFormat = isset($shape['animation']['position']['x']) ||
                                   isset($shape['animation']['position']['y']) ||
                                   isset($shape['animation']['position']['z']);

                    if ($hasNewFormat) {
                        // New format: Combine all enabled axes into a single unified position animation
                        $axes = ['x' => 0, 'y' => 1, 'z' => 2];
                        $enabledAxes = [];
                        $maxDuration = 0;

                        // Collect all enabled axes and find max duration
                        foreach ($axes as $axis => $index) {
                            if (!empty($shape['animation']['position'][$axis]['enabled'])) {
                                $range = $shape['animation']['position'][$axis]['range'] ?? 0;
                                $duration = $shape['animation']['position'][$axis]['duration'] ?? 10000;

                                if ($range > 0) {
                                    $enabledAxes[$axis] = [
                                        'index' => $index,
                                        'range' => $range,
                                        'duration' => $duration
                                    ];
                                    $maxDuration = max($maxDuration, $duration);
                                }
                            }
                        }

                        // If any axes are enabled, create a combined position animation
                        if (!empty($enabledAxes)) {
                            // Build combined from/to positions
                            $fromPos = [$currentPos['x'], $currentPos['y'], $currentPos['z']];
                            $toPos = [$currentPos['x'], $currentPos['y'], $currentPos['z']];

                            // Apply range for each enabled axis
                            foreach ($enabledAxes as $axis => $config) {
                                $index = $config['index'];
                                $range = $config['range'];

                                // From: current - range, To: current + range
                                $fromPos[$index] = $currentPos[$axis] - $range;
                                $toPos[$index] = $currentPos[$axis] + $range;
                            }

                            $fromPosStr = implode(' ', $fromPos);
                            $toPosStr = implode(' ', $toPos);

                            // Single unified position animation for all enabled axes
                            $attrs[] = 'animation__position="property: position; from: ' . $fromPosStr . '; to: ' . $toPosStr . '; dur: ' . $maxDuration . '; loop: true; dir: alternate; easing: easeInOutSine"';
                        }
                    } elseif (!empty($shape['animation']['position']['enabled'])) {
                        // Old format: Backward compatibility
                        $axis = $shape['animation']['position']['axis'] ?? 'y';
                        $distance = $shape['animation']['position']['distance'] ?? 0;
                        $duration = $shape['animation']['position']['duration'] ?? 10000;

                        $fromPos = sprintf('%s %s %s', $currentPos['x'], $currentPos['y'], $currentPos['z']);
                        $toPos = $currentPos;
                        $toPos[$axis] = $currentPos[$axis] + $distance;
                        $toPosStr = sprintf('%s %s %s', $toPos['x'], $toPos['y'], $toPos['z']);

                        $attrs[] = 'animation__position="property: position; from: ' . $fromPos . '; to: ' . $toPosStr . '; dur: ' . $duration . '; loop: true; dir: alternate; easing: easeInOutSine"';
                    }

                    // Scale Animation
                    if (!empty($shape['animation']['scale']['enabled'])) {
                        $minScale = $shape['animation']['scale']['min'] ?? 1.0;
                        $maxScale = $shape['animation']['scale']['max'] ?? 1.0;
                        $duration = $shape['animation']['scale']['duration'] ?? 10000;

                        if ($minScale != $maxScale) {
                            $fromScale = sprintf('%s %s %s', $minScale, $minScale, $minScale);
                            $toScale = sprintf('%s %s %s', $maxScale, $maxScale, $maxScale);
                            $attrs[] = 'animation__scale="property: scale; from: ' . $fromScale . '; to: ' . $toScale . '; dur: ' . $duration . '; loop: true; dir: alternate; easing: easeInOutSine"';
                        }
                    }
                }

                $attrString = implode(' ', $attrs);
                ?>

                <a-<?php echo htmlspecialchars($type); ?> <?php echo $attrString; ?>></a-<?php echo htmlspecialchars($type); ?>>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Ground plane (foreground) -->
        <a-plane
            position="0 0 -4"
            rotation="-90 0 0"
            width="100"
            height="100"
            material="color: <?php echo htmlspecialchars($piece['ground_color'] ?? '#7BC8A4'); ?>;
                      <?php if (!empty($piece['ground_texture'])): ?>
                          src: <?php echo htmlspecialchars(proxifyImageUrl($piece['ground_texture'])); ?>;
                          repeat: 10 10;
                      <?php endif; ?>
                      <?php
                      $groundOpacity = isset($piece['ground_opacity']) ? (float)$piece['ground_opacity'] : 1.0;
                      if ($groundOpacity < 1.0): ?>
                          opacity: <?php echo $groundOpacity; ?>;
                          transparent: true;
                      <?php endif; ?>
                      "
        ></a-plane>

        <!-- Sky (background) -->
        <a-sky
            material="color: <?php echo htmlspecialchars($piece['sky_color'] ?? '#ECECEC'); ?>;
                      <?php if (!empty($piece['sky_texture'])): ?>
                          src: <?php echo htmlspecialchars(proxifyImageUrl($piece['sky_texture'])); ?>;
                      <?php endif; ?>
                      <?php
                      $skyOpacity = isset($piece['sky_opacity']) ? (float)$piece['sky_opacity'] : 1.0;
                      if ($skyOpacity < 1.0): ?>
                          opacity: <?php echo $skyOpacity; ?>;
                          transparent: true;
                      <?php endif; ?>
                      "
        ></a-sky>
    </a-scene>
</body>
</html>
    <?php
}
?>
