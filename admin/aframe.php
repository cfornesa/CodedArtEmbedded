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

// Preserve form data on validation errors
$formData = null;

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
            'sky_color' => $_POST['sky_color'] ?? '#ECECEC',
            'sky_texture' => $_POST['sky_texture'] ?? '',
            'sky_opacity' => $_POST['sky_opacity'] ?? '1.0',
            'ground_color' => $_POST['ground_color'] ?? '#7BC8A4',
            'ground_texture' => $_POST['ground_texture'] ?? '',
            'ground_opacity' => $_POST['ground_opacity'] ?? '1.0',
            'tags' => $_POST['tags'] ?? '',
            'status' => $_POST['status'] ?? 'active',
            'sort_order' => $_POST['sort_order'] ?? 0
        ];

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
            // Preserve form data so user doesn't lose their work
            $formData = $data;
            // Preserve configuration JSON
            if (isset($_POST['configuration_json'])) {
                $formData['configuration_json_raw'] = $_POST['configuration_json'];
            }
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
                    value="<?php echo $formData ? htmlspecialchars($formData['thumbnail_url'] ?? '') : ($editPiece ? htmlspecialchars($editPiece['thumbnail_url'] ?? '') : ''); ?>"
                >
                <small class="form-help">URL to thumbnail image (WEBP, JPG, PNG)</small>
                <img id="thumbnail-preview" style="display: none; max-width: 200px; margin-top: 10px;" />
            </div>

            <div class="form-group">
                <label for="scene_type" class="form-label">Scene Type</label>
                <select id="scene_type" name="scene_type" class="form-control">
                    <?php $currentSceneType = $formData ? $formData['scene_type'] : ($editPiece ? $editPiece['scene_type'] : 'custom'); ?>
                    <option value="space" <?php echo $currentSceneType === 'space' ? 'selected' : ''; ?>>Space</option>
                    <option value="alt" <?php echo $currentSceneType === 'alt' ? 'selected' : ''; ?>>Alt</option>
                    <option value="custom" <?php echo $currentSceneType === 'custom' ? 'selected' : ''; ?>>Custom</option>
                </select>
            </div>

            <!-- Scene Environment Settings -->
            <div class="card" style="margin-top: 20px; border: 2px solid #4a90e2;">
                <div class="card-header" style="background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%); color: white;">
                    <h3 style="margin: 0;">🌅 Scene Environment</h3>
                    <p style="margin: 5px 0 0 0; font-size: 14px; opacity: 0.95;">Configure sky (background) and ground (foreground) appearance</p>
                </div>

                <div style="padding: 20px;">
                    <div class="form-group">
                        <label for="sky_color" class="form-label">Sky Color (Background)</label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input
                                type="color"
                                id="sky_color"
                                name="sky_color"
                                class="form-control"
                                style="width: 80px; height: 40px; padding: 2px;"
                                value="<?php echo $formData ? ($formData['sky_color'] ?? '#ECECEC') : ($editPiece && !empty($editPiece['sky_color']) ? $editPiece['sky_color'] : '#ECECEC'); ?>"
                            >
                            <input
                                type="text"
                                class="form-control"
                                placeholder="#ECECEC"
                                value="<?php echo $formData ? ($formData['sky_color'] ?? '#ECECEC') : ($editPiece && !empty($editPiece['sky_color']) ? $editPiece['sky_color'] : '#ECECEC'); ?>"
                                onchange="document.getElementById('sky_color').value = this.value"
                                style="flex: 1;"
                            >
                        </div>
                        <small class="form-help">The color of the sky/background (distant environment)</small>
                    </div>

                    <div class="form-group">
                        <label for="sky_texture" class="form-label">Sky Texture URL (optional)</label>
                        <input
                            type="url"
                            id="sky_texture"
                            name="sky_texture"
                            class="form-control"
                            placeholder="https://example.com/sky-texture.jpg"
                            value="<?php echo $formData ? ($formData['sky_texture'] ?? '') : ($editPiece ? ($editPiece['sky_texture'] ?? '') : ''); ?>"
                        >
                        <small class="form-help">Optional: Apply a texture/image to the sky sphere (360° panorama works best)</small>
                    </div>

                    <div class="form-group">
                        <label for="sky_opacity" class="form-label">Sky Color Opacity</label>
                        <div style="display: flex; gap: 15px; align-items: center;">
                            <input
                                type="range"
                                id="sky_opacity"
                                name="sky_opacity"
                                class="form-control"
                                min="0"
                                max="1"
                                step="0.01"
                                value="<?php echo $formData ? ($formData['sky_opacity'] ?? '1.0') : ($editPiece && isset($editPiece['sky_opacity']) ? $editPiece['sky_opacity'] : '1.0'); ?>"
                                oninput="document.getElementById('sky_opacity_value').textContent = this.value"
                                style="flex: 1;"
                            >
                            <span id="sky_opacity_value" style="min-width: 40px; font-weight: 600; color: #495057;">
                                <?php echo $formData ? ($formData['sky_opacity'] ?? '1.0') : ($editPiece && isset($editPiece['sky_opacity']) ? $editPiece['sky_opacity'] : '1.0'); ?>
                            </span>
                        </div>
                        <small class="form-help">0 = fully transparent, 1 = fully opaque (default: 1.0)</small>
                    </div>

                    <div class="form-group">
                        <label for="ground_color" class="form-label">Ground Color (Foreground)</label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input
                                type="color"
                                id="ground_color"
                                name="ground_color"
                                class="form-control"
                                style="width: 80px; height: 40px; padding: 2px;"
                                value="<?php echo $formData ? ($formData['ground_color'] ?? '#7BC8A4') : ($editPiece && !empty($editPiece['ground_color']) ? $editPiece['ground_color'] : '#7BC8A4'); ?>"
                            >
                            <input
                                type="text"
                                class="form-control"
                                placeholder="#7BC8A4"
                                value="<?php echo $formData ? ($formData['ground_color'] ?? '#7BC8A4') : ($editPiece && !empty($editPiece['ground_color']) ? $editPiece['ground_color'] : '#7BC8A4'); ?>"
                                onchange="document.getElementById('ground_color').value = this.value"
                                style="flex: 1;"
                            >
                        </div>
                        <small class="form-help">The color of the ground plane (floor/foreground)</small>
                    </div>

                    <div class="form-group">
                        <label for="ground_texture" class="form-label">Ground Texture URL (optional)</label>
                        <input
                            type="url"
                            id="ground_texture"
                            name="ground_texture"
                            class="form-control"
                            placeholder="https://example.com/ground-texture.jpg"
                            value="<?php echo $formData ? ($formData['ground_texture'] ?? '') : ($editPiece ? ($editPiece['ground_texture'] ?? '') : ''); ?>"
                        >
                        <small class="form-help">Optional: Apply a texture/image to the ground plane (tiling textures work best)</small>
                    </div>

                    <div class="form-group">
                        <label for="ground_opacity" class="form-label">Ground Color Opacity</label>
                        <div style="display: flex; gap: 15px; align-items: center;">
                            <input
                                type="range"
                                id="ground_opacity"
                                name="ground_opacity"
                                class="form-control"
                                min="0"
                                max="1"
                                step="0.01"
                                value="<?php echo $formData ? ($formData['ground_opacity'] ?? '1.0') : ($editPiece && isset($editPiece['ground_opacity']) ? $editPiece['ground_opacity'] : '1.0'); ?>"
                                oninput="document.getElementById('ground_opacity_value').textContent = this.value"
                                style="flex: 1;"
                            >
                            <span id="ground_opacity_value" style="min-width: 40px; font-weight: 600; color: #495057;">
                                <?php echo $formData ? ($formData['ground_opacity'] ?? '1.0') : ($editPiece && isset($editPiece['ground_opacity']) ? $editPiece['ground_opacity'] : '1.0'); ?>
                            </span>
                        </div>
                        <small class="form-help">0 = fully transparent, 1 = fully opaque (default: 1.0)</small>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="tags" class="form-label">Tags</label>
                <input
                    type="text"
                    id="tags"
                    name="tags"
                    class="form-control"
                    placeholder="WebVR, A-Frame, 3D, Animation"
                    value="<?php echo $formData ? htmlspecialchars($formData['tags'] ?? '') : ($editPiece ? htmlspecialchars($editPiece['tags'] ?? '') : ''); ?>"
                >
                <small class="form-help">Comma-separated tags</small>
            </div>

            <div class="form-group">
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="form-control">
                    <?php $currentStatus = $formData ? $formData['status'] : ($editPiece ? $editPiece['status'] : 'active'); ?>
                    <option value="active" <?php echo $currentStatus === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="draft" <?php echo $currentStatus === 'draft' ? 'selected' : ''; ?>>Draft</option>
                    <option value="archived" <?php echo $currentStatus === 'archived' ? 'selected' : ''; ?>>Archived</option>
                </select>
            </div>

            <div class="form-group">
                <label for="sort_order" class="form-label">Sort Order</label>
                <input
                    type="number"
                    id="sort_order"
                    name="sort_order"
                    class="form-control"
                    value="<?php echo $formData ? $formData['sort_order'] : ($editPiece ? $editPiece['sort_order'] : 0); ?>"
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
                <button type="button" id="preview-btn" class="btn btn-info btn-lg" style="margin-left: 10px;" onclick="showPreview()">
                    🔍 Show Preview
                </button>
            </div>
        </form>

        <!-- Preview Section -->
        <div id="preview-section" style="display: none; margin-top: 30px; border: 3px solid #17a2b8; border-radius: 8px; padding: 20px; background: #f8f9fa;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin: 0; color: #17a2b8;">
                    🔍 Live Preview
                    <small style="font-size: 14px; color: #6c757d; font-weight: normal; margin-left: 10px;">
                        (Changes not saved until you click "Update Piece")
                    </small>
                </h3>
                <button type="button" class="btn btn-sm btn-secondary" onclick="hidePreview()">Close Preview</button>
            </div>
            <div style="background: #fff; border: 2px solid #dee2e6; border-radius: 4px; overflow: hidden; position: relative;">
                <iframe id="preview-iframe" src="" style="width: 100%; height: 600px; border: none;"></iframe>
                <div id="preview-loading" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); display: none; text-align: center; background: rgba(255,255,255,0.95); padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <div style="font-size: 24px; margin-bottom: 10px;">⏳</div>
                    <div style="font-weight: 600; color: #495057;">Loading preview...</div>
                </div>
            </div>
        </div>
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
                        '&type=aframe' +
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
            opacity: 1.0,  // NEW: Per-shape opacity
            width: 1,
            height: 1,
            depth: 1,
            radius: 1,
            animation: {
                rotation: {  // CHANGED: Granular animation control
                    enabled: false,
                    degrees: 360,
                    duration: 10000
                },
                position: {  // NEW: Position animation
                    enabled: false,
                    axis: 'y',
                    distance: 0,
                    duration: 10000
                },
                scale: {  // NEW: Scale animation
                    enabled: false,
                    min: 1.0,
                    max: 1.0,
                    duration: 10000
                }
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

                <div class="shape-field-group">
                    <label class="shape-field-label">Opacity</label>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="range" class="shape-field-input"
                               min="0" max="1" step="0.01"
                               value="${shapeData.opacity}"
                               oninput="this.nextElementSibling.textContent = this.value; updateShapeProperty(${shapeData.id}, 'opacity', parseFloat(this.value))"
                               style="flex: 1;">
                        <span style="min-width: 40px; font-weight: 600; color: #495057;">${shapeData.opacity}</span>
                    </div>
                    <small style="display: block; margin-top: 5px; color: #6c757d; font-size: 0.875em;">0 = transparent, 1 = opaque</small>
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

            <!-- Animation Settings -->
            <div style="margin-top: 15px; border: 1px solid #dee2e6; border-radius: 4px; overflow: hidden;">
                <div style="background: #e9ecef; padding: 10px; font-weight: 600; color: #495057;">
                    ⚙️ Animation Settings (optional)
                </div>

                <!-- Rotation Animation -->
                <details style="border-bottom: 1px solid #dee2e6;">
                    <summary style="cursor: pointer; padding: 10px; background: #f8f9fa; font-weight: 500; color: #495057;">
                        📐 Rotation Animation
                    </summary>
                    <div style="padding: 15px; background: white;">
                        <div class="shape-field-group" style="margin-bottom: 15px;">
                            <label class="shape-field-label">
                                <input type="checkbox" ${shapeData.animation.rotation.enabled ? 'checked' : ''}
                                       onchange="updateRotationAnimation(${shapeData.id}, 'enabled', this.checked)">
                                Enable Rotation
                            </label>
                        </div>
                        <div class="shape-field-group" style="margin-bottom: 15px;">
                            <label class="shape-field-label">Rotation Degrees (0-360°)</label>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <input type="range" class="shape-field-input"
                                       min="0" max="360" step="1"
                                       value="${shapeData.animation.rotation.degrees}"
                                       oninput="this.nextElementSibling.textContent = this.value + '°'; updateRotationAnimation(${shapeData.id}, 'degrees', parseInt(this.value))"
                                       style="flex: 1;">
                                <span style="min-width: 50px; font-weight: 600; color: #495057;">${shapeData.animation.rotation.degrees}°</span>
                            </div>
                        </div>
                        <div class="shape-field-group">
                            <label class="shape-field-label">Duration (milliseconds)</label>
                            <input type="number" class="shape-field-input"
                                   value="${shapeData.animation.rotation.duration}"
                                   step="1000" min="100"
                                   onchange="updateRotationAnimation(${shapeData.id}, 'duration', parseInt(this.value))">
                        </div>
                    </div>
                </details>

                <!-- Position Animation -->
                <details style="border-bottom: 1px solid #dee2e6;">
                    <summary style="cursor: pointer; padding: 10px; background: #f8f9fa; font-weight: 500; color: #495057;">
                        📍 Position Animation
                    </summary>
                    <div style="padding: 15px; background: white;">
                        <div class="shape-field-group" style="margin-bottom: 15px;">
                            <label class="shape-field-label">
                                <input type="checkbox" ${shapeData.animation.position.enabled ? 'checked' : ''}
                                       onchange="updatePositionAnimation(${shapeData.id}, 'enabled', this.checked)">
                                Enable Position
                            </label>
                        </div>
                        <div class="shape-field-group" style="margin-bottom: 15px;">
                            <label class="shape-field-label">Axis</label>
                            <select class="shape-field-input" onchange="updatePositionAnimation(${shapeData.id}, 'axis', this.value)">
                                <option value="x" ${shapeData.animation.position.axis === 'x' ? 'selected' : ''}>X (Left/Right)</option>
                                <option value="y" ${shapeData.animation.position.axis === 'y' ? 'selected' : ''}>Y (Up/Down)</option>
                                <option value="z" ${shapeData.animation.position.axis === 'z' ? 'selected' : ''}>Z (Forward/Back)</option>
                            </select>
                        </div>
                        <div class="shape-field-group" style="margin-bottom: 15px;">
                            <label class="shape-field-label">Distance (±5 units)</label>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <input type="range" class="shape-field-input"
                                       min="-5" max="5" step="0.1"
                                       value="${shapeData.animation.position.distance}"
                                       oninput="this.nextElementSibling.textContent = this.value; updatePositionAnimation(${shapeData.id}, 'distance', parseFloat(this.value))"
                                       style="flex: 1;">
                                <span style="min-width: 50px; font-weight: 600; color: #495057;">${shapeData.animation.position.distance}</span>
                            </div>
                        </div>
                        <div class="shape-field-group">
                            <label class="shape-field-label">Duration (milliseconds)</label>
                            <input type="number" class="shape-field-input"
                                   value="${shapeData.animation.position.duration}"
                                   step="1000" min="100"
                                   onchange="updatePositionAnimation(${shapeData.id}, 'duration', parseInt(this.value))">
                        </div>
                    </div>
                </details>

                <!-- Scale Animation -->
                <details>
                    <summary style="cursor: pointer; padding: 10px; background: #f8f9fa; font-weight: 500; color: #495057;">
                        📏 Scale Animation
                    </summary>
                    <div style="padding: 15px; background: white;">
                        <div class="shape-field-group" style="margin-bottom: 15px;">
                            <label class="shape-field-label">
                                <input type="checkbox" ${shapeData.animation.scale.enabled ? 'checked' : ''}
                                       onchange="updateScaleAnimation(${shapeData.id}, 'enabled', this.checked)">
                                Enable Scale
                            </label>
                        </div>
                        <div class="shape-field-group" style="margin-bottom: 15px;">
                            <label class="shape-field-label">Minimum Scale (0.1-10x)</label>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <input type="range" class="shape-field-input"
                                       min="0.1" max="10" step="0.1"
                                       value="${shapeData.animation.scale.min}"
                                       oninput="this.nextElementSibling.textContent = this.value + 'x'; updateScaleAnimation(${shapeData.id}, 'min', parseFloat(this.value))"
                                       style="flex: 1;">
                                <span style="min-width: 50px; font-weight: 600; color: #495057;">${shapeData.animation.scale.min}x</span>
                            </div>
                            <small style="display: block; margin-top: 5px; color: #6c757d; font-size: 0.875em;">0.1 = 10% size, 1.0 = 100% size, 10 = 1000% size</small>
                        </div>
                        <div class="shape-field-group" style="margin-bottom: 15px;">
                            <label class="shape-field-label">Maximum Scale (0.1-10x)</label>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <input type="range" class="shape-field-input"
                                       min="0.1" max="10" step="0.1"
                                       value="${shapeData.animation.scale.max}"
                                       oninput="this.nextElementSibling.textContent = this.value + 'x'; updateScaleAnimation(${shapeData.id}, 'max', parseFloat(this.value))"
                                       style="flex: 1;">
                                <span style="min-width: 50px; font-weight: 600; color: #495057;">${shapeData.animation.scale.max}x</span>
                            </div>
                            <small style="display: block; margin-top: 5px; color: #6c757d; font-size: 0.875em;">0.1 = 10% size, 1.0 = 100% size, 10 = 1000% size</small>
                        </div>
                        <div class="shape-field-group" style="margin-bottom: 15px;" id="scale-validation-${shapeData.id}"></div>
                        <div class="shape-field-group">
                            <label class="shape-field-label">Duration (milliseconds)</label>
                            <input type="number" class="shape-field-input"
                                   value="${shapeData.animation.scale.duration}"
                                   step="1000" min="100"
                                   onchange="updateScaleAnimation(${shapeData.id}, 'duration', parseInt(this.value))">
                        </div>
                    </div>
                </details>
            </div>
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

    // Rotation Animation Functions
    function updateRotationAnimation(id, field, value) {
        const shape = shapes.find(s => s.id === id);
        if (shape) {
            shape.animation.rotation[field] = value;
            updateConfiguration();
        }
    }

    // Position Animation Functions
    function updatePositionAnimation(id, field, value) {
        const shape = shapes.find(s => s.id === id);
        if (shape) {
            shape.animation.position[field] = value;
            updateConfiguration();
        }
    }

    // Scale Animation Functions
    function updateScaleAnimation(id, field, value) {
        const shape = shapes.find(s => s.id === id);
        if (shape) {
            shape.animation.scale[field] = value;

            // Validate min <= max
            if (field === 'min' || field === 'max') {
                validateScaleMinMax(id);
            }

            updateConfiguration();
        }
    }

    function validateScaleMinMax(id) {
        const shape = shapes.find(s => s.id === id);
        if (!shape) return;

        const validationDiv = document.getElementById(`scale-validation-${id}`);
        if (!validationDiv) return;

        const min = parseFloat(shape.animation.scale.min);
        const max = parseFloat(shape.animation.scale.max);

        if (min > max) {
            validationDiv.innerHTML = '<small style="color: #dc3545; font-weight: 600;">⚠️ Warning: Minimum scale cannot be greater than maximum scale</small>';
            validationDiv.style.display = 'block';
        } else {
            validationDiv.innerHTML = '';
            validationDiv.style.display = 'none';
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

    // Convert old animation format to new Phase 2 format
    function migrateAnimationFormat(shapeData) {
        // Check if this shape has the old animation format
        if (shapeData.animation && typeof shapeData.animation.enabled !== 'undefined' &&
            !shapeData.animation.rotation && !shapeData.animation.position && !shapeData.animation.scale) {

            // Old format detected - convert to new format
            const oldAnim = shapeData.animation;
            const property = oldAnim.property || 'rotation';
            const duration = oldAnim.dur || 10000;

            shapeData.animation = {
                rotation: {
                    enabled: oldAnim.enabled && property === 'rotation',
                    degrees: 360,
                    duration: duration
                },
                position: {
                    enabled: oldAnim.enabled && property === 'position',
                    axis: 'y',
                    distance: 2,
                    duration: duration
                },
                scale: {
                    enabled: oldAnim.enabled && property === 'scale',
                    min: 0.5,
                    max: 2.0,
                    duration: duration
                }
            };

            console.log('Migrated old animation format for shape', shapeData.id);
        }

        // Ensure opacity field exists (default to 1.0)
        if (typeof shapeData.opacity === 'undefined') {
            shapeData.opacity = 1.0;
        }

        // Ensure all animation sub-structures exist
        if (!shapeData.animation) {
            shapeData.animation = {};
        }
        if (!shapeData.animation.rotation) {
            shapeData.animation.rotation = { enabled: false, degrees: 360, duration: 10000 };
        }
        if (!shapeData.animation.position) {
            shapeData.animation.position = { enabled: false, axis: 'y', distance: 0, duration: 10000 };
        }
        if (!shapeData.animation.scale) {
            shapeData.animation.scale = { enabled: false, min: 1.0, max: 1.0, duration: 10000 };
        }

        return shapeData;
    }

    // Load existing configuration when editing
    <?php if ($editPiece && !empty($editPiece['configuration'])): ?>
    document.addEventListener('DOMContentLoaded', function() {
        try {
            const config = <?php echo $editPiece['configuration']; ?>;
            if (config && config.shapes) {
                config.shapes.forEach(shapeData => {
                    // Migrate old animation format to new Phase 2 format
                    const migratedShape = migrateAnimationFormat(shapeData);

                    shapes.push(migratedShape);
                    shapeCount++;
                    renderShape(migratedShape);
                });
                updateAddButtonState();
                updateConfiguration();
            }
        } catch (e) {
            console.error('Error loading shape configuration:', e);
            console.error('Stack trace:', e.stack);
        }
    });
    <?php endif; ?>

    // ============================================
    // PREVIEW FUNCTIONS
    // ============================================

    function showPreview() {
        const previewSection = document.getElementById('preview-section');
        const previewIframe = document.getElementById('preview-iframe');
        const previewLoading = document.getElementById('preview-loading');

        // Ensure configuration is up to date
        updateConfiguration();

        // Get current form data
        const formData = new FormData(document.getElementById('art-form'));

        // Show preview section and loading indicator
        previewSection.style.display = 'block';
        previewLoading.style.display = 'block';

        // Scroll to preview
        previewSection.scrollIntoView({ behavior: 'smooth', block: 'start' });

        // Send data to preview endpoint via POST
        fetch('<?php echo url('admin/includes/preview.php'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(formData)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Preview failed: ' + response.statusText);
            }
            return response.text();
        })
        .then(html => {
            // Create a blob URL for the preview content
            const blob = new Blob([html], { type: 'text/html' });
            const blobUrl = URL.createObjectURL(blob);

            // Load preview in iframe
            previewIframe.src = blobUrl;

            // Hide loading indicator after iframe loads
            previewIframe.onload = function() {
                previewLoading.style.display = 'none';
            };
        })
        .catch(error => {
            console.error('Preview error:', error);
            previewLoading.innerHTML = `
                <div style="color: #dc3545; font-weight: 600;">
                    ❌ Preview failed
                </div>
                <div style="font-size: 14px; color: #6c757d; margin-top: 10px;">
                    ${error.message}
                </div>
            `;

            setTimeout(() => {
                previewLoading.style.display = 'none';
            }, 3000);
        });
    }

    function hidePreview() {
        const previewSection = document.getElementById('preview-section');
        const previewIframe = document.getElementById('preview-iframe');

        previewSection.style.display = 'none';
        previewIframe.src = '';  // Clear iframe to stop any running animations
    }

    </script>

<?php endif; ?>

<?php require_once(__DIR__ . '/includes/footer.php'); ?>
