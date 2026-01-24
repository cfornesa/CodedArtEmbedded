<?php
/**
 * P5.js Art Management
 * CRUD interface for P5.js art pieces
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

$page_title = 'P5.js Art Management';

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
    $result = deleteArtPieceWithSlug('p5', $pieceId, $permanent);
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
            'background_image_url' => $_POST['background_image_url'] ?? '',
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
            $result = createArtPieceWithSlug('p5', $data);
        } else {
            $result = updateArtPieceWithSlug('p5', $pieceId, $data);
        }

        if ($result['success']) {
            $success = $result['message'];
            $action = 'list';
        } else {
            $error = $result['message'];
            // Preserve form data so user doesn't lose their work
            $formData = $data;
            // Also preserve array inputs in original format
            // Preserve configuration JSON
            if (isset($_POST['configuration_json'])) {
                $formData['configuration_json_raw'] = $_POST['configuration_json'];
            }
        }
    }
}

// Get active art pieces for listing (excludes soft-deleted)
$artPieces = getActiveArtPieces('p5', 'all');

// Get single piece for editing
$editPiece = null;
if ($action === 'edit' && $pieceId) {
    $editPiece = getArtPiece('p5', $pieceId);
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
            <h2>P5.js Art Pieces</h2>
            <div>
                <a href="<?php echo url('admin/deleted.php?type=p5'); ?>" class="btn btn-secondary" style="margin-right: 10px;">
                    🗑️ Deleted Items
                </a>
                <a href="<?php echo url('admin/p5.php?action=create'); ?>" class="btn btn-success">
                    + Add New Piece
                </a>
            </div>
        </div>

        <?php if (empty($artPieces)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">🎨</div>
                <p>No P5.js art pieces yet.</p>
                <a href="<?php echo url('admin/p5.php?action=create'); ?>" class="btn btn-primary">
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
                                    href="<?php echo url('admin/p5.php?action=edit&id=' . $piece['id']); ?>"
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
                                    href="<?php echo url('admin/p5.php?action=delete&id=' . $piece['id']); ?>"
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
            <h2><?php echo $action === 'create' ? 'Add New' : 'Edit'; ?> P5.js Piece</h2>
        </div>

        <form method="POST" action="" data-validate id="art-form"
              data-slug-check-url="<?php echo url('admin/includes/check-slug.php'); ?>"
              data-slug-type="p5"
              data-slug-exclude-id="<?php echo $editPiece ? $editPiece['id'] : ''; ?>"
              data-slug-auto-init="<?php echo $action === 'create' ? '1' : '0'; ?>"
              data-live-preview="true"
              data-live-preview-url="<?php echo url('admin/includes/preview.php'); ?>"
              data-live-preview-debounce="500"
              data-live-preview-initial-delay="1000"
              data-live-preview-section="#live-preview-section"
              data-live-preview-iframe="#live-preview-iframe"
              data-live-preview-loading="#live-preview-loading"
              data-live-preview-global="true">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

            <!-- LIVE PREVIEW SECTION (matching A-Frame pattern) -->
            <div id="live-preview-section" style="margin: 20px; padding: 20px; background: #fff5f7; border: 3px solid #ED225D; border-radius: 8px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3 style="margin: 0; color: #ED225D; font-size: 20px;">
                        🎨 LIVE PREVIEW
                    </h3>
                    <div>
                        <button type="button" class="btn btn-sm btn-secondary" id="toggle-preview-btn" data-live-preview-toggle>
                            Hide Preview
                        </button>
                    </div>
                </div>

                <p style="margin: 0 0 15px 0; color: #6c757d; font-size: 14px;">
                    See your P5.js sketch in real-time as you configure it. Preview updates automatically with 500ms debounce.
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

            <!-- File path is auto-generated from slug: /p5/view.php?slug=your-slug -->

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
                <label class="form-label">Background Image URL (optional)</label>
                <input
                    type="url"
                    id="background_image_url"
                    name="background_image_url"
                    class="form-control"
                    placeholder="https://example.com/background.png"
                    value="<?php echo $formData ? htmlspecialchars($formData['background_image_url'] ?? '') : ($editPiece ? htmlspecialchars($editPiece['background_image_url'] ?? '') : ''); ?>"
                >
                <small class="form-help">Optional background image for the sketch</small>
            </div>

            <div class="form-group">
                <label for="tags" class="form-label">Tags</label>
                <input
                    type="text"
                    id="tags"
                    name="tags"
                    class="form-control"
                    placeholder="P5.js, Processing, Generative, Animation"
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

            <!-- Advanced P5.js Sketch Configurator -->
            <div class="card" style="margin-top: 30px; border: 2px solid #ED225D;">
                <div class="card-header" style="background: linear-gradient(135deg, #ED225D 0%, #F06292 100%); color: white;">
                    <h3 style="margin: 0; display: flex; align-items: center; justify-content: space-between;">
                        <span>🎨 P5.js Sketch Configurator</span>
                        <small style="opacity: 0.9; font-weight: normal;">(Creative Coding Settings)</small>
                    </h3>
                    <p style="margin: 5px 0 0 0; font-size: 14px; opacity: 0.95;">
                        Configure your P5.js sketch parameters for generative art
                    </p>
                </div>

                <div style="padding: 20px;">
                    <!-- Canvas Setup -->
                    <div class="sketch-section">
                        <h4 class="sketch-section-title">Canvas Setup</h4>

                        <div class="sketch-row">
                            <div class="sketch-field-group">
                                <label class="sketch-field-label">Canvas Width (px)</label>
                                <input type="number" id="p5-width" class="sketch-field-input" value="800" step="10">
                            </div>
                            <div class="sketch-field-group">
                                <label class="sketch-field-label">Canvas Height (px)</label>
                                <input type="number" id="p5-height" class="sketch-field-input" value="600" step="10">
                            </div>
                            <div class="sketch-field-group">
                                <label class="sketch-field-label">Renderer</label>
                                <select id="p5-renderer" class="sketch-field-input">
                                    <option value="P2D">2D (P2D)</option>
                                    <option value="WEBGL">3D (WEBGL)</option>
                                </select>
                            </div>
                        </div>

                        <div class="sketch-row">
                            <div class="sketch-field-group">
                                <label class="sketch-field-label">Background Color</label>
                                <input type="color" id="p5-background" class="sketch-field-input" value="#FFFFFF">
                            </div>
                            <div class="sketch-field-group">
                                <label class="sketch-field-label">Color Mode</label>
                                <select id="p5-color-mode" class="sketch-field-input">
                                    <option value="RGB">RGB (0-255)</option>
                                    <option value="HSB">HSB (Hue, Saturation, Brightness)</option>
                                </select>
                            </div>
                            <div class="sketch-field-group">
                                <label class="sketch-field-label">Frame Rate</label>
                                <input type="number" id="p5-frame-rate" class="sketch-field-input" value="60" min="1" max="120">
                            </div>
                        </div>
                    </div>

                    <!-- Drawing Style -->
                    <div class="sketch-section">
                        <h4 class="sketch-section-title">Drawing Style</h4>

                        <div class="sketch-row">
                            <div class="sketch-field-group">
                                <label class="sketch-field-label">Shape Type</label>
                                <select id="p5-shape-type" class="sketch-field-input">
                                    <option value="ellipse">Ellipse/Circle</option>
                                    <option value="rect">Rectangle</option>
                                    <option value="triangle">Triangle</option>
                                    <option value="line">Line</option>
                                    <option value="point">Point</option>
                                    <option value="bezier">Bezier Curve</option>
                                    <option value="polygon">Polygon</option>
                                    <option value="custom">Custom Shape</option>
                                </select>
                            </div>
                            <div class="sketch-field-group">
                                <label class="sketch-field-label">Shape Count</label>
                                <input type="number" id="p5-shape-count" class="sketch-field-input" value="100" min="1" max="10000">
                            </div>
                            <div class="sketch-field-group">
                                <label class="sketch-field-label">Shape Size</label>
                                <input type="range" id="p5-shape-size" class="sketch-field-input" value="20" step="0.1" min="0.1" max="100">
                                <span id="p5-shape-size-value" style="color: #ED225D; font-weight: bold;">20.0</span>
                            </div>
                        </div>

                        <div class="sketch-row">
                            <div class="sketch-field-group">
                                <label class="sketch-field-label">Stroke Weight</label>
                                <input type="number" id="p5-stroke-weight" class="sketch-field-input" value="1" step="0.5" min="0">
                            </div>
                            <div class="sketch-field-group">
                                <label class="sketch-field-label">Stroke Color</label>
                                <input type="color" id="p5-stroke-color" class="sketch-field-input" value="#000000">
                            </div>
                            <div class="sketch-field-group">
                                <label class="sketch-field-label">
                                    <input type="checkbox" id="p5-no-stroke">
                                    No Stroke
                                </label>
                            </div>
                        </div>

                        <div class="sketch-row">
                            <div class="sketch-field-group">
                                <label class="sketch-field-label">Fill Color</label>
                                <input type="color" id="p5-fill-color" class="sketch-field-input" value="#ED225D">
                            </div>
                            <div class="sketch-field-group">
                                <label class="sketch-field-label">Fill Opacity</label>
                                <input type="range" id="p5-fill-opacity" class="sketch-field-input" value="255" min="0" max="255">
                                <span id="p5-fill-opacity-value">255</span>
                            </div>
                            <div class="sketch-field-group">
                                <label class="sketch-field-label">
                                    <input type="checkbox" id="p5-no-fill">
                                    No Fill
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Shape & Color Palette -->
                    <div class="sketch-section">
                        <h4 class="sketch-section-title">Shape & Color Palette</h4>
                        <p class="form-help" style="margin-bottom: 15px;">Define the shapes and colors that will be used in your sketch</p>

                        <div id="p5-shape-palette-container">
                            <!-- Shape+color items will be dynamically added here -->
                        </div>
                        <button type="button" class="btn btn-sm btn-success" onclick="addP5Shape()">
                            + Add Shape & Color
                        </button>
                        <div class="sketch-field-group" style="margin-top: 15px;">
                            <label class="sketch-field-label">
                                <input type="checkbox" id="p5-use-palette">
                                Use Random Shapes from Palette
                            </label>
                        </div>
                    </div>

                    <!-- Pattern & Generation -->
                    <div class="sketch-section">
                        <h4 class="sketch-section-title">Pattern & Generation</h4>

                        <div class="sketch-row">
                            <div class="sketch-field-group">
                                <label class="sketch-field-label">Pattern Type</label>
                                <select id="p5-pattern-type" class="sketch-field-input">
                                    <option value="grid">Grid</option>
                                    <option value="random">Random</option>
                                    <option value="noise">Perlin Noise</option>
                                    <option value="spiral">Spiral</option>
                                    <option value="radial">Radial</option>
                                    <option value="flow">Flow Field</option>
                                    <option value="fractal">Fractal</option>
                                </select>
                            </div>
                            <div class="sketch-field-group">
                                <label class="sketch-field-label">Spacing/Density</label>
                                <input type="number" id="p5-spacing" class="sketch-field-input" value="30" step="1" min="1">
                            </div>
                            <div class="sketch-field-group">
                                <label class="sketch-field-label">Random Seed</label>
                                <input type="number" id="p5-random-seed" class="sketch-field-input" value="42">
                            </div>
                        </div>

                        <div class="sketch-row">
                            <div class="sketch-field-group">
                                <label class="sketch-field-label">Noise Scale</label>
                                <input type="number" id="p5-noise-scale" class="sketch-field-input" value="0.01" step="0.001" min="0.001">
                                <small class="form-help">Controls Perlin noise smoothness when Pattern Type is set to Noise.</small>
                            </div>
                            <div class="sketch-field-group">
                                <label class="sketch-field-label">Noise Detail</label>
                                <input type="number" id="p5-noise-detail" class="sketch-field-input" value="4" step="1" min="1" max="8">
                                <small class="form-help">Sets Perlin noise octaves for the Noise pattern.</small>
                            </div>
                        </div>
                    </div>

                    <!-- Animation Settings -->
                    <div class="sketch-section">
                        <h4 class="sketch-section-title">Animation Settings</h4>
                        <p class="form-help" style="margin-bottom: 15px;">Enable independent animations for your sketch. Multiple animations can run simultaneously.</p>

                        <div class="sketch-row">
                            <div class="sketch-field-group">
                                <label class="sketch-field-label">
                                    <input type="checkbox" id="p5-clear-background">
                                    Clear Background Each Frame
                                </label>
                            </div>
                        </div>

                        <!-- Rotation Animation -->
                        <details class="animation-details" style="margin-bottom: 15px; border: 1px solid #ddd; border-radius: 6px; padding: 10px; background: #f8f9fa;">
                            <summary style="cursor: pointer; font-weight: bold; color: #ED225D;">📐 Rotation Animation</summary>
                            <div style="margin-top: 15px; padding-left: 20px;">
                                <div class="sketch-row">
                                    <div class="sketch-field-group">
                                        <label class="sketch-field-label">
                                            <input type="checkbox" id="p5-animation-rotation-enabled" onchange="updateP5Configuration()">
                                            Enable Rotation
                                        </label>
                                    </div>
                                    <div class="sketch-field-group">
                                        <label class="sketch-field-label">
                                            <input type="checkbox" id="p5-animation-rotation-loop" checked onchange="updateP5Configuration()">
                                            Loop
                                        </label>
                                    </div>
                                    <div class="sketch-field-group">
                                        <label class="sketch-field-label">
                                            <input type="checkbox" id="p5-animation-rotation-counterclockwise" onchange="updateP5Configuration()">
                                            Counterclockwise
                                        </label>
                                    </div>
                                </div>
                                <div class="sketch-row">
                                    <div class="sketch-field-group">
                                        <label class="sketch-field-label">Speed</label>
                                        <input type="range" id="p5-animation-rotation-speed" class="sketch-field-input" value="1" step="0.1" min="1" max="10" onchange="updateP5Configuration()">
                                        <span id="p5-animation-rotation-speed-value" style="color: #ED225D; font-weight: bold;">1.0</span>
                                    </div>
                                </div>
                            </div>
                        </details>

                        <!-- Scale/Pulse Animation -->
                        <details class="animation-details" style="margin-bottom: 15px; border: 1px solid #ddd; border-radius: 6px; padding: 10px; background: #f8f9fa;">
                            <summary style="cursor: pointer; font-weight: bold; color: #ED225D;">📏 Scale/Pulse Animation</summary>
                            <div style="margin-top: 15px; padding-left: 20px;">
                                <div class="sketch-row">
                                    <div class="sketch-field-group">
                                        <label class="sketch-field-label">
                                            <input type="checkbox" id="p5-animation-scale-enabled" onchange="updateP5Configuration()">
                                            Enable Scale/Pulse
                                        </label>
                                    </div>
                                    <div class="sketch-field-group">
                                        <label class="sketch-field-label">
                                            <input type="checkbox" id="p5-animation-scale-loop" checked onchange="updateP5Configuration()">
                                            Loop
                                        </label>
                                    </div>
                                </div>

                                <!-- Dual-thumb slider for scale min/max -->
                                <div class="sketch-field-group" style="margin-top: 15px;">
                                    <label class="sketch-field-label">Scale Range (0.1x - 10x)</label>
                                    <!-- Dual-thumb slider container -->
                                    <div id="p5-scale-slider" style="position: relative; height: 50px;">
                                        <!-- Track background -->
                                        <div style="position: absolute; top: 20px; left: 0; right: 0; height: 6px; background: #e9ecef; border-radius: 3px;"></div>
                                        <!-- Range highlight (between min and max) -->
                                        <div id="p5-scale-range-highlight" style="position: absolute; top: 20px; height: 6px; background: #28a745; border-radius: 3px; pointer-events: none;"></div>
                                        <!-- Minimum slider -->
                                        <input type="range" id="p5-scale-min"
                                               min="0.1" max="10" step="0.1"
                                               value="0.5"
                                               oninput="updateDualThumbScaleP5('min', parseFloat(this.value))"
                                               style="position: absolute; width: 100%; top: 12px; pointer-events: all; background: transparent; -webkit-appearance: none; appearance: none;">
                                        <!-- Maximum slider -->
                                        <input type="range" id="p5-scale-max"
                                               min="0.1" max="10" step="0.1"
                                               value="2.0"
                                               oninput="updateDualThumbScaleP5('max', parseFloat(this.value))"
                                               style="position: absolute; width: 100%; top: 12px; pointer-events: all; background: transparent; -webkit-appearance: none; appearance: none;">
                                    </div>
                                    <!-- Value labels -->
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 5px;">
                                        <span style="font-size: 13px; color: #6c757d;">
                                            Min: <strong id="p5-scale-min-label" style="color: #495057;">0.5x</strong>
                                        </span>
                                        <span style="font-size: 13px; color: #6c757d;">
                                            Max: <strong id="p5-scale-max-label" style="color: #495057;">2.0x</strong>
                                        </span>
                                    </div>
                                    <small style="display: block; margin-top: 5px; color: #6c757d; font-size: 0.875em;">Drag left thumb for minimum, right thumb for maximum. 0.1 = 10% size, 1.0 = 100% size, 10 = 1000% size</small>
                                </div>

                                <div class="sketch-row" style="margin-top: 15px;">
                                    <div class="sketch-field-group">
                                        <label class="sketch-field-label">Speed</label>
                                        <input type="range" id="p5-animation-scale-speed" class="sketch-field-input" value="1" step="0.1" min="1" max="10" onchange="updateP5Configuration()">
                                        <span id="p5-animation-scale-speed-value" style="color: #ED225D; font-weight: bold;">1.0</span>
                                    </div>
                                </div>
                            </div>
                        </details>

                        <!-- Translation/Movement Animation -->
                        <details class="animation-details" style="margin-bottom: 15px; border: 1px solid #ddd; border-radius: 6px; padding: 10px; background: #f8f9fa;">
                            <summary style="cursor: pointer; font-weight: bold; color: #ED225D;">📍 Translation/Movement Animation</summary>
                            <div style="margin-top: 15px; padding-left: 20px;">
                                <div class="sketch-row">
                                    <div class="sketch-field-group">
                                        <label class="sketch-field-label">
                                            <input type="checkbox" id="p5-animation-translation-enabled" onchange="updateP5Configuration()">
                                            Enable Translation
                                        </label>
                                    </div>
                                    <div class="sketch-field-group">
                                        <label class="sketch-field-label">
                                            <input type="checkbox" id="p5-animation-translation-loop" checked onchange="updateP5Configuration()">
                                            Loop
                                        </label>
                                    </div>
                                </div>
                                <div class="sketch-row">
                                    <div class="sketch-field-group">
                                        <label class="sketch-field-label">Speed</label>
                                        <input type="range" id="p5-animation-translation-speed" class="sketch-field-input" value="1" step="0.1" min="1" max="10" onchange="updateP5Configuration()">
                                        <span id="p5-animation-translation-speed-value" style="color: #ED225D; font-weight: bold;">1.0</span>
                                    </div>
                                </div>
                            </div>
                        </details>

                        <!-- Color Shift Animation -->
                        <details class="animation-details" style="margin-bottom: 15px; border: 1px solid #ddd; border-radius: 6px; padding: 10px; background: #f8f9fa;">
                            <summary style="cursor: pointer; font-weight: bold; color: #ED225D;">🎨 Color Shift Animation</summary>
                            <div style="margin-top: 15px; padding-left: 20px;">
                                <div class="sketch-row">
                                    <div class="sketch-field-group">
                                        <label class="sketch-field-label">
                                            <input type="checkbox" id="p5-animation-color-enabled" onchange="updateP5Configuration()">
                                            Enable Color Shift
                                        </label>
                                    </div>
                                    <div class="sketch-field-group">
                                        <label class="sketch-field-label">
                                            <input type="checkbox" id="p5-animation-color-loop" checked onchange="updateP5Configuration()">
                                            Loop
                                        </label>
                                    </div>
                                </div>
                                <div class="sketch-row">
                                    <div class="sketch-field-group">
                                        <label class="sketch-field-label">Speed</label>
                                        <input type="range" id="p5-animation-color-speed" class="sketch-field-input" value="1" step="0.1" min="1" max="10" onchange="updateP5Configuration()">
                                        <span id="p5-animation-color-speed-value" style="color: #ED225D; font-weight: bold;">1.0</span>
                                    </div>
                                </div>
                            </div>
                        </details>
                    </div>

                    <!-- Interaction Settings -->
                    <div class="sketch-section">
                        <h4 class="sketch-section-title">Interaction Settings</h4>

                        <div class="sketch-row">
                            <div class="sketch-field-group">
                                <label class="sketch-field-label">
                                    <input type="checkbox" id="p5-mouse-interaction">
                                    Enable Mouse Interaction
                                </label>
                            </div>
                            <div class="sketch-field-group">
                                <label class="sketch-field-label">Interaction Type</label>
                                <select id="p5-interaction-type" class="sketch-field-input">
                                    <option value="follow">Follow Mouse</option>
                                    <option value="repel">Repel from Mouse</option>
                                    <option value="attract">Attract to Mouse</option>
                                    <option value="draw">Draw on Click</option>
                                    <option value="change-color">Change Color</option>
                                </select>
                            </div>
                        </div>

                        <div class="sketch-row">
                            <div class="sketch-field-group">
                                <label class="sketch-field-label">
                                    <input type="checkbox" id="p5-keyboard-interaction">
                                    Enable Keyboard Interaction
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Advanced Settings -->
                    <details style="margin-top: 20px;">
                        <summary style="cursor: pointer; font-weight: 600; color: #ED225D; font-size: 16px;">
                            ⚙️ Advanced Settings
                        </summary>
                        <div style="margin-top: 15px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                            <div class="sketch-row">
                                <div class="sketch-field-group">
                                    <label class="sketch-field-label">Blend Mode</label>
                                    <select id="p5-blend-mode" class="sketch-field-input">
                                        <option value="BLEND">Normal (BLEND)</option>
                                        <option value="ADD">Add</option>
                                        <option value="MULTIPLY">Multiply</option>
                                        <option value="SCREEN">Screen</option>
                                        <option value="OVERLAY">Overlay</option>
                                        <option value="DIFFERENCE">Difference</option>
                                        <option value="EXCLUSION">Exclusion</option>
                                    </select>
                                </div>
                                <div class="sketch-field-group">
                                    <label class="sketch-field-label">Rect Mode</label>
                                    <select id="p5-rect-mode" class="sketch-field-input">
                                        <option value="CORNER">Corner</option>
                                        <option value="CENTER">Center</option>
                                        <option value="RADIUS">Radius</option>
                                        <option value="CORNERS">Corners</option>
                                    </select>
                                </div>
                            </div>
                            <div class="sketch-row">
                                <div class="sketch-field-group">
                                    <label class="sketch-field-label">Ellipse Mode</label>
                                    <select id="p5-ellipse-mode" class="sketch-field-input">
                                        <option value="CENTER">Center</option>
                                        <option value="CORNER">Corner</option>
                                        <option value="RADIUS">Radius</option>
                                        <option value="CORNERS">Corners</option>
                                    </select>
                                </div>
                                <div class="sketch-field-group">
                                    <label class="sketch-field-label">Angle Mode</label>
                                    <select id="p5-angle-mode" class="sketch-field-input">
                                        <option value="RADIANS">Radians</option>
                                        <option value="DEGREES">Degrees</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </details>
                </div>
            </div>

            <!-- Hidden field to store P5.js configuration as JSON -->
            <input type="hidden" name="configuration_json" id="configuration_json" value="<?php
                if ($formData && isset($formData['configuration_json_raw'])) {
                    echo htmlspecialchars($formData['configuration_json_raw']);
                } elseif ($editPiece && !empty($editPiece['configuration'])) {
                    echo htmlspecialchars($editPiece['configuration']);
                }
            ?>">

            <div class="form-group" style="margin-top: 30px;">
                <button type="submit" class="btn btn-primary btn-lg">
                    <?php echo $action === 'create' ? 'Create Piece' : 'Update Piece'; ?>
                </button>
                <a href="<?php echo url('admin/p5.php'); ?>" class="btn btn-secondary btn-lg">
                    Cancel
                </a>
                <button type="button" id="preview-btn" class="btn btn-info btn-lg" style="margin-left: 10px;" data-live-preview-scroll>
                    ⬆️ Scroll to Preview
                </button>
            </div>
        </form>
    </div>

    <style>
    .sketch-section {
        background: white;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .sketch-section-title {
        color: #ED225D;
        font-size: 18px;
        font-weight: 600;
        margin: 0 0 15px 0;
        padding-bottom: 10px;
        border-bottom: 2px solid #ED225D;
    }

    .sketch-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 15px;
    }

    .sketch-field-group {
        display: flex;
        flex-direction: column;
    }

    .sketch-field-label {
        font-weight: 600;
        margin-bottom: 5px;
        color: #495057;
        font-size: 14px;
    }

    .sketch-field-input {
        padding: 8px 12px;
        border: 1px solid #ced4da;
        border-radius: 4px;
        font-size: 14px;
    }

    .sketch-field-input:focus {
        outline: none;
        border-color: #ED225D;
        box-shadow: 0 0 0 0.2rem rgba(237, 34, 93, 0.25);
    }

    .p5-color-item {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
    }

    .p5-color-remove-btn {
        background: #dc3545;
        color: white;
        border: none;
        padding: 4px 8px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
    }

    .p5-color-remove-btn:hover {
        background: #c82333;
    }

    /* Dual-thumb range slider styling for P5.js scale animation */
    input[type="range"]#p5-scale-min,
    input[type="range"]#p5-scale-max {
        -webkit-appearance: none;
        appearance: none;
        background: transparent;
        cursor: pointer;
    }

    /* Webkit browsers (Chrome, Safari, Edge) */
    input[type="range"]#p5-scale-min::-webkit-slider-track,
    input[type="range"]#p5-scale-max::-webkit-slider-track {
        background: transparent;
        height: 6px;
    }

    input[type="range"]#p5-scale-min::-webkit-slider-thumb,
    input[type="range"]#p5-scale-max::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #ED225D;
        border: 2px solid white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        cursor: grab;
        margin-top: -6px;
    }

    input[type="range"]#p5-scale-min::-webkit-slider-thumb:active,
    input[type="range"]#p5-scale-max::-webkit-slider-thumb:active {
        cursor: grabbing;
        background: #c81d4f;
    }

    /* Firefox */
    input[type="range"]#p5-scale-min::-moz-range-track,
    input[type="range"]#p5-scale-max::-moz-range-track {
        background: transparent;
        height: 6px;
    }

    input[type="range"]#p5-scale-min::-moz-range-thumb,
    input[type="range"]#p5-scale-max::-moz-range-thumb {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #ED225D;
        border: 2px solid white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        cursor: grab;
    }

    input[type="range"]#p5-scale-min::-moz-range-thumb:active,
    input[type="range"]#p5-scale-max::-moz-range-thumb:active {
        cursor: grabbing;
        background: #c81d4f;
    }
    </style>

    <script>
    // P5.js sketch configuration state
    const p5Config = {
        canvas: {
            width: 800,
            height: 600,
            renderer: 'P2D',
            background: '#FFFFFF',
            colorMode: 'RGB',
            frameRate: 60
        },
        drawing: {
            shapeType: 'ellipse',
            shapeCount: 100,
            shapeSize: 20,
            strokeWeight: 1,
            strokeColor: '#000000',
            noStroke: false,
            fillColor: '#ED225D',
            fillOpacity: 255,
            noFill: false
        },
        shapes: [
            { shape: 'ellipse', color: '#ED225D' },
            { shape: 'rect', color: '#F06292' },
            { shape: 'triangle', color: '#BA68C8' }
        ],
        usePalette: false,
        pattern: {
            type: 'grid',
            spacing: 30,
            randomSeed: 42,
            noiseScale: 0.01,
            noiseDetail: 4
        },
        animation: {
            rotation: {
                enabled: false,
                loop: true,
                counterclockwise: false,
                speed: 1
            },
            scale: {
                enabled: false,
                loop: true,
                min: 0.5,      // NEW: Minimum scale (default different from max for visibility)
                max: 2.0,      // NEW: Maximum scale
                speed: 1
            },
            translation: {
                enabled: false,
                loop: true,
                speed: 1
            },
            color: {
                enabled: false,
                loop: true,
                speed: 1
            },
            clearBackground: true
        },
        interaction: {
            mouse: false,
            mouseType: 'follow',
            keyboard: false
        },
        advanced: {
            blendMode: 'BLEND',
            rectMode: 'CORNER',
            ellipseMode: 'CENTER',
            angleMode: 'RADIANS'
        }
    };

    // Initialize P5 shape palette
    function initializeP5ShapePalette() {
        const container = document.getElementById('p5-shape-palette-container');
        container.innerHTML = '';
        p5Config.shapes.forEach((item, index) => {
            addP5ShapeWithValue(item.shape, item.color);
        });
        updateP5Configuration();
    }

    // Add shape to P5 palette
    function addP5Shape() {
        const shapes = ['ellipse', 'rect', 'triangle'];
        const randomShape = shapes[Math.floor(Math.random() * shapes.length)];
        const randomColor = '#' + Math.floor(Math.random()*16777215).toString(16);
        addP5ShapeWithValue(randomShape, randomColor);
        updateP5Configuration();
    }

    // Add shape with specific values
    function addP5ShapeWithValue(shape, color) {
        const container = document.getElementById('p5-shape-palette-container');
        const index = container.children.length;

        const shapeItem = document.createElement('div');
        shapeItem.className = 'p5-shape-item';
        shapeItem.style.cssText = 'display: flex; align-items: center; margin-bottom: 10px; background: #f8f9fa; padding: 10px; border-radius: 6px;';
        shapeItem.innerHTML = `
            <select onchange="updateP5Shape(${index}, this.value)" style="width: 140px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; margin-right: 10px; font-size: 14px;">
                <option value="ellipse" ${shape === 'ellipse' ? 'selected' : ''}>● Ellipse</option>
                <option value="rect" ${shape === 'rect' ? 'selected' : ''}>■ Rectangle</option>
                <option value="triangle" ${shape === 'triangle' ? 'selected' : ''}>▲ Triangle</option>
                <option value="polygon" ${shape === 'polygon' ? 'selected' : ''}>⬢ Polygon</option>
                <option value="line" ${shape === 'line' ? 'selected' : ''}>━ Line</option>
            </select>
            <input type="color" value="${color}" onchange="updateP5ShapeColor(${index}, this.value)" style="width: 60px; height: 40px; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;">
            <input type="text" value="${color}" onchange="updateP5ShapeColor(${index}, this.value)" style="flex: 1; margin: 0 10px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-family: monospace; font-size: 14px;">
            <button type="button" class="p5-shape-remove-btn" onclick="removeP5Shape(${index})" style="background: #dc3545; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-weight: bold;">✕</button>
        `;
        container.appendChild(shapeItem);

        // Update the shapes array
        if (index >= p5Config.shapes.length) {
            p5Config.shapes.push({ shape: shape, color: color });
        }
    }

    // Update shape type in P5 palette
    function updateP5Shape(index, shape) {
        p5Config.shapes[index].shape = shape;
        updateP5Configuration();
    }

    // Update shape color in P5 palette
    function updateP5ShapeColor(index, value) {
        p5Config.shapes[index].color = value;
        // Sync both inputs
        const shapeItem = document.querySelectorAll('.p5-shape-item')[index];
        const inputs = shapeItem.querySelectorAll('input');
        inputs[0].value = value;
        inputs[1].value = value;
        updateP5Configuration();
    }

    // Remove shape from P5 palette
    function removeP5Shape(index) {
        if (p5Config.shapes.length <= 1) {
            alert('You must have at least one shape in the palette!');
            return;
        }
        p5Config.shapes.splice(index, 1);
        initializeP5ShapePalette();
    }

    // Legacy function - no longer needed with granular animation controls
    // Kept for backward compatibility
    function updateP5AnimationFields() {
        // No-op - granular controls are always visible
    }

    // Collect all P5 form values and update configuration
    function collectP5FormValues() {
        // Canvas settings
        p5Config.canvas.width = parseInt(document.getElementById('p5-width').value);
        p5Config.canvas.height = parseInt(document.getElementById('p5-height').value);
        p5Config.canvas.renderer = document.getElementById('p5-renderer').value;
        p5Config.canvas.background = document.getElementById('p5-background').value;
        p5Config.canvas.colorMode = document.getElementById('p5-color-mode').value;
        p5Config.canvas.frameRate = parseInt(document.getElementById('p5-frame-rate').value);

        // Drawing settings
        p5Config.drawing.shapeType = document.getElementById('p5-shape-type').value;
        p5Config.drawing.shapeCount = parseInt(document.getElementById('p5-shape-count').value);
        p5Config.drawing.shapeSize = parseFloat(document.getElementById('p5-shape-size').value);
        p5Config.drawing.strokeWeight = parseFloat(document.getElementById('p5-stroke-weight').value);
        p5Config.drawing.strokeColor = document.getElementById('p5-stroke-color').value;
        p5Config.drawing.noStroke = document.getElementById('p5-no-stroke').checked;
        p5Config.drawing.fillColor = document.getElementById('p5-fill-color').value;
        p5Config.drawing.fillOpacity = parseInt(document.getElementById('p5-fill-opacity').value);
        p5Config.drawing.noFill = document.getElementById('p5-no-fill').checked;

        // Color palette
        p5Config.usePalette = document.getElementById('p5-use-palette').checked;

        // Pattern settings
        p5Config.pattern.type = document.getElementById('p5-pattern-type').value;
        p5Config.pattern.spacing = parseFloat(document.getElementById('p5-spacing').value);
        p5Config.pattern.randomSeed = parseInt(document.getElementById('p5-random-seed').value);
        p5Config.pattern.noiseScale = parseFloat(document.getElementById('p5-noise-scale').value);
        p5Config.pattern.noiseDetail = parseInt(document.getElementById('p5-noise-detail').value);

        // Animation settings - granular controls
        p5Config.animation.rotation.enabled = document.getElementById('p5-animation-rotation-enabled').checked;
        p5Config.animation.rotation.loop = document.getElementById('p5-animation-rotation-loop').checked;
        p5Config.animation.rotation.counterclockwise = document.getElementById('p5-animation-rotation-counterclockwise').checked;
        p5Config.animation.rotation.speed = parseFloat(document.getElementById('p5-animation-rotation-speed').value);

        p5Config.animation.scale.enabled = document.getElementById('p5-animation-scale-enabled').checked;
        p5Config.animation.scale.loop = document.getElementById('p5-animation-scale-loop').checked;
        p5Config.animation.scale.min = parseFloat(document.getElementById('p5-scale-min').value);
        p5Config.animation.scale.max = parseFloat(document.getElementById('p5-scale-max').value);
        p5Config.animation.scale.speed = parseFloat(document.getElementById('p5-animation-scale-speed').value);

        p5Config.animation.translation.enabled = document.getElementById('p5-animation-translation-enabled').checked;
        p5Config.animation.translation.loop = document.getElementById('p5-animation-translation-loop').checked;
        p5Config.animation.translation.speed = parseFloat(document.getElementById('p5-animation-translation-speed').value);

        p5Config.animation.color.enabled = document.getElementById('p5-animation-color-enabled').checked;
        p5Config.animation.color.loop = document.getElementById('p5-animation-color-loop').checked;
        p5Config.animation.color.speed = parseFloat(document.getElementById('p5-animation-color-speed').value);

        p5Config.animation.clearBackground = document.getElementById('p5-clear-background').checked;

        // Interaction settings
        p5Config.interaction.mouse = document.getElementById('p5-mouse-interaction').checked;
        p5Config.interaction.mouseType = document.getElementById('p5-interaction-type').value;
        p5Config.interaction.keyboard = document.getElementById('p5-keyboard-interaction').checked;

        // Advanced settings
        p5Config.advanced.blendMode = document.getElementById('p5-blend-mode').value;
        p5Config.advanced.rectMode = document.getElementById('p5-rect-mode').value;
        p5Config.advanced.ellipseMode = document.getElementById('p5-ellipse-mode').value;
        p5Config.advanced.angleMode = document.getElementById('p5-angle-mode').value;
    }

    // Dual-thumb scale slider functions
    function updateDualThumbScaleP5(thumb, value) {
        // Update the value
        if (thumb === 'min') {
            // If min is dragged above max, swap them
            if (value > p5Config.animation.scale.max) {
                p5Config.animation.scale.min = p5Config.animation.scale.max;
                p5Config.animation.scale.max = value;
            } else {
                p5Config.animation.scale.min = value;
            }
        } else { // max thumb
            // If max is dragged below min, swap them
            if (value < p5Config.animation.scale.min) {
                p5Config.animation.scale.max = p5Config.animation.scale.min;
                p5Config.animation.scale.min = value;
            } else {
                p5Config.animation.scale.max = value;
            }
        }

        // Update the UI
        updateDualThumbScaleP5UI();
        updateP5Configuration();
    }

    function updateDualThumbScaleP5UI() {
        const min = p5Config.animation.scale.min;
        const max = p5Config.animation.scale.max;

        // Update slider values
        const minSlider = document.getElementById('p5-scale-min');
        const maxSlider = document.getElementById('p5-scale-max');
        if (minSlider) minSlider.value = min;
        if (maxSlider) maxSlider.value = max;

        // Update labels
        const minLabel = document.getElementById('p5-scale-min-label');
        const maxLabel = document.getElementById('p5-scale-max-label');
        if (minLabel) minLabel.textContent = min.toFixed(1) + 'x';
        if (maxLabel) maxLabel.textContent = max.toFixed(1) + 'x';

        // Update range highlight visual
        const rangeHighlight = document.getElementById('p5-scale-range-highlight');
        if (rangeHighlight) {
            const minPercent = ((min - 0.1) / (10 - 0.1)) * 100;
            const maxPercent = ((max - 0.1) / (10 - 0.1)) * 100;
            rangeHighlight.style.left = minPercent + '%';
            rangeHighlight.style.right = (100 - maxPercent) + '%';
        }
    }

    // Update the hidden configuration field
    function updateP5Configuration() {
        collectP5FormValues();
        document.getElementById('configuration_json').value = JSON.stringify(p5Config, null, 2);
        updateLivePreview(); // Trigger live preview update
    }

    // Initialize everything on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Update fill opacity display
        const fillOpacityInput = document.getElementById('p5-fill-opacity');
        const fillOpacityValue = document.getElementById('p5-fill-opacity-value');

        if (fillOpacityInput && fillOpacityValue) {
            fillOpacityInput.addEventListener('input', function() {
                fillOpacityValue.textContent = this.value;
                updateP5Configuration();
            });
        }

        // Update shape size display
        const shapeSizeInput = document.getElementById('p5-shape-size');
        const shapeSizeValue = document.getElementById('p5-shape-size-value');

        if (shapeSizeInput && shapeSizeValue) {
            shapeSizeInput.addEventListener('input', function() {
                shapeSizeValue.textContent = parseFloat(this.value).toFixed(1);
                updateP5Configuration();
            });
        }

        // Update animation speed displays
        const rotationSpeedInput = document.getElementById('p5-animation-rotation-speed');
        const rotationSpeedValue = document.getElementById('p5-animation-rotation-speed-value');
        if (rotationSpeedInput && rotationSpeedValue) {
            rotationSpeedInput.addEventListener('input', function() {
                rotationSpeedValue.textContent = parseFloat(this.value).toFixed(1);
            });
        }

        const scaleSpeedInput = document.getElementById('p5-animation-scale-speed');
        const scaleSpeedValue = document.getElementById('p5-animation-scale-speed-value');
        if (scaleSpeedInput && scaleSpeedValue) {
            scaleSpeedInput.addEventListener('input', function() {
                scaleSpeedValue.textContent = parseFloat(this.value).toFixed(1);
            });
        }

        const translationSpeedInput = document.getElementById('p5-animation-translation-speed');
        const translationSpeedValue = document.getElementById('p5-animation-translation-speed-value');
        if (translationSpeedInput && translationSpeedValue) {
            translationSpeedInput.addEventListener('input', function() {
                translationSpeedValue.textContent = parseFloat(this.value).toFixed(1);
            });
        }

        const colorSpeedInput = document.getElementById('p5-animation-color-speed');
        const colorSpeedValue = document.getElementById('p5-animation-color-speed-value');
        if (colorSpeedInput && colorSpeedValue) {
            colorSpeedInput.addEventListener('input', function() {
                colorSpeedValue.textContent = parseFloat(this.value).toFixed(1);
            });
        }

        // CRITICAL: Explicit background color listener (defensive programming)
        // Ensures background color changes are always captured
        const backgroundColorInput = document.getElementById('p5-background');
        if (backgroundColorInput) {
            backgroundColorInput.addEventListener('change', function() {
                console.log('P5.js background color changed to:', this.value);
                updateP5Configuration();
            });
            backgroundColorInput.addEventListener('input', function() {
                updateP5Configuration();
            });
        }

        // Add change listeners to all inputs
        const inputs = document.querySelectorAll('.sketch-field-input, #p5-no-stroke, #p5-no-fill, #p5-use-palette, #p5-clear-background, #p5-mouse-interaction, #p5-keyboard-interaction');
        inputs.forEach(input => {
            input.addEventListener('change', updateP5Configuration);
            input.addEventListener('input', updateP5Configuration);
        });

        // Initialize shape palette
        initializeP5ShapePalette();

        // CRITICAL: Update configuration before form submission
        // Ensures latest values (especially background color) are captured
        const artForm = document.getElementById('art-form');
        if (artForm) {
            artForm.addEventListener('submit', function(e) {
                console.log('P5.js form submitting - updating configuration...');
                updateP5Configuration();
                console.log('Configuration JSON:', document.getElementById('configuration_json').value);
            });
        }

        // Load existing configuration if editing or on form errors
        <?php
        $configToLoad = null;
        if ($formData && isset($formData['configuration_json_raw'])) {
            // Form error - reload from preserved data
            $configToLoad = $formData['configuration_json_raw'];
        } elseif ($editPiece && !empty($editPiece['configuration'])) {
            // Normal edit mode
            $configToLoad = $editPiece['configuration'];
        }
        ?>
        <?php if ($configToLoad): ?>
        try {
            const savedConfig = <?php echo $configToLoad; ?>;
            if (savedConfig) {
                // Load canvas settings
                if (savedConfig.canvas) {
                    document.getElementById('p5-width').value = savedConfig.canvas.width;
                    document.getElementById('p5-height').value = savedConfig.canvas.height;
                    document.getElementById('p5-renderer').value = savedConfig.canvas.renderer;
                    document.getElementById('p5-background').value = savedConfig.canvas.background;
                    document.getElementById('p5-color-mode').value = savedConfig.canvas.colorMode;
                    document.getElementById('p5-frame-rate').value = savedConfig.canvas.frameRate;
                }

                // Load drawing settings
                if (savedConfig.drawing) {
                    document.getElementById('p5-shape-type').value = savedConfig.drawing.shapeType;
                    document.getElementById('p5-shape-count').value = savedConfig.drawing.shapeCount;
                    document.getElementById('p5-shape-size').value = savedConfig.drawing.shapeSize;
                    document.getElementById('p5-shape-size-value').textContent = parseFloat(savedConfig.drawing.shapeSize).toFixed(1);
                    document.getElementById('p5-stroke-weight').value = savedConfig.drawing.strokeWeight;
                    document.getElementById('p5-stroke-color').value = savedConfig.drawing.strokeColor;
                    document.getElementById('p5-no-stroke').checked = savedConfig.drawing.noStroke;
                    document.getElementById('p5-fill-color').value = savedConfig.drawing.fillColor;
                    document.getElementById('p5-fill-opacity').value = savedConfig.drawing.fillOpacity;
                    fillOpacityValue.textContent = savedConfig.drawing.fillOpacity;
                    document.getElementById('p5-no-fill').checked = savedConfig.drawing.noFill;
                }

                // Load color palette
                if (savedConfig.colors) {
                    p5Config.colors = savedConfig.colors;
                    initializeP5ColorPalette();
                }
                if (savedConfig.usePalette !== undefined) {
                    document.getElementById('p5-use-palette').checked = savedConfig.usePalette;
                }

                // Load pattern settings
                if (savedConfig.pattern) {
                    document.getElementById('p5-pattern-type').value = savedConfig.pattern.type;
                    document.getElementById('p5-spacing').value = savedConfig.pattern.spacing;
                    document.getElementById('p5-random-seed').value = savedConfig.pattern.randomSeed;
                    document.getElementById('p5-noise-scale').value = savedConfig.pattern.noiseScale;
                    document.getElementById('p5-noise-detail').value = savedConfig.pattern.noiseDetail;
                }

                // Load shapes (with backward compatibility for old colors format)
                if (savedConfig.shapes) {
                    p5Config.shapes = savedConfig.shapes;
                    initializeP5ShapePalette();
                } else if (savedConfig.colors) {
                    // Migrate old colors format to new shapes format
                    p5Config.shapes = savedConfig.colors.map(color => ({
                        shape: 'ellipse',
                        color: color
                    }));
                    initializeP5ShapePalette();
                }

                // Load animation settings
                if (savedConfig.animation) {
                    // Check for new granular format
                    if (savedConfig.animation.rotation) {
                        // New format with granular controls
                        p5Config.animation = savedConfig.animation;

                        // Load rotation animation
                        if (savedConfig.animation.rotation) {
                            document.getElementById('p5-animation-rotation-enabled').checked = savedConfig.animation.rotation.enabled || false;
                            document.getElementById('p5-animation-rotation-loop').checked = savedConfig.animation.rotation.loop !== false;
                            document.getElementById('p5-animation-rotation-counterclockwise').checked = savedConfig.animation.rotation.counterclockwise || false;
                            document.getElementById('p5-animation-rotation-speed').value = savedConfig.animation.rotation.speed || 1;
                            document.getElementById('p5-animation-rotation-speed-value').textContent = parseFloat(savedConfig.animation.rotation.speed || 1).toFixed(1);
                        }

                        // Load scale animation
                        if (savedConfig.animation.scale) {
                            document.getElementById('p5-animation-scale-enabled').checked = savedConfig.animation.scale.enabled || false;
                            document.getElementById('p5-animation-scale-loop').checked = savedConfig.animation.scale.loop !== false;

                            // Load min/max values (with backward compatibility)
                            p5Config.animation.scale.min = savedConfig.animation.scale.min !== undefined ? savedConfig.animation.scale.min : 0.5;
                            p5Config.animation.scale.max = savedConfig.animation.scale.max !== undefined ? savedConfig.animation.scale.max : 2.0;
                            document.getElementById('p5-scale-min').value = p5Config.animation.scale.min;
                            document.getElementById('p5-scale-max').value = p5Config.animation.scale.max;

                            // Update UI for dual-thumb slider
                            updateDualThumbScaleP5UI();

                            document.getElementById('p5-animation-scale-speed').value = savedConfig.animation.scale.speed || 1;
                            document.getElementById('p5-animation-scale-speed-value').textContent = parseFloat(savedConfig.animation.scale.speed || 1).toFixed(1);
                        }

                        // Load translation animation
                        if (savedConfig.animation.translation) {
                            document.getElementById('p5-animation-translation-enabled').checked = savedConfig.animation.translation.enabled || false;
                            document.getElementById('p5-animation-translation-loop').checked = savedConfig.animation.translation.loop !== false;
                            document.getElementById('p5-animation-translation-speed').value = savedConfig.animation.translation.speed || 1;
                            document.getElementById('p5-animation-translation-speed-value').textContent = parseFloat(savedConfig.animation.translation.speed || 1).toFixed(1);
                        }

                        // Load color animation
                        if (savedConfig.animation.color) {
                            document.getElementById('p5-animation-color-enabled').checked = savedConfig.animation.color.enabled || false;
                            document.getElementById('p5-animation-color-loop').checked = savedConfig.animation.color.loop !== false;
                            document.getElementById('p5-animation-color-speed').value = savedConfig.animation.color.speed || 1;
                            document.getElementById('p5-animation-color-speed-value').textContent = parseFloat(savedConfig.animation.color.speed || 1).toFixed(1);
                        }

                        // Load clearBackground
                        if (savedConfig.animation.clearBackground !== undefined) {
                            document.getElementById('p5-clear-background').checked = savedConfig.animation.clearBackground;
                        }
                    } else {
                        // Old format - migrate to new format
                        console.log('Migrating old animation format to granular format');
                        const oldType = savedConfig.animation.type || 'rotation';
                        const oldSpeed = savedConfig.animation.speed || 1;
                        const oldLoop = savedConfig.animation.loop !== false;
                        const oldAnimated = savedConfig.animation.animated || false;

                        // Map old type to new format
                        if (oldAnimated) {
                            if (oldType === 'rotation') {
                                p5Config.animation.rotation.enabled = true;
                                p5Config.animation.rotation.speed = oldSpeed;
                                p5Config.animation.rotation.loop = oldLoop;
                                document.getElementById('p5-animation-rotation-enabled').checked = true;
                                document.getElementById('p5-animation-rotation-speed').value = oldSpeed;
                                document.getElementById('p5-animation-rotation-speed-value').textContent = parseFloat(oldSpeed).toFixed(1);
                                document.getElementById('p5-animation-rotation-loop').checked = oldLoop;
                            } else if (oldType === 'scale') {
                                p5Config.animation.scale.enabled = true;
                                p5Config.animation.scale.speed = oldSpeed;
                                p5Config.animation.scale.loop = oldLoop;
                                document.getElementById('p5-animation-scale-enabled').checked = true;
                                document.getElementById('p5-animation-scale-speed').value = oldSpeed;
                                document.getElementById('p5-animation-scale-speed-value').textContent = parseFloat(oldSpeed).toFixed(1);
                                document.getElementById('p5-animation-scale-loop').checked = oldLoop;
                            } else if (oldType === 'translation') {
                                p5Config.animation.translation.enabled = true;
                                p5Config.animation.translation.speed = oldSpeed;
                                p5Config.animation.translation.loop = oldLoop;
                                document.getElementById('p5-animation-translation-enabled').checked = true;
                                document.getElementById('p5-animation-translation-speed').value = oldSpeed;
                                document.getElementById('p5-animation-translation-speed-value').textContent = parseFloat(oldSpeed).toFixed(1);
                                document.getElementById('p5-animation-translation-loop').checked = oldLoop;
                            }
                        }

                        // Load clearBackground
                        if (savedConfig.animation.clearBackground !== undefined) {
                            document.getElementById('p5-clear-background').checked = savedConfig.animation.clearBackground;
                        }
                    }
                }

                // Load interaction settings
                if (savedConfig.interaction) {
                    document.getElementById('p5-mouse-interaction').checked = savedConfig.interaction.mouse;
                    document.getElementById('p5-interaction-type').value = savedConfig.interaction.mouseType;
                    document.getElementById('p5-keyboard-interaction').checked = savedConfig.interaction.keyboard;
                }

                // Load advanced settings
                if (savedConfig.advanced) {
                    document.getElementById('p5-blend-mode').value = savedConfig.advanced.blendMode;
                    document.getElementById('p5-rect-mode').value = savedConfig.advanced.rectMode;
                    document.getElementById('p5-ellipse-mode').value = savedConfig.advanced.ellipseMode;
                    document.getElementById('p5-angle-mode').value = savedConfig.advanced.angleMode;
                }

                updateP5Configuration();
            }
        } catch (e) {
            console.error('Error loading P5.js configuration:', e);
        }
        <?php endif; ?>
    });

    </script>

<?php endif; ?>

<?php require_once(__DIR__ . '/includes/footer.php'); ?>
