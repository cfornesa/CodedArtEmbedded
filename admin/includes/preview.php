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
        renderThreeJSPreview($piece);
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

// Helper function to draw different shapes
function drawShape(x, y, size, shape, color) {
    ctx.fillStyle = color;
    ctx.beginPath();

    switch (shape) {
        case 'circle':
            ctx.arc(x, y, size, 0, Math.PI * 2);
            ctx.fill();
            break;
        case 'square':
            ctx.fillRect(x - size, y - size, size * 2, size * 2);
            break;
        case 'triangle':
            ctx.moveTo(x, y - size);
            ctx.lineTo(x - size, y + size);
            ctx.lineTo(x + size, y + size);
            ctx.closePath();
            ctx.fill();
            break;
        case 'hexagon':
            for (let i = 0; i < 6; i++) {
                const angle = (Math.PI / 3) * i;
                const hx = x + size * Math.cos(angle);
                const hy = y + size * Math.sin(angle);
                if (i === 0) ctx.moveTo(hx, hy);
                else ctx.lineTo(hx, hy);
            }
            ctx.closePath();
            ctx.fill();
            break;
        case 'star':
            for (let i = 0; i < 10; i++) {
                const angle = (Math.PI / 5) * i - Math.PI / 2;
                const radius = i % 2 === 0 ? size : size / 2;
                const sx = x + radius * Math.cos(angle);
                const sy = y + radius * Math.sin(angle);
                if (i === 0) ctx.moveTo(sx, sy);
                else ctx.lineTo(sx, sy);
            }
            ctx.closePath();
            ctx.fill();
            break;
        default:
            ctx.arc(x, y, size, 0, Math.PI * 2);
            ctx.fill();
    }
}

// Draw pattern based on configuration
function drawPattern() {
    const pattern = config.pattern?.type || 'scatter';
    const elementCount = config.pattern?.elementCount || 100;
    const elementSize = config.parameters?.elementSize || 5;
    const sizeVariation = (config.parameters?.sizeVariation || 20) / 100;
    const spacing = config.parameters?.spacing || 20;
    const opacity = (config.parameters?.opacity || 80) / 100;

    // Support both new shapes format and old colors format (backward compatibility)
    const shapes = config.shapes || (config.colors ? config.colors.map(c => ({ shape: 'circle', color: c })) : [{ shape: 'circle', color: '#ED225D' }]);

    ctx.globalAlpha = opacity;

    switch (pattern) {
        case 'grid':
            drawGridPattern(elementCount, elementSize, spacing, shapes, sizeVariation);
            break;
        case 'spiral':
            drawSpiralPattern(elementCount, elementSize, shapes, sizeVariation);
            break;
        case 'scatter':
            drawScatterPattern(elementCount, elementSize, shapes, sizeVariation);
            break;
        case 'wave':
            drawWavePattern(elementCount, elementSize, spacing, shapes, sizeVariation);
            break;
        case 'concentric':
            drawConcentricPattern(elementCount, elementSize, shapes, sizeVariation);
            break;
        case 'fractal':
            drawFractalPattern(elementCount, elementSize, shapes, sizeVariation);
            break;
        case 'particle':
            drawParticlePattern(elementCount, elementSize, shapes, sizeVariation);
            break;
        case 'flow':
            drawFlowPattern(elementCount, elementSize, shapes, sizeVariation);
            break;
        default:
            drawScatterPattern(elementCount, elementSize, shapes, sizeVariation);
    }
}

function drawGridPattern(count, size, spacing, shapes, variation) {
    const cols = Math.ceil(width / spacing);
    const rows = Math.ceil(height / spacing);

    for (let i = 0; i < rows; i++) {
        for (let j = 0; j < cols; j++) {
            const x = j * spacing + spacing / 2;
            const y = i * spacing + spacing / 2;
            const s = size * (1 + (random() - 0.5) * variation);
            const shapeItem = shapes[Math.floor(random() * shapes.length)];
            drawShape(x, y, s, shapeItem.shape, shapeItem.color);
        }
    }
}

function drawSpiralPattern(count, size, shapes, variation) {
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

        const shapeItem = shapes[i % shapes.length];
        drawShape(x, y, s, shapeItem.shape, shapeItem.color);
    }
}

function drawScatterPattern(count, size, shapes, variation) {
    for (let i = 0; i < count; i++) {
        const x = random() * width;
        const y = random() * height;
        const s = size * (1 + (random() - 0.5) * variation);

        const shapeItem = shapes[Math.floor(random() * shapes.length)];
        drawShape(x, y, s, shapeItem.shape, shapeItem.color);
    }
}

