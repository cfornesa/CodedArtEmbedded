<?php
/**
 * Admin Header Component
 * Included at the top of all admin pages
 */

// Ensure user is authenticated
require_once(__DIR__ . '/auth.php');
requireAuth();

$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Admin'; ?> - CodedArt</title>
    <link rel="stylesheet" href="<?php echo url('admin/assets/admin.css'); ?>">
</head>
<body>
    <div class="admin-header">
        <div class="admin-container">
            <div class="d-flex justify-between align-center">
                <div>
                    <h1>CodedArt Admin</h1>
                    <div class="user-info">
                        Welcome, <?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?>
                    </div>
                </div>
                <div>
                    <a href="<?php echo url('admin/profile.php'); ?>" class="btn btn-secondary btn-sm">
                        Profile
                    </a>
                    <a href="<?php echo url('admin/logout.php'); ?>" class="btn btn-secondary btn-sm">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php require_once(__DIR__ . '/nav.php'); ?>

    <div class="admin-container">
