<?php
/**
 * C2.js Dynamic Piece Viewer
 * Renders C2.js art pieces from database configuration
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
        "SELECT * FROM c2_art WHERE slug = ? AND deleted_at IS NULL",
        [$slug]
    );

    if (!$piece) {
        http_response_code(404);
        die('Art piece not found.');
    }

    // Load configuration
    $config = !empty($piece['configuration']) ? json_decode($piece['configuration'], true) : null;

    // Extract configuration sections
    $canvasConfig = $config['canvas'] ?? [];
    $patternConfig = $config['pattern'] ?? [];

    // Backward compatibility: Support both new shapes format and old colors format
    $shapes = [];
    if (!empty($config['shapes'])) {
        // New format: array of {shape, color} objects
        $shapes = $config['shapes'];
    } elseif (!empty($config['colors'])) {
        // Old format: array of color strings - migrate to shapes
        $shapes = array_map(function($color) {
            return ['shape' => 'circle', 'color' => $color];
        }, $config['colors']);
    } else {
        // Default fallback
        $shapes = [['shape' => 'circle', 'color' => '#FF6B6B']];
    }

    $parameters = $config['parameters'] ?? [];
    $animation = $config['animation'] ?? [];
    $interaction = $config['interaction'] ?? [];
    $advanced = $config['advanced'] ?? [];

    // Set page metadata
    $page_name = htmlspecialchars($piece['title']);
    $tagline = htmlspecialchars($piece['description'] ?? 'C2.js Generative Art Piece');

} catch (Exception $e) {
    error_log('Error loading C2.js piece: ' . $e->getMessage());
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
        #c2-canvas {
            display: block;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <canvas id="c2-canvas"
            width="<?php echo $canvasConfig['width'] ?? 800; ?>"
            height="<?php echo $canvasConfig['height'] ?? 600; ?>">
    </canvas>

<script src="<?php echo url('js/c2.min.js'); ?>"></script>
<script>
// C2.js Pattern Configuration
const config = <?php echo json_encode($config); ?>;

// Initialize canvas
const canvas = document.getElementById('c2-canvas');
const ctx = canvas.getContext('2d');
const width = canvas.width;
const height = canvas.height;

// Apply background
ctx.fillStyle = config.canvas.background || '#FFFFFF';
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
                const px = x + size * Math.cos(angle);
                const py = y + size * Math.sin(angle);
                if (i === 0) ctx.moveTo(px, py);
                else ctx.lineTo(px, py);
            }
            ctx.closePath();
            ctx.fill();
            break;
        case 'star':
            const spikes = 5;
            const outerRadius = size;
            const innerRadius = size * 0.5;
            let rot = Math.PI / 2 * 3;
            let step = Math.PI / spikes;

            ctx.moveTo(x, y - outerRadius);
            for (let i = 0; i < spikes; i++) {
                ctx.lineTo(x + Math.cos(rot) * outerRadius, y + Math.sin(rot) * outerRadius);
                rot += step;
                ctx.lineTo(x + Math.cos(rot) * innerRadius, y + Math.sin(rot) * innerRadius);
                rot += step;
            }
            ctx.lineTo(x, y - outerRadius);
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
    const pattern = config.pattern.type;
    const elementCount = config.pattern.elementCount || 100;
    const elementSize = config.parameters.elementSize || 5;
    const sizeVariation = config.parameters.sizeVariation / 100 || 0.2;
    const spacing = config.parameters.spacing || 20;
    const opacity = config.parameters.opacity / 100 || 0.8;

    // Backward compatibility: Support both new shapes format and old colors format
    const shapes = config.shapes || (config.colors ? config.colors.map(c => ({ shape: 'circle', color: c })) : [{ shape: 'circle', color: '#FF6B6B' }]);

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
            const shapeData = shapes[Math.floor(random() * shapes.length)];
            drawShape(x, y, s, shapeData.shape, shapeData.color);
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

        const shapeData = shapes[Math.floor(random() * shapes.length)];
        drawShape(x, y, s, shapeData.shape, shapeData.color);
    }
}

function drawScatterPattern(count, size, shapes, variation) {
    for (let i = 0; i < count; i++) {
        const x = random() * width;
        const y = random() * height;
        const s = size * (1 + (random() - 0.5) * variation);

        const shapeData = shapes[Math.floor(random() * shapes.length)];
        drawShape(x, y, s, shapeData.shape, shapeData.color);
    }
}

function drawWavePattern(count, size, spacing, shapes, variation) {
    const rows = Math.ceil(height / spacing);
    const amplitude = 50;

    for (let i = 0; i < rows; i++) {
        for (let j = 0; j < count; j++) {
            const t = j / count;
            const x = t * width;
            const y = i * spacing + Math.sin(t * Math.PI * 4) * amplitude;
            const s = size * (1 + (random() - 0.5) * variation);

            const shapeData = shapes[Math.floor(random() * shapes.length)];
            drawShape(x, y, s, shapeData.shape, shapeData.color);
        }
    }
}

function drawConcentricPattern(count, size, shapes, variation) {
    const centerX = width / 2;
    const centerY = height / 2;
    const maxRadius = Math.min(width, height) / 2;

    for (let i = 0; i < count; i++) {
        const radius = (i / count) * maxRadius;
        const circumference = 2 * Math.PI * radius;
        const points = Math.max(8, Math.floor(circumference / 20));

        for (let j = 0; j < points; j++) {
            const angle = (j / points) * Math.PI * 2;
            const x = centerX + Math.cos(angle) * radius;
            const y = centerY + Math.sin(angle) * radius;
            const s = size * (1 + (random() - 0.5) * variation);

            const shapeData = shapes[Math.floor(random() * shapes.length)];
            drawShape(x, y, s, shapeData.shape, shapeData.color);
        }
    }
}

function drawFractalPattern(count, size, shapes, variation) {
    function drawFractalBranch(x, y, angle, depth, length) {
        if (depth === 0) return;

        const x2 = x + Math.cos(angle) * length;
        const y2 = y + Math.sin(angle) * length;

        const shapeData = shapes[Math.floor(random() * shapes.length)];
        ctx.strokeStyle = shapeData.color;
        ctx.lineWidth = size * depth / count;
        ctx.beginPath();
        ctx.moveTo(x, y);
        ctx.lineTo(x2, y2);
        ctx.stroke();

        drawFractalBranch(x2, y2, angle - 0.5, depth - 1, length * 0.7);
        drawFractalBranch(x2, y2, angle + 0.5, depth - 1, length * 0.7);
    }

    const depth = Math.min(8, Math.floor(count / 10));
    drawFractalBranch(width / 2, height, -Math.PI / 2, depth, 100);
}

function drawParticlePattern(count, size, shapes, variation) {
    for (let i = 0; i < count; i++) {
        const x = random() * width;
        const y = random() * height;
        const s = size * (1 + (random() - 0.5) * variation);
        const opacity = random() * 0.5 + 0.5;

        ctx.globalAlpha = opacity;
        const shapeData = shapes[Math.floor(random() * shapes.length)];
        drawShape(x, y, s, shapeData.shape, shapeData.color);
    }
}

function drawFlowPattern(count, size, shapes, variation) {
    const noiseScale = config.pattern.noiseScale || 0.01;

    for (let i = 0; i < count; i++) {
        const x = random() * width;
        const y = random() * height;
        const angle = (random() - 0.5) * Math.PI * 2;
        const length = size * 5;
        const s = size * (1 + (random() - 0.5) * variation);

        const shapeData = shapes[Math.floor(random() * shapes.length)];
        ctx.strokeStyle = shapeData.color;
        ctx.lineWidth = s;
        ctx.beginPath();
        ctx.moveTo(x, y);
        ctx.lineTo(x + Math.cos(angle) * length, y + Math.sin(angle) * length);
        ctx.stroke();
    }
}

// Initial draw
drawPattern();

// Animation if enabled
if (config.animation && config.animation.enabled) {
    let frame = 0;
    const speed = config.animation.speed || 1;

    function animate() {
        if (!config.animation.loop && frame > 60 * 10) return; // Stop after 10 seconds if not looping

        // Clear with trails effect if enabled
        if (config.advanced?.enableTrails) {
            ctx.fillStyle = config.canvas.background + '20'; // Semi-transparent
            ctx.fillRect(0, 0, width, height);
        } else if (config.animation.clearBackground !== false) {
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

// Mouse interaction if enabled
if (config.interaction && config.interaction.enabled) {
    // Backward compatibility: Extract shapes for interaction
    const interactionShapes = config.shapes || (config.colors ? config.colors.map(c => ({ shape: 'circle', color: c })) : [{ shape: 'circle', color: '#FF6B6B' }]);

    canvas.addEventListener('mousemove', function(e) {
        const rect = canvas.getBoundingClientRect();
        const mouseX = e.clientX - rect.left;
        const mouseY = e.clientY - rect.top;
        const radius = config.interaction.radius || 100;

        // Simple interaction - draw circle at mouse position
        ctx.fillStyle = interactionShapes[0].color;
        ctx.globalAlpha = 0.3;
        ctx.beginPath();
        ctx.arc(mouseX, mouseY, radius / 4, 0, Math.PI * 2);
        ctx.fill();
    });
}
</script>
</body>
</html>
