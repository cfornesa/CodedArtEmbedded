<?php
/**
 * Three.js Dynamic Piece Viewer
 * Simple iframe-embeddable viewer following first-whole.php pattern
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
        "SELECT * FROM threejs_art WHERE slug = ? AND deleted_at IS NULL",
        [$slug]
    );

    if (!$piece) {
        http_response_code(404);
        die('Art piece not found.');
    }

    // Load configuration
    $config = !empty($piece['configuration']) ? json_decode($piece['configuration'], true) : [];
    $geometries = $config['geometries'] ?? [];

} catch (Exception $e) {
    error_log('Error loading Three.js piece: ' . $e->getMessage());
    http_response_code(500);
    die('Error loading art piece.');
}

// Get background color (from database field or config, with fallback)
$backgroundColor = $piece['background_color'] ?? ($config['sceneSettings']['background'] ?? '#000000');

// Get background image URL if specified (standardized field)
$backgroundImageUrl = $piece['background_image_url'] ?? null;
// Get background image URL if specified (prefer texture_urls array)
$backgroundImageUrl = null;
if (!empty($piece['texture_urls'])) {
    $textureUrls = json_decode($piece['texture_urls'], true);
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script>
        // Scene setup
        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
        const renderer = new THREE.WebGLRenderer({ antialias: true });

        renderer.setSize(window.innerWidth, window.innerHeight);
        renderer.setClearColor('<?php echo htmlspecialchars($backgroundColor); ?>', 1);
        document.body.appendChild(renderer.domElement);

        // Background image (if specified)
        <?php if (!empty($backgroundImageUrl)): ?>
        const bgTextureLoader = new THREE.TextureLoader();
        bgTextureLoader.load('<?php echo htmlspecialchars(proxifyImageUrl($backgroundImageUrl), ENT_QUOTES); ?>', function(texture) {
            scene.background = texture;
        });
        <?php endif; ?>

        // Store all meshes for animation
        const allMeshes = [];

        // Create geometries from configuration
        const geometries = <?php echo json_encode($geometries); ?>;

        geometries.forEach(geomConfig => {
            const geometryType = geomConfig.type || geomConfig.geometryType;
            // Create geometry based on type
            let geometry;
            switch (geometryType) {
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
                        geomConfig.height || 1,
                        geomConfig.radialSegments || 32
                    );
                    break;
                case 'ConeGeometry':
                    geometry = new THREE.ConeGeometry(
                        geomConfig.radius || 1,
                        geomConfig.height || 1,
                        geomConfig.radialSegments || 32
                    );
                    break;
                case 'TorusGeometry':
                    geometry = new THREE.TorusGeometry(
                        geomConfig.radius || 1,
                        geomConfig.tube || 0.4,
                        geomConfig.radialSegments || 16,
                        geomConfig.tubularSegments || 100
                    );
                    break;
                case 'PlaneGeometry':
                    geometry = new THREE.PlaneGeometry(
                        geomConfig.width || 1,
                        geomConfig.height || 1
                    );
                    break;
                default:
                    geometry = new THREE.BoxGeometry(1, 1, 1);
            }

            // Material options
            const materialOptions = {
                color: geomConfig.color || '#ffffff',
                wireframe: geomConfig.wireframe || false,
                metalness: geomConfig.metalness || 0.5,
                roughness: geomConfig.roughness || 0.5,
                opacity: geomConfig.opacity !== undefined ? geomConfig.opacity : 1.0,
                transparent: (geomConfig.opacity !== undefined && geomConfig.opacity < 1.0) || false
            };

            // Load texture if specified
            if (geomConfig.texture) {
                const textureLoader = new THREE.TextureLoader();
                textureLoader.load('<?php echo proxifyImageUrl(''); ?>' + geomConfig.texture, function(texture) {
                    materialOptions.map = texture;
                    mesh.material.map = texture;
                    mesh.material.needsUpdate = true;
                });
            }

            const materialType = geomConfig.material || 'MeshStandardMaterial';
            const materialConstructors = {
                MeshStandardMaterial: THREE.MeshStandardMaterial,
                MeshBasicMaterial: THREE.MeshBasicMaterial,
                MeshPhongMaterial: THREE.MeshPhongMaterial,
                MeshLambertMaterial: THREE.MeshLambertMaterial
            };
            const MaterialConstructor = materialConstructors[materialType] || THREE.MeshStandardMaterial;
            const material = new MaterialConstructor(materialOptions);
            const mesh = new THREE.Mesh(geometry, material);

            // Position
            mesh.position.set(
                geomConfig.position?.x || 0,
                geomConfig.position?.y || 0,
                geomConfig.position?.z || -5
            );

            // Rotation
            mesh.rotation.set(
                geomConfig.rotation?.x || 0,
                geomConfig.rotation?.y || 0,
                geomConfig.rotation?.z || 0
            );

            // Scale
            mesh.scale.set(
                geomConfig.scale?.x || 1,
                geomConfig.scale?.y || 1,
                geomConfig.scale?.z || 1
            );

            scene.add(mesh);

            // Store mesh with animation config for render loop
            mesh.userData.animationConfig = geomConfig.animation || {};
            mesh.userData.initialPosition = { ...mesh.position };
            allMeshes.push(mesh);
        });

        // Add lights
        const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
        scene.add(ambientLight);

        const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
        directionalLight.position.set(5, 10, 7.5);
        scene.add(directionalLight);

        // Camera position
        camera.position.z = 5;

        // Animation loop
        function animate() {
            requestAnimationFrame(animate);

            allMeshes.forEach(mesh => {
                const anim = mesh.userData.animationConfig;

                // Rotation animation
                if (anim.rotation?.enabled) {
                    const speed = 0.01;
                    const direction = anim.rotation.counterclockwise ? -1 : 1;
                    mesh.rotation.y += speed * direction;
                }

                // Position animation (X, Y, Z independent)
                if (anim.position) {
                    const time = Date.now();

                    if (anim.position.x?.enabled && anim.position.x.range > 0) {
                        const duration = anim.position.x.duration || 10000;
                        const range = anim.position.x.range;
                        const offset = Math.sin(time / duration * Math.PI * 2) * range;
                        mesh.position.x = mesh.userData.initialPosition.x + offset;
                    }

                    if (anim.position.y?.enabled && anim.position.y.range > 0) {
                        const duration = anim.position.y.duration || 10000;
                        const range = anim.position.y.range;
                        const offset = Math.sin(time / duration * Math.PI * 2) * range;
                        mesh.position.y = mesh.userData.initialPosition.y + offset;
                    }

                    if (anim.position.z?.enabled && anim.position.z.range > 0) {
                        const duration = anim.position.z.duration || 10000;
                        const range = anim.position.z.range;
                        const offset = Math.sin(time / duration * Math.PI * 2) * range;
                        mesh.position.z = mesh.userData.initialPosition.z + offset;
                    }
                }

                // Scale animation
                if (anim.scale?.enabled && anim.scale.min !== anim.scale.max) {
                    const time = Date.now();
                    const duration = anim.scale.duration || 10000;
                    const min = anim.scale.min || 1.0;
                    const max = anim.scale.max || 1.0;
                    const mid = (min + max) / 2;
                    const range = (max - min) / 2;
                    const scaleValue = mid + Math.sin(time / duration * Math.PI * 2) * range;
                    mesh.scale.set(scaleValue, scaleValue, scaleValue);
                }
            });

            renderer.render(scene, camera);
        }

        // Handle window resize
        window.addEventListener('resize', () => {
            camera.aspect = window.innerWidth / window.innerHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(window.innerWidth, window.innerHeight);
        });

        // Start animation
        animate();
    </script>
</body>
</html>
