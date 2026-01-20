<?php
/**
 * A-Frame Art Management
 * CRUD interface for A-Frame art pieces
 */

require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/includes/functions.php');

$page_title = 'A-Frame Art Management';

// Handle actions
$action = $_GET['action'] ?? 'list';
$pieceId = $_GET['id'] ?? null;
$error = '';
$success = '';

// Handle delete action
if ($action === 'delete' && $pieceId) {
    $result = deleteArtPiece('aframe', $pieceId);
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
            'description' => $_POST['description'] ?? '',
            'file_path' => $_POST['file_path'] ?? '',
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
            $result = createArtPiece('aframe', $data);
        } else {
            $result = updateArtPiece('aframe', $pieceId, $data);
        }

        if ($result['success']) {
            $success = $result['message'];
            $action = 'list';
        } else {
            $error = $result['message'];
        }
    }
}

// Get art pieces for listing
$artPieces = getArtPieces('aframe');

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
            <a href="<?php echo url('admin/aframe.php?action=create'); ?>" class="btn btn-success">
                + Add New Piece
            </a>
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
                >
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
                    placeholder="/a-frame/piece-name.php"
                    value="<?php echo $editPiece ? htmlspecialchars($editPiece['file_path']) : ''; ?>"
                >
                <small class="form-help">Relative path to the PHP file (e.g., /a-frame/alt-piece.php)</small>
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

            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-lg">
                    <?php echo $action === 'create' ? 'Create Piece' : 'Update Piece'; ?>
                </button>
                <a href="<?php echo url('admin/aframe.php'); ?>" class="btn btn-secondary btn-lg">
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
    </script>

<?php endif; ?>

<?php require_once(__DIR__ . '/includes/footer.php'); ?>
