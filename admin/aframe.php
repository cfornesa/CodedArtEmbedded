<?php
/**
 * A-Frame Art Management
 * CRUD interface for A-Frame art pieces
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

$page_title = 'A-Frame Art Management';

// Handle actions
$action = $_GET['action'] ?? 'list';
$pieceId = $_GET['id'] ?? null;
$error = '';
$success = '';

// Handle delete action (soft delete by default)
if ($action === 'delete' && $pieceId) {
    $permanent = isset($_GET['permanent']) && $_GET['permanent'] === '1';
    $result = deleteArtPieceWithSlug('aframe', $pieceId, $permanent);
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
            'thumbnail_url' => $_POST['thumbnail_url'] ?? '',
            'scene_type' => $_POST['scene_type'] ?? 'custom',
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
            $result = createArtPieceWithSlug('aframe', $data);
        } else {
            $result = updateArtPieceWithSlug('aframe', $pieceId, $data);
        }

        if ($result['success']) {
            $success = $result['message'];
            $action = 'list';
        } else {
            $error = $result['message'];
        }
    }
}

// Get active art pieces for listing (excludes soft-deleted)
$artPieces = getActiveArtPieces('aframe', 'all');

// Get single piece for editing
$editPiece = null;
if ($action === 'edit' && $pieceId) {
    $editPiece = getArtPiece('aframe', $pieceId);
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
            <h2>A-Frame Art Pieces</h2>
            <div>
                <a href="<?php echo url('admin/deleted.php?type=aframe'); ?>" class="btn btn-secondary" style="margin-right: 10px;">
                    🗑️ Deleted Items
                </a>
                <a href="<?php echo url('admin/aframe.php?action=create'); ?>" class="btn btn-success">
                    + Add New Piece
                </a>
            </div>
        </div>

        <?php if (empty($artPieces)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">🎨</div>
                <p>No A-Frame art pieces yet.</p>
                <a href="<?php echo url('admin/aframe.php?action=create'); ?>" class="btn btn-primary">
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
                        <th>Scene Type</th>
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
                            <span class="badge badge-secondary">
                                <?php echo htmlspecialchars(ucfirst($piece['scene_type'])); ?>
                            </span>
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
                                    href="<?php echo url('admin/aframe.php?action=edit&id=' . $piece['id']); ?>"
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
                                    href="<?php echo url('admin/aframe.php?action=delete&id=' . $piece['id']); ?>"
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
            <h2><?php echo $action === 'create' ? 'Add New' : 'Edit'; ?> A-Frame Piece</h2>
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
                    value="<?php echo $editPiece ? htmlspecialchars($editPiece['title']) : ''; ?>"
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
                    value="<?php echo $editPiece ? htmlspecialchars($editPiece['slug']) : ''; ?>"
                    pattern="[a-z0-9-]+"
                    title="Only lowercase letters, numbers, and hyphens"
                >
                <small class="form-help">
                    Leave empty to auto-generate from title.
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
                ><?php echo $editPiece ? htmlspecialchars($editPiece['description']) : ''; ?></textarea>
            </div>

            <!-- File path is auto-generated from slug: /a-frame/view.php?slug=your-slug -->

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
                    value="<?php echo $editPiece ? htmlspecialchars($editPiece['thumbnail_url']) : ''; ?>"
                >
                <small class="form-help">URL to thumbnail image (WEBP, JPG, PNG)</small>
                <img id="thumbnail-preview" style="display: none; max-width: 200px; margin-top: 10px;" />
            </div>

            <div class="form-group">
                <label for="scene_type" class="form-label">Scene Type</label>
                <select id="scene_type" name="scene_type" class="form-control">
                    <option value="space" <?php echo ($editPiece && $editPiece['scene_type'] === 'space') ? 'selected' : ''; ?>>Space</option>
                    <option value="alt" <?php echo ($editPiece && $editPiece['scene_type'] === 'alt') ? 'selected' : ''; ?>>Alt</option>
                    <option value="custom" <?php echo (!$editPiece || $editPiece['scene_type'] === 'custom') ? 'selected' : ''; ?>>Custom</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Texture URLs (optional)</label>
                <div id="texture-urls-container">
                    <?php
                    $textureUrls = [];
                    if ($editPiece && !empty($editPiece['texture_urls'])) {
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
                    placeholder="WebVR, A-Frame, 3D, Animation"
                    value="<?php echo $editPiece ? htmlspecialchars($editPiece['tags']) : ''; ?>"
                >
                <small class="form-help">Comma-separated tags</small>
            </div>

            <div class="form-group">
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="form-control">
                    <option value="active" <?php echo (!$editPiece || $editPiece['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                    <option value="draft" <?php echo ($editPiece && $editPiece['status'] === 'draft') ? 'selected' : ''; ?>>Draft</option>
                    <option value="archived" <?php echo ($editPiece && $editPiece['status'] === 'archived') ? 'selected' : ''; ?>>Archived</option>
                </select>
            </div>

            <div class="form-group">
                <label for="sort_order" class="form-label">Sort Order</label>
                <input
                    type="number"
                    id="sort_order"
                    name="sort_order"
                    class="form-control"
                    value="<?php echo $editPiece ? $editPiece['sort_order'] : 0; ?>"
                >
                <small class="form-help">Lower numbers appear first</small>
            </div>

            <!-- Advanced Shape Configuration Builder -->
            <div class="card" style="margin-top: 30px; border: 2px solid #667eea;">
                <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <h3 style="margin: 0; display: flex; align-items: center; justify-content: space-between;">
                        <span>🎨 Shape Configuration Builder</span>
                        <small style="opacity: 0.9; font-weight: normal;">(Max: 40 shapes)</small>
                    </h3>
                    <p style="margin: 5px 0 0 0; font-size: 14px; opacity: 0.95;">
                        Add and configure 3D shapes for your A-Frame scene
                    </p>
                </div>

                <div style="padding: 20px;">
                    <div id="shapes-container"></div>

                    <button type="button" class="btn btn-success" onclick="addShape()" id="add-shape-btn">
                        + Add New Shape
                    </button>

                    <small class="form-help" style="display: block; margin-top: 10px;">
                        <strong>Tip:</strong> Click "Add New Shape" to add shapes to your scene. Each shape can be fully customized with position, rotation, color, and textures.
                    </small>
                </div>
            </div>

            <!-- Hidden field to store shape configuration as JSON -->
            <input type="hidden" name="configuration_json" id="configuration_json">

            <div class="form-group" style="margin-top: 30px;">
                <button type="submit" class="btn btn-primary btn-lg">
                    <?php echo $action === 'create' ? 'Create Piece' : 'Update Piece'; ?>
                </button>
                <a href="<?php echo url('admin/aframe.php'); ?>" class="btn btn-secondary btn-lg">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    <style>
    .shape-panel {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 15px;
        position: relative;
    }

    .shape-panel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #667eea;
    }

    .shape-panel-title {
        font-weight: bold;
        color: #333;
        font-size: 16px;
    }

    .shape-remove-btn {
        background: #dc3545;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
    }

    .shape-remove-btn:hover {
        background: #c82333;
    }

    .shape-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 15px;
    }

    .shape-field-group {
        display: flex;
        flex-direction: column;
    }

    .shape-field-label {
        font-weight: 600;
        margin-bottom: 5px;
        color: #495057;
        font-size: 14px;
    }

    .shape-field-input {
        padding: 8px 12px;
        border: 1px solid #ced4da;
        border-radius: 4px;
        font-size: 14px;
    }

    .shape-field-input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }

    .xyz-inputs {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
    }

    .xyz-input-group {
        display: flex;
        flex-direction: column;
    }

    .xyz-label {
        font-weight: 600;
        font-size: 12px;
        color: #6c757d;
        margin-bottom: 3px;
    }

    #shapes-container:empty::before {
        content: 'No shapes added yet. Click "Add New Shape" to get started.';
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
    let shapeCount = 0;
    const MAX_SHAPES = 40;
    const shapes = [];

    // A-Frame shape types with their specific properties
    const shapeTypes = {
        'box': { dimensions: true, label: 'Box' },
        'sphere': { radius: true, label: 'Sphere' },
        'cylinder': { radius: true, height: true, label: 'Cylinder' },
        'cone': { radius: true, height: true, label: 'Cone' },
        'plane': { dimensions: true, label: 'Plane' },
        'torus': { radius: true, tube: true, label: 'Torus' },
        'ring': { radiusInner: true, radiusOuter: true, label: 'Ring' },
        'dodecahedron': { radius: true, label: 'Dodecahedron' },
        'octahedron': { radius: true, label: 'Octahedron' },
        'tetrahedron': { radius: true, label: 'Tetrahedron' },
        'icosahedron': { radius: true, label: 'Icosahedron' }
    };

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

    // ============================================
    // SHAPE BUILDER FUNCTIONS
    // ============================================

    function addShape() {
        if (shapeCount >= MAX_SHAPES) {
            alert(`Maximum of ${MAX_SHAPES} shapes reached!`);
            return;
        }

        const id = Date.now();
        shapeCount++;

        const shapeData = {
            id: id,
            type: 'box',
            position: { x: 0, y: 0, z: -5 },
            rotation: { x: 0, y: 0, z: 0 },
            scale: { x: 1, y: 1, z: 1 },
            color: '#4CC3D9',
            texture: '',
            width: 1,
            height: 1,
            depth: 1,
            radius: 1,
            animation: {
                enabled: false,
                property: 'rotation',
                to: '0 360 0',
                dur: 10000,
                loop: true
            }
        };

        shapes.push(shapeData);
        renderShape(shapeData);
        updateAddButtonState();
        updateConfiguration();
    }

    function renderShape(shapeData) {
        const container = document.getElementById('shapes-container');
        const shapeIndex = shapes.findIndex(s => s.id === shapeData.id);

        const panel = document.createElement('div');
        panel.className = 'shape-panel';
        panel.id = `shape-${shapeData.id}`;
        panel.innerHTML = `
            <div class="shape-panel-header">
                <span class="shape-panel-title">Shape #${shapeIndex + 1}</span>
                <button type="button" class="shape-remove-btn" onclick="removeShape(${shapeData.id})">
                    ✕ Remove
                </button>
            </div>

            <!-- Shape Type -->
            <div class="shape-row">
                <div class="shape-field-group">
                    <label class="shape-field-label">Shape Type</label>
                    <select class="shape-field-input" onchange="updateShapeType(${shapeData.id}, this.value)">
                        ${Object.entries(shapeTypes).map(([value, config]) => `
                            <option value="${value}" ${shapeData.type === value ? 'selected' : ''}>
                                ${config.label}
                            </option>
                        `).join('')}
                    </select>
                </div>

                <div class="shape-field-group">
                    <label class="shape-field-label">Color</label>
                    <input type="color" class="shape-field-input" value="${shapeData.color}"
                           onchange="updateShapeProperty(${shapeData.id}, 'color', this.value)">
                </div>

                <div class="shape-field-group">
                    <label class="shape-field-label">Texture URL (optional)</label>
                    <input type="url" class="shape-field-input" value="${shapeData.texture}"
                           placeholder="https://example.com/texture.png"
                           onchange="updateShapeProperty(${shapeData.id}, 'texture', this.value)">
                </div>
            </div>

            <!-- Dimensions (type-specific) -->
            <div class="shape-row" id="dimensions-${shapeData.id}">
                ${renderDimensions(shapeData)}
            </div>

            <!-- Position -->
            <div class="shape-row">
                <div class="shape-field-group">
                    <label class="shape-field-label">Position</label>
                    <div class="xyz-inputs">
                        <div class="xyz-input-group">
                            <label class="xyz-label">X</label>
                            <input type="number" class="shape-field-input" value="${shapeData.position.x}" step="0.1"
                                   onchange="updateShapeXYZ(${shapeData.id}, 'position', 'x', this.value)">
                        </div>
                        <div class="xyz-input-group">
                            <label class="xyz-label">Y</label>
                            <input type="number" class="shape-field-input" value="${shapeData.position.y}" step="0.1"
                                   onchange="updateShapeXYZ(${shapeData.id}, 'position', 'y', this.value)">
                        </div>
                        <div class="xyz-input-group">
                            <label class="xyz-label">Z</label>
                            <input type="number" class="shape-field-input" value="${shapeData.position.z}" step="0.1"
                                   onchange="updateShapeXYZ(${shapeData.id}, 'position', 'z', this.value)">
                        </div>
                    </div>
                </div>

                <!-- Rotation -->
                <div class="shape-field-group">
                    <label class="shape-field-label">Rotation (degrees)</label>
                    <div class="xyz-inputs">
                        <div class="xyz-input-group">
                            <label class="xyz-label">X</label>
                            <input type="number" class="shape-field-input" value="${shapeData.rotation.x}" step="15"
                                   onchange="updateShapeXYZ(${shapeData.id}, 'rotation', 'x', this.value)">
                        </div>
                        <div class="xyz-input-group">
                            <label class="xyz-label">Y</label>
                            <input type="number" class="shape-field-input" value="${shapeData.rotation.y}" step="15"
                                   onchange="updateShapeXYZ(${shapeData.id}, 'rotation', 'y', this.value)">
                        </div>
                        <div class="xyz-input-group">
                            <label class="xyz-label">Z</label>
                            <input type="number" class="shape-field-input" value="${shapeData.rotation.z}" step="15"
                                   onchange="updateShapeXYZ(${shapeData.id}, 'rotation', 'z', this.value)">
                        </div>
                    </div>
                </div>

                <!-- Scale -->
                <div class="shape-field-group">
                    <label class="shape-field-label">Scale</label>
                    <div class="xyz-inputs">
                        <div class="xyz-input-group">
                            <label class="xyz-label">X</label>
                            <input type="number" class="shape-field-input" value="${shapeData.scale.x}" step="0.1" min="0.1"
                                   onchange="updateShapeXYZ(${shapeData.id}, 'scale', 'x', this.value)">
                        </div>
                        <div class="xyz-input-group">
                            <label class="xyz-label">Y</label>
                            <input type="number" class="shape-field-input" value="${shapeData.scale.y}" step="0.1" min="0.1"
                                   onchange="updateShapeXYZ(${shapeData.id}, 'scale', 'y', this.value)">
                        </div>
                        <div class="xyz-input-group">
                            <label class="xyz-label">Z</label>
                            <input type="number" class="shape-field-input" value="${shapeData.scale.z}" step="0.1" min="0.1"
                                   onchange="updateShapeXYZ(${shapeData.id}, 'scale', 'z', this.value)">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Animation -->
            <details style="margin-top: 15px;">
                <summary style="cursor: pointer; font-weight: 600; color: #495057; padding: 10px; background: #e9ecef; border-radius: 4px;">
                    ⚙️ Animation Settings (optional)
                </summary>
                <div style="padding: 15px; background: white; border: 1px solid #dee2e6; border-radius: 0 0 4px 4px;">
                    <div class="shape-row">
                        <div class="shape-field-group">
                            <label class="shape-field-label">
                                <input type="checkbox" ${shapeData.animation.enabled ? 'checked' : ''}
                                       onchange="updateAnimationEnabled(${shapeData.id}, this.checked)">
                                Enable Animation
                            </label>
                        </div>
                        <div class="shape-field-group">
                            <label class="shape-field-label">Property to Animate</label>
                            <select class="shape-field-input" onchange="updateAnimationProperty(${shapeData.id}, 'property', this.value)">
                                <option value="rotation" ${shapeData.animation.property === 'rotation' ? 'selected' : ''}>Rotation</option>
                                <option value="position" ${shapeData.animation.property === 'position' ? 'selected' : ''}>Position</option>
                                <option value="scale" ${shapeData.animation.property === 'scale' ? 'selected' : ''}>Scale</option>
                            </select>
                        </div>
                        <div class="shape-field-group">
                            <label class="shape-field-label">Duration (ms)</label>
                            <input type="number" class="shape-field-input" value="${shapeData.animation.dur}" step="1000" min="0"
                                   onchange="updateAnimationProperty(${shapeData.id}, 'dur', this.value)">
                        </div>
                    </div>
                </div>
            </details>
        `;

        container.appendChild(panel);
    }

    function renderDimensions(shapeData) {
        const config = shapeTypes[shapeData.type];

        if (config.dimensions) {
            return `
                <div class="shape-field-group">
                    <label class="shape-field-label">Width</label>
                    <input type="number" class="shape-field-input" value="${shapeData.width}" step="0.1" min="0.1"
                           onchange="updateShapeProperty(${shapeData.id}, 'width', this.value)">
                </div>
                <div class="shape-field-group">
                    <label class="shape-field-label">Height</label>
                    <input type="number" class="shape-field-input" value="${shapeData.height}" step="0.1" min="0.1"
                           onchange="updateShapeProperty(${shapeData.id}, 'height', this.value)">
                </div>
                <div class="shape-field-group">
                    <label class="shape-field-label">Depth</label>
                    <input type="number" class="shape-field-input" value="${shapeData.depth}" step="0.1" min="0.1"
                           onchange="updateShapeProperty(${shapeData.id}, 'depth', this.value)">
                </div>
            `;
        } else if (config.radius && config.height) {
            return `
                <div class="shape-field-group">
                    <label class="shape-field-label">Radius</label>
                    <input type="number" class="shape-field-input" value="${shapeData.radius}" step="0.1" min="0.1"
                           onchange="updateShapeProperty(${shapeData.id}, 'radius', this.value)">
                </div>
                <div class="shape-field-group">
                    <label class="shape-field-label">Height</label>
                    <input type="number" class="shape-field-input" value="${shapeData.height}" step="0.1" min="0.1"
                           onchange="updateShapeProperty(${shapeData.id}, 'height', this.value)">
                </div>
            `;
        } else if (config.radius) {
            return `
                <div class="shape-field-group">
                    <label class="shape-field-label">Radius</label>
                    <input type="number" class="shape-field-input" value="${shapeData.radius}" step="0.1" min="0.1"
                           onchange="updateShapeProperty(${shapeData.id}, 'radius', this.value)">
                </div>
            `;
        }

        return '';
    }

    function removeShape(id) {
        const index = shapes.findIndex(s => s.id === id);
        if (index > -1) {
            shapes.splice(index, 1);
            shapeCount--;
            document.getElementById(`shape-${id}`).remove();
            updateAddButtonState();
            updateConfiguration();
            renumberShapes();
        }
    }

    function updateShapeType(id, type) {
        const shape = shapes.find(s => s.id === id);
        if (shape) {
            shape.type = type;
            // Re-render dimensions section
            const dimensionsContainer = document.getElementById(`dimensions-${id}`);
            dimensionsContainer.innerHTML = renderDimensions(shape);
            updateConfiguration();
        }
    }

    function updateShapeProperty(id, property, value) {
        const shape = shapes.find(s => s.id === id);
        if (shape) {
            shape[property] = property === 'color' || property === 'texture' ? value : parseFloat(value);
            updateConfiguration();
        }
    }

    function updateShapeXYZ(id, property, axis, value) {
        const shape = shapes.find(s => s.id === id);
        if (shape && shape[property]) {
            shape[property][axis] = parseFloat(value);
            updateConfiguration();
        }
    }

    function updateAnimationEnabled(id, enabled) {
        const shape = shapes.find(s => s.id === id);
        if (shape) {
            shape.animation.enabled = enabled;
            updateConfiguration();
        }
    }

    function updateAnimationProperty(id, property, value) {
        const shape = shapes.find(s => s.id === id);
        if (shape) {
            shape.animation[property] = property === 'dur' ? parseInt(value) : value;
            updateConfiguration();
        }
    }

    function updateConfiguration() {
        const config = {
            shapes: shapes,
            sceneSettings: {
                background: '#ECECEC',
                fog: 'type: linear; color: #AAA'
            }
        };
        document.getElementById('configuration_json').value = JSON.stringify(config, null, 2);
    }

    function updateAddButtonState() {
        const btn = document.getElementById('add-shape-btn');
        if (shapeCount >= MAX_SHAPES) {
            btn.disabled = true;
            btn.textContent = `Maximum Shapes Reached (${MAX_SHAPES}/${MAX_SHAPES})`;
            btn.style.opacity = '0.5';
        } else {
            btn.disabled = false;
            btn.textContent = `+ Add New Shape (${shapeCount}/${MAX_SHAPES})`;
            btn.style.opacity = '1';
        }
    }

    function renumberShapes() {
        shapes.forEach((shape, index) => {
            const panel = document.getElementById(`shape-${shape.id}`);
            if (panel) {
                const title = panel.querySelector('.shape-panel-title');
                title.textContent = `Shape #${index + 1}`;
            }
        });
    }

    // Load existing configuration when editing
    <?php if ($editPiece && !empty($editPiece['configuration'])): ?>
    document.addEventListener('DOMContentLoaded', function() {
        try {
            const config = <?php echo $editPiece['configuration']; ?>;
            if (config && config.shapes) {
                config.shapes.forEach(shapeData => {
                    shapes.push(shapeData);
                    shapeCount++;
                    renderShape(shapeData);
                });
                updateAddButtonState();
                updateConfiguration();
            }
        } catch (e) {
            console.error('Error loading shape configuration:', e);
        }
    });
    <?php endif; ?>
    </script>

<?php endif; ?>

<?php require_once(__DIR__ . '/includes/footer.php'); ?>
