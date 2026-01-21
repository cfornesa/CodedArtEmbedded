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

// Include head (DOCTYPE, HTML, A-Frame library)
require_once(__DIR__ . '/../resources/templates/head.php');
?>
<body>
<?php require_once(__DIR__ . '/../resources/templates/header.php'); ?>

<!-- A-Frame Scene -->
<a-scene <?php if (isset($sceneSettings['background'])): ?>background="color: <?php echo htmlspecialchars($sceneSettings['background']); ?>"<?php endif; ?>>

    <!-- Camera -->
    <a-camera position="0 1.6 0" look-controls wasd-controls></a-camera>

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

            // Color
            if (!empty($shape['color'])) {
                $attrs[] = 'color="' . htmlspecialchars($shape['color']) . '"';
            }

            // Texture (use CORS proxy for external images)
            if (!empty($shape['texture'])) {
                $textureUrl = proxifyImageUrl($shape['texture']);
                $attrs[] = 'src="' . htmlspecialchars($textureUrl) . '"';
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

            // Animation
            if (!empty($shape['animation']) && $shape['animation']['enabled']) {
                $animAttrs = [];
                $animAttrs[] = 'property: ' . ($shape['animation']['property'] ?? 'rotation');
                $animAttrs[] = 'to: ' . ($shape['animation']['to'] ?? '0 360 0');
                $animAttrs[] = 'dur: ' . ($shape['animation']['dur'] ?? 10000);
                $animAttrs[] = 'loop: ' . ($shape['animation']['loop'] ? 'true' : 'false');
                $attrs[] = 'animation="' . implode('; ', $animAttrs) . '"';
            }

            $attrString = implode(' ', $attrs);
            ?>

            <a-<?php echo htmlspecialchars($type); ?> <?php echo $attrString; ?>></a-<?php echo htmlspecialchars($type); ?>>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Ground plane -->
    <a-plane position="0 0 -4" rotation="-90 0 0" width="100" height="100" color="#7BC8A4"></a-plane>

    <!-- Sky -->
    <a-sky color="<?php echo htmlspecialchars($sceneSettings['background'] ?? '#ECECEC'); ?>"></a-sky>
</a-scene>

<?php require_once(__DIR__ . '/../resources/templates/footer.php'); ?>
</body>
</html>