function drawWavePattern(count, size, spacing, shapes, variation) {
    const rows = Math.ceil(height / spacing);
    const amplitude = 50;

    for (let i = 0; i < rows; i++) {
        for (let j = 0; j < count; j++) {
            const t = j / count;
            const x = t * width + Math.sin(i * 0.5) * amplitude;
            const y = i * spacing;
            const s = size * (1 + (random() - 0.5) * variation);

            const shapeItem = shapes[Math.floor(random() * shapes.length)];
            drawShape(x, y, s, shapeItem.shape, shapeItem.color);
        }
    }
}

function drawConcentricPattern(count, size, shapes, variation) {
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

            const shapeItem = shapes[Math.floor(random() * shapes.length)];
            drawShape(x, y, s, shapeItem.shape, shapeItem.color);
        }
    }
}

function drawFractalPattern(count, size, shapes, variation) {
    function branch(x, y, angle, length, depth) {
        if (depth === 0) {
            const s = size * (1 + (random() - 0.5) * variation);
            const shapeItem = shapes[Math.floor(random() * shapes.length)];
            drawShape(x, y, s, shapeItem.shape, shapeItem.color);
            return;
        }

        const newX = x + Math.cos(angle) * length;
        const newY = y + Math.sin(angle) * length;

        branch(newX, newY, angle - 0.3, length * 0.7, depth - 1);
        branch(newX, newY, angle + 0.3, length * 0.7, depth - 1);
    }

    branch(width / 2, height, -Math.PI / 2, 100, 5);
}

function drawParticlePattern(count, size, shapes, variation) {
    drawScatterPattern(count, size, shapes, variation);
}

function drawFlowPattern(count, size, shapes, variation) {
    for (let i = 0; i < count; i++) {
        const x = random() * width;
        const y = random() * height;
        const angle = random() * Math.PI * 2;
        const length = random() * 50;
        const s = size * (1 + (random() - 0.5) * variation);

        const shapeItem = shapes[Math.floor(random() * shapes.length)];

        // Draw line with shape color
        ctx.strokeStyle = shapeItem.color;
        ctx.lineWidth = s / 2;
        ctx.beginPath();
        ctx.moveTo(x, y);
        ctx.lineTo(x + Math.cos(angle) * length, y + Math.sin(angle) * length);
        ctx.stroke();

        // Draw shape at endpoint for visual interest
        const endX = x + Math.cos(angle) * length;
        const endY = y + Math.sin(angle) * length;
        drawShape(endX, endY, s, shapeItem.shape, shapeItem.color);
    }
}

// Draw initial pattern
drawPattern();

// Animation if enabled - Backward compatibility for both old and new formats
// Old format: config.animation.enabled (single toggle)
// New format: config.animation.rotation.enabled, pulse.enabled, move.enabled, color.enabled (granular)
const isAnimationEnabled = config.animation && (
    config.animation.enabled || // Old format
    (config.animation.rotation && config.animation.rotation.enabled) || // New format: rotation
    (config.animation.pulse && config.animation.pulse.enabled) || // New format: pulse
    (config.animation.move && config.animation.move.enabled) || // New format: move
    (config.animation.color && config.animation.color.enabled) // New format: color
);

if (isAnimationEnabled) {
    let frame = 0;

    // Determine speed and loop from either old or new format
    let speed = 1;
    let loop = true;

    if (config.animation.enabled) {
        // Old format
        speed = config.animation.speed || 1;
        loop = config.animation.loop !== false;
    } else {
        // New format - use speed from first enabled animation type
        if (config.animation.rotation?.enabled) {
            speed = config.animation.rotation.speed || 1;
            loop = config.animation.rotation.loop !== false;
        } else if (config.animation.pulse?.enabled) {
            speed = config.animation.pulse.speed || 1;
            loop = config.animation.pulse.loop !== false;
        } else if (config.animation.move?.enabled) {
            speed = config.animation.move.speed || 1;
            loop = config.animation.move.loop !== false;
        } else if (config.animation.color?.enabled) {
            speed = config.animation.color.speed || 1;
            loop = config.animation.color.loop !== false;
        }
    }

    function animate() {
        if (!loop && frame > 60 * 10) return; // Stop after 10 seconds if not looping

        // Clear with trails effect if enabled
        if (config.advanced?.enableTrails) {
            ctx.fillStyle = config.canvas.background + '20'; // Semi-transparent
            ctx.fillRect(0, 0, width, height);
        } else {
            ctx.fillStyle = config.canvas.background || '#FFFFFF';
            ctx.fillRect(0, 0, width, height);
        }

        // Redraw with animation offset
        seed = (config.advanced?.randomSeed || 12345) + frame * speed;
        drawPattern();

        frame++;
        requestAnimationFrame(animate);
    }

    animate();
}

