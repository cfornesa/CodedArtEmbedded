<?php
/**
 * Three.js Art Management
 * CRUD interface for Three.js art pieces
 */

require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/includes/db-check.php');
require_once(__DIR__ . '/includes/auth.php');
require_once(__DIR__ . '/includes/functions.php');
require_once(__DIR__ . '/includes/slug_functions.php');

// Check database is initialized
requireDatabaseInitialized();

// Require authentication
requireAuth();

$page_title = 'Three.js Art Management';

// Handle actions
$action = $_GET['action'] ?? 'list';
$pieceId = $_GET['id'] ?? null;
$error = '';
$success = '';

// Preserve form data on validation errors
$formData = null;

// Handle delete action (soft delete by default)
if ($action === 'delete' && $pieceId) {
    $permanent = isset($_GET['permanent']) && $_GET['permanent'] === '1';
    $result = deleteArtPieceWithSlug('threejs', $pieceId, $permanent);
    if ($result['success']) {
        $success = $result['message'];
        $action = 'list';
    } else {
        $error = $result['message'];
    }
}

// Handle form submission (create/edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['create', 'edit'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        // Prepare data (file_path will be auto-generated from slug)
        $data = [
            'title' => $_POST['title'] ?? '',
            'slug' => $_POST['slug'] ?? '',  // Optional: auto-generated if empty
            'description' => $_POST['description'] ?? '',
            'embedded_path' => $_POST['embedded_path'] ?? '',
            'js_file' => $_POST['js_file'] ?? '',
            'thumbnail_url' => $_POST['thumbnail_url'] ?? '',
            'tags' => $_POST['tags'] ?? '',
            'status' => $_POST['status'] ?? 'active',
            'sort_order' => $_POST['sort_order'] ?? 0
        ];

        // Handle texture URLs (array input)
        if (isset($_POST['texture_urls']) && is_array($_POST['texture_urls'])) {
            $data['texture_urls'] = array_filter($_POST['texture_urls']);
        }

        // Handle configuration JSON if provided
        if (!empty($_POST['configuration_json'])) {
            $config = json_decode($_POST['configuration_json'], true);
            if ($config !== null) {
                $data['configuration'] = $config;
            }
        }

        if ($action === 'create') {
            $result = createArtPieceWithSlug('threejs', $data);
        } else {
            $result = updateArtPieceWithSlug('threejs', $pieceId, $data);
        }

        if ($result['success']) {
            $success = $result['message'];
            $action = 'list';
        } else {
            $error = $result['message'];
            // Preserve form data so user doesn't lose their work
            $formData = $data;
            // Also preserve array inputs in original format
            if (isset($_POST['texture_urls'])) {
                $formData['texture_urls_raw'] = $_POST['texture_urls'];
            }
            // Preserve configuration JSON
            if (isset($_POST['configuration_json'])) {
                $formData['configuration_json_raw'] = $_POST['configuration_json'];
            }
        }
    }
}

// Get active art pieces for listing (excludes soft-deleted)
$artPieces = getActiveArtPieces('threejs', 'all');

// Get single piece for editing
$editPiece = null;
if ($action === 'edit' && $pieceId) {
    $editPiece = getArtPiece('threejs', $pieceId);
    if (!$editPiece) {
        $error = 'Art piece not found.';
        $action = 'list';
    }
}

// Generate CSRF token
$csrfToken = generateCsrfToken();

