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
    $colors = $config['colors'] ?? ['#FF6B6B', '#4ECDC4', '#45B7D1'];
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

// Include head (DOCTYPE, HTML, libraries)
require_once(__DIR__ . '/../resources/templates/head.php');
?>
<body>
<?php require_once(__DIR__ . '/../resources/templates/header.php'); ?>

<style>
    #c2-canvas {
        display: block;
        margin: 20px auto;
        border: 1px solid #ddd;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
</style>

<div style="text-align: center; padding: 20px;">
    <canvas id="c2-canvas"
            width="<?php echo $canvasConfig['width'] ?? 800; ?>"
            height="<?php echo $canvasConfig['height'] ?? 600; ?>">
    </canvas>
</div>

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

// Draw pattern based on configuration
function drawPattern() {
    const pattern = config.pattern.type;
    const elementCount = config.pattern.elementCount || 100;
    const elementSize = config.parameters.elementSize || 5;
    const sizeVariation = config.parameters.sizeVariation / 100 || 0.2;
    const spacing = config.parameters.spacing || 20;
    const opacity = config.parameters.opacity / 100 || 0.8;
    const colors = config.colors || ['#FF6B6B'];

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
            const x = t * width;
            const y = i * spacing + Math.sin(t * Math.PI * 4) * amplitude;
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
        const circumference = 2 * Math.PI * radius;
        const points = Math.max(8, Math.floor(circumference / 20));

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
    function drawFractalBranch(x, y, angle, depth, length) {
        if (depth === 0) return;

        const x2 = x + Math.cos(angle) * length;
        const y2 = y + Math.sin(angle) * length;

        ctx.strokeStyle = colors[Math.floor(random() * colors.length)];
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

function drawParticlePattern(count, size, colors, variation) {
    for (let i = 0; i < count; i++) {
        const x = random() * width;
        const y = random() * height;
        const s = size * (1 + (random() - 0.5) * variation);
        const opacity = random() * 0.5 + 0.5;

        ctx.globalAlpha = opacity;
        ctx.fillStyle = colors[Math.floor(random() * colors.length)];
        ctx.beginPath();
        ctx.arc(x, y, s, 0, Math.PI * 2);
        ctx.fill();
    }
}

function drawFlowPattern(count, size, colors, variation) {
    const noiseScale = config.pattern.noiseScale || 0.01;

    for (let i = 0; i < count; i++) {
        const x = random() * width;
        const y = random() * height;
        const angle = (random() - 0.5) * Math.PI * 2;
        const length = size * 5;
        const s = size * (1 + (random() - 0.5) * variation);

        ctx.strokeStyle = colors[Math.floor(random() * colors.length)];
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
    canvas.addEventListener('mousemove', function(e) {
        const rect = canvas.getBoundingClientRect();
        const mouseX = e.clientX - rect.left;
        const mouseY = e.clientY - rect.top;
        const radius = config.interaction.radius || 100;

        // Simple interaction - draw circle at mouse position
        ctx.fillStyle = config.colors[0] || '#FF6B6B';
        ctx.globalAlpha = 0.3;
        ctx.beginPath();
        ctx.arc(mouseX, mouseY, radius / 4, 0, Math.PI * 2);
        ctx.fill();
    });
}
</script>

<?php require_once(__DIR__ . '/../resources/templates/footer.php'); ?>
</body>
</html>
