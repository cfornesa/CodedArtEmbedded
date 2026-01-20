<?php
/**
 * Admin Logout Handler
 * Destroys session and redirects to login page
 */

require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/includes/auth.php');

// Logout user
logout();

// Set success message
$_SESSION['logout_success'] = 'You have been successfully logged out.';

// Redirect to login page
redirect(url('admin/login.php'));
exit;
