<?php
/**
 * P5.js Dynamic Piece Viewer
 * Simple iframe-embeddable viewer
 */

require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../config/helpers.php');

// Get slug from query parameter
$slug = $_GET['slug'] ?? null;

if (!$slug) {
    http_response_code(404);
    die('Art piece not found.');
}

// Query database for the piece
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
    $config = !empty($piece['configuration']) ? json_decode($piece['configuration'], true) : [];

} catch (Exception $e) {
    error_log('Error loading P5.js piece: ' . $e->getMessage());
    http_response_code(500);
    die('Error loading art piece.');
}

// Get background image URL if specified
$backgroundImageUrl = $piece['background_image_url'] ?? null;
// Backward compatibility: fallback to first image from old image_urls array
if (empty($backgroundImageUrl) && !empty($piece['image_urls'])) {
    $imageUrls = is_array($piece['image_urls']) ? $piece['image_urls'] : json_decode($piece['image_urls'], true);
    if (is_array($imageUrls) && !empty($imageUrls)) {
        $backgroundImageUrl = $imageUrls[0];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($piece['title']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { width: 100%; height: 100%; overflow: hidden; }
    </style>
</head>
<body>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/p5.js/1.7.0/p5.min.js"></script>
    <script>
        // Configuration from database
        const config = <?php echo json_encode($config); ?>;

        // P5.js sketch in instance mode
        const sketch = (p) => {
            let backgroundImage = null;
            let elements = [];
            let animationFrame = 0;

            // Preload background image
            p.preload = function() {
                <?php if (!empty($backgroundImageUrl)): ?>
                try {
                    backgroundImage = p.loadImage('<?php echo htmlspecialchars($backgroundImageUrl, ENT_QUOTES); ?>');
                } catch (e) {
                    console.error('Error loading background image:', e);
                }
                <?php endif; ?>
            };

            // Setup
            p.setup = function() {
                const canvasConfig = config.canvas || {};
                const width = canvasConfig.width || 800;
                const height = canvasConfig.height || 600;
                const renderer = canvasConfig.renderer === 'WEBGL' ? p.WEBGL : p.P2D;

                p.createCanvas(width, height, renderer);

                // Background
                const bgColor = canvasConfig.background || '#ffffff';
                p.background(bgColor);

                // Draw background image if loaded
                if (backgroundImage) {
                    p.image(backgroundImage, 0, 0, width, height);
                }

                // Initialize pattern elements
                const drawingConfig = config.drawing || {};
                const patternConfig = config.pattern || {};
                const shapeCount = drawingConfig.shapeCount || 10;
                const shapeSize = drawingConfig.shapeSize || 50;

                // Get shapes/colors (backward compatibility)
                const shapes = config.shapes || (config.colors ? config.colors.map(c => ({ shape: 'ellipse', color: c })) : [{ shape: 'ellipse', color: '#ED225D' }]);

                // Create pattern elements
                for (let i = 0; i < shapeCount; i++) {
                    const shape = shapes[i % shapes.length];
                    elements.push({
                        x: p.random(width),
                        y: p.random(height),
                        size: shapeSize + p.random(-shapeSize * 0.3, shapeSize * 0.3),
                        color: shape.color || '#ED225D',
                        shapeType: shape.shape || 'ellipse',
                        rotation: p.random(p.TWO_PI),
                        vx: p.random(-2, 2),
                        vy: p.random(-2, 2)
                    });
                }
            };

            // Draw loop
            p.draw = function() {
                const canvasConfig = config.canvas || {};
                const animationConfig = config.animation || {};
                const drawingConfig = config.drawing || {};

                // Check if animated (backward compatibility)
                const animated = animationConfig.animated ||
                    animationConfig.rotation?.enabled ||
                    animationConfig.scale?.enabled ||
                    animationConfig.translation?.enabled ||
                    animationConfig.color?.enabled;

                // Clear background if not animated or if clearBackground is true
                if (!animated || animationConfig.clearBackground) {
                    const bgColor = canvasConfig.background || '#ffffff';
                    p.background(bgColor);

                    // Redraw background image if available
                    if (backgroundImage) {
                        p.image(backgroundImage, 0, 0, canvasConfig.width || 800, canvasConfig.height || 600);
                    }
                }

                // Draw elements
                elements.forEach((el, idx) => {
                    p.push();

                    // Set color with opacity
                    const fillOpacity = drawingConfig.fillOpacity !== undefined ? drawingConfig.fillOpacity : 255;
                    const c = p.color(el.color);
                    c.setAlpha(fillOpacity);
                    p.fill(c);

                    if (drawingConfig.useStroke) {
                        const strokeWeight = drawingConfig.strokeWeight || 1;
                        p.strokeWeight(strokeWeight);
                        p.stroke(el.color);
                    } else {
                        p.noStroke();
                    }

                    // Apply animation
                    if (animated) {
                        const speed = animationConfig.speed || 1;

                        // Translation/movement
                        if (animationConfig.translation?.enabled) {
                            el.x += el.vx * speed;
                            el.y += el.vy * speed;

                            // Wrap around edges
                            if (el.x < 0) el.x = canvasConfig.width || 800;
                            if (el.x > (canvasConfig.width || 800)) el.x = 0;
                            if (el.y < 0) el.y = canvasConfig.height || 600;
                            if (el.y > (canvasConfig.height || 600)) el.y = 0;
                        }

                        // Rotation
                        if (animationConfig.rotation?.enabled) {
                            el.rotation += 0.05 * speed;
                        }

                        // Scale/pulse
                        if (animationConfig.scale?.enabled) {
                            const pulse = p.sin(animationFrame * 0.05 * speed) * 0.3 + 1;
                            el.size = (drawingConfig.shapeSize || 50) * pulse;
                        }
                    }

                    // Position and draw shape
                    p.translate(el.x, el.y);
                    p.rotate(el.rotation);

                    // Draw based on shape type
                    switch (el.shapeType) {
                        case 'ellipse':
                            p.ellipse(0, 0, el.size, el.size);
                            break;
                        case 'rect':
                            p.rectMode(p.CENTER);
                            p.rect(0, 0, el.size, el.size);
                            break;
                        case 'triangle':
                            const h = el.size * 0.866; // height of equilateral triangle
                            p.triangle(-el.size/2, h/2, el.size/2, h/2, 0, -h/2);
                            break;
                        case 'line':
                            p.line(-el.size/2, 0, el.size/2, 0);
                            break;
                        default:
                            p.ellipse(0, 0, el.size, el.size);
                    }

                    p.pop();
                });

                animationFrame++;
            };

            // Mouse interaction
            p.mouseMoved = function() {
                const interactionConfig = config.interaction || {};
                if (interactionConfig.enabled) {
                    // Simple repel effect
                    elements.forEach(el => {
                        const d = p.dist(p.mouseX, p.mouseY, el.x, el.y);
                        if (d < 100) {
                            const angle = p.atan2(el.y - p.mouseY, el.x - p.mouseX);
                            el.x += p.cos(angle) * 2;
                            el.y += p.sin(angle) * 2;
                        }
                    });
                }
            };
        };

        // Create P5.js instance
        new p5(sketch);
    </script>
</body>
</html>
