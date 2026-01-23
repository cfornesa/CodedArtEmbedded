<?php
/**
 * Three.js Dynamic Piece Viewer
 * Renders Three.js art pieces from database configuration
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
        "SELECT * FROM threejs_art WHERE slug = ? AND deleted_at IS NULL",
        [$slug]
    );

    if (!$piece) {
        http_response_code(404);
        die('Art piece not found.');
    }

    // Load configuration
    $config = !empty($piece['configuration']) ? json_decode($piece['configuration'], true) : null;
    $geometries = $config['geometries'] ?? [];
    $sceneSettings = $config['sceneSettings'] ?? [];

    // Proxy external texture URLs for CORS compatibility
    if (!empty($config['geometries'])) {
        foreach ($config['geometries'] as &$geom) {
            if (!empty($geom['texture'])) {
                $geom['texture'] = proxifyImageUrl($geom['texture']);
            }
        }
        unset($geom);
    }

    // Set page metadata
    $page_name = htmlspecialchars($piece['title']);
    $tagline = htmlspecialchars($piece['description'] ?? 'Three.js WebGL Art Piece');

} catch (Exception $e) {
    error_log('Error loading Three.js piece: ' . $e->getMessage());
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
        #threejs-container {
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            background: <?php echo htmlspecialchars($sceneSettings['background'] ?? '#000000'); ?>;
        }
    </style>
</head>
<body>
<div id="threejs-container"></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script>
// Three.js Scene Configuration
const config = <?php echo json_encode($config); ?>;

// Scene setup
const container = document.getElementById('threejs-container');
const scene = new THREE.Scene();

// Set initial background color (from database or config)
<?php
$backgroundColor = $piece['background_color'] ?? ($config['sceneSettings']['background'] ?? '#000000');
?>
scene.background = new THREE.Color('<?php echo htmlspecialchars($backgroundColor); ?>');

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

// Load background image if specified
<?php
$backgroundImageUrl = $piece['background_image_url'] ?? null;
// Backward compatibility: fallback to first texture from old texture_urls array
if (empty($backgroundImageUrl) && !empty($piece['texture_urls'])) {
    $textureUrls = json_decode($piece['texture_urls'], true);
    if (is_array($textureUrls) && !empty($textureUrls)) {
        $backgroundImageUrl = $textureUrls[0];
    }
}
if (!empty($backgroundImageUrl)):
?>
const backgroundTextureLoader = new THREE.TextureLoader();
backgroundTextureLoader.load('<?php echo htmlspecialchars(proxifyImageUrl($backgroundImageUrl), ENT_QUOTES); ?>', function(texture) {
    scene.background = texture;
});
<?php endif; ?>

// Lights from configuration
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
        let material;
        const materialType = geomConfig.material || 'MeshStandardMaterial';
        const materialOptions = {
            color: geomConfig.color || '#764ba2',
            wireframe: geomConfig.wireframe || false,
            // NEW: Per-geometry opacity support
            opacity: geomConfig.opacity !== undefined ? geomConfig.opacity : 1.0,
            transparent: (geomConfig.opacity !== undefined && geomConfig.opacity < 1.0) || false
        };

        // Add texture if provided
        if (geomConfig.texture) {
            const textureLoader = new THREE.TextureLoader();
            materialOptions.map = textureLoader.load(geomConfig.texture);
        }

        switch (materialType) {
            case 'MeshBasicMaterial':
                material = new THREE.MeshBasicMaterial(materialOptions);
                break;
            case 'MeshPhongMaterial':
                material = new THREE.MeshPhongMaterial(materialOptions);
                break;
            case 'MeshLambertMaterial':
                material = new THREE.MeshLambertMaterial(materialOptions);
                break;
            case 'MeshStandardMaterial':
            default:
                material = new THREE.MeshStandardMaterial(materialOptions);
        }

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

        // Store animation config
        mesh.userData.animation = geomConfig.animation || {};

        scene.add(mesh);
        meshes.push(mesh);
    });
}

// Animation loop with granular animation support (matching A-Frame pattern)
function animate() {
    requestAnimationFrame(animate);

    // Animate each mesh based on its configuration
    meshes.forEach((mesh) => {
        const anim = mesh.userData.animation;

        if (anim) {
            const time = Date.now();

            // Backward compatibility: Check for old animation format
            if (anim.hasOwnProperty('enabled') && anim.hasOwnProperty('property')) {
                // OLD FORMAT: Use legacy animation logic
                if (anim.enabled) {
                    const speed = anim.speed || 0.01;
                    const property = anim.property || 'rotation.y';

                    const parts = property.split('.');
                    if (parts.length === 2) {
                        const obj = parts[0]; // rotation, position, scale
                        const axis = parts[1]; // x, y, z

                        if (mesh[obj]) {
                            if (obj === 'rotation') {
                                mesh[obj][axis] += speed;
                            } else if (obj === 'position') {
                                mesh[obj][axis] = Math.sin(time * speed * 0.001) * 2;
                            } else if (obj === 'scale') {
                                const pulse = 1 + Math.sin(time * speed * 0.001) * 0.3;
                                mesh[obj][axis] = pulse;
                            }
                        }
                    }
                }
            } else {
                // NEW FORMAT: Granular animation controls

                // 1. ROTATION ANIMATION
                if (anim.rotation && anim.rotation.enabled) {
                    const duration = anim.rotation.duration || 10000;
                    const speed = (Math.PI * 2) / duration; // Full rotation per duration
                    const direction = anim.rotation.counterclockwise ? -1 : 1;
                    mesh.rotation.y += speed * direction * 16.67; // ~60fps frame time
                }

                // 2. POSITION ANIMATION (X/Y/Z independent)
                if (anim.position) {
                    // Store initial position if not set
                    if (!mesh.userData.initialPosition) {
                        mesh.userData.initialPosition = {
                            x: mesh.position.x,
                            y: mesh.position.y,
                            z: mesh.position.z
                        };
                    }

                    // X-axis animation
                    if (anim.position.x && anim.position.x.enabled && anim.position.x.range > 0) {
                        const duration = anim.position.x.duration || 10000;
                        const range = anim.position.x.range;
                        const offset = Math.sin(time / duration * Math.PI * 2) * range;
                        mesh.position.x = mesh.userData.initialPosition.x + offset;
                    }

                    // Y-axis animation
                    if (anim.position.y && anim.position.y.enabled && anim.position.y.range > 0) {
                        const duration = anim.position.y.duration || 10000;
                        const range = anim.position.y.range;
                        const offset = Math.sin(time / duration * Math.PI * 2) * range;
                        mesh.position.y = mesh.userData.initialPosition.y + offset;
                    }

                    // Z-axis animation
                    if (anim.position.z && anim.position.z.enabled && anim.position.z.range > 0) {
                        const duration = anim.position.z.duration || 10000;
                        const range = anim.position.z.range;
                        const offset = Math.sin(time / duration * Math.PI * 2) * range;
                        mesh.position.z = mesh.userData.initialPosition.z + offset;
                    }
                }

                // 3. SCALE ANIMATION
                if (anim.scale && anim.scale.enabled && anim.scale.min !== anim.scale.max) {
                    const duration = anim.scale.duration || 10000;
                    const min = anim.scale.min || 1.0;
                    const max = anim.scale.max || 1.0;
                    const range = (max - min) / 2;
                    const mid = (min + max) / 2;
                    const scaleValue = mid + Math.sin(time / duration * Math.PI * 2) * range;
                    mesh.scale.set(scaleValue, scaleValue, scaleValue);
                }
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

// Simple mouse controls
let mouseDown = false;
let mouseX = 0;
let mouseY = 0;

container.addEventListener('mousedown', (e) => {
    mouseDown = true;
    mouseX = e.clientX;
    mouseY = e.clientY;
});

container.addEventListener('mouseup', () => {
    mouseDown = false;
});

container.addEventListener('mousemove', (e) => {
    if (mouseDown) {
        const deltaX = e.clientX - mouseX;
        const deltaY = e.clientY - mouseY;

        camera.position.x += deltaX * 0.01;
        camera.position.y -= deltaY * 0.01;

        mouseX = e.clientX;
        mouseY = e.clientY;
    }
});

// Mouse wheel zoom
container.addEventListener('wheel', (e) => {
    e.preventDefault();
    camera.position.z += e.deltaY * 0.01;
    camera.position.z = Math.max(1, Math.min(20, camera.position.z));
});
</script>
</body>
</html>
