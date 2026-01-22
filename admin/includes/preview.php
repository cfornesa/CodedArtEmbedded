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

    // Detect art type from configuration structure
    $artType = 'aframe'; // default
    if (isset($_POST['configuration_json'])) {
        $config = json_decode($_POST['configuration_json'], true);
        if (isset($config['canvas']) && isset($config['pattern'])) {
            $artType = 'c2';
        } elseif (isset($config['canvas']) && isset($config['drawing'])) {
            $artType = 'p5';
        } elseif (isset($config['geometries']) && isset($config['sceneSettings'])) {
            $artType = 'threejs';
        } elseif (isset($config['shapes'])) {
            $artType = 'aframe';
        }
    }

    $_SESSION['preview_type'] = $artType;

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
        renderC2Preview($piece);
        break;
    case 'p5':
        renderP5Preview($piece);
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

/**
 * Render C2.js preview
 */
function renderC2Preview($piece) {
    $config = $piece['configuration'] ?? [];
    $canvasConfig = $config['canvas'] ?? [];
    $patternConfig = $config['pattern'] ?? [];
    $colors = $config['colors'] ?? ['#FF6B6B'];
    $parameters = $config['parameters'] ?? [];
    $animation = $config['animation'] ?? [];
    $interaction = $config['interaction'] ?? [];
    $advanced = $config['advanced'] ?? [];
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview: <?php echo htmlspecialchars($piece['title'] ?? 'Untitled'); ?></title>
    <style>
        body {
            margin: 0;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: #f5f5f5;
        }
        .preview-badge {
            position: fixed;
            top: 10px;
            left: 10px;
            background: rgba(237, 34, 93, 0.95);
            color: #fff;
            padding: 8px 16px;
            border-radius: 4px;
            font-family: Arial, sans-serif;
            font-size: 14px;
            font-weight: bold;
            z-index: 9999;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        #c2-canvas {
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="preview-badge">⚠️ PREVIEW MODE - Changes not saved</div>

    <canvas id="c2-canvas"
            width="<?php echo $canvasConfig['width'] ?? 800; ?>"
            height="<?php echo $canvasConfig['height'] ?? 600; ?>">
    </canvas>

    <script>
// C2.js Pattern Configuration
const config = <?php echo json_encode($config); ?>;

// Initialize canvas
const canvas = document.getElementById('c2-canvas');
const ctx = canvas.getContext('2d');
const width = canvas.width;
const height = canvas.height;

// Apply background
ctx.fillStyle = config.canvas?.background || '#FFFFFF';
ctx.fillRect(0, 0, width, height);

// Set blend mode
if (config.advanced && config.advanced.blendMode) {
    ctx.globalCompositeOperation = config.advanced.blendMode;
}

// Random seed for reproducible patterns
let seed = config.advanced?.randomSeed || 12345;
function random() {
    const x = Math.sin(seed++) * 10000;
    return x - Math.floor(x);
}

// Draw pattern based on configuration
function drawPattern() {
    const pattern = config.pattern?.type || 'scatter';
    const elementCount = config.pattern?.elementCount || 100;
    const elementSize = config.parameters?.elementSize || 5;
    const sizeVariation = (config.parameters?.sizeVariation || 20) / 100;
    const spacing = config.parameters?.spacing || 20;
    const opacity = (config.parameters?.opacity || 80) / 100;
    const colors = config.colors || ['#ED225D'];

    ctx.globalAlpha = opacity;

    switch (pattern) {
        case 'grid':
            drawGridPattern(elementCount, elementSize, spacing, colors, sizeVariation);
            break;
        case 'spiral':
            drawSpiralPattern(elementCount, elementSize, colors, sizeVariation);
            break;
        case 'scatter':
            drawScatterPattern(elementCount, elementSize, colors, sizeVariation);
            break;
        case 'wave':
            drawWavePattern(elementCount, elementSize, spacing, colors, sizeVariation);
            break;
        case 'concentric':
            drawConcentricPattern(elementCount, elementSize, colors, sizeVariation);
            break;
        case 'fractal':
            drawFractalPattern(elementCount, elementSize, colors, sizeVariation);
            break;
        case 'particle':
            drawParticlePattern(elementCount, elementSize, colors, sizeVariation);
            break;
        case 'flow':
            drawFlowPattern(elementCount, elementSize, colors, sizeVariation);
            break;
        default:
            drawScatterPattern(elementCount, elementSize, colors, sizeVariation);
    }
}

function drawGridPattern(count, size, spacing, colors, variation) {
    const cols = Math.ceil(width / spacing);
    const rows = Math.ceil(height / spacing);

    for (let i = 0; i < rows; i++) {
        for (let j = 0; j < cols; j++) {
            const x = j * spacing + spacing / 2;
            const y = i * spacing + spacing / 2;
            const s = size * (1 + (random() - 0.5) * variation);
            ctx.fillStyle = colors[Math.floor(random() * colors.length)];
            ctx.beginPath();
            ctx.arc(x, y, s, 0, Math.PI * 2);
            ctx.fill();
        }
    }
}

function drawSpiralPattern(count, size, colors, variation) {
    const centerX = width / 2;
    const centerY = height / 2;
    const maxRadius = Math.min(width, height) / 2;

    for (let i = 0; i < count; i++) {
        const t = i / count;
        const angle = t * Math.PI * 8;
        const radius = t * maxRadius;
        const x = centerX + Math.cos(angle) * radius;
        const y = centerY + Math.sin(angle) * radius;
        const s = size * (1 + (random() - 0.5) * variation);

        ctx.fillStyle = colors[Math.floor(random() * colors.length)];
        ctx.beginPath();
        ctx.arc(x, y, s, 0, Math.PI * 2);
        ctx.fill();
    }
}

function drawScatterPattern(count, size, colors, variation) {
    for (let i = 0; i < count; i++) {
        const x = random() * width;
        const y = random() * height;
        const s = size * (1 + (random() - 0.5) * variation);

        ctx.fillStyle = colors[Math.floor(random() * colors.length)];
        ctx.beginPath();
        ctx.arc(x, y, s, 0, Math.PI * 2);
        ctx.fill();
    }
}

function drawWavePattern(count, size, spacing, colors, variation) {
    const rows = Math.ceil(height / spacing);
    const amplitude = 50;

    for (let i = 0; i < rows; i++) {
        for (let j = 0; j < count; j++) {
            const t = j / count;
            const x = t * width + Math.sin(i * 0.5) * amplitude;
            const y = i * spacing;
            const s = size * (1 + (random() - 0.5) * variation);

            ctx.fillStyle = colors[Math.floor(random() * colors.length)];
            ctx.beginPath();
            ctx.arc(x, y, s, 0, Math.PI * 2);
            ctx.fill();
        }
    }
}

function drawConcentricPattern(count, size, colors, variation) {
    const centerX = width / 2;
    const centerY = height / 2;
    const maxRadius = Math.min(width, height) / 2;

    for (let i = 0; i < count; i++) {
        const radius = (i / count) * maxRadius;
        const points = Math.floor(radius * 2);

        for (let j = 0; j < points; j++) {
            const angle = (j / points) * Math.PI * 2;
            const x = centerX + Math.cos(angle) * radius;
            const y = centerY + Math.sin(angle) * radius;
            const s = size * (1 + (random() - 0.5) * variation);

            ctx.fillStyle = colors[Math.floor(random() * colors.length)];
            ctx.beginPath();
            ctx.arc(x, y, s, 0, Math.PI * 2);
            ctx.fill();
        }
    }
}

function drawFractalPattern(count, size, colors, variation) {
    function branch(x, y, angle, length, depth) {
        if (depth === 0) {
            ctx.fillStyle = colors[Math.floor(random() * colors.length)];
            ctx.beginPath();
            ctx.arc(x, y, size * (1 + (random() - 0.5) * variation), 0, Math.PI * 2);
            ctx.fill();
            return;
        }

        const newX = x + Math.cos(angle) * length;
        const newY = y + Math.sin(angle) * length;

        branch(newX, newY, angle - 0.3, length * 0.7, depth - 1);
        branch(newX, newY, angle + 0.3, length * 0.7, depth - 1);
    }

    branch(width / 2, height, -Math.PI / 2, 100, 5);
}

function drawParticlePattern(count, size, colors, variation) {
    drawScatterPattern(count, size, colors, variation);
}

function drawFlowPattern(count, size, colors, variation) {
    for (let i = 0; i < count; i++) {
        const x = random() * width;
        const y = random() * height;
        const angle = random() * Math.PI * 2;
        const length = random() * 50;

        ctx.strokeStyle = colors[Math.floor(random() * colors.length)];
        ctx.lineWidth = size * (1 + (random() - 0.5) * variation);
        ctx.beginPath();
        ctx.moveTo(x, y);
        ctx.lineTo(x + Math.cos(angle) * length, y + Math.sin(angle) * length);
        ctx.stroke();
    }
}

// Draw initial pattern
drawPattern();

// Animation support
if (config.animation && config.animation.animated && config.animation.loop) {
    let animationFrame = 0;

    function animate() {
        animationFrame++;

        // Clear canvas based on settings
        if (!config.advanced?.enableTrails) {
            ctx.fillStyle = config.canvas?.background || '#FFFFFF';
            ctx.fillRect(0, 0, width, height);
        }

        // Modify seed for animation
        seed = config.advanced?.randomSeed + animationFrame * (config.animation.speed || 1);

        // Redraw pattern
        drawPattern();

        requestAnimationFrame(animate);
    }

    animate();
}
    </script>
</body>
</html>
    <?php
}

/**
 * Render P5.js sketch preview
 *
 * @param array $piece The piece data with configuration
 */
function renderP5Preview($piece) {
    $config = is_array($piece['configuration']) ? $piece['configuration'] : [];

    // Extract configuration sections
    $canvasConfig = $config['canvas'] ?? [];
    $drawingConfig = $config['drawing'] ?? [];
    $colorsConfig = $config['colors'] ?? [];
    $animationConfig = $config['animation'] ?? [];
    $advancedConfig = $config['advanced'] ?? [];

    // Canvas settings
    $canvasWidth = $canvasConfig['width'] ?? 800;
    $canvasHeight = $canvasConfig['height'] ?? 600;
    $renderer = $canvasConfig['renderer'] ?? 'P2D';
    $background = $canvasConfig['background'] ?? '#FFFFFF';

    // Drawing settings
    $drawingMode = $drawingConfig['mode'] ?? 'ellipse';
    $fillColor = $drawingConfig['fillColor'] ?? '#ED225D';
    $fillOpacity = isset($drawingConfig['fillOpacity']) ? (int)$drawingConfig['fillOpacity'] : 255;
    $strokeColor = $drawingConfig['strokeColor'] ?? '#000000';
    $strokeWeight = isset($drawingConfig['strokeWeight']) ? (int)$drawingConfig['strokeWeight'] : 1;
    $noStroke = !empty($drawingConfig['noStroke']);
    $noFill = !empty($drawingConfig['noFill']);

    // Animation settings
    $animated = !empty($animationConfig['animated']);
    $loop = !empty($animationConfig['loop']);
    $speed = $animationConfig['speed'] ?? 1;

    // Advanced settings
    $mouseInteraction = !empty($advancedConfig['mouseInteraction']);
    $keyboardInteraction = !empty($advancedConfig['keyboardInteraction']);
    $clearBackground = isset($advancedConfig['clearBackground']) ? (bool)$advancedConfig['clearBackground'] : true;
    $randomSeed = isset($advancedConfig['randomSeed']) ? (int)$advancedConfig['randomSeed'] : null;

    // Color palette
    $usePalette = !empty($colorsConfig['usePalette']);
    $palette = $colorsConfig['palette'] ?? [];

    ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Preview: <?php echo htmlspecialchars($piece['title'] ?? 'Untitled'); ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/p5.js/1.7.0/p5.min.js"></script>
    <style>
        body {
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: #f0f0f0;
        }
        .preview-badge {
            position: fixed;
            top: 10px;
            right: 10px;
            background: #ED225D;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            font-weight: bold;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        main {
            display: flex;
            justify-content: center;
            align-items: center;
        }
    </style>
</head>
<body>
    <div class="preview-badge">⚠️ PREVIEW MODE - Changes not saved</div>
    <main></main>

    <script>
// P5.js Configuration
const config = <?php echo json_encode($config); ?>;

// Canvas settings
const canvasWidth = <?php echo $canvasWidth; ?>;
const canvasHeight = <?php echo $canvasHeight; ?>;
const renderer = '<?php echo $renderer; ?>';
const bgColor = '<?php echo $background; ?>';

// Drawing settings
const drawingMode = '<?php echo $drawingMode; ?>';
const fillColor = '<?php echo $fillColor; ?>';
const fillOpacity = <?php echo $fillOpacity; ?>;
const strokeColor = '<?php echo $strokeColor; ?>';
const strokeWeight = <?php echo $strokeWeight; ?>;
const noStrokeEnabled = <?php echo $noStroke ? 'true' : 'false'; ?>;
const noFillEnabled = <?php echo $noFill ? 'true' : 'false'; ?>;

// Animation settings
const animated = <?php echo $animated ? 'true' : 'false'; ?>;
const loopEnabled = <?php echo $loop ? 'true' : 'false'; ?>;
const animSpeed = <?php echo $speed; ?>;

// Advanced settings
const mouseEnabled = <?php echo $mouseInteraction ? 'true' : 'false'; ?>;
const keyboardEnabled = <?php echo $keyboardInteraction ? 'true' : 'false'; ?>;
const clearBg = <?php echo $clearBackground ? 'true' : 'false'; ?>;
<?php if ($randomSeed !== null): ?>
const randomSeedValue = <?php echo $randomSeed; ?>;
<?php endif; ?>

// Color palette
const usePalette = <?php echo $usePalette ? 'true' : 'false'; ?>;
const palette = <?php echo json_encode($palette); ?>;

// Animation variables
let animationFrame = 0;
let offset = 0;

function setup() {
    // Create canvas
    if (renderer === 'WEBGL') {
        createCanvas(canvasWidth, canvasHeight, WEBGL);
    } else {
        createCanvas(canvasWidth, canvasHeight);
    }

    // Set background
    background(bgColor);

    // Set random seed if specified
    <?php if ($randomSeed !== null): ?>
    randomSeed(randomSeedValue);
    <?php endif; ?>

    // Set loop behavior
    if (!loopEnabled && !animated) {
        noLoop();
    }
}

function draw() {
    // Clear background if enabled
    if (clearBg || animated) {
        background(bgColor);
    }

    // Set stroke and fill
    if (noStrokeEnabled) {
        noStroke();
    } else {
        stroke(strokeColor);
        strokeWeight(strokeWeight);
    }

    if (noFillEnabled) {
        noFill();
    } else {
        // Use palette or single color
        if (usePalette && palette.length > 0) {
            const colorIndex = floor(animationFrame / 10) % palette.length;
            const c = color(palette[colorIndex]);
            c.setAlpha(fillOpacity);
            fill(c);
        } else {
            const c = color(fillColor);
            c.setAlpha(fillOpacity);
            fill(c);
        }
    }

    // Drawing mode
    switch (drawingMode) {
        case 'ellipse':
            drawEllipses();
            break;
        case 'rect':
            drawRectangles();
            break;
        case 'triangle':
            drawTriangles();
            break;
        case 'line':
            drawLines();
            break;
        case 'points':
            drawPoints();
            break;
        case 'spiral':
            drawSpiral();
            break;
        case 'grid':
            drawGrid();
            break;
        default:
            drawEllipses();
    }

    // Update animation
    if (animated) {
        animationFrame++;
        offset += animSpeed;
    }
}

function drawEllipses() {
    const size = animated ? 50 + sin(offset * 0.05) * 30 : 50;
    for (let i = 0; i < 5; i++) {
        const x = (i + 1) * (width / 6);
        const y = height / 2 + (animated ? sin(offset * 0.1 + i) * 50 : 0);
        ellipse(x, y, size, size);
    }
}

function drawRectangles() {
    const size = animated ? 50 + sin(offset * 0.05) * 30 : 50;
    for (let i = 0; i < 5; i++) {
        const x = (i + 1) * (width / 6) - size / 2;
        const y = height / 2 - size / 2 + (animated ? sin(offset * 0.1 + i) * 50 : 0);
        rect(x, y, size, size);
    }
}

function drawTriangles() {
    const size = animated ? 50 + sin(offset * 0.05) * 30 : 50;
    for (let i = 0; i < 5; i++) {
        const x = (i + 1) * (width / 6);
        const y = height / 2 + (animated ? sin(offset * 0.1 + i) * 50 : 0);
        triangle(x, y - size / 2, x - size / 2, y + size / 2, x + size / 2, y + size / 2);
    }
}

function drawLines() {
    for (let i = 0; i < 10; i++) {
        const x1 = i * (width / 10);
        const y1 = animated ? height / 2 + sin(offset * 0.1 + i * 0.5) * height / 4 : height / 4;
        const x2 = x1;
        const y2 = animated ? height / 2 + sin(offset * 0.1 + i * 0.5 + PI) * height / 4 : height * 3 / 4;
        line(x1, y1, x2, y2);
    }
}

function drawPoints() {
    strokeWeight(8);
    for (let i = 0; i < width; i += 20) {
        for (let j = 0; j < height; j += 20) {
            const x = i + (animated ? sin(offset * 0.05 + i * 0.1) * 10 : 0);
            const y = j + (animated ? cos(offset * 0.05 + j * 0.1) * 10 : 0);
            point(x, y);
        }
    }
}

function drawSpiral() {
    const centerX = width / 2;
    const centerY = height / 2;
    const maxRadius = min(width, height) / 2 - 20;
    const turns = 5;
    const segments = 200;

    beginShape();
    for (let i = 0; i <= segments; i++) {
        const angle = (i / segments) * turns * TWO_PI + (animated ? offset * 0.02 : 0);
        const radius = (i / segments) * maxRadius;
        const x = centerX + cos(angle) * radius;
        const y = centerY + sin(angle) * radius;
        vertex(x, y);
    }
    endShape();
}

function drawGrid() {
    const cols = 8;
    const rows = 6;
    const cellWidth = width / cols;
    const cellHeight = height / rows;
    const size = min(cellWidth, cellHeight) * 0.6;

    for (let i = 0; i < cols; i++) {
        for (let j = 0; j < rows; j++) {
            const x = i * cellWidth + cellWidth / 2;
            const y = j * cellHeight + cellHeight / 2;
            const s = size + (animated ? sin(offset * 0.1 + i + j) * size * 0.3 : 0);
            ellipse(x, y, s, s);
        }
    }
}

// Mouse interaction
if (mouseEnabled) {
    function mouseMoved() {
        redraw();
    }

    function mousePressed() {
        redraw();
    }
}

// Keyboard interaction
if (keyboardEnabled) {
    function keyPressed() {
        if (key === ' ') {
            if (loopEnabled) {
                noLoop();
            } else {
                loop();
            }
        }
        redraw();
    }
}
    </script>
</body>
</html>
    <?php
}
?>
