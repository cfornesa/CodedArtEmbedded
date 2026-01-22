<?php
/**
 * C2.js Art Management
 * CRUD interface for C2.js art pieces
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

$page_title = 'C2.js Art Management';

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
    $result = deleteArtPieceWithSlug('c2', $pieceId, $permanent);
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
            'canvas_count' => $_POST['canvas_count'] ?? 1,
            'tags' => $_POST['tags'] ?? '',
            'status' => $_POST['status'] ?? 'active',
            'sort_order' => $_POST['sort_order'] ?? 0
        ];

        // Handle background image URL (single input)
        if (isset($_POST['background_image_url'])) {
            $data['background_image_url'] = trim($_POST['background_image_url']);
        }

        // Handle configuration JSON if provided
        if (!empty($_POST['configuration_json'])) {
            $config = json_decode($_POST['configuration_json'], true);
            if ($config !== null) {
                $data['configuration'] = $config;
            }
        }

        if ($action === 'create') {
            $result = createArtPieceWithSlug('c2', $data);
        } else {
            $result = updateArtPieceWithSlug('c2', $pieceId, $data);
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
$artPieces = getActiveArtPieces('c2', 'all');

// Get single piece for editing
$editPiece = null;
if ($action === 'edit' && $pieceId) {
    $editPiece = getArtPiece('c2', $pieceId);
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
            <h2>C2.js Art Pieces</h2>
            <div>
                <a href="<?php echo url('admin/deleted.php?type=c2'); ?>" class="btn btn-secondary" style="margin-right: 10px;">
                    🗑️ Deleted Items
                </a>
                <a href="<?php echo url('admin/c2.php?action=create'); ?>" class="btn btn-success">
                    + Add New Piece
                </a>
            </div>
        </div>

        <?php if (empty($artPieces)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">🎨</div>
                <p>No C2.js art pieces yet.</p>
                <a href="<?php echo url('admin/c2.php?action=create'); ?>" class="btn btn-primary">
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
                        <th>Canvases</th>
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
                                <?php echo $piece['canvas_count']; ?> canvas<?php echo $piece['canvas_count'] != 1 ? 'es' : ''; ?>
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
                                    href="<?php echo url('admin/c2.php?action=edit&id=' . $piece['id']); ?>"
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
                                    href="<?php echo url('admin/c2.php?action=delete&id=' . $piece['id']); ?>"
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
            <h2><?php echo $action === 'create' ? 'Add New' : 'Edit'; ?> C2.js Piece</h2>
        </div>

        <form method="POST" action="" data-validate id="art-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

            <!-- LIVE PREVIEW SECTION (matching A-Frame pattern) -->
            <div id="live-preview-section" style="margin: 20px; padding: 20px; background: #fff0f5; border: 3px solid #ED225D; border-radius: 8px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3 style="margin: 0; color: #ED225D; font-size: 20px;">
                        🎬 LIVE PREVIEW
                    </h3>
                    <div>
                        <button type="button" class="btn btn-sm btn-secondary" id="toggle-preview-btn" onclick="toggleLivePreview()">
                            Hide Preview
                        </button>
                    </div>
                </div>

                <p style="margin: 0 0 15px 0; color: #6c757d; font-size: 14px;">
                    See your C2.js pattern in real-time as you configure it. Preview updates automatically with 500ms debounce.
                </p>

                <div id="live-preview-container" style="background: #fff; border: 2px solid #dee2e6; border-radius: 4px; overflow: hidden; position: relative;">
                    <iframe id="live-preview-iframe" src="" style="width: 100%; height: 600px; border: none;"></iframe>
                    <div id="live-preview-loading" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; background: rgba(255,255,255,0.95); padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <div style="font-size: 18px; font-weight: 600; color: #ED225D;">
                            🔄 Loading Preview...
                        </div>
                    </div>
                </div>
            </div>

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

            <!-- File path is auto-generated from slug: /c2/view.php?slug=your-slug -->

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
                <label for="canvas_count" class="form-label">Canvas Count</label>
                <input
                    type="number"
                    id="canvas_count"
                    name="canvas_count"
                    class="form-control"
                    min="1"
                    max="20"
                    value="<?php echo $formData ? htmlspecialchars($formData['canvas_count']) : ($editPiece ? $editPiece['canvas_count'] : 1); ?>"
                >
                <small class="form-help">Number of canvases used in this piece</small>
            </div>

            <div class="form-group">
                <label class="form-label">Background Image URL (optional)</label>
                <input
                    type="url"
                    id="background_image_url"
                    name="background_image_url"
                    class="form-control"
                    placeholder="https://example.com/background.png"
                    value="<?php echo $formData ? htmlspecialchars($formData['background_image_url'] ?? '') : ($editPiece ? htmlspecialchars($editPiece['background_image_url'] ?? '') : ''); ?>"
                >
                <small class="form-help">Optional background image for the canvas</small>
            </div>

            <div class="form-group">
                <label for="tags" class="form-label">Tags</label>
                <input
                    type="text"
                    id="tags"
                    name="tags"
                    class="form-control"
                    placeholder="C2.js, Interactive, Canvas, Animation"
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

            <!-- Advanced Pattern Configuration Builder -->
            <div class="card" style="margin-top: 30px; border: 2px solid #FF6B6B;">
                <div class="card-header" style="background: linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%); color: white;">
                    <h3 style="margin: 0; display: flex; align-items: center; justify-content: space-between;">
                        <span>🎨 C2.js Pattern Configurator</span>
                        <small style="opacity: 0.9; font-weight: normal;">(Generative Art Settings)</small>
                    </h3>
                    <p style="margin: 5px 0 0 0; font-size: 14px; opacity: 0.95;">
                        Configure pattern generation parameters for your C2.js art piece
                    </p>
                </div>

                <div style="padding: 20px;">
                    <!-- Canvas Configuration -->
                    <div class="pattern-section">
                        <h4 class="pattern-section-title">Canvas Settings</h4>

                        <div class="pattern-row">
                            <div class="pattern-field-group">
                                <label class="pattern-field-label">Canvas Width (px)</label>
                                <input type="number" id="canvas-width" class="pattern-field-input" value="800" step="10">
                            </div>
                            <div class="pattern-field-group">
                                <label class="pattern-field-label">Canvas Height (px)</label>
                                <input type="number" id="canvas-height" class="pattern-field-input" value="600" step="10">
                            </div>
                            <div class="pattern-field-group">
                                <label class="pattern-field-label">Background Color</label>
                                <input type="color" id="canvas-background" class="pattern-field-input" value="#FFFFFF">
                            </div>
                        </div>
                    </div>

                    <!-- Pattern Type Configuration -->
                    <div class="pattern-section">
                        <h4 class="pattern-section-title">Pattern Type</h4>

                        <div class="pattern-row">
                            <div class="pattern-field-group">
                                <label class="pattern-field-label">Pattern Style</label>
                                <select id="pattern-type" class="pattern-field-input" onchange="updatePatternFields()">
                                    <option value="grid">Grid Pattern</option>
                                    <option value="spiral">Spiral Pattern</option>
                                    <option value="scatter">Random Scatter</option>
                                    <option value="wave">Wave Pattern</option>
                                    <option value="concentric">Concentric Circles</option>
                                    <option value="fractal">Fractal Pattern</option>
                                    <option value="particle">Particle System</option>
                                    <option value="flow">Flow Field</option>
                                    <option value="custom">Custom Pattern</option>
                                </select>
                            </div>
                            <div class="pattern-field-group">
                                <label class="pattern-field-label">Element Count</label>
                                <input type="number" id="element-count" class="pattern-field-input" value="100" min="1" max="10000">
                            </div>
                        </div>
                    </div>

                    <!-- Shape & Color Palette -->
                    <div class="pattern-section">
                        <h4 class="pattern-section-title">Shape & Color Palette</h4>
                        <p class="form-help" style="margin-bottom: 15px;">Define the shapes and colors that will be used in your pattern</p>

                        <div id="shape-palette-container">
                            <!-- Shape+color items will be dynamically added here -->
                        </div>
                        <button type="button" class="btn btn-sm btn-success" onclick="addShapeToPalette()">
                            + Add Shape & Color
                        </button>
                    </div>

                    <!-- Pattern Parameters -->
                    <div class="pattern-section">
                        <h4 class="pattern-section-title">Pattern Parameters</h4>

                        <div class="pattern-row">
                            <div class="pattern-field-group">
                                <label class="pattern-field-label">Element Size</label>
                                <input type="range" id="element-size" class="pattern-field-input" value="5" step="0.1" min="0.1" max="10">
                                <span id="element-size-value" style="color: #ED225D; font-weight: bold;">5.0</span>
                            </div>
                            <div class="pattern-field-group">
                                <label class="pattern-field-label">Size Variation (%)</label>
                                <input type="number" id="size-variation" class="pattern-field-input" value="20" min="0" max="100">
                            </div>
                            <div class="pattern-field-group">
                                <label class="pattern-field-label">Spacing/Density</label>
                                <input type="number" id="spacing" class="pattern-field-input" value="20" step="1" min="1">
                            </div>
                        </div>

                        <div class="pattern-row">
                            <div class="pattern-field-group">
                                <label class="pattern-field-label">Opacity</label>
                                <input type="range" id="opacity" class="pattern-field-input" value="80" min="0" max="100">
                                <span id="opacity-value">80%</span>
                            </div>
                            <div class="pattern-field-group">
                                <label class="pattern-field-label">Rotation (degrees)</label>
                                <input type="number" id="rotation" class="pattern-field-input" value="0" min="0" max="360">
                            </div>
                        </div>
                    </div>

                    <!-- Animation Settings -->
                    <div class="pattern-section">
                        <h4 class="pattern-section-title">Animation Settings</h4>
                        <p class="form-help" style="margin-bottom: 15px;">Enable independent animations for your pattern. Multiple animations can run simultaneously.</p>

                        <!-- Rotation Animation -->
                        <details class="animation-details" style="margin-bottom: 15px; border: 1px solid #ddd; border-radius: 6px; padding: 10px; background: #f8f9fa;">
                            <summary style="cursor: pointer; font-weight: bold; color: #ED225D;">📐 Rotation Animation</summary>
                            <div style="margin-top: 15px; padding-left: 20px;">
                                <div class="pattern-row">
                                    <div class="pattern-field-group">
                                        <label class="pattern-field-label">
                                            <input type="checkbox" id="animation-rotation-enabled" onchange="updateConfiguration()">
                                            Enable Rotation
                                        </label>
                                    </div>
                                    <div class="pattern-field-group">
                                        <label class="pattern-field-label">
                                            <input type="checkbox" id="animation-rotation-loop" checked onchange="updateConfiguration()">
                                            Loop
                                        </label>
                                    </div>
                                    <div class="pattern-field-group">
                                        <label class="pattern-field-label">
                                            <input type="checkbox" id="animation-rotation-counterclockwise" onchange="updateConfiguration()">
                                            Counterclockwise
                                        </label>
                                    </div>
                                </div>
                                <div class="pattern-row">
                                    <div class="pattern-field-group">
                                        <label class="pattern-field-label">Speed</label>
                                        <input type="range" id="animation-rotation-speed" class="pattern-field-input" value="1" step="0.1" min="1" max="10" onchange="updateConfiguration()">
                                        <span id="animation-rotation-speed-value" style="color: #ED225D; font-weight: bold;">1.0</span>
                                    </div>
                                </div>
                            </div>
                        </details>

                        <!-- Pulse/Scale Animation -->
                        <details class="animation-details" style="margin-bottom: 15px; border: 1px solid #ddd; border-radius: 6px; padding: 10px; background: #f8f9fa;">
                            <summary style="cursor: pointer; font-weight: bold; color: #ED225D;">📏 Pulse/Scale Animation</summary>
                            <div style="margin-top: 15px; padding-left: 20px;">
                                <div class="pattern-row">
                                    <div class="pattern-field-group">
                                        <label class="pattern-field-label">
                                            <input type="checkbox" id="animation-pulse-enabled" onchange="updateConfiguration()">
                                            Enable Pulse
                                        </label>
                                    </div>
                                    <div class="pattern-field-group">
                                        <label class="pattern-field-label">
                                            <input type="checkbox" id="animation-pulse-loop" checked onchange="updateConfiguration()">
                                            Loop
                                        </label>
                                    </div>
                                </div>
                                <div class="pattern-row">
                                    <div class="pattern-field-group">
                                        <label class="pattern-field-label">Speed</label>
                                        <input type="range" id="animation-pulse-speed" class="pattern-field-input" value="1" step="0.1" min="1" max="10" onchange="updateConfiguration()">
                                        <span id="animation-pulse-speed-value" style="color: #ED225D; font-weight: bold;">1.0</span>
                                    </div>
                                </div>
                            </div>
                        </details>

                        <!-- Movement Animation -->
                        <details class="animation-details" style="margin-bottom: 15px; border: 1px solid #ddd; border-radius: 6px; padding: 10px; background: #f8f9fa;">
                            <summary style="cursor: pointer; font-weight: bold; color: #ED225D;">📍 Movement Animation</summary>
                            <div style="margin-top: 15px; padding-left: 20px;">
                                <div class="pattern-row">
                                    <div class="pattern-field-group">
                                        <label class="pattern-field-label">
                                            <input type="checkbox" id="animation-move-enabled" onchange="updateConfiguration()">
                                            Enable Movement
                                        </label>
                                    </div>
                                    <div class="pattern-field-group">
                                        <label class="pattern-field-label">
                                            <input type="checkbox" id="animation-move-loop" checked onchange="updateConfiguration()">
                                            Loop
                                        </label>
                                    </div>
                                </div>
                                <div class="pattern-row">
                                    <div class="pattern-field-group">
                                        <label class="pattern-field-label">Speed</label>
                                        <input type="range" id="animation-move-speed" class="pattern-field-input" value="1" step="0.1" min="1" max="10" onchange="updateConfiguration()">
                                        <span id="animation-move-speed-value" style="color: #ED225D; font-weight: bold;">1.0</span>
                                    </div>
                                </div>
                            </div>
                        </details>

                        <!-- Color Shift Animation -->
                        <details class="animation-details" style="margin-bottom: 15px; border: 1px solid #ddd; border-radius: 6px; padding: 10px; background: #f8f9fa;">
                            <summary style="cursor: pointer; font-weight: bold; color: #ED225D;">🎨 Color Shift Animation</summary>
                            <div style="margin-top: 15px; padding-left: 20px;">
                                <div class="pattern-row">
                                    <div class="pattern-field-group">
                                        <label class="pattern-field-label">
                                            <input type="checkbox" id="animation-color-enabled" onchange="updateConfiguration()">
                                            Enable Color Shift
                                        </label>
                                    </div>
                                    <div class="pattern-field-group">
                                        <label class="pattern-field-label">
                                            <input type="checkbox" id="animation-color-loop" checked onchange="updateConfiguration()">
                                            Loop
                                        </label>
                                    </div>
                                </div>
                                <div class="pattern-row">
                                    <div class="pattern-field-group">
                                        <label class="pattern-field-label">Speed</label>
                                        <input type="range" id="animation-color-speed" class="pattern-field-input" value="1" step="0.1" min="1" max="10" onchange="updateConfiguration()">
                                        <span id="animation-color-speed-value" style="color: #ED225D; font-weight: bold;">1.0</span>
                                    </div>
                                </div>
                            </div>
                        </details>
                    </div>

                    <!-- Interaction Settings -->
                    <div class="pattern-section">
                        <h4 class="pattern-section-title">Interaction Settings</h4>

                        <div class="pattern-row">
                            <div class="pattern-field-group">
                                <label class="pattern-field-label">
                                    <input type="checkbox" id="mouse-interaction">
                                    Enable Mouse Interaction
                                </label>
                            </div>
                            <div class="pattern-field-group">
                                <label class="pattern-field-label">Interaction Type</label>
                                <select id="interaction-type" class="pattern-field-input">
                                    <option value="repel">Repel</option>
                                    <option value="attract">Attract</option>
                                    <option value="follow">Follow</option>
                                    <option value="change-color">Change Color</option>
                                    <option value="change-size">Change Size</option>
                                </select>
                            </div>
                            <div class="pattern-field-group">
                                <label class="pattern-field-label">Interaction Radius</label>
                                <input type="range" id="interaction-radius" class="pattern-field-input" value="100" step="10" min="10" max="500">
                                <span id="interaction-radius-value" style="color: #ED225D; font-weight: bold;">100</span>
                            </div>
                        </div>
                    </div>

                    <!-- Advanced Settings -->
                    <details style="margin-top: 20px;">
                        <summary style="cursor: pointer; font-weight: 600; color: #FF6B6B; font-size: 16px;">
                            ⚙️ Advanced Settings
                        </summary>
                        <div style="margin-top: 15px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                            <div class="pattern-row">
                                <div class="pattern-field-group">
                                    <label class="pattern-field-label">Random Seed</label>
                                    <input type="number" id="random-seed" class="pattern-field-input" value="12345">
                                    <small class="form-help">Same seed = same pattern</small>
                                </div>
                                <div class="pattern-field-group">
                                    <label class="pattern-field-label">Blend Mode</label>
                                    <select id="blend-mode" class="pattern-field-input">
                                        <option value="normal">Normal</option>
                                        <option value="multiply">Multiply</option>
                                        <option value="screen">Screen</option>
                                        <option value="overlay">Overlay</option>
                                        <option value="difference">Difference</option>
                                    </select>
                                </div>
                            </div>
                            <div class="pattern-row">
                                <div class="pattern-field-group">
                                    <label class="pattern-field-label">
                                        <input type="checkbox" id="enable-trails">
                                        Enable Motion Trails
                                    </label>
                                </div>
                                <div class="pattern-field-group">
                                    <label class="pattern-field-label">Frame Rate (FPS)</label>
                                    <input type="number" id="frame-rate" class="pattern-field-input" value="60" min="1" max="120">
                                </div>
                            </div>
                        </div>
                    </details>
                </div>
            </div>

            <!-- Hidden field to store pattern configuration as JSON -->
            <input type="hidden" name="configuration_json" id="configuration_json">

            <div class="form-group" style="margin-top: 30px;">
                <button type="submit" class="btn btn-primary btn-lg">
                    <?php echo $action === 'create' ? 'Create Piece' : 'Update Piece'; ?>
                </button>
                <a href="<?php echo url('admin/c2.php'); ?>" class="btn btn-secondary btn-lg">
                    Cancel
                </a>
                <button type="button" id="preview-btn" class="btn btn-info btn-lg" style="margin-left: 10px;" onclick="scrollToLivePreview()">
                    ⬆️ Scroll to Preview
                </button>
            </div>
        </form>
    </div>

    <style>
    .pattern-section {
        background: white;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .pattern-section-title {
        color: #FF6B6B;
        font-size: 18px;
        font-weight: 600;
        margin: 0 0 15px 0;
        padding-bottom: 10px;
        border-bottom: 2px solid #FF6B6B;
    }

    .pattern-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 15px;
    }

    .pattern-field-group {
        display: flex;
        flex-direction: column;
    }

    .pattern-field-label {
        font-weight: 600;
        margin-bottom: 5px;
        color: #495057;
        font-size: 14px;
    }

    .pattern-field-input {
        padding: 8px 12px;
        border: 1px solid #ced4da;
        border-radius: 4px;
        font-size: 14px;
    }

    .pattern-field-input:focus {
        outline: none;
        border-color: #FF6B6B;
        box-shadow: 0 0 0 0.2rem rgba(255, 107, 107, 0.25);
    }

    .color-palette-item {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
    }

    .color-remove-btn {
        background: #dc3545;
        color: white;
        border: none;
        padding: 4px 8px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
    }

    .color-remove-btn:hover {
        background: #c82333;
    }
    </style>

    <script>
    // Pattern configuration state
    const patternConfig = {
        canvas: {
            width: 800,
            height: 600,
            background: '#FFFFFF'
        },
        pattern: {
            type: 'grid',
            elementCount: 100
        },
        shapes: [
            { shape: 'circle', color: '#FF6B6B' },
            { shape: 'square', color: '#4ECDC4' },
            { shape: 'triangle', color: '#45B7D1' }
        ],
        parameters: {
            elementSize: 5,
            sizeVariation: 20,
            spacing: 20,
            opacity: 80,
            rotation: 0
        },
        animation: {
            rotation: {
                enabled: false,
                loop: true,
                counterclockwise: false,
                speed: 1
            },
            pulse: {
                enabled: false,
                loop: true,
                speed: 1
            },
            move: {
                enabled: false,
                loop: true,
                speed: 1
            },
            color: {
                enabled: false,
                loop: true,
                speed: 1
            }
        },
        interaction: {
            enabled: false,
            type: 'repel',
            radius: 100
        },
        advanced: {
            randomSeed: 12345,
            blendMode: 'normal',
            enableTrails: false,
            frameRate: 60
        }
    };

    // Initialize shape palette
    function initializeShapePalette() {
        const container = document.getElementById('shape-palette-container');
        container.innerHTML = '';
        patternConfig.shapes.forEach((item, index) => {
            addShapeToPaletteWithValue(item.shape, item.color);
        });
        updateConfiguration();
    }

    // Add shape to palette
    function addShapeToPalette() {
        const shapes = ['circle', 'square', 'triangle'];
        const randomShape = shapes[Math.floor(Math.random() * shapes.length)];
        const randomColor = '#' + Math.floor(Math.random()*16777215).toString(16);
        addShapeToPaletteWithValue(randomShape, randomColor);
        updateConfiguration();
    }

    // Add shape with specific values
    function addShapeToPaletteWithValue(shape, color) {
        const container = document.getElementById('shape-palette-container');
        const index = container.children.length;

        const shapeItem = document.createElement('div');
        shapeItem.className = 'color-palette-item';
        shapeItem.style.cssText = 'display: flex; align-items: center; margin-bottom: 10px; background: #f8f9fa; padding: 10px; border-radius: 6px;';
        shapeItem.innerHTML = `
            <select onchange="updateShape(${index}, this.value)" style="width: 140px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; margin-right: 10px; font-size: 14px;">
                <option value="circle" ${shape === 'circle' ? 'selected' : ''}>● Circle</option>
                <option value="square" ${shape === 'square' ? 'selected' : ''}>■ Square</option>
                <option value="triangle" ${shape === 'triangle' ? 'selected' : ''}>▲ Triangle</option>
                <option value="hexagon" ${shape === 'hexagon' ? 'selected' : ''}>⬢ Hexagon</option>
                <option value="star" ${shape === 'star' ? 'selected' : ''}>★ Star</option>
            </select>
            <input type="color" value="${color}" onchange="updateShapeColor(${index}, this.value)" style="width: 60px; height: 40px; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;">
            <input type="text" value="${color}" onchange="updateShapeColor(${index}, this.value)" style="flex: 1; margin: 0 10px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-family: monospace; font-size: 14px;">
            <button type="button" class="color-remove-btn" onclick="removeShape(${index})" style="background: #dc3545; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-weight: bold;">✕</button>
        `;
        container.appendChild(shapeItem);

        // Update the shapes array
        if (index >= patternConfig.shapes.length) {
            patternConfig.shapes.push({ shape: shape, color: color });
        }
    }

    // Update shape type in palette
    function updateShape(index, shape) {
        patternConfig.shapes[index].shape = shape;
        updateConfiguration();
    }

    // Update shape color in palette
    function updateShapeColor(index, color) {
        patternConfig.shapes[index].color = color;
        // Sync both inputs
        const shapeItem = document.querySelectorAll('.color-palette-item')[index];
        const inputs = shapeItem.querySelectorAll('input');
        inputs[0].value = color;
        inputs[1].value = color;
        updateConfiguration();
    }

    // Remove shape from palette
    function removeShape(index) {
        if (patternConfig.shapes.length <= 1) {
            alert('You must have at least one shape in the palette!');
            return;
        }
        patternConfig.shapes.splice(index, 1);
        initializeShapePalette();
    }

    // Update pattern fields based on pattern type
    function updatePatternFields() {
        const patternType = document.getElementById('pattern-type').value;
        patternConfig.pattern.type = patternType;
        updateConfiguration();
    }

    // Update animation fields visibility
    function updateAnimationFields() {
        const enabled = document.getElementById('animation-enabled').checked;
        const fields = document.getElementById('animation-fields');
        fields.style.display = enabled ? 'block' : 'none';
        patternConfig.animation.enabled = enabled;
        updateConfiguration();
    }

    // Collect all form values and update configuration
    function collectFormValues() {
        // Canvas settings
        patternConfig.canvas.width = parseInt(document.getElementById('canvas-width').value);
        patternConfig.canvas.height = parseInt(document.getElementById('canvas-height').value);
        patternConfig.canvas.background = document.getElementById('canvas-background').value;

        // Pattern settings
        patternConfig.pattern.type = document.getElementById('pattern-type').value;
        patternConfig.pattern.elementCount = parseInt(document.getElementById('element-count').value);

        // Pattern parameters
        patternConfig.parameters.elementSize = parseFloat(document.getElementById('element-size').value);
        patternConfig.parameters.sizeVariation = parseFloat(document.getElementById('size-variation').value);
        patternConfig.parameters.spacing = parseFloat(document.getElementById('spacing').value);
        patternConfig.parameters.opacity = parseFloat(document.getElementById('opacity').value);
        patternConfig.parameters.rotation = parseFloat(document.getElementById('rotation').value);

        // Animation settings - granular controls
        patternConfig.animation.rotation.enabled = document.getElementById('animation-rotation-enabled').checked;
        patternConfig.animation.rotation.loop = document.getElementById('animation-rotation-loop').checked;
        patternConfig.animation.rotation.counterclockwise = document.getElementById('animation-rotation-counterclockwise').checked;
        patternConfig.animation.rotation.speed = parseFloat(document.getElementById('animation-rotation-speed').value);

        patternConfig.animation.pulse.enabled = document.getElementById('animation-pulse-enabled').checked;
        patternConfig.animation.pulse.loop = document.getElementById('animation-pulse-loop').checked;
        patternConfig.animation.pulse.speed = parseFloat(document.getElementById('animation-pulse-speed').value);

        patternConfig.animation.move.enabled = document.getElementById('animation-move-enabled').checked;
        patternConfig.animation.move.loop = document.getElementById('animation-move-loop').checked;
        patternConfig.animation.move.speed = parseFloat(document.getElementById('animation-move-speed').value);

        patternConfig.animation.color.enabled = document.getElementById('animation-color-enabled').checked;
        patternConfig.animation.color.loop = document.getElementById('animation-color-loop').checked;
        patternConfig.animation.color.speed = parseFloat(document.getElementById('animation-color-speed').value);

        // Interaction settings
        patternConfig.interaction.enabled = document.getElementById('mouse-interaction').checked;
        patternConfig.interaction.type = document.getElementById('interaction-type').value;
        patternConfig.interaction.radius = parseFloat(document.getElementById('interaction-radius').value);

        // Advanced settings
        patternConfig.advanced.randomSeed = parseInt(document.getElementById('random-seed').value);
        patternConfig.advanced.blendMode = document.getElementById('blend-mode').value;
        patternConfig.advanced.enableTrails = document.getElementById('enable-trails').checked;
        patternConfig.advanced.frameRate = parseInt(document.getElementById('frame-rate').value);
    }

    // Update the hidden configuration field
    function updateConfiguration() {
        collectFormValues();
        document.getElementById('configuration_json').value = JSON.stringify(patternConfig, null, 2);

        // Update live preview automatically (debounced)
        updateLivePreview();
    }

    // Update opacity display
    document.addEventListener('DOMContentLoaded', function() {
        const opacityInput = document.getElementById('opacity');
        const opacityValue = document.getElementById('opacity-value');

        if (opacityInput && opacityValue) {
            opacityInput.addEventListener('input', function() {
                opacityValue.textContent = this.value + '%';
                updateConfiguration();
            });
        }

        // Update element size display
        const elementSizeInput = document.getElementById('element-size');
        const elementSizeValue = document.getElementById('element-size-value');

        if (elementSizeInput && elementSizeValue) {
            elementSizeInput.addEventListener('input', function() {
                elementSizeValue.textContent = parseFloat(this.value).toFixed(1);
                updateConfiguration();
            });
        }

        // Update animation speed display
        const animationSpeedInput = document.getElementById('animation-speed');
        const animationSpeedValue = document.getElementById('animation-speed-value');

        if (animationSpeedInput && animationSpeedValue) {
            animationSpeedInput.addEventListener('input', function() {
                animationSpeedValue.textContent = parseFloat(this.value).toFixed(1);
                updateConfiguration();
            });
        }

        // Update interaction radius display
        const interactionRadiusInput = document.getElementById('interaction-radius');
        const interactionRadiusValue = document.getElementById('interaction-radius-value');

        if (interactionRadiusInput && interactionRadiusValue) {
            interactionRadiusInput.addEventListener('input', function() {
                interactionRadiusValue.textContent = this.value;
                updateConfiguration();
            });
        }

        // Update animation speed displays
        const rotationSpeedInput = document.getElementById('animation-rotation-speed');
        const rotationSpeedValue = document.getElementById('animation-rotation-speed-value');
        if (rotationSpeedInput && rotationSpeedValue) {
            rotationSpeedInput.addEventListener('input', function() {
                rotationSpeedValue.textContent = parseFloat(this.value).toFixed(1);
            });
        }

        const pulseSpeedInput = document.getElementById('animation-pulse-speed');
        const pulseSpeedValue = document.getElementById('animation-pulse-speed-value');
        if (pulseSpeedInput && pulseSpeedValue) {
            pulseSpeedInput.addEventListener('input', function() {
                pulseSpeedValue.textContent = parseFloat(this.value).toFixed(1);
            });
        }

        const moveSpeedInput = document.getElementById('animation-move-speed');
        const moveSpeedValue = document.getElementById('animation-move-speed-value');
        if (moveSpeedInput && moveSpeedValue) {
            moveSpeedInput.addEventListener('input', function() {
                moveSpeedValue.textContent = parseFloat(this.value).toFixed(1);
            });
        }

        const colorSpeedInput = document.getElementById('animation-color-speed');
        const colorSpeedValue = document.getElementById('animation-color-speed-value');
        if (colorSpeedInput && colorSpeedValue) {
            colorSpeedInput.addEventListener('input', function() {
                colorSpeedValue.textContent = parseFloat(this.value).toFixed(1);
            });
        }

        // Add change listeners to all inputs
        const inputs = document.querySelectorAll('.pattern-field-input, #mouse-interaction, #enable-trails');
        inputs.forEach(input => {
            input.addEventListener('change', updateConfiguration);
            input.addEventListener('input', updateConfiguration);
        });

        // Initialize color palette
        initializeShapePalette();

        // Load existing configuration if editing
        <?php if ($editPiece && !empty($editPiece['configuration'])): ?>
        try {
            const savedConfig = <?php echo $editPiece['configuration']; ?>;
            if (savedConfig) {
                // Load all values back into the form
                if (savedConfig.canvas) {
                    document.getElementById('canvas-width').value = savedConfig.canvas.width;
                    document.getElementById('canvas-height').value = savedConfig.canvas.height;
                    document.getElementById('canvas-background').value = savedConfig.canvas.background;
                }

                if (savedConfig.pattern) {
                    document.getElementById('pattern-type').value = savedConfig.pattern.type;
                    document.getElementById('element-count').value = savedConfig.pattern.elementCount;
                }

                // Load shapes (with backward compatibility for old colors format)
                if (savedConfig.shapes) {
                    patternConfig.shapes = savedConfig.shapes;
                    initializeShapePalette();
                } else if (savedConfig.colors) {
                    // Migrate old colors format to new shapes format
                    patternConfig.shapes = savedConfig.colors.map(color => ({
                        shape: 'circle',
                        color: color
                    }));
                    initializeShapePalette();
                }

                if (savedConfig.parameters) {
                    document.getElementById('element-size').value = savedConfig.parameters.elementSize;
                    document.getElementById('element-size-value').textContent = parseFloat(savedConfig.parameters.elementSize).toFixed(1);
                    document.getElementById('size-variation').value = savedConfig.parameters.sizeVariation;
                    document.getElementById('spacing').value = savedConfig.parameters.spacing;
                    document.getElementById('opacity').value = savedConfig.parameters.opacity;
                    document.getElementById('opacity-value').textContent = savedConfig.parameters.opacity + '%';
                    document.getElementById('rotation').value = savedConfig.parameters.rotation;
                }

                if (savedConfig.animation) {
                    // Check for new granular format
                    if (savedConfig.animation.rotation) {
                        // New format with granular controls
                        patternConfig.animation = savedConfig.animation;

                        // Load rotation animation
                        if (savedConfig.animation.rotation) {
                            document.getElementById('animation-rotation-enabled').checked = savedConfig.animation.rotation.enabled || false;
                            document.getElementById('animation-rotation-loop').checked = savedConfig.animation.rotation.loop !== false;
                            document.getElementById('animation-rotation-counterclockwise').checked = savedConfig.animation.rotation.counterclockwise || false;
                            document.getElementById('animation-rotation-speed').value = savedConfig.animation.rotation.speed || 1;
                            document.getElementById('animation-rotation-speed-value').textContent = parseFloat(savedConfig.animation.rotation.speed || 1).toFixed(1);
                        }

                        // Load pulse animation
                        if (savedConfig.animation.pulse) {
                            document.getElementById('animation-pulse-enabled').checked = savedConfig.animation.pulse.enabled || false;
                            document.getElementById('animation-pulse-loop').checked = savedConfig.animation.pulse.loop !== false;
                            document.getElementById('animation-pulse-speed').value = savedConfig.animation.pulse.speed || 1;
                            document.getElementById('animation-pulse-speed-value').textContent = parseFloat(savedConfig.animation.pulse.speed || 1).toFixed(1);
                        }

                        // Load move animation
                        if (savedConfig.animation.move) {
                            document.getElementById('animation-move-enabled').checked = savedConfig.animation.move.enabled || false;
                            document.getElementById('animation-move-loop').checked = savedConfig.animation.move.loop !== false;
                            document.getElementById('animation-move-speed').value = savedConfig.animation.move.speed || 1;
                            document.getElementById('animation-move-speed-value').textContent = parseFloat(savedConfig.animation.move.speed || 1).toFixed(1);
                        }

                        // Load color animation
                        if (savedConfig.animation.color) {
                            document.getElementById('animation-color-enabled').checked = savedConfig.animation.color.enabled || false;
                            document.getElementById('animation-color-loop').checked = savedConfig.animation.color.loop !== false;
                            document.getElementById('animation-color-speed').value = savedConfig.animation.color.speed || 1;
                            document.getElementById('animation-color-speed-value').textContent = parseFloat(savedConfig.animation.color.speed || 1).toFixed(1);
                        }
                    } else {
                        // Old format - migrate to new format
                        console.log('Migrating old animation format to granular format');
                        const oldType = savedConfig.animation.type || 'rotate';
                        const oldSpeed = savedConfig.animation.speed || 1;
                        const oldLoop = savedConfig.animation.loop !== false;
                        const oldEnabled = savedConfig.animation.enabled || false;

                        // Map old type to new format
                        if (oldEnabled) {
                            if (oldType === 'rotate') {
                                patternConfig.animation.rotation.enabled = true;
                                patternConfig.animation.rotation.speed = oldSpeed;
                                patternConfig.animation.rotation.loop = oldLoop;
                                document.getElementById('animation-rotation-enabled').checked = true;
                                document.getElementById('animation-rotation-speed').value = oldSpeed;
                                document.getElementById('animation-rotation-speed-value').textContent = parseFloat(oldSpeed).toFixed(1);
                                document.getElementById('animation-rotation-loop').checked = oldLoop;
                            } else if (oldType === 'pulse') {
                                patternConfig.animation.pulse.enabled = true;
                                patternConfig.animation.pulse.speed = oldSpeed;
                                patternConfig.animation.pulse.loop = oldLoop;
                                document.getElementById('animation-pulse-enabled').checked = true;
                                document.getElementById('animation-pulse-speed').value = oldSpeed;
                                document.getElementById('animation-pulse-speed-value').textContent = parseFloat(oldSpeed).toFixed(1);
                                document.getElementById('animation-pulse-loop').checked = oldLoop;
                            } else if (oldType === 'move') {
                                patternConfig.animation.move.enabled = true;
                                patternConfig.animation.move.speed = oldSpeed;
                                patternConfig.animation.move.loop = oldLoop;
                                document.getElementById('animation-move-enabled').checked = true;
                                document.getElementById('animation-move-speed').value = oldSpeed;
                                document.getElementById('animation-move-speed-value').textContent = parseFloat(oldSpeed).toFixed(1);
                                document.getElementById('animation-move-loop').checked = oldLoop;
                            } else if (oldType === 'color') {
                                patternConfig.animation.color.enabled = true;
                                patternConfig.animation.color.speed = oldSpeed;
                                patternConfig.animation.color.loop = oldLoop;
                                document.getElementById('animation-color-enabled').checked = true;
                                document.getElementById('animation-color-speed').value = oldSpeed;
                                document.getElementById('animation-color-speed-value').textContent = parseFloat(oldSpeed).toFixed(1);
                                document.getElementById('animation-color-loop').checked = oldLoop;
                            }
                        }
                    }
                }

                if (savedConfig.interaction) {
                    document.getElementById('mouse-interaction').checked = savedConfig.interaction.enabled;
                    document.getElementById('interaction-type').value = savedConfig.interaction.type;
                    document.getElementById('interaction-radius').value = savedConfig.interaction.radius;
                    document.getElementById('interaction-radius-value').textContent = savedConfig.interaction.radius;
                }

                if (savedConfig.advanced) {
                    document.getElementById('random-seed').value = savedConfig.advanced.randomSeed;
                    document.getElementById('blend-mode').value = savedConfig.advanced.blendMode;
                    document.getElementById('enable-trails').checked = savedConfig.advanced.enableTrails;
                    document.getElementById('frame-rate').value = savedConfig.advanced.frameRate;
                }

                updateConfiguration();
            }
        } catch (e) {
            console.error('Error loading pattern configuration:', e);
        }
        <?php endif; ?>
    });

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
                        '&type=c2' +
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
    // LIVE PREVIEW FUNCTIONS (matching A-Frame pattern)
    // ============================================

    let livePreviewTimeout = null;
    let livePreviewHidden = false;

    function updateLivePreview() {
        // Skip if preview is hidden
        if (livePreviewHidden) return;

        // Debounce: Clear previous timeout
        if (livePreviewTimeout) {
            clearTimeout(livePreviewTimeout);
        }

        // Set new timeout (500ms debounce)
        livePreviewTimeout = setTimeout(() => {
            const previewIframe = document.getElementById('live-preview-iframe');
            const previewLoading = document.getElementById('live-preview-loading');

            if (!previewIframe || !previewLoading) return;

            // Show loading indicator
            previewLoading.style.display = 'block';

            // Get current form data
            const formData = new FormData(document.getElementById('art-form'));

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
                console.error('Live preview error:', error);
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
        }, 500);  // 500ms debounce
    }

    function toggleLivePreview() {
        const previewContainer = document.getElementById('live-preview-container');
        const toggleBtn = document.getElementById('toggle-preview-btn');

        livePreviewHidden = !livePreviewHidden;

        if (livePreviewHidden) {
            previewContainer.style.display = 'none';
            toggleBtn.textContent = 'Show Preview';
        } else {
            previewContainer.style.display = 'block';
            toggleBtn.textContent = 'Hide Preview';
            // Update preview when showing
            updateLivePreview();
        }
    }

    // Scroll to live preview (for the button at bottom)
    function scrollToLivePreview() {
        const previewSection = document.getElementById('live-preview-section');
        if (previewSection) {
            previewSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    // Initialize live preview on page load (1 second delay to allow pattern to load)
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(() => {
            updateLivePreview();
        }, 1000);

        // Add event listeners to all pattern configurator inputs
        // so they trigger live preview updates
        const inputs = document.querySelectorAll('#c2-canvas-count, #c2-width, #c2-height, #c2-background, ' +
            '#c2-pattern-type, #c2-element-size, #c2-size-variation, #c2-spacing, #c2-opacity, ' +
            '#c2-rotation, #c2-animated, #c2-animation-type, #c2-animation-speed, #c2-loop, ' +
            '#c2-mouse-enabled, #c2-interaction-type, #c2-interaction-radius, #c2-random-seed, ' +
            '#c2-blend-mode, #c2-enable-trails, #c2-frame-rate');

        inputs.forEach(input => {
            input.addEventListener('change', updateC2Configuration);
            input.addEventListener('input', updateC2Configuration);
        });
    });
    </script>

<?php endif; ?>

<?php require_once(__DIR__ . '/includes/footer.php'); ?>
