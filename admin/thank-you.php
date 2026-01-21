<?php
/**
 * Registration Thank You Page
 * Shows success message and redirects to login
 */

require_once(__DIR__ . '/../config/config.php');

// Check if registration was successful
if (!isset($_SESSION['registration_success'])) {
    // No registration session - redirect to register page
    header('Location: ' . url('admin/register.php'));
    exit;
}

// Get the success message and user info
$message = $_SESSION['registration_success'];
$userEmail = $_SESSION['registration_email'] ?? '';
$userName = $_SESSION['registration_name'] ?? 'there';

// Clear the session variables
unset($_SESSION['registration_success']);
unset($_SESSION['registration_email']);
unset($_SESSION['registration_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You - CodedArt Admin</title>
    <link rel="stylesheet" href="<?php echo url('admin/assets/admin.css'); ?>">
    <meta http-equiv="refresh" content="10;url=<?php echo url('admin/login.php'); ?>">
    <style>
        .thank-you-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }

        .thank-you-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            overflow: hidden;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .thank-you-header {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            padding: 60px 40px;
            text-align: center;
        }

        .check-icon {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: #11998e;
            animation: scaleIn 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }

        .thank-you-header h1 {
            font-size: 36px;
            margin: 0 0 10px 0;
            font-weight: 700;
        }

        .thank-you-header p {
            font-size: 18px;
            opacity: 0.95;
            margin: 0;
        }

        .thank-you-body {
            padding: 50px 40px;
        }

        .message-box {
            background: #f8f9fa;
            border-left: 4px solid #11998e;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 30px;
        }

        .message-box p {
            margin: 0;
            color: #495057;
            line-height: 1.6;
        }

        .info-list {
            list-style: none;
            padding: 0;
            margin: 30px 0;
        }

        .info-list li {
            padding: 15px 0;
            border-bottom: 1px solid #e9ecef;
            color: #6c757d;
            font-size: 15px;
        }

        .info-list li:last-child {
            border-bottom: none;
        }

        .info-list strong {
            color: #333;
            font-weight: 600;
        }

        .countdown {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background: #e7f3ff;
            border-radius: 8px;
            color: #004085;
        }

        .countdown-number {
            font-size: 48px;
            font-weight: bold;
            color: #667eea;
            display: block;
            margin-bottom: 5px;
        }

        .btn-primary {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-block {
            display: block;
            width: 100%;
            text-align: center;
        }

        .text-center {
            text-align: center;
        }

        .mt-3 {
            margin-top: 20px;
        }

        .text-muted {
            color: #6c757d;
            font-size: 14px;
        }

        a {
            color: #667eea;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="thank-you-container">
        <div class="thank-you-card">
            <div class="thank-you-header">
                <div class="check-icon">✓</div>
                <h1>Thank You<?php echo $userName !== 'there' ? ', ' . htmlspecialchars($userName) : ''; ?>!</h1>
                <p>Your account has been created successfully</p>
            </div>

            <div class="thank-you-body">
                <div class="message-box">
                    <p><?php echo htmlspecialchars($message); ?></p>
                </div>

                <ul class="info-list">
                    <?php if ($userEmail): ?>
                    <li>
                        <strong>Email:</strong> <?php echo htmlspecialchars($userEmail); ?>
                    </li>
                    <?php endif; ?>

                    <li>
                        <strong>✉️ Welcome Email:</strong> A confirmation email has been sent to your inbox with your account details.
                    </li>

                    <li>
                        <strong>🎨 Next Steps:</strong> You can now log in and start managing your art pieces!
                    </li>

                    <li>
                        <strong>🔐 Access:</strong> Use your email and password to sign in to the admin panel.
                    </li>
                </ul>

                <div class="countdown">
                    <span class="countdown-number" id="countdown">10</span>
                    <p>Redirecting to login page...</p>
                </div>

                <a href="<?php echo url('admin/login.php'); ?>" class="btn btn-primary btn-block">
                    Continue to Login →
                </a>

                <div class="text-center mt-3">
                    <p class="text-muted">
                        <a href="<?php echo url(); ?>">← Back to Main Site</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Countdown timer
        let seconds = 10;
        const countdownElement = document.getElementById('countdown');

        const interval = setInterval(function() {
            seconds--;
            countdownElement.textContent = seconds;

            if (seconds <= 0) {
                clearInterval(interval);
                window.location.href = '<?php echo url('admin/login.php'); ?>';
            }
        }, 1000);
    </script>
</body>
</html>
