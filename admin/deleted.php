<?php
/**
 * Deleted Art Management
 * View and restore soft-deleted art pieces
 */

require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/includes/functions.php');
require_once(__DIR__ . '/includes/slug_functions.php');

$page_title = 'Deleted Art Pieces';

// Get art type filter
$typeFilter = $_GET['type'] ?? 'all';
$validTypes = ['all', 'aframe', 'c2', 'p5', 'threejs'];
if (!in_array($typeFilter, $validTypes)) {
    $typeFilter = 'all';
}

// Handle restore action
if (isset($_GET['action']) && $_GET['action'] === 'restore' && isset($_GET['id']) && isset($_GET['type'])) {
    $restoreId = (int)$_GET['id'];
    $restoreType = $_GET['type'];

    if (restoreArtPiece($restoreType, $restoreId)) {
        $success = "Art piece restored successfully! It has been set to 'draft' status.";
    } else {
        $error = "Failed to restore art piece. It may no longer exist or the slug may be in use.";
    }
}

// Handle permanent delete action
if (isset($_GET['action']) && $_GET['action'] === 'permanent_delete' && isset($_GET['id']) && isset($_GET['type'])) {
    if (isset($_POST['confirm_permanent_delete'])) {
        $deleteId = (int)$_GET['id'];
        $deleteType = $_GET['type'];

        $result = deleteArtPieceWithSlug($deleteType, $deleteId, true); // permanent = true

        if ($result['success']) {
            $success = "Art piece permanently deleted. This action cannot be undone.";
        } else {
            $error = $result['message'];
        }
    }
}

// Get deleted pieces
$deletedPieces = [];
if ($typeFilter === 'all') {
    foreach (['aframe', 'c2', 'p5', 'threejs'] as $type) {
        $pieces = getDeletedArtPieces($type);
        foreach ($pieces as &$piece) {
            $piece['art_type'] = $type;
        }
        $deletedPieces = array_merge($deletedPieces, $pieces);
    }
    // Sort by deleted_at desc
    usort($deletedPieces, function($a, $b) {
        return strtotime($b['deleted_at']) - strtotime($a['deleted_at']);
    });
} else {
    $pieces = getDeletedArtPieces($typeFilter);
    foreach ($pieces as &$piece) {
        $piece['art_type'] = $typeFilter;
    }
    $deletedPieces = $pieces;
}

// Calculate days until permanent deletion
$reservationDays = getSiteConfig('slug_reservation_days', 30);

// Include header
require_once(__DIR__ . '/includes/header.php');
?>

