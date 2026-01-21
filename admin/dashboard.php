<?php
/**
 * Admin Dashboard
 * Main admin landing page with overview of all art pieces
 */

require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/includes/db-check.php');
require_once(__DIR__ . '/includes/auth.php');
require_once(__DIR__ . '/includes/functions.php');

// Check database is initialized
requireDatabaseInitialized();

// Require authentication
requireAuth();

$page_title = 'Dashboard';

// Get filter parameter
$filterType = isset($_GET['filter']) ? $_GET['filter'] : null;
$validFilters = ['aframe', 'c2', 'p5', 'threejs'];
if ($filterType && !in_array($filterType, $validFilters)) {
    $filterType = null;
}

// Get counts for each art type
$aframeCount = count(getArtPieces('aframe', 'active'));
$c2Count = count(getArtPieces('c2', 'active'));
$p5Count = count(getArtPieces('p5', 'active'));
$threejsCount = count(getArtPieces('threejs', 'active'));

// Get recent activity (filtered if needed)
if ($filterType) {
    // Get activity for specific type
    $recentActivity = dbFetchAll(
        "SELECT al.*, u.first_name, u.last_name
         FROM activity_log al
         JOIN users u ON al.user_id = u.id
         WHERE al.art_type = ?
         ORDER BY al.created_at DESC
         LIMIT 20",
        [$filterType]
    );
} else {
    $recentActivity = getActivityLog(null, 20);
}

// Include header
require_once(__DIR__ . '/includes/header.php');
?>

<div class="card">
    <div class="card-header">
        <h2>Dashboard Overview</h2>
        <?php if ($filterType): ?>
            <p style="margin: 10px 0 0 0; color: #666; font-size: 14px;">
                Showing: <strong><?php echo htmlspecialchars(getArtTypeDisplayName($filterType)); ?></strong>
                <a href="<?php echo url('admin/dashboard.php'); ?>" style="margin-left: 10px; text-decoration: underline;">
                    Clear Filter
                </a>
            </p>
        <?php endif; ?>
    </div>

    <!-- Filter Buttons -->
    <div style="padding: 15px; border-bottom: 1px solid #e9ecef;">
        <div class="d-flex gap-2" style="flex-wrap: wrap;">
            <a href="<?php echo url('admin/dashboard.php'); ?>"
               class="btn btn-sm <?php echo !$filterType ? 'btn-primary' : 'btn-secondary'; ?>">
                All
            </a>
            <a href="<?php echo url('admin/dashboard.php?filter=aframe'); ?>"
               class="btn btn-sm <?php echo $filterType === 'aframe' ? 'btn-primary' : 'btn-secondary'; ?>">
                A-Frame
            </a>
            <a href="<?php echo url('admin/dashboard.php?filter=c2'); ?>"
               class="btn btn-sm <?php echo $filterType === 'c2' ? 'btn-primary' : 'btn-secondary'; ?>">
                C2.js
            </a>
            <a href="<?php echo url('admin/dashboard.php?filter=p5'); ?>"
               class="btn btn-sm <?php echo $filterType === 'p5' ? 'btn-primary' : 'btn-secondary'; ?>">
                P5.js
            </a>
            <a href="<?php echo url('admin/dashboard.php?filter=threejs'); ?>"
               class="btn btn-sm <?php echo $filterType === 'threejs' ? 'btn-primary' : 'btn-secondary'; ?>">
                Three.js
            </a>
        </div>
    </div>

    <div class="d-flex gap-2" style="flex-wrap: wrap; padding: 20px;">
        <?php if (!$filterType || $filterType === 'aframe'): ?>
        <div class="card" style="flex: 1; min-width: 200px; <?php echo $filterType === 'aframe' ? 'border: 2px solid #667eea;' : ''; ?>">
            <h3 style="color: #667eea;"><?php echo $aframeCount; ?></h3>
            <p>A-Frame Pieces</p>
            <a href="<?php echo url('admin/aframe.php'); ?>" class="btn btn-primary btn-sm">Manage</a>
        </div>
        <?php endif; ?>

        <?php if (!$filterType || $filterType === 'c2'): ?>
        <div class="card" style="flex: 1; min-width: 200px; <?php echo $filterType === 'c2' ? 'border: 2px solid #FF6B6B;' : ''; ?>">
            <h3 style="color: #FF6B6B;"><?php echo $c2Count; ?></h3>
            <p>C2.js Pieces</p>
            <a href="<?php echo url('admin/c2.php'); ?>" class="btn btn-primary btn-sm">Manage</a>
        </div>
        <?php endif; ?>

        <?php if (!$filterType || $filterType === 'p5'): ?>
        <div class="card" style="flex: 1; min-width: 200px; <?php echo $filterType === 'p5' ? 'border: 2px solid #ED225D;' : ''; ?>">
            <h3 style="color: #ED225D;"><?php echo $p5Count; ?></h3>
            <p>P5.js Pieces</p>
            <a href="<?php echo url('admin/p5.php'); ?>" class="btn btn-primary btn-sm">Manage</a>
        </div>
        <?php endif; ?>

        <?php if (!$filterType || $filterType === 'threejs'): ?>
        <div class="card" style="flex: 1; min-width: 200px; <?php echo $filterType === 'threejs' ? 'border: 2px solid #764ba2;' : ''; ?>">
            <h3 style="color: #764ba2;"><?php echo $threejsCount; ?></h3>
            <p>Three.js Pieces</p>
            <a href="<?php echo url('admin/threejs.php'); ?>" class="btn btn-primary btn-sm">Manage</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($recentActivity)): ?>
