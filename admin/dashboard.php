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

// Get counts for each art type
$aframeCount = count(getArtPieces('aframe', 'active'));
$c2Count = count(getArtPieces('c2', 'active'));
$p5Count = count(getArtPieces('p5', 'active'));
$threejsCount = count(getArtPieces('threejs', 'active'));

// Get recent activity
$recentActivity = getActivityLog(null, 10);

// Include header
require_once(__DIR__ . '/includes/header.php');
?>

<div class="card">
    <div class="card-header">
        <h2>Dashboard Overview</h2>
    </div>

    <div class="d-flex gap-2" style="flex-wrap: wrap;">
        <div class="card" style="flex: 1; min-width: 200px;">
            <h3 style="color: var(--primary-color);"><?php echo $aframeCount; ?></h3>
            <p>A-Frame Pieces</p>
            <a href="<?php echo url('admin/aframe.php'); ?>" class="btn btn-primary btn-sm">Manage</a>
        </div>

        <div class="card" style="flex: 1; min-width: 200px;">
            <h3 style="color: var(--primary-color);"><?php echo $c2Count; ?></h3>
            <p>C2.js Pieces</p>
            <a href="<?php echo url('admin/c2.php'); ?>" class="btn btn-primary btn-sm">Manage</a>
        </div>

        <div class="card" style="flex: 1; min-width: 200px;">
            <h3 style="color: var(--primary-color);"><?php echo $p5Count; ?></h3>
            <p>P5.js Pieces</p>
            <a href="<?php echo url('admin/p5.php'); ?>" class="btn btn-primary btn-sm">Manage</a>
        </div>

        <div class="card" style="flex: 1; min-width: 200px;">
            <h3 style="color: var(--primary-color);"><?php echo $threejsCount; ?></h3>
            <p>Three.js Pieces</p>
            <a href="<?php echo url('admin/threejs.php'); ?>" class="btn btn-primary btn-sm">Manage</a>
        </div>
    </div>
</div>

<?php if (!empty($recentActivity)): ?>
<div class="card">
    <div class="card-header">
        <h2>Recent Activity</h2>
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
        <a href="<?php echo url('admin/aframe.php?action=create'); ?>" class="btn btn-success">
            + Add A-Frame Piece
        </a>
        <a href="<?php echo url('admin/c2.php?action=create'); ?>" class="btn btn-success">
            + Add C2.js Piece
        </a>
        <a href="<?php echo url('admin/p5.php?action=create'); ?>" class="btn btn-success">
            + Add P5.js Piece
        </a>
        <a href="<?php echo url('admin/threejs.php?action=create'); ?>" class="btn btn-success">
            + Add Three.js Piece
        </a>
    </div>
</div>

<div class="alert alert-info">
    <strong>Welcome to CodedArt Admin!</strong> Use the navigation above to manage your art pieces.
    All changes will be logged and you'll receive email notifications for each operation.
</div>

<?php require_once(__DIR__ . '/includes/footer.php'); ?>
