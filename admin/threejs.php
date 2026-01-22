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
                <div style="position: relative;">
                    <input
                        type="text"
                        id="slug"
                        name="slug"
                        class="form-control"
                        placeholder="auto-generated-from-title"
                        value="<?php echo $formData ? htmlspecialchars($formData['slug']) : ($editPiece ? htmlspecialchars($editPiece['slug']) : ''); ?>"
                        pattern="[a-z0-9-]+"
                        title="Only lowercase letters, numbers, and hyphens"
                        onkeyup="checkSlugAvailability()"
                        onblur="checkSlugAvailability()"
                    >
                    <span id="slug-status" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); display: none;"></span>
                </div>
                <small class="form-help">
                    Leave empty to auto-generate from title. Must be unique.
                    <span id="slug-preview" style="display: none;">Preview: <code></code></span>
                    <span id="slug-feedback" style="display: none; margin-left: 10px;"></span>
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
                    value="<?php echo $formData ? htmlspecialchars($formData['thumbnail_url'] ?? '') : ($editPiece ? htmlspecialchars($editPiece['thumbnail_url'] ?? '') : ''); ?>"
                >
                <small class="form-help">URL to thumbnail image (WEBP, JPG, PNG)</small>
                <img id="thumbnail-preview" style="display: none; max-width: 200px; margin-top: 10px;" />
            </div>

            <div class="form-group">
                <label class="form-label">Background Image URLs (optional)</label>
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
                        placeholder="https://example.com/background.png"
                        value="<?php echo htmlspecialchars($url); ?>"
                    >
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn-sm btn-secondary mt-1" onclick="addTextureUrl()">
                    + Add Another Background Image URL
                </button>
                <small class="form-help">Background image URLs for the scene. If multiple URLs are provided, one will be randomly selected each time the piece loads. Individual geometry textures are configured in the Geometry Builder below.</small>
            </div>

            <div class="form-group">
                <label for="tags" class="form-label">Tags</label>
                <input
                    type="text"
                    id="tags"
                    name="tags"
                    class="form-control"
                    placeholder="Three.js, 3D, WebGL, Animation"
                    value="<?php echo $formData ? htmlspecialchars($formData['tags'] ?? '') : ($editPiece ? htmlspecialchars($editPiece['tags'] ?? '') : ''); ?>"
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

    /* Dual-thumb slider styling (matching A-Frame implementation) */
    .dual-thumb-min,
    .dual-thumb-max {
        -webkit-appearance: none;
        appearance: none;
        height: 8px;
        background: transparent;
        outline: none;
    }

    .dual-thumb-min::-webkit-slider-thumb,
    .dual-thumb-max::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #764ba2;
        cursor: pointer;
        border: 2px solid white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        pointer-events: auto;
    }

    .dual-thumb-min::-moz-range-thumb,
    .dual-thumb-max::-moz-range-thumb {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #764ba2;
        cursor: pointer;
        border: 2px solid white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        pointer-events: auto;
    }

    .scale-range-highlight {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        height: 8px;
        background: #28a745;
        pointer-events: none;
        border-radius: 4px;
        z-index: 0;
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
            opacity: 1.0,  // NEW: Per-geometry opacity (0-1 range)
            width: 1,
            height: 1,
            depth: 1,
            radius: 1,
            wireframe: false,
            material: 'MeshStandardMaterial',
            animation: {
                // NEW: Granular animation controls (matching A-Frame pattern)
                rotation: {
                    enabled: false,
                    counterclockwise: false,
                    duration: 10000
                },
                position: {
                    x: { enabled: false, range: 0, duration: 10000 },
                    y: { enabled: false, range: 0, duration: 10000 },
                    z: { enabled: false, range: 0, duration: 10000 }
                },
                scale: {
                    enabled: false,
                    min: 1.0,
                    max: 1.0,
                    duration: 10000
                }
            }
        };

        geometries.push(geometryData);
        renderGeometry(geometryData);

        // Initialize dual-thumb scale slider UI
        setTimeout(() => updateDualThumbScaleUI(geometryData.id), 100);

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

            <div class="geometry-row">
                <div class="geometry-field-group">
                    <label class="geometry-field-label">Opacity</label>
                    <input type="range" min="0" max="1" step="0.01" class="geometry-field-input"
                           value="${geometryData.opacity || 1.0}"
                           oninput="updateOpacity(${geometryData.id}, this.value)">
                    <span id="opacity-value-${geometryData.id}" style="margin-left: 10px; font-weight: 600; color: #764ba2;">
                        ${(geometryData.opacity || 1.0).toFixed(2)}
                    </span>
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

            <!-- GRANULAR ANIMATION CONTROLS (matching A-Frame pattern) -->
            <details style="margin-top: 15px;">
                <summary style="cursor: pointer; font-weight: 600; color: #764ba2;">📐 Rotation Animation</summary>
                <div style="margin-top: 10px; padding: 15px; background: white; border-radius: 4px;">
                    <div class="geometry-row">
                        <div class="geometry-field-group">
                            <label class="geometry-field-label">
                                <input type="checkbox" ${geometryData.animation.rotation?.enabled ? 'checked' : ''}
                                       onchange="updateRotationAnimation(${geometryData.id}, 'enabled', this.checked)">
                                Enable Rotation
                            </label>
                        </div>
                        <div class="geometry-field-group">
                            <label class="geometry-field-label">
                                <input type="checkbox" ${geometryData.animation.rotation?.counterclockwise ? 'checked' : ''}
                                       onchange="updateRotationAnimation(${geometryData.id}, 'counterclockwise', this.checked)">
                                Enable Counterclockwise
                            </label>
                        </div>
                    </div>
                    <div class="geometry-row">
                        <div class="geometry-field-group">
                            <label class="geometry-field-label">Duration (milliseconds)</label>
                            <input type="range" min="100" max="10000" step="100" class="geometry-field-input"
                                   value="${geometryData.animation.rotation?.duration || 10000}"
                                   oninput="updateRotationAnimation(${geometryData.id}, 'duration', this.value)">
                            <span id="rotation-duration-${geometryData.id}" style="margin-left: 10px; font-weight: 600; color: #764ba2;">
                                ${geometryData.animation.rotation?.duration || 10000}ms
                            </span>
                        </div>
                    </div>
                </div>
            </details>

            <details style="margin-top: 10px;">
                <summary style="cursor: pointer; font-weight: 600; color: #764ba2;">📍 Position Animation</summary>
                <div style="margin-top: 10px; padding: 15px; background: white; border-radius: 4px;">
                    <!-- X-axis (Left/Right) -->
                    <div style="margin-bottom: 15px; padding: 10px; background: #f8f9fa; border-radius: 4px;">
                        <div class="geometry-row">
                            <div class="geometry-field-group">
                                <label class="geometry-field-label">
                                    <input type="checkbox" ${geometryData.animation.position?.x?.enabled ? 'checked' : ''}
                                           onchange="updatePositionAnimation(${geometryData.id}, 'x', 'enabled', this.checked)">
                                    Enable X (Left/Right) Movement
                                </label>
                            </div>
                        </div>
                        <div class="geometry-row">
                            <div class="geometry-field-group">
                                <label class="geometry-field-label">Range (±units)</label>
                                <input type="range" min="0" max="10" step="0.1" class="geometry-field-input"
                                       value="${geometryData.animation.position?.x?.range || 0}"
                                       oninput="updatePositionAnimation(${geometryData.id}, 'x', 'range', this.value)">
                                <span id="position-x-range-${geometryData.id}" style="margin-left: 10px; font-weight: 600; color: #764ba2;">
                                    ±${geometryData.animation.position?.x?.range || 0} units
                                </span>
                            </div>
                            <div class="geometry-field-group">
                                <label class="geometry-field-label">Duration (milliseconds)</label>
                                <input type="range" min="100" max="10000" step="100" class="geometry-field-input"
                                       value="${geometryData.animation.position?.x?.duration || 10000}"
                                       oninput="updatePositionAnimation(${geometryData.id}, 'x', 'duration', this.value)">
                                <span id="position-x-duration-${geometryData.id}" style="margin-left: 10px; font-weight: 600; color: #764ba2;">
                                    ${geometryData.animation.position?.x?.duration || 10000}ms
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Y-axis (Up/Down) -->
                    <div style="margin-bottom: 15px; padding: 10px; background: #f8f9fa; border-radius: 4px;">
                        <div class="geometry-row">
                            <div class="geometry-field-group">
                                <label class="geometry-field-label">
                                    <input type="checkbox" ${geometryData.animation.position?.y?.enabled ? 'checked' : ''}
                                           onchange="updatePositionAnimation(${geometryData.id}, 'y', 'enabled', this.checked)">
                                    Enable Y (Up/Down) Movement
                                </label>
                            </div>
                        </div>
                        <div class="geometry-row">
                            <div class="geometry-field-group">
                                <label class="geometry-field-label">Range (±units)</label>
                                <input type="range" min="0" max="10" step="0.1" class="geometry-field-input"
                                       value="${geometryData.animation.position?.y?.range || 0}"
                                       oninput="updatePositionAnimation(${geometryData.id}, 'y', 'range', this.value)">
                                <span id="position-y-range-${geometryData.id}" style="margin-left: 10px; font-weight: 600; color: #764ba2;">
                                    ±${geometryData.animation.position?.y?.range || 0} units
                                </span>
                            </div>
                            <div class="geometry-field-group">
                                <label class="geometry-field-label">Duration (milliseconds)</label>
                                <input type="range" min="100" max="10000" step="100" class="geometry-field-input"
                                       value="${geometryData.animation.position?.y?.duration || 10000}"
                                       oninput="updatePositionAnimation(${geometryData.id}, 'y', 'duration', this.value)">
                                <span id="position-y-duration-${geometryData.id}" style="margin-left: 10px; font-weight: 600; color: #764ba2;">
                                    ${geometryData.animation.position?.y?.duration || 10000}ms
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Z-axis (Forward/Back) -->
                    <div style="margin-bottom: 15px; padding: 10px; background: #f8f9fa; border-radius: 4px;">
                        <div class="geometry-row">
                            <div class="geometry-field-group">
                                <label class="geometry-field-label">
                                    <input type="checkbox" ${geometryData.animation.position?.z?.enabled ? 'checked' : ''}
                                           onchange="updatePositionAnimation(${geometryData.id}, 'z', 'enabled', this.checked)">
                                    Enable Z (Forward/Back) Movement
                                </label>
                            </div>
                        </div>
                        <div class="geometry-row">
                            <div class="geometry-field-group">
                                <label class="geometry-field-label">Range (±units)</label>
                                <input type="range" min="0" max="10" step="0.1" class="geometry-field-input"
                                       value="${geometryData.animation.position?.z?.range || 0}"
                                       oninput="updatePositionAnimation(${geometryData.id}, 'z', 'range', this.value)">
                                <span id="position-z-range-${geometryData.id}" style="margin-left: 10px; font-weight: 600; color: #764ba2;">
                                    ±${geometryData.animation.position?.z?.range || 0} units
                                </span>
                            </div>
                            <div class="geometry-field-group">
                                <label class="geometry-field-label">Duration (milliseconds)</label>
                                <input type="range" min="100" max="10000" step="100" class="geometry-field-input"
                                       value="${geometryData.animation.position?.z?.duration || 10000}"
                                       oninput="updatePositionAnimation(${geometryData.id}, 'z', 'duration', this.value)">
                                <span id="position-z-duration-${geometryData.id}" style="margin-left: 10px; font-weight: 600; color: #764ba2;">
                                    ${geometryData.animation.position?.z?.duration || 10000}ms
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </details>

            <details style="margin-top: 10px;">
                <summary style="cursor: pointer; font-weight: 600; color: #764ba2;">📏 Scale Animation</summary>
                <div style="margin-top: 10px; padding: 15px; background: white; border-radius: 4px;">
                    <div class="geometry-row">
                        <div class="geometry-field-group">
                            <label class="geometry-field-label">
                                <input type="checkbox" ${geometryData.animation.scale?.enabled ? 'checked' : ''}
                                       onchange="updateScaleAnimation(${geometryData.id}, 'enabled', this.checked)">
                                Enable Scale Animation
                            </label>
                        </div>
                    </div>
                    <div class="geometry-row">
                        <div class="geometry-field-group" style="position: relative;">
                            <label class="geometry-field-label">Scale Range (Min/Max)</label>
                            <div style="position: relative; height: 40px; margin-bottom: 10px;">
                                <!-- Dual-thumb slider implementation -->
                                <input type="range" min="0.1" max="10" step="0.1" class="geometry-field-input dual-thumb-min"
                                       id="scale-min-${geometryData.id}"
                                       value="${geometryData.animation.scale?.min || 1.0}"
                                       oninput="updateDualThumbScale(${geometryData.id}, 'min', this.value)"
                                       style="position: absolute; pointer-events: none; width: 100%;">
                                <input type="range" min="0.1" max="10" step="0.1" class="geometry-field-input dual-thumb-max"
                                       id="scale-max-${geometryData.id}"
                                       value="${geometryData.animation.scale?.max || 1.0}"
                                       oninput="updateDualThumbScale(${geometryData.id}, 'max', this.value)"
                                       style="position: absolute; pointer-events: auto; width: 100%;">
                                <div id="scale-range-highlight-${geometryData.id}" class="scale-range-highlight"></div>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 14px; font-weight: 600; color: #764ba2;">
                                <span>Min: <span id="scale-min-value-${geometryData.id}">${(geometryData.animation.scale?.min || 1.0).toFixed(1)}</span>x</span>
                                <span>Max: <span id="scale-max-value-${geometryData.id}">${(geometryData.animation.scale?.max || 1.0).toFixed(1)}</span>x</span>
                            </div>
                            <div id="scale-validation-${geometryData.id}" style="color: #dc3545; font-size: 12px; margin-top: 5px; display: none;">
                                ⚠️ Warning: Min should be less than Max
                            </div>
                        </div>
                        <div class="geometry-field-group">
                            <label class="geometry-field-label">Duration (milliseconds)</label>
                            <input type="range" min="100" max="10000" step="100" class="geometry-field-input"
                                   value="${geometryData.animation.scale?.duration || 10000}"
                                   oninput="updateScaleAnimation(${geometryData.id}, 'duration', this.value)">
                            <span id="scale-duration-${geometryData.id}" style="margin-left: 10px; font-weight: 600; color: #764ba2;">
                                ${geometryData.animation.scale?.duration || 10000}ms
                            </span>
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

    // Update opacity
    function updateOpacity(id, value) {
        const geometry = geometries.find(g => g.id === id);
        if (geometry) {
            geometry.opacity = parseFloat(value);
            const valueSpan = document.getElementById(`opacity-value-${id}`);
            if (valueSpan) {
                valueSpan.textContent = parseFloat(value).toFixed(2);
            }
            updateConfiguration();
        }
    }

    // Update rotation animation
    function updateRotationAnimation(id, field, value) {
        const geometry = geometries.find(g => g.id === id);
        if (geometry) {
            if (field === 'enabled' || field === 'counterclockwise') {
                geometry.animation.rotation[field] = Boolean(value);
            } else if (field === 'duration') {
                geometry.animation.rotation[field] = parseInt(value);
                const durationSpan = document.getElementById(`rotation-duration-${id}`);
                if (durationSpan) {
                    durationSpan.textContent = value + 'ms';
                }
            }
            updateConfiguration();
        }
    }

    // Update position animation
    function updatePositionAnimation(id, axis, field, value) {
        const geometry = geometries.find(g => g.id === id);
        if (geometry) {
            if (field === 'enabled') {
                geometry.animation.position[axis][field] = Boolean(value);
            } else if (field === 'range') {
                geometry.animation.position[axis][field] = parseFloat(value);
                const rangeSpan = document.getElementById(`position-${axis}-range-${id}`);
                if (rangeSpan) {
                    rangeSpan.textContent = `±${value} units`;
                }
            } else if (field === 'duration') {
                geometry.animation.position[axis][field] = parseInt(value);
                const durationSpan = document.getElementById(`position-${axis}-duration-${id}`);
                if (durationSpan) {
                    durationSpan.textContent = value + 'ms';
                }
            }
            updateConfiguration();
        }
    }

    // Update scale animation
    function updateScaleAnimation(id, field, value) {
        const geometry = geometries.find(g => g.id === id);
        if (geometry) {
            if (field === 'enabled') {
                geometry.animation.scale[field] = Boolean(value);
            } else if (field === 'duration') {
                geometry.animation.scale[field] = parseInt(value);
                const durationSpan = document.getElementById(`scale-duration-${id}`);
                if (durationSpan) {
                    durationSpan.textContent = value + 'ms';
                }
            }
            updateConfiguration();
        }
    }

    // Dual-thumb scale slider (matching A-Frame implementation)
    function updateDualThumbScale(id, thumb, value) {
        const geometry = geometries.find(g => g.id === id);
        if (geometry) {
            value = parseFloat(value);

            // Auto-swap if min > max
            if (thumb === 'min' && value > geometry.animation.scale.max) {
                geometry.animation.scale.max = value;
                document.getElementById(`scale-max-${id}`).value = value;
                document.getElementById(`scale-max-value-${id}`).textContent = value.toFixed(1);
            } else if (thumb === 'max' && value < geometry.animation.scale.min) {
                geometry.animation.scale.min = value;
                document.getElementById(`scale-min-${id}`).value = value;
                document.getElementById(`scale-min-value-${id}`).textContent = value.toFixed(1);
            }

            geometry.animation.scale[thumb] = value;

            // Update display
            document.getElementById(`scale-${thumb}-value-${id}`).textContent = value.toFixed(1);

            // Update visual range highlight
            updateDualThumbScaleUI(id);

            // Validate min < max
            validateScaleMinMax(id);

            updateConfiguration();
        }
    }

    // Update dual-thumb scale slider visual highlight
    function updateDualThumbScaleUI(id) {
        const geometry = geometries.find(g => g.id === id);
        if (geometry) {
            const min = geometry.animation.scale.min;
            const max = geometry.animation.scale.max;
            const highlight = document.getElementById(`scale-range-highlight-${id}`);

            if (highlight) {
                const minPercent = ((min - 0.1) / (10 - 0.1)) * 100;
                const maxPercent = ((max - 0.1) / (10 - 0.1)) * 100;

                highlight.style.left = minPercent + '%';
                highlight.style.width = (maxPercent - minPercent) + '%';
            }
        }
    }

    // Validate scale min/max
    function validateScaleMinMax(id) {
        const geometry = geometries.find(g => g.id === id);
        if (geometry) {
            const validationDiv = document.getElementById(`scale-validation-${id}`);
            if (validationDiv) {
                if (geometry.animation.scale.min > geometry.animation.scale.max) {
                    validationDiv.style.display = 'block';
                } else {
                    validationDiv.style.display = 'none';
                }
            }
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

    // Migrate old animation format to new granular format (backward compatibility)
    function migrateAnimationFormat(geometry) {
        if (!geometry.animation) return;

        // Check if using old format (has 'enabled' and 'property' fields directly)
        if (geometry.animation.hasOwnProperty('enabled') && geometry.animation.hasOwnProperty('property')) {
            console.log(`Migrating animation from old format to granular format for geometry ${geometry.id}`);

            const oldEnabled = geometry.animation.enabled;
            const oldProperty = geometry.animation.property || 'rotation.y';
            const oldSpeed = geometry.animation.speed || 0.01;

            // Convert speed to duration (approximate)
            const estimatedDuration = Math.max(100, Math.min(10000, Math.round(100 / oldSpeed)));

            // Create new animation structure
            geometry.animation = {
                rotation: {
                    enabled: false,
                    counterclockwise: false,
                    duration: estimatedDuration
                },
                position: {
                    x: { enabled: false, range: 0, duration: estimatedDuration },
                    y: { enabled: false, range: 0, duration: estimatedDuration },
                    z: { enabled: false, range: 0, duration: estimatedDuration }
                },
                scale: {
                    enabled: false,
                    min: 1.0,
                    max: 1.0,
                    duration: estimatedDuration
                }
            };

            // Map old property to new structure
            if (oldEnabled) {
                if (oldProperty.includes('rotation')) {
                    geometry.animation.rotation.enabled = true;
                } else if (oldProperty === 'position.y') {
                    geometry.animation.position.y.enabled = true;
                    geometry.animation.position.y.range = 2; // Default bounce range
                }
            }
        }

        // Ensure all required fields exist (progressive enhancement)
        if (!geometry.animation.rotation) {
            geometry.animation.rotation = { enabled: false, counterclockwise: false, duration: 10000 };
        }
        if (!geometry.animation.position) {
            geometry.animation.position = {
                x: { enabled: false, range: 0, duration: 10000 },
                y: { enabled: false, range: 0, duration: 10000 },
                z: { enabled: false, range: 0, duration: 10000 }
            };
        }
        if (!geometry.animation.scale) {
            geometry.animation.scale = { enabled: false, min: 1.0, max: 1.0, duration: 10000 };
        }

        // Ensure opacity exists
        if (geometry.opacity === undefined) {
            geometry.opacity = 1.0;
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
                    // Migrate old animation format to new granular format
                    migrateAnimationFormat(geometryData);

                    geometries.push(geometryData);
                    geometryCount++;
                    renderGeometry(geometryData);

                    // Initialize dual-thumb scale slider UI
                    if (geometryData.animation.scale) {
                        setTimeout(() => updateDualThumbScaleUI(geometryData.id), 100);
                    }
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

    // Real-time slug availability checking
    let slugCheckTimeout = null;
    function checkSlugAvailability() {
        const slugInput = document.getElementById('slug');
        const slugStatus = document.getElementById('slug-status');
        const slugFeedback = document.getElementById('slug-feedback');
        const slug = slugInput.value.trim();

        // Clear previous timeout
        if (slugCheckTimeout) {
            clearTimeout(slugCheckTimeout);
        }

        // Empty slug is valid (will be auto-generated)
        if (slug === '') {
            slugStatus.style.display = 'none';
            slugFeedback.style.display = 'none';
            slugInput.style.borderColor = '';
            return;
        }

        // Show checking indicator
        slugStatus.innerHTML = '⏳';
        slugStatus.style.display = 'block';
        slugStatus.style.color = '#6c757d';
        slugFeedback.style.display = 'none';

        // Debounce the AJAX request
        slugCheckTimeout = setTimeout(() => {
            const excludeId = <?php echo $editPiece ? $editPiece['id'] : 'null'; ?>;
            const url = '<?php echo url('admin/includes/check-slug.php'); ?>?slug=' +
                        encodeURIComponent(slug) +
                        '&type=threejs' +
                        (excludeId ? '&exclude_id=' + excludeId : '');

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.valid && data.available) {
                        // Slug is available
                        slugStatus.innerHTML = '✓';
                        slugStatus.style.color = '#28a745';
                        slugFeedback.textContent = data.message;
                        slugFeedback.style.color = '#28a745';
                        slugFeedback.style.display = 'inline';
                        slugInput.style.borderColor = '#28a745';
                    } else {
                        // Slug is not available or invalid
                        slugStatus.innerHTML = '✗';
                        slugStatus.style.color = '#dc3545';
                        slugFeedback.textContent = data.message;
                        slugFeedback.style.color = '#dc3545';
                        slugFeedback.style.display = 'inline';
                        slugInput.style.borderColor = '#dc3545';
                    }
                })
                .catch(error => {
                    console.error('Slug check error:', error);
                    slugStatus.style.display = 'none';
                    slugFeedback.style.display = 'none';
                    slugInput.style.borderColor = '';
                });
        }, 500); // 500ms debounce delay
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