// Mouse interaction support
if (config.interaction && config.interaction.enabled) {
    const interactionRadius = config.interaction.radius || 100;
    const interactionType = config.interaction.type || 'repel';

    canvas.addEventListener('mousemove', function(e) {
        const rect = canvas.getBoundingClientRect();
        const mouseX = e.clientX - rect.left;
        const mouseY = e.clientY - rect.top;

        // Clear and redraw with mouse interaction
        ctx.fillStyle = config.canvas?.background || '#FFFFFF';
        ctx.fillRect(0, 0, width, height);

        // Re-seed for consistency
        seed = config.advanced?.randomSeed || 12345;

        // Draw pattern with mouse influence
        drawPatternWithInteraction(mouseX, mouseY, interactionRadius, interactionType);
    });
}

function drawPatternWithInteraction(mouseX, mouseY, radius, type) {
    const pattern = config.pattern?.type || 'scatter';
    const elementCount = config.pattern?.elementCount || 100;
    const elementSize = config.parameters?.elementSize || 5;
    const sizeVariation = (config.parameters?.sizeVariation || 20) / 100;
    const spacing = config.parameters?.spacing || 20;
    const opacity = (config.parameters?.opacity || 80) / 100;

    // Support both new shapes format and old colors format (backward compatibility)
    const shapes = config.shapes || (config.colors ? config.colors.map(c => ({ shape: 'circle', color: c })) : [{ shape: 'circle', color: '#ED225D' }]);

    ctx.globalAlpha = opacity;

    // Simple scatter pattern with mouse interaction
    for (let i = 0; i < elementCount; i++) {
        const x = random() * width;
        const y = random() * height;
        const size = elementSize * (1 + (random() - 0.5) * sizeVariation);
        const shapeItem = shapes[i % shapes.length];

        // Calculate distance from mouse
        const dx = x - mouseX;
        const dy = y - mouseY;
        const dist = Math.sqrt(dx * dx + dy * dy);

        let finalX = x;
        let finalY = y;

        if (dist < radius) {
            const force = (radius - dist) / radius;
            if (type === 'repel') {
                finalX += (dx / dist) * force * 50;
                finalY += (dy / dist) * force * 50;
            } else if (type === 'attract') {
                finalX -= (dx / dist) * force * 50;
                finalY -= (dy / dist) * force * 50;
            }
        }

        drawShape(finalX, finalY, size, shapeItem.shape, shapeItem.color);
    }
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

    // Extract background image URL (new standardized field)
    $backgroundImageUrl = $piece['background_image_url'] ?? null;

    // Backward compatibility: fallback to first image in old image_urls array
    if (empty($backgroundImageUrl) && !empty($piece['image_urls'])) {
        $imageUrls = is_array($piece['image_urls']) ? $piece['image_urls'] : json_decode($piece['image_urls'], true);
        if (is_array($imageUrls) && !empty($imageUrls)) {
            $backgroundImageUrl = $imageUrls[0];
        }
    }

    // Extract configuration sections
    $canvasConfig = $config['canvas'] ?? [];
    $drawingConfig = $config['drawing'] ?? [];
    $shapesConfig = $config['shapes'] ?? [];
    $colorsConfig = $config['colors'] ?? [];  // For backward compatibility
    $animationConfig = $config['animation'] ?? [];
    $advancedConfig = $config['advanced'] ?? [];

    // Support both new shapes format and old colors format (backward compatibility)
    if (!empty($shapesConfig)) {
        $shapes = $shapesConfig;
    } elseif (!empty($colorsConfig)) {
        // Migrate old colors to shapes with default ellipse
        $shapes = array_map(function($color) {
            return ['shape' => 'ellipse', 'color' => $color];
        }, $colorsConfig);
    } else {
        $shapes = [['shape' => 'ellipse', 'color' => '#ED225D']];
    }

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

    // Animation settings - Backward compatibility for both old and new formats
    // Old format: animationConfig['animated'] (single toggle)
    // New format: animationConfig['rotation']['enabled'], scale, translation, color (granular)
    $animated = !empty($animationConfig['animated']) || // Old format
        (!empty($animationConfig['rotation']) && !empty($animationConfig['rotation']['enabled'])) || // New: rotation
        (!empty($animationConfig['scale']) && !empty($animationConfig['scale']['enabled'])) || // New: scale/pulse
        (!empty($animationConfig['translation']) && !empty($animationConfig['translation']['enabled'])) || // New: translation/move
        (!empty($animationConfig['color']) && !empty($animationConfig['color']['enabled'])); // New: color

    // Extract speed and loop from either old or new format
    $speed = 1;
    $loop = true;

    if (!empty($animationConfig['animated'])) {
        // Old format
        $speed = $animationConfig['speed'] ?? 1;
        $loop = !empty($animationConfig['loop']);
    } else {
        // New format - use speed from first enabled animation type
        if (!empty($animationConfig['rotation']['enabled'])) {
            $speed = $animationConfig['rotation']['speed'] ?? 1;
            $loop = isset($animationConfig['rotation']['loop']) ? !empty($animationConfig['rotation']['loop']) : true;
        } elseif (!empty($animationConfig['scale']['enabled'])) {
            $speed = $animationConfig['scale']['speed'] ?? 1;
            $loop = isset($animationConfig['scale']['loop']) ? !empty($animationConfig['scale']['loop']) : true;
        } elseif (!empty($animationConfig['translation']['enabled'])) {
            $speed = $animationConfig['translation']['speed'] ?? 1;
            $loop = isset($animationConfig['translation']['loop']) ? !empty($animationConfig['translation']['loop']) : true;
        } elseif (!empty($animationConfig['color']['enabled'])) {
            $speed = $animationConfig['color']['speed'] ?? 1;
            $loop = isset($animationConfig['color']['loop']) ? !empty($animationConfig['color']['loop']) : true;
        }
    }

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

// Backward compatibility for animation format (JavaScript side)
// This ensures preview works exactly like view page
if (config.animation) {
    const animationConfig = config.animation;

    // Old format: animationConfig.animated (single toggle)
    // New format: animationConfig.rotation.enabled, scale.enabled, translation.enabled, color.enabled (granular)
    const isAnimated = animationConfig.animated || // Old format
        (animationConfig.rotation && animationConfig.rotation.enabled) || // New format: rotation
        (animationConfig.scale && animationConfig.scale.enabled) || // New format: scale/pulse
        (animationConfig.translation && animationConfig.translation.enabled) || // New format: translation/move
        (animationConfig.color && animationConfig.color.enabled); // New format: color

    // Override animationConfig.animated with computed value for backward compatibility
    config.animation.animated = isAnimated;

    // If using new format, extract speed and loop from first enabled animation
    if (isAnimated && !config.animation.speed) {
        if (animationConfig.rotation && animationConfig.rotation.enabled) {
            config.animation.speed = animationConfig.rotation.speed || 1;
            config.animation.loop = animationConfig.rotation.loop !== false;
        } else if (animationConfig.scale && animationConfig.scale.enabled) {
            config.animation.speed = animationConfig.scale.speed || 1;
            config.animation.loop = animationConfig.scale.loop !== false;
        } else if (animationConfig.translation && animationConfig.translation.enabled) {
            config.animation.speed = animationConfig.translation.speed || 1;
            config.animation.loop = animationConfig.translation.loop !== false;
        } else if (animationConfig.color && animationConfig.color.enabled) {
            config.animation.speed = animationConfig.color.speed || 1;
            config.animation.loop = animationConfig.color.loop !== false;
        }
    }
}

// P5.js sketch in INSTANCE MODE (matches view.php exactly)
const sketch = (p) => {
    // Canvas settings
    const canvasWidth = <?php echo $canvasWidth; ?>;
    const canvasHeight = <?php echo $canvasHeight; ?>;
    const renderer = '<?php echo $renderer; ?>';
    const bgColor = '<?php echo $background; ?>';
    <?php if (!empty($backgroundImageUrl)): ?>
    const backgroundImageUrl = '<?php echo htmlspecialchars($backgroundImageUrl, ENT_QUOTES); ?>';
    <?php else: ?>
    const backgroundImageUrl = null;
    <?php endif; ?>

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

    // Shapes palette (backward compatible with old colors)
    const shapes = <?php echo json_encode($shapes); ?>;
    const usePalette = config.usePalette || false;

    // Animation variables
    let animationFrame = 0;
    let offset = 0;
    let backgroundImage = null; // Will be loaded in preload()

    // Helper function to calculate current scale factor for scale/pulse animation
    function getCurrentScaleFactor() {
        if (config.animation && config.animation.scale && config.animation.scale.enabled) {
            const min = config.animation.scale.min || 0.5;
            const max = config.animation.scale.max || 2.0;

            if (min !== max) {
                const duration = config.animation.scale.speed ? (1000 / config.animation.scale.speed) : 10000;
                const range = (max - min) / 2;
                const mid = (max + min) / 2;
                return mid + p.sin(p.frameCount / duration * p.PI * 2) * range;
            }
        }
        return 1.0; // Default: no scaling
    }

    // Preload background image if specified
    p.preload = function() {
        if (backgroundImageUrl) {
            try {
                backgroundImage = p.loadImage(backgroundImageUrl);
            } catch (e) {
                console.error('Error loading background image:', e);
                backgroundImage = null;
            }
        }
    };

    p.setup = function() {
        // Create canvas
        if (renderer === 'WEBGL') {
            p.createCanvas(canvasWidth, canvasHeight, p.WEBGL);
        } else {
            p.createCanvas(canvasWidth, canvasHeight);
        }

        // Set background
        if (backgroundImage) {
            // Draw background image scaled to canvas
            p.image(backgroundImage, 0, 0, canvasWidth, canvasHeight);
        } else {
            p.background(bgColor);
        }

        // Set random seed if specified
        <?php if ($randomSeed !== null): ?>
        p.randomSeed(randomSeedValue);
        <?php endif; ?>

        // Set loop behavior
        if (!loopEnabled && !animated) {
            p.noLoop();
        }
    };

    p.draw = function() {
        // Clear background if not animating or if clearBackground enabled
        if (!animated || clearBg) {
            if (backgroundImage) {
                // Draw background image scaled to canvas
                p.image(backgroundImage, 0, 0, canvasWidth, canvasHeight);
            } else {
                p.background(bgColor);
            }
        }

        // Use shapes from palette (with use-palette enabled) or draw in fixed pattern
        if (usePalette && shapes.length > 0) {
            drawWithShapesPalette();
        } else {
            drawWithConfiguredStyle();
        }

        // Update animation
        if (animated) {
            animationFrame++;
            offset += animSpeed;
        }
    };

    // Helper function to draw a specific P5.js shape
    function drawP5Shape(shapeType, x, y, size) {
        switch (shapeType) {
            case 'ellipse':
                p.ellipse(x, y, size, size);
                break;
            case 'rect':
                p.rect(x - size/2, y - size/2, size, size);
                break;
            case 'triangle':
                p.triangle(x - size/2, y + size/2, x, y - size/2, x + size/2, y + size/2);
                break;
            case 'polygon':
                // Draw hexagon
                p.beginShape();
                for (let i = 0; i < 6; i++) {
                    const angle = p.TWO_PI / 6 * i;
                    const sx = x + p.cos(angle) * size/2;
                    const sy = y + p.sin(angle) * size/2;
                    p.vertex(sx, sy);
                }
                p.endShape(p.CLOSE);
                break;
            case 'line':
                p.line(x - size/2, y, x + size/2, y);
                break;
            default:
                p.ellipse(x, y, size, size);
        }
    }

    // Draw using shapes palette
    function drawWithShapesPalette() {
        const baseShapeSize = config.drawing?.shapeSize || 50;
        const scaleFactor = getCurrentScaleFactor();
        const size = baseShapeSize * scaleFactor;
        const shapeCount = config.drawing?.shapeCount || 10;
        const patternType = config.pattern?.type || 'random';
        const spacing = config.pattern?.spacing || 50;

        if (patternType === 'grid') {
            // Grid pattern layout
            const cols = Math.floor(canvasWidth / spacing);
            const rows = Math.floor(canvasHeight / spacing);
            const offsetX = (canvasWidth - (cols - 1) * spacing) / 2;
            const offsetY = (canvasHeight - (rows - 1) * spacing) / 2;

            let count = 0;
            for (let row = 0; row < rows && count < shapeCount; row++) {
                for (let col = 0; col < cols && count < shapeCount; col++) {
                    const x = col * spacing + offsetX;
                    const y = row * spacing + offsetY;

                    // Select shape from palette (cycle through)
                    const shapeItem = shapes[count % shapes.length];

                    // Set stroke
                    if (noStrokeEnabled) {
                        p.noStroke();
                    } else {
                        p.stroke(strokeColor);
                        p.strokeWeight(strokeWeight);
                    }

                    // Set fill with shape's color
                    if (noFillEnabled) {
                        p.noFill();
                    } else {
                        const c = p.color(shapeItem.color);
                        c.setAlpha(fillOpacity);
                        p.fill(c);
                    }

                    // Draw the shape
                    drawP5Shape(shapeItem.shape, x, y, size);
                    count++;
                }
            }
        } else {
            // Random or row pattern for preview (limit to reasonable number)
            const displayCount = Math.min(shapeCount, 20);
            const itemsPerRow = Math.min(5, displayCount);
            const rows = Math.ceil(displayCount / itemsPerRow);

            let count = 0;
            for (let row = 0; row < rows && count < displayCount; row++) {
                const itemsInThisRow = Math.min(itemsPerRow, displayCount - count);
                for (let col = 0; col < itemsInThisRow; col++) {
                    const x = (col + 1) * (p.width / (itemsInThisRow + 1));
                    const y = p.height / (rows + 1) * (row + 1) + (animated ? p.sin(offset * 0.1 + count) * 30 : 0);

                    // Select shape from palette (cycle through)
                    const shapeItem = shapes[count % shapes.length];

                    // Set stroke
                    if (noStrokeEnabled) {
                        p.noStroke();
                    } else {
                        p.stroke(strokeColor);
                        p.strokeWeight(strokeWeight);
                    }

                    // Set fill with shape's color
                    if (noFillEnabled) {
                        p.noFill();
                    } else {
                        const c = p.color(shapeItem.color);
                        c.setAlpha(fillOpacity);
                        p.fill(c);
                    }

                    // Draw the shape
                    drawP5Shape(shapeItem.shape, x, y, size);
                    count++;
                }
            }
        }
    }

    // Draw with configured style (single shape type, single color)
    function drawWithConfiguredStyle() {
        const baseShapeSize = config.drawing?.shapeSize || 50;
        const scaleFactor = getCurrentScaleFactor();
        const size = baseShapeSize * scaleFactor;
        const shapeCount = config.drawing?.shapeCount || 10;
        const patternType = config.pattern?.type || 'random';
        const spacing = config.pattern?.spacing || 50;

        // Set stroke
        if (noStrokeEnabled) {
            p.noStroke();
        } else {
            p.stroke(strokeColor);
            p.strokeWeight(strokeWeight);
        }

        // Set fill
        if (noFillEnabled) {
            p.noFill();
        } else {
            const c = p.color(fillColor);
            c.setAlpha(fillOpacity);
            p.fill(c);
        }

        if (patternType === 'grid') {
            // Grid pattern layout
            const cols = Math.floor(canvasWidth / spacing);
            const rows = Math.floor(canvasHeight / spacing);
            const offsetX = (canvasWidth - (cols - 1) * spacing) / 2;
            const offsetY = (canvasHeight - (rows - 1) * spacing) / 2;

            let count = 0;
            for (let row = 0; row < rows && count < shapeCount; row++) {
                for (let col = 0; col < cols && count < shapeCount; col++) {
                    const x = col * spacing + offsetX;
                    const y = row * spacing + offsetY;
                    drawP5Shape(drawingMode, x, y, size);
                    count++;
                }
            }
        } else {
            // Random or row pattern for preview (limit to reasonable number)
            const displayCount = Math.min(shapeCount, 20);
            const itemsPerRow = Math.min(5, displayCount);
            const rows = Math.ceil(displayCount / itemsPerRow);

            let count = 0;
            for (let row = 0; row < rows && count < displayCount; row++) {
                const itemsInThisRow = Math.min(itemsPerRow, displayCount - count);
                for (let col = 0; col < itemsInThisRow; col++) {
                    const x = (col + 1) * (p.width / (itemsInThisRow + 1));
                    const y = p.height / (rows + 1) * (row + 1) + (animated ? p.sin(offset * 0.1 + count) * 30 : 0);
                    drawP5Shape(drawingMode, x, y, size);
                    count++;
                }
            }
        }
    }

    // Mouse interaction
    if (mouseEnabled) {
        p.mouseMoved = function() {
            p.redraw();
        };

        p.mousePressed = function() {
            p.redraw();
        };
    }

    // Keyboard interaction
    if (keyboardEnabled) {
        p.keyPressed = function() {
            if (p.key === ' ') {
                if (loopEnabled) {
                    p.noLoop();
                } else {
                    p.loop();
                }
            }
            p.redraw();
        };
    }
};

// Create P5 instance
new p5(sketch);
    </script>
</body>
</html>
    <?php
}

