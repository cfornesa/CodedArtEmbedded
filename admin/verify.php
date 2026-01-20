<?php
/**
 * Email Verification Page
 * Handles email verification tokens
 */

require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/includes/auth.php');

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

// Process verification if token provided
if (!empty($token)) {
    $result = verifyEmail($token);

    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = $result['message'];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - CodedArt Admin</title>
    <link rel="stylesheet" href="<?php echo url('admin/assets/admin.css'); ?>">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>Email Verification</h1>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>

                <div class="text-center mt-3">
                    <p>
                        <a href="<?php echo url('admin/login.php'); ?>" class="btn btn-primary">
                            Go to Login
                        </a>
                    </p>
                </div>
            <?php elseif ($success): ?>
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
            <?php else: ?>
                <div class="alert alert-info">
                    <p>Please click the verification link in your email to verify your account.</p>
                </div>

                <div class="text-center mt-3">
                    <p>
                        <a href="<?php echo url('admin/login.php'); ?>">
                            Back to Login
                        </a>
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
</body>
</html>
