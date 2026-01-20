<?php
/**
 * Reset Password Page
 * Set new password with reset token
 */

require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/includes/auth.php');

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(url('admin/dashboard.php'));
    exit;
}

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

// Validate token exists
if (empty($token)) {
    $error = 'Invalid or missing reset token.';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $resetToken = $_POST['reset_token'] ?? '';

        if (empty($newPassword) || empty($confirmPassword)) {
            $error = 'All fields are required.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } else {
            $result = resetPassword($resetToken, $newPassword);

            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Generate CSRF token
$csrfToken = generateCsrfToken();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - CodedArt Admin</title>
    <link rel="stylesheet" href="<?php echo url('admin/assets/admin.css'); ?>">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>Set New Password</h1>
                <p>Choose a strong password for your account</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>

                <?php if (strpos($error, 'token') !== false): ?>
                    <div class="text-center mt-3">
                        <p>
                            <a href="<?php echo url('admin/forgot-password.php'); ?>" class="btn btn-primary">
                                Request New Reset Link
                            </a>
                        </p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>

                <div class="text-center mt-3">
                    <p>
                        <a href="<?php echo url('admin/login.php'); ?>" class="btn btn-primary btn-lg">
                            Continue to Login
                        </a>
                    </p>
                </div>
            <?php elseif (!$error || strpos($error, 'token') === false): ?>
                <form method="POST" action="" data-validate>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="reset_token" value="<?php echo htmlspecialchars($token); ?>">

                    <div class="form-group">
                        <label for="new_password" class="form-label required">New Password</label>
                        <input
                            type="password"
                            id="new_password"
                            name="new_password"
                            class="form-control"
                            required
                            autofocus
                            autocomplete="new-password"
                            minlength="<?php echo defined('PASSWORD_MIN_LENGTH') ? PASSWORD_MIN_LENGTH : 8; ?>"
                        >
                        <small class="form-help">
                            Minimum <?php echo defined('PASSWORD_MIN_LENGTH') ? PASSWORD_MIN_LENGTH : 8; ?> characters, including uppercase, lowercase, and numbers
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password" class="form-label required">Confirm Password</label>
                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            class="form-control"
                            required
                            autocomplete="new-password"
                        >
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-block btn-lg">
                            Reset Password
                        </button>
                    </div>
                </form>
            <?php endif; ?>

            <div class="text-center mt-3">
                <p>
                    <a href="<?php echo url('admin/login.php'); ?>">
                        Back to Login
                    </a>
                </p>
            </div>

            <div class="text-center mt-3" style="font-size: 14px; color: #6c757d;">
                <p>
                    <a href="<?php echo url(); ?>">
                        ← Back to Main Site
                    </a>
                </p>
            </div>
        </div>
    </div>

    <script src="<?php echo url('admin/assets/admin.js'); ?>"></script>
</body>
</html>
