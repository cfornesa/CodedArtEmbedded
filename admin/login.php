<?php
/**
 * Admin Login Page
 * Secure login with rate limiting and session management
 */

require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/includes/db-check.php');
require_once(__DIR__ . '/includes/auth.php');

// Check if database is initialized (will show setup page if not)
requireDatabaseInitialized();

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(url('admin/dashboard.php'));
    exit;
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $result = login($email, $password);

        if ($result['success']) {
            // Successful login - redirect to intended page or dashboard
            $redirectUrl = getRedirectAfterLogin();
            redirect($redirectUrl);
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

// Generate CSRF token for form
$csrfToken = generateCsrfToken();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - CodedArt</title>
    <link rel="stylesheet" href="<?php echo url('admin/assets/admin.css'); ?>">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>CodedArt Admin</h1>
                <p>Sign in to manage your art pieces</p>
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
            <?php endif; ?>

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
                        autocomplete="email"
                        autofocus
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="password" class="form-label required">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        required
                        autocomplete="current-password"
                    >
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block btn-lg">
                        Sign In
                    </button>
                </div>
            </form>

            <div class="text-center mt-3">
                <p>
                    <a href="<?php echo url('admin/register.php'); ?>">
                        Don't have an account? Register here
                    </a>
                </p>
                <p>
                    <a href="<?php echo url('admin/forgot-password.php'); ?>">
                        Forgot your password?
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
