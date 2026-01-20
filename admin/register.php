<?php
/**
 * Admin Registration Page
 * New user registration with RECAPTCHA and email verification
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

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        // Verify RECAPTCHA v3 if enabled
        $recaptchaValid = true;
        if (defined('RECAPTCHA_SECRET_KEY') && !empty(RECAPTCHA_SECRET_KEY)) {
            $recaptchaToken = $_POST['recaptcha_token'] ?? '';

            if (empty($recaptchaToken)) {
                $error = 'RECAPTCHA verification failed. Please try again.';
                $recaptchaValid = false;
            } else {
                // Verify with Google reCAPTCHA v3 API
                $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
                $data = [
                    'secret' => RECAPTCHA_SECRET_KEY,
                    'response' => $recaptchaToken,
                    'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
                ];

                $options = [
                    'http' => [
                        'method' => 'POST',
                        'header' => 'Content-type: application/x-www-form-urlencoded',
                        'content' => http_build_query($data)
                    ]
                ];

                $context = stream_context_create($options);
                $response = file_get_contents($verifyUrl, false, $context);
                $responseData = json_decode($response, true);

                // reCAPTCHA v3 returns a score (0.0 to 1.0)
                // 1.0 is very likely a good interaction, 0.0 is very likely a bot
                $minScore = defined('RECAPTCHA_MIN_SCORE') ? RECAPTCHA_MIN_SCORE : 0.5;

                if (!$responseData['success']) {
                    $error = 'RECAPTCHA verification failed. Please try again.';
                    $recaptchaValid = false;
                    error_log('reCAPTCHA v3 verification failed: ' . json_encode($responseData));
                } elseif ($responseData['score'] < $minScore) {
                    $error = 'Registration blocked. If you believe this is an error, please contact support.';
                    $recaptchaValid = false;
                    error_log('reCAPTCHA v3 score too low: ' . $responseData['score'] . ' (minimum: ' . $minScore . ')');
                } elseif ($responseData['action'] !== 'register') {
                    $error = 'RECAPTCHA verification failed. Invalid action.';
                    $recaptchaValid = false;
                    error_log('reCAPTCHA v3 action mismatch: ' . $responseData['action']);
                }
            }
        }

        if ($recaptchaValid && empty($error)) {
            // Validate password confirmation
            if ($_POST['password'] !== $_POST['password_confirm']) {
                $error = 'Passwords do not match.';
            } else {
                $registrationData = [
                    'email' => $_POST['email'] ?? '',
                    'password' => $_POST['password'] ?? '',
                    'first_name' => $_POST['first_name'] ?? '',
                    'last_name' => $_POST['last_name'] ?? ''
                ];

                $result = registerUser($registrationData);

                if ($result['success']) {
                    // Send verification email if token exists
                    if (isset($result['verification_token']) && $result['verification_token']) {
                        $userName = $registrationData['first_name'] . ' ' . $registrationData['last_name'];
                        sendVerificationEmail(
                            $registrationData['email'],
                            $result['verification_token'],
                            $userName
                        );
                    }

                    $success = $result['message'];

                    // If first user (auto-verified), redirect to login
                    if (strpos($result['message'], 'check your email') === false) {
                        $_SESSION['registration_success'] = $result['message'];
                        redirect(url('admin/login.php'));
                        exit;
                    }
                } else {
                    $error = $result['message'];
                }
            }
        }
    }
}

// Generate CSRF token
$csrfToken = generateCsrfToken();

// Get RECAPTCHA site key
$recaptchaSiteKey = defined('RECAPTCHA_SITE_KEY') ? RECAPTCHA_SITE_KEY : '';
$recaptchaEnabled = !empty($recaptchaSiteKey);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - CodedArt Admin</title>
    <link rel="stylesheet" href="<?php echo url('admin/assets/admin.css'); ?>">
    <?php if ($recaptchaEnabled): ?>
    <script src="https://www.google.com/recaptcha/api.js?render=<?php echo htmlspecialchars($recaptchaSiteKey); ?>"></script>
    <?php endif; ?>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>Create Account</h1>
                <p>Register for CodedArt Admin access</p>
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

            <?php if (!$success): ?>
            <form method="POST" action="" id="registration-form" data-validate>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                <div class="form-group">
                    <label for="first_name" class="form-label required">First Name</label>
                    <input
                        type="text"
                        id="first_name"
                        name="first_name"
                        class="form-control"
                        required
                        autocomplete="given-name"
                        value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="last_name" class="form-label required">Last Name</label>
                    <input
                        type="text"
                        id="last_name"
                        name="last_name"
                        class="form-control"
                        required
                        autocomplete="family-name"
                        value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="email" class="form-label required">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        required
                        autocomplete="email"
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
                        autocomplete="new-password"
                        minlength="<?php echo defined('PASSWORD_MIN_LENGTH') ? PASSWORD_MIN_LENGTH : 8; ?>"
                    >
                    <small class="form-help">
                        Minimum <?php echo defined('PASSWORD_MIN_LENGTH') ? PASSWORD_MIN_LENGTH : 8; ?> characters, including uppercase, lowercase, and numbers
                    </small>
                </div>

                <div class="form-group">
                    <label for="password_confirm" class="form-label required">Confirm Password</label>
                    <input
                        type="password"
                        id="password_confirm"
                        name="password_confirm"
                        class="form-control"
                        required
                        autocomplete="new-password"
                    >
                </div>

                <?php if ($recaptchaEnabled): ?>
                <!-- reCAPTCHA v3 hidden token field -->
                <input type="hidden" name="recaptcha_token" id="recaptcha_token">
                <?php endif; ?>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block btn-lg" id="submit-btn">
                        Create Account
                    </button>
                </div>
            </form>
            <?php endif; ?>

            <div class="text-center mt-3">
                <p>
                    Already have an account? <a href="<?php echo url('admin/login.php'); ?>">Sign in here</a>
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

    <?php if ($recaptchaEnabled): ?>
    <script>
        // reCAPTCHA v3 - Execute on form submission
        document.getElementById('registration-form').addEventListener('submit', function(e) {
            e.preventDefault();

            grecaptcha.ready(function() {
                grecaptcha.execute('<?php echo htmlspecialchars($recaptchaSiteKey); ?>', {action: 'register'})
                    .then(function(token) {
                        document.getElementById('recaptcha_token').value = token;
                        document.getElementById('registration-form').submit();
                    })
                    .catch(function(error) {
                        console.error('reCAPTCHA error:', error);
                        alert('reCAPTCHA verification failed. Please try again.');
                    });
            });
        });
    </script>
    <?php endif; ?>
</body>
</html>
