<?php
/**
 * Three.js Art Management
 * CRUD interface for Three.js art pieces
 */

require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/includes/functions.php');
require_once(__DIR__ . '/includes/slug_functions.php');

$page_title = 'Three.js Art Management';

// Handle actions
$action = $_GET['action'] ?? 'list';
$pieceId = $_GET['id'] ?? null;
$error = '';
$success = '';

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
        // Prepare data
        $data = [
            'title' => $_POST['title'] ?? '',
            'slug' => $_POST['slug'] ?? '',  // Optional: auto-generated if empty
            'description' => $_POST['description'] ?? '',
            'file_path' => $_POST['file_path'] ?? '',
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

            <div class="form-group">
                <label for="file_path" class="form-label required">File Path</label>
                <input
                    type="text"
                    id="file_path"
                    name="file_path"
                    class="form-control"
                    required
                    placeholder="/three-js/first.php"
                    value="<?php echo $editPiece ? htmlspecialchars($editPiece['file_path']) : ''; ?>"
                >
                <small class="form-help">Relative path to the PHP file (e.g., /three-js/first.php)</small>
            </div>

            <div class="form-group">
                <label for="embedded_path" class="form-label">Embedded Path</label>
                <input
                    type="text"
                    id="embedded_path"
                    name="embedded_path"
                    class="form-control"
                    placeholder="/three-js/first-whole.php"
                    value="<?php echo $editPiece ? htmlspecialchars($editPiece['embedded_path']) : ''; ?>"
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
                    value="<?php echo $editPiece ? htmlspecialchars($editPiece['js_file']) : ''; ?>"
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
                    value="<?php echo $editPiece ? htmlspecialchars($editPiece['thumbnail_url']) : ''; ?>"
                >
                <small class="form-help">URL to thumbnail image (WEBP, JPG, PNG)</small>
                <img id="thumbnail-preview" style="display: none; max-width: 200px; margin-top: 10px;" />
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
                    placeholder="Three.js, 3D, WebGL, Animation"
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

            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-lg">
                    <?php echo $action === 'create' ? 'Create Piece' : 'Update Piece'; ?>
                </button>
                <a href="<?php echo url('admin/threejs.php'); ?>" class="btn btn-secondary btn-lg">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    <script>
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