// Include header
require_once(__DIR__ . '/includes/header.php');
?>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
    <!-- List View -->
    <div class="card">
        <div class="card-header d-flex justify-between align-center">
            <h2>Three.js Art Pieces</h2>
            <div>
                <a href="<?php echo url('admin/deleted.php?type=threejs'); ?>" class="btn btn-secondary" style="margin-right: 10px;">
                    🗑️ Deleted Items
                </a>
                <a href="<?php echo url('admin/threejs.php?action=create'); ?>" class="btn btn-success">
                    + Add New Piece
                </a>
            </div>
        </div>

        <?php if (empty($artPieces)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">🎨</div>
                <p>No Three.js art pieces yet.</p>
                <a href="<?php echo url('admin/threejs.php?action=create'); ?>" class="btn btn-primary">
                    Create Your First Piece
                </a>
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Thumbnail</th>
                        <th>Title</th>
                        <th>Slug</th>
                        <th>JS File</th>
                        <th>Status</th>
                        <th>Sort Order</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($artPieces as $piece): ?>
                    <tr>
                        <td>
                            <?php if ($piece['thumbnail_url']): ?>
                                <img
                                    src="<?php echo htmlspecialchars($piece['thumbnail_url']); ?>"
                                    alt="<?php echo htmlspecialchars($piece['title']); ?>"
                                    class="table-thumbnail"
                                >
                            <?php else: ?>
                                <div style="width: 80px; height: 80px; background-color: #e0e0e0; display: flex; align-items: center; justify-content: center; border-radius: 4px;">
                                    No Image
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($piece['title']); ?></strong>
                            <br>
                            <small><?php echo htmlspecialchars(substr($piece['description'], 0, 100)) . (strlen($piece['description']) > 100 ? '...' : ''); ?></small>
                        </td>
                        <td>
                            <code style="font-size: 0.85em;"><?php echo htmlspecialchars($piece['slug'] ?? 'N/A'); ?></code>
                        </td>
                        <td>
                            <small><?php echo htmlspecialchars($piece['js_file'] ?: 'N/A'); ?></small>
                        </td>
                        <td>
                            <span class="badge badge-<?php
                                echo $piece['status'] === 'active' ? 'success' :
                                    ($piece['status'] === 'draft' ? 'warning' : 'secondary');
                            ?>">
                                <?php echo htmlspecialchars(ucfirst($piece['status'])); ?>
                            </span>
                        </td>
                        <td><?php echo $piece['sort_order']; ?></td>
                        <td>
                            <div class="action-buttons">
                                <a
                                    href="<?php echo url('admin/threejs.php?action=edit&id=' . $piece['id']); ?>"
                                    class="btn btn-sm btn-primary"
                                >
                                    Edit
                                </a>
                                <a
                                    href="<?php echo htmlspecialchars($piece['file_path']); ?>"
                                    class="btn btn-sm btn-secondary"
                                    target="_blank"
                                >
                                    View
                                </a>
                                <a
                                    href="<?php echo url('admin/threejs.php?action=delete&id=' . $piece['id']); ?>"
                                    class="btn btn-sm btn-danger btn-delete"
                                    data-name="<?php echo htmlspecialchars($piece['title']); ?>"
                                >
                                    Delete
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

<?php elseif (in_array($action, ['create', 'edit'])): ?>
    <!-- Create/Edit Form -->
    <div class="card">
        <div class="card-header">
            <h2><?php echo $action === 'create' ? 'Add New' : 'Edit'; ?> Three.js Piece</h2>
        </div>

        <form method="POST" action="" data-validate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

            <div class="form-group">
                <label for="title" class="form-label required">Title</label>
                <input
                    type="text"
                    id="title"
                    name="title"
                    class="form-control"
                    required
                    value="<?php echo $formData ? htmlspecialchars($formData['title']) : ($editPiece ? htmlspecialchars($editPiece['title']) : ''); ?>"
                    onkeyup="updateSlugPreview()"
                >
            </div>

            <div class="form-group">
                <label for="slug" class="form-label">URL Slug</label>
                <input
                    type="text"
                    id="slug"
                    name="slug"
                    class="form-control"
                    placeholder="auto-generated-from-title"
                    value="<?php echo $formData ? htmlspecialchars($formData['slug']) : ($editPiece ? htmlspecialchars($editPiece['slug']) : ''); ?>"
                    pattern="[a-z0-9-]+"
                    title="Only lowercase letters, numbers, and hyphens"
                >
                <small class="form-help">
                    Leave empty to auto-generate from title. Must be unique.
                    <span id="slug-preview" style="display: none;">Preview: <code></code></span>
                    <?php if ($editPiece && !empty($editPiece['slug'])): ?>
                    <br><strong>Note:</strong> Changing the slug will create a redirect from the old URL.
                    <?php endif; ?>
                </small>
            </div>

            <div class="form-group">
                <label for="description" class="form-label">Description</label>
                <textarea
                    id="description"
                    name="description"
                    class="form-control"
                    rows="4"
                ><?php echo $formData ? htmlspecialchars($formData['description']) : ($editPiece ? htmlspecialchars($editPiece['description']) : ''); ?></textarea>
            </div>

            <!-- File path is auto-generated from slug: /three-js/view.php?slug=your-slug -->

            <div class="form-group">
                <label for="embedded_path" class="form-label">Embedded Path</label>
                <input
                    type="text"
                    id="embedded_path"
                    name="embedded_path"
                    class="form-control"
                    placeholder="/three-js/first-whole.php"
                    value="<?php echo $formData ? htmlspecialchars($formData['embedded_path']) : ($editPiece ? htmlspecialchars($editPiece['embedded_path']) : ''); ?>"
                >
                <small class="form-help">Path to *-whole.php version for embedding</small>
            </div>

            <div class="form-group">
                <label for="js_file" class="form-label">JavaScript File</label>
                <input
                    type="text"
                    id="js_file"
                    name="js_file"
                    class="form-control"
                    placeholder="first.js"
                    value="<?php echo $formData ? htmlspecialchars($formData['js_file']) : ($editPiece ? htmlspecialchars($editPiece['js_file']) : ''); ?>"
                >
                <small class="form-help">JavaScript filename (e.g., first.js)</small>
            </div>

            <div class="form-group">
                <label for="thumbnail_url" class="form-label">Thumbnail URL</label>
                <input
                    type="url"
                    id="thumbnail_url"
                    name="thumbnail_url"
                    class="form-control"
                    data-type="url"
                    data-preview="thumbnail-preview"
                    placeholder="https://example.com/image.png"
                    value="<?php echo $formData ? htmlspecialchars($formData['thumbnail_url']) : ($editPiece ? htmlspecialchars($editPiece['thumbnail_url']) : ''); ?>"
                >
                <small class="form-help">URL to thumbnail image (WEBP, JPG, PNG)</small>
                <img id="thumbnail-preview" style="display: none; max-width: 200px; margin-top: 10px;" />
            </div>

            <div class="form-group">
                <label class="form-label">Texture URLs (optional)</label>
                <div id="texture-urls-container">
                    <?php
                    $textureUrls = [];
                    if ($formData && !empty($formData['texture_urls_raw'])) {
                        $textureUrls = $formData['texture_urls_raw'];
                    } elseif ($editPiece && !empty($editPiece['texture_urls'])) {
                        $textureUrls = json_decode($editPiece['texture_urls'], true) ?: [];
                    }

                    if (empty($textureUrls)) {
                        $textureUrls = [''];
                    }

                    foreach ($textureUrls as $index => $url):
                    ?>
                    <input
                        type="url"
                        name="texture_urls[]"
                        class="form-control mb-1"
                        placeholder="https://example.com/texture.png"
                        value="<?php echo htmlspecialchars($url); ?>"
                    >
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn-sm btn-secondary mt-1" onclick="addTextureUrl()">
                    + Add Another Texture URL
                </button>
            </div>

            <div class="form-group">
                <label for="tags" class="form-label">Tags</label>
                <input
                    type="text"
                    id="tags"
                    name="tags"
                    class="form-control"
                    placeholder="Three.js, 3D, WebGL, Animation"
                    value="<?php echo $formData ? htmlspecialchars($formData['tags']) : ($editPiece ? htmlspecialchars($editPiece['tags']) : ''); ?>"
                >
                <small class="form-help">Comma-separated tags</small>
            </div>

            <div class="form-group">
                <label for="status" class="form-label">Status</label>
                <?php
                $currentStatus = $formData ? $formData['status'] : ($editPiece ? $editPiece['status'] : 'active');
                ?>
                <select id="status" name="status" class="form-control">
                    <option value="active" <?php echo ($currentStatus === 'active') ? 'selected' : ''; ?>>Active</option>
                    <option value="draft" <?php echo ($currentStatus === 'draft') ? 'selected' : ''; ?>>Draft</option>
                    <option value="archived" <?php echo ($currentStatus === 'archived') ? 'selected' : ''; ?>>Archived</option>
                </select>
            </div>

            <div class="form-group">
                <label for="sort_order" class="form-label">Sort Order</label>
                <input
                    type="number"
                    id="sort_order"
                    name="sort_order"
                    class="form-control"
                    value="<?php echo $formData ? htmlspecialchars($formData['sort_order']) : ($editPiece ? $editPiece['sort_order'] : 0); ?>"
                >
                <small class="form-help">Lower numbers appear first</small>
            </div>

            <!-- Advanced Geometry Configuration Builder -->
            <div class="card" style="margin-top: 30px; border: 2px solid #764ba2;">
                <div class="card-header" style="background: linear-gradient(135deg, #764ba2 0%, #667eea 100%); color: white;">
                    <h3 style="margin: 0; display: flex; align-items: center; justify-content: space-between;">
                        <span>🎬 Three.js Geometry Builder</span>
                        <small style="opacity: 0.9; font-weight: normal;">(Max: 40 geometries)</small>
                    </h3>
                    <p style="margin: 5px 0 0 0; font-size: 14px; opacity: 0.95;">
                        Add and configure 3D geometries for your Three.js scene
                    </p>
                </div>

                <div style="padding: 20px;">
                    <div id="geometries-container"></div>

                    <button type="button" class="btn btn-success" onclick="addGeometry()" id="add-geometry-btn">
                        + Add New Geometry
                    </button>

                    <small class="form-help" style="display: block; margin-top: 10px;">
                        <strong>Tip:</strong> Click "Add New Geometry" to add objects to your scene. Each geometry can be fully customized with position, rotation, material properties, and textures.
                    </small>
                </div>
            </div>

            <!-- Hidden field to store geometry configuration as JSON -->
            <input type="hidden" name="configuration_json" id="configuration_json">

            <div class="form-group" style="margin-top: 30px;">
                <button type="submit" class="btn btn-primary btn-lg">
                    <?php echo $action === 'create' ? 'Create Piece' : 'Update Piece'; ?>
                </button>
                <a href="<?php echo url('admin/threejs.php'); ?>" class="btn btn-secondary btn-lg">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    <style>
    .geometry-panel {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 15px;
        position: relative;
    }

    .geometry-panel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #764ba2;
    }

    .geometry-panel-title {
        font-weight: bold;
        color: #333;
        font-size: 16px;
    }

    .geometry-remove-btn {
        background: #dc3545;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
    }

    .geometry-remove-btn:hover {
        background: #c82333;
    }

    .geometry-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 15px;
    }

    .geometry-field-group {
        display: flex;
        flex-direction: column;
    }

    .geometry-field-label {
        font-weight: 600;
        margin-bottom: 5px;
        color: #495057;
        font-size: 14px;
    }

    .geometry-field-input {
        padding: 8px 12px;
        border: 1px solid #ced4da;
        border-radius: 4px;
        font-size: 14px;
    }

    .geometry-field-input:focus {
        outline: none;
        border-color: #764ba2;
        box-shadow: 0 0 0 0.2rem rgba(118, 75, 162, 0.25);
    }

    #geometries-container:empty::before {
        content: 'No geometries added yet. Click "Add New Geometry" to get started.';
        display: block;
        padding: 40px;
        text-align: center;
        color: #6c757d;
        background: #f8f9fa;
        border: 2px dashed #dee2e6;
        border-radius: 8px;
        margin-bottom: 15px;
    }
    </style>

    <script>
    let geometryCount = 0;
    const MAX_GEOMETRIES = 40;
    const geometries = [];

    // Three.js geometry types
    const geometryTypes = {
        'BoxGeometry': { width: true, height: true, depth: true, label: 'Box' },
        'SphereGeometry': { radius: true, widthSegments: true, heightSegments: true, label: 'Sphere' },
        'CylinderGeometry': { radiusTop: true, radiusBottom: true, height: true, label: 'Cylinder' },
        'ConeGeometry': { radius: true, height: true, label: 'Cone' },
        'PlaneGeometry': { width: true, height: true, label: 'Plane' },
        'TorusGeometry': { radius: true, tube: true, label: 'Torus' },
        'TorusKnotGeometry': { radius: true, tube: true, label: 'Torus Knot' },
        'DodecahedronGeometry': { radius: true, label: 'Dodecahedron' },
        'IcosahedronGeometry': { radius: true, label: 'Icosahedron' },
        'OctahedronGeometry': { radius: true, label: 'Octahedron' },
        'TetrahedronGeometry': { radius: true, label: 'Tetrahedron' },
        'RingGeometry': { innerRadius: true, outerRadius: true, label: 'Ring' }
    };

    // Add new geometry to the scene
    function addGeometry() {
        if (geometryCount >= MAX_GEOMETRIES) {
            alert(`Maximum of ${MAX_GEOMETRIES} geometries reached!`);
            return;
        }

        const id = Date.now();
        geometryCount++;

        const geometryData = {
            id: id,
            type: 'BoxGeometry',
            position: { x: 0, y: 0, z: 0 },
            rotation: { x: 0, y: 0, z: 0 },
            scale: { x: 1, y: 1, z: 1 },
            color: '#764ba2',
            texture: '',
            width: 1,
            height: 1,
            depth: 1,
            radius: 1,
            wireframe: false,
            material: 'MeshStandardMaterial',
            animation: {
                enabled: false,
                property: 'rotation.y',
                speed: 0.01
            }
        };

        geometries.push(geometryData);
        renderGeometry(geometryData);
        updateAddButtonState();
        updateConfiguration();
    }

    // Render a geometry panel
    function renderGeometry(geometryData) {
        const container = document.getElementById('geometries-container');
        const index = geometries.findIndex(g => g.id === geometryData.id);
        const geometryNumber = index + 1;

        const panel = document.createElement('div');
        panel.className = 'geometry-panel';
        panel.id = `geometry-${geometryData.id}`;
        panel.innerHTML = `
            <div class="geometry-panel-header">
                <span class="geometry-panel-title">Geometry #${geometryNumber}</span>
                <button type="button" class="geometry-remove-btn" onclick="removeGeometry(${geometryData.id})">
                    Remove
                </button>
            </div>

            <div class="geometry-row">
                <div class="geometry-field-group">
                    <label class="geometry-field-label">Geometry Type</label>
                    <select class="geometry-field-input" onchange="updateGeometryType(${geometryData.id}, this.value)">
                        ${Object.keys(geometryTypes).map(type =>
                            `<option value="${type}" ${geometryData.type === type ? 'selected' : ''}>${geometryTypes[type].label}</option>`
                        ).join('')}
                    </select>
                </div>

                <div class="geometry-field-group">
                    <label class="geometry-field-label">Material</label>
                    <select class="geometry-field-input" onchange="updateGeometryProperty(${geometryData.id}, 'material', this.value)">
                        <option value="MeshStandardMaterial" ${geometryData.material === 'MeshStandardMaterial' ? 'selected' : ''}>Standard</option>
                        <option value="MeshBasicMaterial" ${geometryData.material === 'MeshBasicMaterial' ? 'selected' : ''}>Basic</option>
                        <option value="MeshPhongMaterial" ${geometryData.material === 'MeshPhongMaterial' ? 'selected' : ''}>Phong</option>
                        <option value="MeshLambertMaterial" ${geometryData.material === 'MeshLambertMaterial' ? 'selected' : ''}>Lambert</option>
                    </select>
                </div>

                <div class="geometry-field-group">
                    <label class="geometry-field-label">Color</label>
                    <input type="color" class="geometry-field-input" value="${geometryData.color}"
                           onchange="updateGeometryProperty(${geometryData.id}, 'color', this.value)">
                </div>

                <div class="geometry-field-group">
                    <label class="geometry-field-label">
                        <input type="checkbox" ${geometryData.wireframe ? 'checked' : ''}
                               onchange="updateGeometryProperty(${geometryData.id}, 'wireframe', this.checked)">
                        Wireframe
                    </label>
                </div>
            </div>

            <div class="geometry-row">
                <div class="geometry-field-group">
                    <label class="geometry-field-label">Texture URL (Optional)</label>
                    <input type="url" class="geometry-field-input" value="${geometryData.texture}"
                           placeholder="https://example.com/texture.png"
                           onchange="updateGeometryProperty(${geometryData.id}, 'texture', this.value)">
                </div>
            </div>

            <div id="dimensions-${geometryData.id}">
                ${renderDimensions(geometryData)}
            </div>

            <div class="geometry-row">
                <div class="geometry-field-group">
                    <label class="geometry-field-label">Position (X, Y, Z)</label>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 5px;">
                        <input type="number" step="0.1" class="geometry-field-input" value="${geometryData.position.x}"
                               placeholder="X" onchange="updateGeometryXYZ(${geometryData.id}, 'position', 'x', this.value)">
                        <input type="number" step="0.1" class="geometry-field-input" value="${geometryData.position.y}"
                               placeholder="Y" onchange="updateGeometryXYZ(${geometryData.id}, 'position', 'y', this.value)">
                        <input type="number" step="0.1" class="geometry-field-input" value="${geometryData.position.z}"
                               placeholder="Z" onchange="updateGeometryXYZ(${geometryData.id}, 'position', 'z', this.value)">
                    </div>
                </div>
            </div>

            <div class="geometry-row">
                <div class="geometry-field-group">
                    <label class="geometry-field-label">Rotation (X, Y, Z) in radians</label>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 5px;">
                        <input type="number" step="0.1" class="geometry-field-input" value="${geometryData.rotation.x}"
                               placeholder="X" onchange="updateGeometryXYZ(${geometryData.id}, 'rotation', 'x', this.value)">
                        <input type="number" step="0.1" class="geometry-field-input" value="${geometryData.rotation.y}"
                               placeholder="Y" onchange="updateGeometryXYZ(${geometryData.id}, 'rotation', 'y', this.value)">
                        <input type="number" step="0.1" class="geometry-field-input" value="${geometryData.rotation.z}"
                               placeholder="Z" onchange="updateGeometryXYZ(${geometryData.id}, 'rotation', 'z', this.value)">
                    </div>
                </div>
            </div>

            <div class="geometry-row">
                <div class="geometry-field-group">
                    <label class="geometry-field-label">Scale (X, Y, Z)</label>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 5px;">
                        <input type="number" step="0.1" class="geometry-field-input" value="${geometryData.scale.x}"
                               placeholder="X" onchange="updateGeometryXYZ(${geometryData.id}, 'scale', 'x', this.value)">
                        <input type="number" step="0.1" class="geometry-field-input" value="${geometryData.scale.y}"
                               placeholder="Y" onchange="updateGeometryXYZ(${geometryData.id}, 'scale', 'y', this.value)">
                        <input type="number" step="0.1" class="geometry-field-input" value="${geometryData.scale.z}"
                               placeholder="Z" onchange="updateGeometryXYZ(${geometryData.id}, 'scale', 'z', this.value)">
                    </div>
                </div>
            </div>

            <details style="margin-top: 15px;">
                <summary style="cursor: pointer; font-weight: 600; color: #764ba2;">Animation Settings</summary>
                <div style="margin-top: 10px; padding: 15px; background: white; border-radius: 4px;">
                    <div class="geometry-row">
                        <div class="geometry-field-group">
                            <label class="geometry-field-label">
                                <input type="checkbox" ${geometryData.animation.enabled ? 'checked' : ''}
                                       onchange="updateAnimationEnabled(${geometryData.id}, this.checked)">
                                Enable Animation
                            </label>
                        </div>
                    </div>
                    <div class="geometry-row">
                        <div class="geometry-field-group">
                            <label class="geometry-field-label">Animate Property</label>
                            <select class="geometry-field-input" onchange="updateAnimationProperty(${geometryData.id}, 'property', this.value)">
                                <option value="rotation.x" ${geometryData.animation.property === 'rotation.x' ? 'selected' : ''}>Rotation X</option>
                                <option value="rotation.y" ${geometryData.animation.property === 'rotation.y' ? 'selected' : ''}>Rotation Y</option>
                                <option value="rotation.z" ${geometryData.animation.property === 'rotation.z' ? 'selected' : ''}>Rotation Z</option>
                                <option value="position.y" ${geometryData.animation.property === 'position.y' ? 'selected' : ''}>Position Y (bounce)</option>
                            </select>
                        </div>
                        <div class="geometry-field-group">
                            <label class="geometry-field-label">Speed</label>
                            <input type="number" step="0.001" class="geometry-field-input" value="${geometryData.animation.speed}"
                                   onchange="updateAnimationProperty(${geometryData.id}, 'speed', this.value)">
                        </div>
                    </div>
                </div>
            </details>
        `;

        container.appendChild(panel);
    }

    // Render dimension fields based on geometry type
    function renderDimensions(geometryData) {
        const type = geometryTypes[geometryData.type];
        let html = '<div class="geometry-row">';

        if (type.width) {
            html += `
                <div class="geometry-field-group">
                    <label class="geometry-field-label">Width</label>
                    <input type="number" step="0.1" class="geometry-field-input" value="${geometryData.width || 1}"
                           onchange="updateGeometryProperty(${geometryData.id}, 'width', this.value)">
                </div>
            `;
        }

        if (type.height) {
            html += `
                <div class="geometry-field-group">
                    <label class="geometry-field-label">Height</label>
                    <input type="number" step="0.1" class="geometry-field-input" value="${geometryData.height || 1}"
                           onchange="updateGeometryProperty(${geometryData.id}, 'height', this.value)">
                </div>
            `;
        }

        if (type.depth) {
            html += `
                <div class="geometry-field-group">
                    <label class="geometry-field-label">Depth</label>
                    <input type="number" step="0.1" class="geometry-field-input" value="${geometryData.depth || 1}"
                           onchange="updateGeometryProperty(${geometryData.id}, 'depth', this.value)">
                </div>
            `;
        }

        if (type.radius) {
            html += `
                <div class="geometry-field-group">
                    <label class="geometry-field-label">Radius</label>
                    <input type="number" step="0.1" class="geometry-field-input" value="${geometryData.radius || 1}"
                           onchange="updateGeometryProperty(${geometryData.id}, 'radius', this.value)">
                </div>
            `;
        }

        if (type.tube) {
            html += `
                <div class="geometry-field-group">
                    <label class="geometry-field-label">Tube Size</label>
                    <input type="number" step="0.1" class="geometry-field-input" value="${geometryData.tube || 0.4}"
                           onchange="updateGeometryProperty(${geometryData.id}, 'tube', this.value)">
                </div>
            `;
        }

        if (type.radiusTop) {
            html += `
                <div class="geometry-field-group">
                    <label class="geometry-field-label">Top Radius</label>
                    <input type="number" step="0.1" class="geometry-field-input" value="${geometryData.radiusTop || 1}"
                           onchange="updateGeometryProperty(${geometryData.id}, 'radiusTop', this.value)">
                </div>
            `;
        }

        if (type.radiusBottom) {
            html += `
                <div class="geometry-field-group">
                    <label class="geometry-field-label">Bottom Radius</label>
                    <input type="number" step="0.1" class="geometry-field-input" value="${geometryData.radiusBottom || 1}"
                           onchange="updateGeometryProperty(${geometryData.id}, 'radiusBottom', this.value)">
                </div>
            `;
        }

        if (type.innerRadius) {
            html += `
                <div class="geometry-field-group">
                    <label class="geometry-field-label">Inner Radius</label>
                    <input type="number" step="0.1" class="geometry-field-input" value="${geometryData.innerRadius || 0.5}"
                           onchange="updateGeometryProperty(${geometryData.id}, 'innerRadius', this.value)">
                </div>
            `;
        }

        if (type.outerRadius) {
            html += `
                <div class="geometry-field-group">
                    <label class="geometry-field-label">Outer Radius</label>
                    <input type="number" step="0.1" class="geometry-field-input" value="${geometryData.outerRadius || 1}"
                           onchange="updateGeometryProperty(${geometryData.id}, 'outerRadius', this.value)">
                </div>
            `;
        }

        if (type.widthSegments) {
            html += `
                <div class="geometry-field-group">
                    <label class="geometry-field-label">Width Segments</label>
                    <input type="number" step="1" class="geometry-field-input" value="${geometryData.widthSegments || 32}"
                           onchange="updateGeometryProperty(${geometryData.id}, 'widthSegments', this.value)">
                </div>
            `;
        }

        if (type.heightSegments) {
            html += `
                <div class="geometry-field-group">
                    <label class="geometry-field-label">Height Segments</label>
                    <input type="number" step="1" class="geometry-field-input" value="${geometryData.heightSegments || 32}"
                           onchange="updateGeometryProperty(${geometryData.id}, 'heightSegments', this.value)">
                </div>
            `;
        }

        html += '</div>';
        return html;
    }

    // Update geometry type
    function updateGeometryType(id, newType) {
        const geometry = geometries.find(g => g.id === id);
        if (geometry) {
            geometry.type = newType;

            // Re-render the dimensions section
            const dimensionsContainer = document.getElementById(`dimensions-${id}`);
            dimensionsContainer.innerHTML = renderDimensions(geometry);

            updateConfiguration();
        }
    }

    // Update geometry property
    function updateGeometryProperty(id, property, value) {
        const geometry = geometries.find(g => g.id === id);
        if (geometry) {
            // Convert to appropriate type
            if (['width', 'height', 'depth', 'radius', 'tube', 'radiusTop', 'radiusBottom',
                 'innerRadius', 'outerRadius', 'widthSegments', 'heightSegments'].includes(property)) {
                geometry[property] = parseFloat(value);
            } else if (property === 'wireframe') {
                geometry[property] = Boolean(value);
            } else {
                geometry[property] = value;
            }
            updateConfiguration();
        }
    }

    // Update XYZ values (position, rotation, scale)
    function updateGeometryXYZ(id, type, axis, value) {
        const geometry = geometries.find(g => g.id === id);
        if (geometry) {
            geometry[type][axis] = parseFloat(value);
            updateConfiguration();
        }
    }

    // Update animation enabled status
    function updateAnimationEnabled(id, enabled) {
        const geometry = geometries.find(g => g.id === id);
        if (geometry) {
            geometry.animation.enabled = enabled;
            updateConfiguration();
        }
    }

    // Update animation property
    function updateAnimationProperty(id, property, value) {
        const geometry = geometries.find(g => g.id === id);
        if (geometry) {
            if (property === 'speed') {
                geometry.animation[property] = parseFloat(value);
            } else {
                geometry.animation[property] = value;
            }
            updateConfiguration();
        }
    }

    // Remove a geometry
    function removeGeometry(id) {
        const index = geometries.findIndex(g => g.id === id);
        if (index !== -1) {
            geometries.splice(index, 1);
            geometryCount--;

            // Remove the panel from DOM
            const panel = document.getElementById(`geometry-${id}`);
            if (panel) {
                panel.remove();
            }

            // Renumber remaining geometries
            renumberGeometries();
            updateAddButtonState();
            updateConfiguration();
        }
    }

    // Renumber geometry panels after deletion
    function renumberGeometries() {
        geometries.forEach((geometry, index) => {
            const panel = document.getElementById(`geometry-${geometry.id}`);
            if (panel) {
                const title = panel.querySelector('.geometry-panel-title');
                if (title) {
                    title.textContent = `Geometry #${index + 1}`;
                }
            }
        });
    }

    // Update the add button state based on geometry count
    function updateAddButtonState() {
        const btn = document.getElementById('add-geometry-btn');
        if (geometryCount >= MAX_GEOMETRIES) {
            btn.disabled = true;
            btn.textContent = `Maximum ${MAX_GEOMETRIES} Geometries Reached`;
            btn.style.opacity = '0.6';
            btn.style.cursor = 'not-allowed';
        } else {
            btn.disabled = false;
            btn.textContent = `+ Add New Geometry (${geometryCount}/${MAX_GEOMETRIES})`;
            btn.style.opacity = '1';
            btn.style.cursor = 'pointer';
        }
    }

    // Update the hidden configuration field
    function updateConfiguration() {
        const config = {
            geometries: geometries,
            sceneSettings: {
                background: '#000000',
                lights: [
                    { type: 'AmbientLight', color: '#ffffff', intensity: 0.5 },
                    { type: 'DirectionalLight', color: '#ffffff', intensity: 0.8, position: { x: 5, y: 10, z: 7.5 } }
                ]
            }
        };
        document.getElementById('configuration_json').value = JSON.stringify(config, null, 2);
    }

    // Load existing geometry configuration when editing
    <?php if ($editPiece && !empty($editPiece['configuration'])): ?>
    document.addEventListener('DOMContentLoaded', function() {
        try {
            const config = <?php echo $editPiece['configuration']; ?>;
            if (config && config.geometries) {
                config.geometries.forEach(geometryData => {
                    geometries.push(geometryData);
                    geometryCount++;
                    renderGeometry(geometryData);
                });
                updateAddButtonState();
                updateConfiguration();
            }
        } catch (e) {
            console.error('Error loading geometry configuration:', e);
        }
    });
    <?php endif; ?>

    function addTextureUrl() {
        const container = document.getElementById('texture-urls-container');
        const input = document.createElement('input');
        input.type = 'url';
        input.name = 'texture_urls[]';
        input.className = 'form-control mb-1';
        input.placeholder = 'https://example.com/texture.png';
        container.appendChild(input);
    }

    function updateSlugPreview() {
        const titleInput = document.getElementById('title');
        const slugInput = document.getElementById('slug');
        const slugPreview = document.getElementById('slug-preview');

        if (!titleInput.value) {
            slugPreview.style.display = 'none';
            return;
        }

        // Only show preview if slug field is empty (auto-generation mode)
        if (!slugInput.value) {
            const previewSlug = titleInput.value
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '')
                .substring(0, 200);

            if (previewSlug) {
                slugPreview.style.display = 'inline';
                slugPreview.querySelector('code').textContent = previewSlug;
            } else {
                slugPreview.style.display = 'none';
            }
        } else {
            slugPreview.style.display = 'none';
        }
    }

    // Initialize slug preview on page load if creating new piece
    <?php if ($action === 'create'): ?>
    document.addEventListener('DOMContentLoaded', function() {
        updateSlugPreview();
    });
    <?php endif; ?>
    </script>

<?php endif; ?>

<?php require_once(__DIR__ . '/includes/footer.php'); ?>