<style>
.days-remaining {
    font-size: 0.85em;
    color: #666;
}
.days-remaining.warning {
    color: #ffc107;
    font-weight: bold;
}
.days-remaining.danger {
    color: #dc3545;
    font-weight: bold;
}
.type-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 0.75em;
    font-weight: bold;
    text-transform: uppercase;
}
.type-aframe { background-color: #ff6b6b; color: white; }
.type-c2 { background-color: #4ecdc4; color: white; }
.type-p5 { background-color: #45b7d1; color: white; }
.type-threejs { background-color: #96ceb4; color: white; }
</style>

<?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<?php if (isset($success)): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header d-flex justify-between align-center">
        <h2>🗑️ Deleted Art Pieces</h2>
        <div>
            <select onchange="window.location.href='?type=' + this.value" class="form-control" style="display: inline-block; width: auto;">
                <option value="all" <?php echo $typeFilter === 'all' ? 'selected' : ''; ?>>All Types</option>
                <option value="aframe" <?php echo $typeFilter === 'aframe' ? 'selected' : ''; ?>>A-Frame</option>
                <option value="c2" <?php echo $typeFilter === 'c2' ? 'selected' : ''; ?>>C2.js</option>
                <option value="p5" <?php echo $typeFilter === 'p5' ? 'selected' : ''; ?>>P5.js</option>
                <option value="threejs" <?php echo $typeFilter === 'threejs' ? 'selected' : ''; ?>>Three.js</option>
            </select>
        </div>
    </div>

    <?php if (empty($deletedPieces)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">✨</div>
            <p>No deleted art pieces. Everything is active!</p>
            <a href="<?php echo url('admin/dashboard.php'); ?>" class="btn btn-primary">
                Back to Dashboard
            </a>
        </div>
    <?php else: ?>
        <div style="padding: 15px; background: #f8f9fa; border-bottom: 1px solid #dee2e6;">
            <p style="margin: 0; font-size: 0.9em;">
                <strong>ℹ️ About Soft Delete:</strong> Deleted pieces are kept for <?php echo $reservationDays; ?> days before permanent deletion.
                During this time, their slugs are reserved and pieces can be restored.
            </p>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Title</th>
                    <th>Slug</th>
                    <th>Deleted</th>
                    <th>Days Remaining</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($deletedPieces as $piece): ?>
                    <?php
                    $deletedTime = strtotime($piece['deleted_at']);
                    $expiryTime = strtotime("+{$reservationDays} days", $deletedTime);
                    $daysRemaining = floor(($expiryTime - time()) / 86400);

                    $daysClass = '';
                    if ($daysRemaining <= 3) {
                        $daysClass = 'danger';
                    } elseif ($daysRemaining <= 7) {
                        $daysClass = 'warning';
                    }
                    ?>
                    <tr>
                        <td>
                            <span class="type-badge type-<?php echo $piece['art_type']; ?>">
                                <?php echo strtoupper($piece['art_type']); ?>
                            </span>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($piece['title']); ?></strong>
                            <?php if (!empty($piece['description'])): ?>
                                <br>
                                <small><?php echo htmlspecialchars(substr($piece['description'], 0, 80)) . (strlen($piece['description']) > 80 ? '...' : ''); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <code><?php echo htmlspecialchars($piece['slug'] ?? 'N/A'); ?></code>
                        </td>
                        <td>
                            <small><?php echo date('M d, Y H:i', $deletedTime); ?></small>
                        </td>
                        <td>
                            <span class="days-remaining <?php echo $daysClass; ?>">
                                <?php if ($daysRemaining > 0): ?>
                                    <?php echo $daysRemaining; ?> day<?php echo $daysRemaining != 1 ? 's' : ''; ?>
                                <?php else: ?>
                                    Expired
                                <?php endif; ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a
                                    href="?action=restore&type=<?php echo urlencode($piece['art_type']); ?>&id=<?php echo $piece['id']; ?>"
                                    class="btn btn-sm btn-success"
                                    onclick="return confirm('Restore this art piece? It will be set to draft status.');"
                                >
                                    Restore
                                </a>
                                <button
                                    class="btn btn-sm btn-danger"
                                    onclick="confirmPermanentDelete(<?php echo $piece['id']; ?>, '<?php echo htmlspecialchars($piece['art_type'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($piece['title'], ENT_QUOTES); ?>')"
                                >
                                    Delete Forever
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div style="padding: 15px; background: #fff3cd; border-top: 1px solid #dee2e6;">
            <p style="margin: 0; font-size: 0.85em; color: #856404;">
                ⚠️ <strong>Note:</strong> Pieces shown in <span style="color: #ffc107; font-weight: bold;">yellow</span> or
                <span style="color: #dc3545; font-weight: bold;">red</span> will be permanently deleted soon.
                Restore them if you want to keep them.
            </p>
        </div>
    <?php endif; ?>
</div>

<!-- Permanent Delete Confirmation Modal -->
<div id="confirmModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h3>⚠️ Confirm Permanent Deletion</h3>
        <p>Are you absolutely sure you want to <strong>permanently delete</strong> this art piece?</p>
        <p id="modalPieceName" style="font-weight: bold; color: #dc3545;"></p>
        <p style="color: #666; font-size: 0.9em;">
            This action <strong>CANNOT be undone</strong>. The piece and its slug will be permanently removed from the database.
        </p>
        <form id="permanentDeleteForm" method="POST">
            <input type="hidden" name="confirm_permanent_delete" value="1">
            <div class="form-group" style="margin-top: 20px;">
                <button type="submit" class="btn btn-danger btn-lg">Yes, Delete Forever</button>
                <button type="button" class="btn btn-secondary btn-lg" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function confirmPermanentDelete(id, type, title) {
    document.getElementById('modalPieceName').textContent = title;
    document.getElementById('permanentDeleteForm').action = '?action=permanent_delete&type=' + encodeURIComponent(type) + '&id=' + id;
    document.getElementById('confirmModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('confirmModal').style.display = 'none';
}

// Close modal on outside click
document.getElementById('confirmModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

<style>
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.modal-content {
    background: white;
    padding: 30px;
    border-radius: 8px;
    max-width: 500px;
    width: 90%;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.modal-content h3 {
    margin-top: 0;
    color: #dc3545;
}
</style>

<?php require_once(__DIR__ . '/includes/footer.php'); ?>
