<?php
/**
 * A-Frame Dynamic Piece Viewer
 * Renders A-Frame art pieces from database configuration
 */

require_once(__DIR__ . '/../resources/templates/name.php');
require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../config/helpers.php');

// Get slug from query parameter
$slug = $_GET['slug'] ?? null;

if (!$slug) {
    http_response_code(404);
    die('Art piece not found. No slug provided.');
}

// Query database for the piece (check both active and draft status during development)
try {
    $piece = dbFetchOne(
        "SELECT * FROM aframe_art WHERE slug = ? AND deleted_at IS NULL",
        [$slug]
    );

    if (!$piece) {
        http_response_code(404);
        die('Art piece not found.');
    }

    // Load configuration
    $config = !empty($piece['configuration']) ? json_decode($piece['configuration'], true) : null;
    $shapes = $config['shapes'] ?? [];
    $sceneSettings = $config['sceneSettings'] ?? [];

    // Set page metadata
    $page_name = htmlspecialchars($piece['title']);
    $tagline = htmlspecialchars($piece['description'] ?? 'A-Frame WebVR Art Piece');

} catch (Exception $e) {
    error_log('Error loading A-Frame piece: ' . $e->getMessage());
    http_response_code(500);
    die('Error loading art piece.');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_name); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($tagline); ?>">
    <script src="https://aframe.io/releases/1.6.0/aframe.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        html, body {
            width: 100%;
            height: 100%;
            overflow: hidden;
        }
    </style>
</head>
<body>
<!-- Fullscreen immersive view - no header/navigation for distraction-free viewing -->

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
            } else if (!empty($shape['color'])) {
                // Fallback for backward compatibility
                $attrs[] = 'color="' . htmlspecialchars($shape['color']) . '"';
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
                    // Polyhedrons (dodecahedron, octahedron, etc.)
                    if (isset($shape['radius'])) {
                        $attrs[] = 'radius="' . $shape['radius'] . '"';
                    }
            }

            // Granular Animations (rotation, position, scale)
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

                    // Only animate if min and max are different
                    if ($minScale != $maxScale) {
                        $fromScale = sprintf('%s %s %s', $minScale, $minScale, $minScale);
                        $toScale = sprintf('%s %s %s', $maxScale, $maxScale, $maxScale);
                        $attrs[] = 'animation__scale="property: scale; from: ' . $fromScale . '; to: ' . $toScale . '; dur: ' . $duration . '; loop: true; dir: alternate; easing: easeInOutSine"';
                    }
                }

                // Backward compatibility: Support old animation structure
                if (isset($shape['animation']['enabled']) && $shape['animation']['enabled'] &&
                    empty($shape['animation']['rotation']) &&
                    empty($shape['animation']['position']) &&
                    empty($shape['animation']['scale'])) {
                    $animAttrs = [];
                    $animAttrs[] = 'property: ' . ($shape['animation']['property'] ?? 'rotation');
                    $animAttrs[] = 'to: ' . ($shape['animation']['to'] ?? '0 360 0');
                    $animAttrs[] = 'dur: ' . ($shape['animation']['dur'] ?? 10000);
                    $animAttrs[] = 'loop: true';
                    $attrs[] = 'animation="' . implode('; ', $animAttrs) . '"';
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

<!-- Fullscreen immersive viewing - no footer -->
</body>
</html>
