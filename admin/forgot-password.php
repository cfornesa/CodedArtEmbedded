<?php
/**
 * Forgot Password Page
 * Request password reset link
 */

require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/includes/auth.php');
require_once(__DIR__ . '/includes/email-notifications.php');

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(url('admin/dashboard.php'));
    exit;
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email = $_POST['email'] ?? '';

        $result = initiatePasswordReset($email);

        if ($result['success']) {
            $success = $result['message'];

            // Send email if token was generated
            if (isset($result['token']) && $result['token']) {
                $user = dbFetchOne("SELECT * FROM users WHERE email = ?", [sanitizeEmail($email)]);
                if ($user) {
                    sendPasswordResetEmail(
                        $user['email'],
                        $result['token'],
                        $user['first_name'] . ' ' . $user['last_name']
                    );
                }
            }
        } else {
            // Don't show error to prevent user enumeration
            $success = $result['message'];
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
    <title>Forgot Password - CodedArt Admin</title>
    <link rel="stylesheet" href="<?php echo url('admin/assets/admin.css'); ?>">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>Reset Password</h1>
                <p>Enter your email to receive a password reset link</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>

                <div class="text-center mt-3">
                    <p>
                        <a href="<?php echo url('admin/login.php'); ?>">
                            Return to Login
                        </a>
                    </p>
                </div>
            <?php else: ?>
                <form method="POST" action="" data-validate>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                    <div class="form-group">
                        <label for="email" class="form-label required">Email Address</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control"
                            required
                            autofocus
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                        >
                        <small class="form-help">
                            We'll send you a link to reset your password
                        </small>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-block btn-lg">
                            Send Reset Link
                        </button>
                    </div>
                </form>

                <div class="text-center mt-3">
                    <p>
                        Remember your password? <a href="<?php echo url('admin/login.php'); ?>">Sign in</a>
                    </p>
                </div>
            <?php endif; ?>

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