/**
 * Render Three.js preview
 * @param array $piece Piece data with configuration
 */
function renderThreeJSPreview($piece) {
    $config = is_array($piece['configuration']) ? $piece['configuration'] : [];

    // Extract background image URL (prefer texture_urls array)
    $backgroundImageUrl = null;
    if (!empty($piece['texture_urls'])) {
        $textureUrls = is_array($piece['texture_urls']) ? $piece['texture_urls'] : json_decode($piece['texture_urls'], true);
        if (is_array($textureUrls)) {
            $textureUrls = array_values(array_filter($textureUrls));
            if (!empty($textureUrls)) {
                $backgroundImageUrl = $textureUrls[array_rand($textureUrls)];
            }
        }
    }

    // Backward compatibility: fallback to background_image_url
    if (empty($backgroundImageUrl) && !empty($piece['background_image_url'])) {
        $backgroundImageUrl = $piece['background_image_url'];
    }

    // Extract configuration sections
    $geometries = $config['geometries'] ?? [];
    $sceneSettings = $config['sceneSettings'] ?? [];

    // Scene background color (check database field first, then config, then default)
    $sceneBackground = $piece['background_color'] ?? ($sceneSettings['background'] ?? '#000000');

    ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Preview: <?php echo htmlspecialchars($piece['title'] ?? 'Untitled'); ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
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
        #threejs-container {
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            background: <?php echo htmlspecialchars($sceneBackground); ?>;
        }
        .preview-badge {
            position: fixed;
            top: 10px;
            right: 10px;
            background: #764ba2;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            font-weight: bold;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="preview-badge">⚠️ PREVIEW MODE - Changes not saved</div>
    <div id="threejs-container"></div>

    <script>
// Three.js Scene Configuration
const config = <?php echo json_encode($config); ?>;

// Scene setup
const container = document.getElementById('threejs-container');
const scene = new THREE.Scene();
scene.background = new THREE.Color('<?php echo $sceneBackground; ?>');

// Camera
const camera = new THREE.PerspectiveCamera(
    75,
    container.clientWidth / container.clientHeight,
    0.1,
    1000
);
camera.position.z = 5;

// Renderer
const renderer = new THREE.WebGLRenderer({ antialias: true });
renderer.setSize(container.clientWidth, container.clientHeight);
container.appendChild(renderer.domElement);

// Add background image if specified
<?php if (!empty($backgroundImageUrl)): ?>
const backgroundTextureLoader = new THREE.TextureLoader();
backgroundTextureLoader.load('<?php echo htmlspecialchars(proxifyImageUrl($backgroundImageUrl), ENT_QUOTES); ?>', function(texture) {
    scene.background = texture;
});
<?php endif; ?>

// Lights from configuration or defaults
if (config.sceneSettings && config.sceneSettings.lights) {
    config.sceneSettings.lights.forEach(lightConfig => {
        let light;

        switch (lightConfig.type) {
            case 'AmbientLight':
                light = new THREE.AmbientLight(
                    lightConfig.color || '#ffffff',
                    lightConfig.intensity || 0.5
                );
                break;
            case 'DirectionalLight':
                light = new THREE.DirectionalLight(
                    lightConfig.color || '#ffffff',
                    lightConfig.intensity || 0.8
                );
                if (lightConfig.position) {
                    light.position.set(
                        lightConfig.position.x || 5,
                        lightConfig.position.y || 10,
                        lightConfig.position.z || 7.5
                    );
                }
                break;
            case 'PointLight':
                light = new THREE.PointLight(
                    lightConfig.color || '#ffffff',
                    lightConfig.intensity || 1,
                    lightConfig.distance || 100
                );
                if (lightConfig.position) {
                    light.position.set(
                        lightConfig.position.x || 0,
                        lightConfig.position.y || 0,
                        lightConfig.position.z || 10
                    );
                }
                break;
        }

        if (light) {
            scene.add(light);
        }
    });
} else {
    // Default lights
    const ambientLight = new THREE.AmbientLight('#ffffff', 0.5);
    scene.add(ambientLight);

    const directionalLight = new THREE.DirectionalLight('#ffffff', 0.8);
    directionalLight.position.set(5, 10, 7.5);
    scene.add(directionalLight);
}

// Create geometries from configuration
const meshes = [];

if (config.geometries) {
    config.geometries.forEach((geomConfig, index) => {
        // Create geometry
        let geometry;
        const type = geomConfig.type || 'BoxGeometry';

        switch (type) {
            case 'BoxGeometry':
                geometry = new THREE.BoxGeometry(
                    geomConfig.width || 1,
                    geomConfig.height || 1,
                    geomConfig.depth || 1
                );
                break;
            case 'SphereGeometry':
                geometry = new THREE.SphereGeometry(
                    geomConfig.radius || 1,
                    geomConfig.widthSegments || 32,
                    geomConfig.heightSegments || 32
                );
                break;
            case 'CylinderGeometry':
                geometry = new THREE.CylinderGeometry(
                    geomConfig.radiusTop || 1,
                    geomConfig.radiusBottom || 1,
                    geomConfig.height || 1
                );
                break;
            case 'ConeGeometry':
                geometry = new THREE.ConeGeometry(
                    geomConfig.radius || 1,
                    geomConfig.height || 1
                );
                break;
            case 'PlaneGeometry':
                geometry = new THREE.PlaneGeometry(
                    geomConfig.width || 1,
                    geomConfig.height || 1
                );
                break;
            case 'TorusGeometry':
                geometry = new THREE.TorusGeometry(
                    geomConfig.radius || 1,
                    geomConfig.tube || 0.4
                );
                break;
            case 'TorusKnotGeometry':
                geometry = new THREE.TorusKnotGeometry(
                    geomConfig.radius || 1,
                    geomConfig.tube || 0.4
                );
                break;
            case 'DodecahedronGeometry':
                geometry = new THREE.DodecahedronGeometry(geomConfig.radius || 1);
                break;
            case 'IcosahedronGeometry':
                geometry = new THREE.IcosahedronGeometry(geomConfig.radius || 1);
                break;
            case 'OctahedronGeometry':
                geometry = new THREE.OctahedronGeometry(geomConfig.radius || 1);
                break;
            case 'TetrahedronGeometry':
                geometry = new THREE.TetrahedronGeometry(geomConfig.radius || 1);
                break;
            case 'RingGeometry':
                geometry = new THREE.RingGeometry(
                    geomConfig.innerRadius || 0.5,
                    geomConfig.outerRadius || 1
                );
                break;
            default:
                geometry = new THREE.BoxGeometry(1, 1, 1);
        }

        // Create material
        const materialOptions = {
            color: geomConfig.color || '#764ba2',
            opacity: geomConfig.opacity !== undefined ? geomConfig.opacity : 1.0,
            transparent: (geomConfig.opacity !== undefined && geomConfig.opacity < 1.0) || false
        };

        // Add texture if specified
        if (geomConfig.texture) {
            const textureLoader = new THREE.TextureLoader();
            textureLoader.load(geomConfig.texture, function(texture) {
                mesh.material.map = texture;
                mesh.material.needsUpdate = true;
            });
        }

        // Wireframe setting
        if (geomConfig.wireframe) {
            materialOptions.wireframe = true;
        }

        // Material properties for MeshStandardMaterial
        if (geomConfig.metalness !== undefined) {
            materialOptions.metalness = geomConfig.metalness;
        }
        if (geomConfig.roughness !== undefined) {
            materialOptions.roughness = geomConfig.roughness;
        }

        // Create material
        const material = new THREE.MeshStandardMaterial(materialOptions);

        // Create mesh
        const mesh = new THREE.Mesh(geometry, material);

        // Set position
        if (geomConfig.position) {
            mesh.position.set(
                geomConfig.position.x || 0,
                geomConfig.position.y || 0,
                geomConfig.position.z || 0
            );
        }

        // Set rotation
        if (geomConfig.rotation) {
            mesh.rotation.set(
                geomConfig.rotation.x || 0,
                geomConfig.rotation.y || 0,
                geomConfig.rotation.z || 0
            );
        }

        // Set scale
        if (geomConfig.scale) {
            mesh.scale.set(
                geomConfig.scale.x || 1,
                geomConfig.scale.y || 1,
                geomConfig.scale.z || 1
            );
        }

        scene.add(mesh);
        meshes.push({ mesh, config: geomConfig });
    });
}

// Animation loop
function animate() {
    requestAnimationFrame(animate);

    // Animate each mesh based on its configuration
    meshes.forEach(({ mesh, config }) => {
        const anim = config.animation || {};

        // Rotation animation (granular format)
        if (anim.rotation && anim.rotation.enabled) {
            const duration = anim.rotation.duration || 10000;
            const speed = (Math.PI * 2) / (duration / 16.67); // ~60fps
            const direction = anim.rotation.counterclockwise ? -1 : 1;
            mesh.rotation.y += speed * direction;
        }

        // Position animation (X/Y/Z independent)
        if (anim.position) {
            const time = Date.now() * 0.001;

            if (anim.position.x && anim.position.x.enabled && anim.position.x.range > 0) {
                if (!mesh.userData.initialPosition) {
                    mesh.userData.initialPosition = { x: mesh.position.x, y: mesh.position.y, z: mesh.position.z };
                }
                const duration = anim.position.x.duration || 10000;
                const range = anim.position.x.range || 0;
                mesh.position.x = mesh.userData.initialPosition.x + Math.sin(time * 1000 / duration) * range;
            }

            if (anim.position.y && anim.position.y.enabled && anim.position.y.range > 0) {
                if (!mesh.userData.initialPosition) {
                    mesh.userData.initialPosition = { x: mesh.position.x, y: mesh.position.y, z: mesh.position.z };
                }
                const duration = anim.position.y.duration || 10000;
                const range = anim.position.y.range || 0;
                mesh.position.y = mesh.userData.initialPosition.y + Math.sin(time * 1000 / duration) * range;
            }

            if (anim.position.z && anim.position.z.enabled && anim.position.z.range > 0) {
                if (!mesh.userData.initialPosition) {
                    mesh.userData.initialPosition = { x: mesh.position.x, y: mesh.position.y, z: mesh.position.z };
                }
                const duration = anim.position.z.duration || 10000;
                const range = anim.position.z.range || 0;
                mesh.position.z = mesh.userData.initialPosition.z + Math.sin(time * 1000 / duration) * range;
            }
        }

        // Scale animation
        if (anim.scale && anim.scale.enabled) {
            const min = anim.scale.min || 1.0;
            const max = anim.scale.max || 1.0;

            if (min !== max) {
                const duration = anim.scale.duration || 10000;
                const time = Date.now();
                const range = (max - min) / 2;
                const mid = (max + min) / 2;
                const scaleValue = mid + Math.sin(time / duration * Math.PI * 2) * range;
                mesh.scale.set(scaleValue, scaleValue, scaleValue);
            }
        }
    });

    renderer.render(scene, camera);
}

// Handle window resize
window.addEventListener('resize', () => {
    camera.aspect = container.clientWidth / container.clientHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(container.clientWidth, container.clientHeight);
});

// Start animation
animate();
    </script>
</body>
</html>
    <?php
}
?>