<div class="card">
    <div class="card-header">
        <h2>Recent Activity <?php echo $filterType ? '- ' . htmlspecialchars(getArtTypeDisplayName($filterType)) : ''; ?></h2>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Action</th>
                <th>Art Type</th>
                <th>User</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recentActivity as $activity): ?>
            <tr>
                <td>
                    <span class="badge badge-<?php
                        echo $activity['action_type'] === 'create' ? 'success' :
                            ($activity['action_type'] === 'delete' ? 'danger' : 'secondary');
                    ?>">
                        <?php echo htmlspecialchars(getActionDisplayName($activity['action_type'])); ?>
                    </span>
                </td>
                <td>
                    <?php echo htmlspecialchars(getArtTypeDisplayName($activity['art_type'])); ?>
                </td>
                <td>
                    <?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?>
                </td>
                <td>
                    <?php echo htmlspecialchars(formatDate($activity['created_at'], 'M d, Y g:i A')); ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2>Quick Actions</h2>
    </div>

    <div class="d-flex gap-2" style="flex-wrap: wrap;">
        <?php if (!$filterType || $filterType === 'aframe'): ?>
        <a href="<?php echo url('admin/aframe.php?action=create'); ?>" class="btn btn-success">
            + Add A-Frame Piece
        </a>
        <?php endif; ?>

        <?php if (!$filterType || $filterType === 'c2'): ?>
        <a href="<?php echo url('admin/c2.php?action=create'); ?>" class="btn btn-success">
            + Add C2.js Piece
        </a>
        <?php endif; ?>

        <?php if (!$filterType || $filterType === 'p5'): ?>
        <a href="<?php echo url('admin/p5.php?action=create'); ?>" class="btn btn-success">
            + Add P5.js Piece
        </a>
        <?php endif; ?>

        <?php if (!$filterType || $filterType === 'threejs'): ?>
        <a href="<?php echo url('admin/threejs.php?action=create'); ?>" class="btn btn-success">
            + Add Three.js Piece
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="alert alert-info">
    <strong>Welcome to CodedArt Admin!</strong> Use the navigation above to manage your art pieces.
    All changes will be logged and you'll receive email notifications for each operation.
</div>

<?php require_once(__DIR__ . '/includes/footer.php'); ?>
