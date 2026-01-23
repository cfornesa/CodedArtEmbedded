<?php
/**
 * P5.js Dynamic Piece Viewer
 * Renders P5.js art pieces from database configuration
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
        "SELECT * FROM p5_art WHERE slug = ? AND deleted_at IS NULL",
        [$slug]
    );

    if (!$piece) {
        http_response_code(404);
        die('Art piece not found.');
    }

    // Load configuration
    $config = !empty($piece['configuration']) ? json_decode($piece['configuration'], true) : null;

    // Set page metadata
    $page_name = htmlspecialchars($piece['title']);
    $tagline = htmlspecialchars($piece['description'] ?? 'P5.js Processing Art Piece');

} catch (Exception $e) {
    error_log('Error loading P5.js piece: ' . $e->getMessage());
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
        #p5-container {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            height: 100%;
        }
    </style>
</head>
<body>
<div id="p5-container"></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/p5.js/1.7.0/p5.min.js"></script>
<script>
// P5.js Sketch Configuration
const config = <?php echo json_encode($config); ?>;

// Background image URL (from database)
<?php
$backgroundImageUrl = $piece['background_image_url'] ?? null;
// Backward compatibility: fallback to first image from old image_urls array
if (empty($backgroundImageUrl) && !empty($piece['image_urls'])) {
    $imageUrls = is_array($piece['image_urls']) ? $piece['image_urls'] : json_decode($piece['image_urls'], true);
    if (is_array($imageUrls) && !empty($imageUrls)) {
        $backgroundImageUrl = $imageUrls[0];
    }
}
if (!empty($backgroundImageUrl)):
?>
const backgroundImageUrl = '<?php echo htmlspecialchars($backgroundImageUrl, ENT_QUOTES); ?>';
<?php else: ?>
const backgroundImageUrl = null;
<?php endif; ?>

// P5.js sketch function
const sketch = (p) => {
    let backgroundImage = null; // Will be loaded in preload()

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

    // Extract configuration
    const canvasConfig = config.canvas || {};
    const drawingConfig = config.drawing || {};

    // Backward compatibility: Support both new shapes format and old colors format
    const shapes = config.shapes || (config.colors ? config.colors.map(c => ({ shape: 'ellipse', color: c })) : [{ shape: 'ellipse', color: '#ED225D' }]);
    const usePalette = config.usePalette || false;
    const patternConfig = config.pattern || {};
    const animationConfig = config.animation || {};
    const interactionConfig = config.interaction || {};
    const advancedConfig = config.advanced || {};

    // Backward compatibility for animation format
    // Old format: animationConfig.animated (single toggle)
    // New format: animationConfig.rotation.enabled, scale.enabled, translation.enabled, color.enabled (granular)
    const isAnimated = animationConfig.animated || // Old format
        (animationConfig.rotation && animationConfig.rotation.enabled) || // New format: rotation
        (animationConfig.scale && animationConfig.scale.enabled) || // New format: scale/pulse
        (animationConfig.translation && animationConfig.translation.enabled) || // New format: translation/move
        (animationConfig.color && animationConfig.color.enabled); // New format: color

    // Override animationConfig.animated with computed value for backward compatibility
    animationConfig.animated = isAnimated;

    // If using new format, extract speed and loop from first enabled animation
    if (isAnimated && !config.animation.speed) {
        if (animationConfig.rotation?.enabled) {
            animationConfig.speed = animationConfig.rotation.speed || 1;
            animationConfig.loop = animationConfig.rotation.loop !== false;
        } else if (animationConfig.scale?.enabled) {
            animationConfig.speed = animationConfig.scale.speed || 1;
            animationConfig.loop = animationConfig.scale.loop !== false;
        } else if (animationConfig.translation?.enabled) {
            animationConfig.speed = animationConfig.translation.speed || 1;
            animationConfig.loop = animationConfig.translation.loop !== false;
        } else if (animationConfig.color?.enabled) {
            animationConfig.speed = animationConfig.color.speed || 1;
            animationConfig.loop = animationConfig.color.loop !== false;
        }
    }

    // Variables for animation
    let time = 0;
    let elements = [];

    // Helper function to calculate current scale factor for scale/pulse animation
    function getCurrentScaleFactor() {
        if (animationConfig.scale && animationConfig.scale.enabled) {
            const min = animationConfig.scale.min || 0.5;
            const max = animationConfig.scale.max || 2.0;

            if (min !== max) {
                const duration = animationConfig.scale.speed ? (1000 / animationConfig.scale.speed) : 10000;
                const range = (max - min) / 2;
                const mid = (max + min) / 2;
                return mid + Math.sin(p.frameCount / duration * Math.PI * 2) * range;
            }
        }
        return 1.0; // Default: no scaling
    }

    // Setup function
    p.setup = function() {
        // Create canvas
        const width = canvasConfig.width || 800;
        const height = canvasConfig.height || 600;
        const renderer = canvasConfig.renderer === 'WEBGL' ? p.WEBGL : p.P2D;

        const canvas = p.createCanvas(width, height, renderer);
        canvas.parent('p5-container');

        // Set color mode
        if (canvasConfig.colorMode === 'HSB') {
            p.colorMode(p.HSB, 360, 100, 100, 100);
        } else {
            p.colorMode(p.RGB, 255, 255, 255, 100);
        }

        // Set frame rate
        p.frameRate(canvasConfig.frameRate || 60);

        // Set angle mode
        if (advancedConfig.angleMode === 'DEGREES') {
            p.angleMode(p.DEGREES);
        } else {
            p.angleMode(p.RADIANS);
        }

        // Set rect and ellipse modes
        if (advancedConfig.rectMode) {
            p.rectMode(p[advancedConfig.rectMode]);
        }
        if (advancedConfig.ellipseMode) {
            p.ellipseMode(p[advancedConfig.ellipseMode]);
        }

        // Set random seed
        if (patternConfig.randomSeed) {
            p.randomSeed(patternConfig.randomSeed);
            p.noiseSeed(patternConfig.randomSeed);
        }

        // Noise detail
        if (patternConfig.noiseDetail) {
            p.noiseDetail(patternConfig.noiseDetail, 0.5);
        }

        // Initialize elements for pattern
        initializePattern();

        // Background
        if (backgroundImage) {
            // Draw background image scaled to canvas
            p.image(backgroundImage, 0, 0, canvasConfig.width || 800, canvasConfig.height || 600);
        } else {
            p.background(canvasConfig.background || '#FFFFFF');
        }
    };

    // Draw function
    p.draw = function() {
        // Clear background if configured
        if (!animationConfig.animated || animationConfig.clearBackground) {
            if (backgroundImage) {
                // Draw background image scaled to canvas
                p.image(backgroundImage, 0, 0, canvasConfig.width || 800, canvasConfig.height || 600);
            } else {
                p.background(canvasConfig.background || '#FFFFFF');
            }
        }

        // Set blend mode
        if (advancedConfig.blendMode) {
            p.blendMode(p[advancedConfig.blendMode]);
        }

        // Set stroke
        if (drawingConfig.noStroke) {
            p.noStroke();
        } else {
            p.stroke(drawingConfig.strokeColor || '#000000');
            p.strokeWeight(drawingConfig.strokeWeight || 1);
        }

        // Set fill
        if (drawingConfig.noFill) {
            p.noFill();
        } else {
            const fillColor = p.color(drawingConfig.fillColor || '#ED225D');
            fillColor.setAlpha(drawingConfig.fillOpacity || 255);
            p.fill(fillColor);
        }

        // Draw pattern
        drawPattern();

        // Update time
        if (animationConfig.animated) {
            time += animationConfig.speed || 1;

            // Stop if not looping
            if (!animationConfig.loop && p.frameCount > 60 * 10) {
                p.noLoop();
            }
        } else {
            p.noLoop();
        }
    };

    function initializePattern() {
        const shapeCount = drawingConfig.shapeCount || 100;
        elements = [];

        for (let i = 0; i < shapeCount; i++) {
            const shapeData = usePalette ? p.random(shapes) : shapes[0];
            elements.push({
                x: p.random(p.width),
                y: p.random(p.height),
                vx: p.random(-2, 2),
                vy: p.random(-2, 2),
                size: drawingConfig.shapeSize || 20,
                shapeType: shapeData.shape,
                color: shapeData.color,
                offset: p.random(1000)
            });
        }
    }

    // Helper function to draw P5.js shapes
    function drawP5Shape(shapeType, x, y, size) {
        switch (shapeType) {
            case 'ellipse':
                p.ellipse(x, y, size, size);
                break;
            case 'rect':
                p.rect(x - size/2, y - size/2, size, size);
                break;
            case 'triangle':
                p.triangle(x, y - size/2, x - size/2, y + size/2, x + size/2, y + size/2);
                break;
            case 'polygon':
                const sides = 6;
                p.beginShape();
                for (let i = 0; i < sides; i++) {
                    const angle = p.TWO_PI / sides * i;
                    const px = x + p.cos(angle) * size/2;
                    const py = y + p.sin(angle) * size/2;
                    p.vertex(px, py);
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

    function drawPattern() {
        const patternType = patternConfig.type || 'grid';
        const shapeType = drawingConfig.shapeType || 'ellipse';
        const spacing = patternConfig.spacing || 30;

        switch (patternType) {
            case 'grid':
                drawGridPattern(shapeType, spacing);
                break;
            case 'random':
                drawRandomPattern(shapeType);
                break;
            case 'noise':
                drawNoisePattern(shapeType);
                break;
            case 'spiral':
                drawSpiralPattern(shapeType);
                break;
            case 'radial':
                drawRadialPattern(shapeType);
                break;
            case 'flow':
                drawFlowPattern(shapeType);
                break;
            default:
                drawRandomPattern(shapeType);
        }
    }

    function drawGridPattern(shapeType, spacing) {
        const cols = Math.floor(p.width / spacing);
        const rows = Math.floor(p.height / spacing);
        const scaleFactor = getCurrentScaleFactor();

        for (let i = 0; i < rows; i++) {
            for (let j = 0; j < cols; j++) {
                const x = j * spacing + spacing / 2;
                const y = i * spacing + spacing / 2;
                const baseSize = drawingConfig.shapeSize || 20;
                const size = baseSize * scaleFactor;

                if (usePalette) {
                    const shapeData = shapes[(i * cols + j) % shapes.length];
                    p.fill(shapeData.color);
                    drawP5Shape(shapeData.shape, x, y, size);
                } else {
                    drawP5Shape(shapeType, x, y, size);
                }
            }
        }
    }

    function drawRandomPattern(shapeType) {
        const scaleFactor = getCurrentScaleFactor();

        elements.forEach((el, i) => {
            if (usePalette) {
                p.fill(el.color);
            }

            let x = el.x;
            let y = el.y;

            if (animationConfig.animated) {
                x += el.vx;
                y += el.vy;

                // Wrap around edges
                if (x < 0) x = p.width;
                if (x > p.width) x = 0;
                if (y < 0) y = p.height;
                if (y > p.height) y = 0;

                el.x = x;
                el.y = y;
            }

            const elShape = usePalette ? el.shapeType : shapeType;
            const size = el.size * scaleFactor;
            drawP5Shape(elShape, x, y, size);
        });
    }

    function drawNoisePattern(shapeType) {
        const scale = patternConfig.noiseScale || 0.01;
        const scaleFactor = getCurrentScaleFactor();

        elements.forEach((el, i) => {
            const noiseVal = p.noise(el.x * scale, el.y * scale, time * 0.01);
            const size = el.size * (0.5 + noiseVal) * scaleFactor;

            if (usePalette) {
                const colorIndex = Math.floor(noiseVal * shapes.length);
                const shapeData = shapes[colorIndex % shapes.length];
                p.fill(shapeData.color);
                drawP5Shape(shapeData.shape, el.x, el.y, size);
            } else {
                drawP5Shape(shapeType, el.x, el.y, size);
            }
        });
    }

    function drawSpiralPattern(shapeType) {
        const centerX = p.width / 2;
        const centerY = p.height / 2;
        const count = drawingConfig.shapeCount || 100;
        const maxRadius = Math.min(p.width, p.height) / 2;
        const scaleFactor = getCurrentScaleFactor();

        for (let i = 0; i < count; i++) {
            const t = i / count;
            const angle = t * p.TWO_PI * 8 + time * 0.01;
            const radius = t * maxRadius;
            const x = centerX + p.cos(angle) * radius;
            const y = centerY + p.sin(angle) * radius;
            const baseSize = drawingConfig.shapeSize || 10;
            const size = baseSize * scaleFactor;

            if (usePalette) {
                const shapeData = shapes[i % shapes.length];
                p.fill(shapeData.color);
                drawP5Shape(shapeData.shape, x, y, size);
            } else {
                drawP5Shape(shapeType, x, y, size);
            }
        }
    }

    function drawRadialPattern(shapeType) {
        const centerX = p.width / 2;
        const centerY = p.height / 2;
        const count = drawingConfig.shapeCount || 100;
        const maxRadius = Math.min(p.width, p.height) / 2;
        const scaleFactor = getCurrentScaleFactor();

        for (let i = 0; i < count; i++) {
            const angle = (i / count) * p.TWO_PI + time * 0.01;
            const radius = maxRadius * 0.8;
            const x = centerX + p.cos(angle) * radius;
            const y = centerY + p.sin(angle) * radius;
            const baseSize = drawingConfig.shapeSize || 10;
            const size = baseSize * scaleFactor;

            if (usePalette) {
                const shapeData = shapes[i % shapes.length];
                p.fill(shapeData.color);
                drawP5Shape(shapeData.shape, x, y, size);
            } else {
                drawP5Shape(shapeType, x, y, size);
            }
        }
    }

    function drawFlowPattern(shapeType) {
        const scale = patternConfig.noiseScale || 0.01;
        const scaleFactor = getCurrentScaleFactor();

        elements.forEach((el) => {
            const angle = p.noise(el.x * scale, el.y * scale, time * 0.01) * p.TWO_PI * 2;

            if (animationConfig.animated) {
                el.x += p.cos(angle) * 2;
                el.y += p.sin(angle) * 2;

                // Wrap
                if (el.x < 0) el.x = p.width;
                if (el.x > p.width) el.x = 0;
                if (el.y < 0) el.y = p.height;
                if (el.y > p.height) el.y = 0;
            }

            if (usePalette) {
                p.fill(el.color);
            }

            const elShape = usePalette ? el.shapeType : shapeType;
            const size = el.size * scaleFactor;
            drawP5Shape(elShape, el.x, el.y, size);
        });
    }


    // Mouse interaction
    if (interactionConfig.mouse) {
        p.mouseMoved = function() {
            // Simple mouse interaction
            const interactionRadius = interactionConfig.radius || 100;

            elements.forEach(el => {
                const d = p.dist(p.mouseX, p.mouseY, el.x, el.y);

                if (d < interactionRadius) {
                    switch (interactionConfig.mouseType) {
                        case 'repel':
                            const angle = p.atan2(el.y - p.mouseY, el.x - p.mouseX);
                            el.x += p.cos(angle) * 2;
                            el.y += p.sin(angle) * 2;
                            break;
                        case 'attract':
                            const attractAngle = p.atan2(p.mouseY - el.y, p.mouseX - el.x);
                            el.x += p.cos(attractAngle) * 0.5;
                            el.y += p.sin(attractAngle) * 0.5;
                            break;
                    }
                }
            });
        };
    }
};

// Create P5 instance
new p5(sketch);
</script>
</body>
</html>
