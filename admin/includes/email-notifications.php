<?php
/**
 * Email Notification System
 * Sends email notifications for CRUD operations on art pieces
 *
 * Features:
 * - Detailed configuration snapshots
 * - Shape-by-shape breakdowns for A-Frame/Three.js
 * - HTML and plain text formatting
 * - Error handling and logging
 */

/**
 * Send email notification for art piece activity
 * @param array $user User who performed the action
 * @param string $action Action type (create, update, delete)
 * @param string $artType Art type
 * @param int $artId Art piece ID
 * @param array $data Piece configuration data
 * @return bool Success status
 */
function sendArtPieceNotification($user, $action, $artType, $artId, $data) {
    // Get admin email
    $adminEmail = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : $user['email'];

    // Build email subject
    $artTypeDisplay = getArtTypeDisplayName($artType);
    $actionDisplay = getActionDisplayName($action);
    $subject = sprintf('[CodedArt] %s - %s - %s', $actionDisplay, $artTypeDisplay, $data['title']);

    // Build email body
    $body = buildNotificationEmailBody($user, $action, $artType, $artId, $data);

    // Send email
    try {
        return sendEmail($adminEmail, $subject, $body);
    } catch (Exception $e) {
        error_log("Failed to send notification email: " . $e->getMessage());
        return false;
    }
}

/**
 * Build notification email body
 * @param array $user User data
 * @param string $action Action type
 * @param string $artType Art type
 * @param int $artId Art piece ID
 * @param array $data Piece data
 * @return string Email body (HTML)
 */
function buildNotificationEmailBody($user, $action, $artType, $artId, $data) {
    $artTypeDisplay = getArtTypeDisplayName($artType);
    $actionDisplay = strtoupper($action);
    $userName = $user['first_name'] . ' ' . $user['last_name'];
    $timestamp = date('Y-m-d H:i:s');

    $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .header { background-color: #4a90e2; color: white; padding: 20px; }
        .content { padding: 20px; }
        .section { margin: 20px 0; padding: 15px; background-color: #f5f5f5; border-left: 4px solid #4a90e2; }
        .config-details { background-color: #fff; padding: 15px; border: 1px solid #ddd; margin: 10px 0; }
        .shape-item { margin: 10px 0; padding: 10px; background-color: #f9f9f9; border-left: 3px solid #4a90e2; }
        .field { margin: 5px 0; }
        .field-label { font-weight: bold; color: #666; }
        .field-value { margin-left: 10px; }
        .footer { margin-top: 30px; padding: 20px; background-color: #f5f5f5; font-size: 12px; color: #666; }
        ul { list-style-type: none; padding-left: 0; }
        li { padding: 3px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>CodedArt Admin Notification</h1>
    </div>

    <div class="content">
        <div class="section">
            <h2>Action Summary</h2>
            <div class="field"><span class="field-label">Action:</span><span class="field-value">{$actionDisplay}</span></div>
            <div class="field"><span class="field-label">Art Type:</span><span class="field-value">{$artTypeDisplay}</span></div>
            <div class="field"><span class="field-label">Piece ID:</span><span class="field-value">{$artId}</span></div>
            <div class="field"><span class="field-label">Title:</span><span class="field-value">{$data['title']}</span></div>
            <div class="field"><span class="field-label">Performed By:</span><span class="field-value">{$userName} ({$user['email']})</span></div>
            <div class="field"><span class="field-label">Timestamp:</span><span class="field-value">{$timestamp}</span></div>
        </div>

HTML;

    // Add piece details section
    $html .= buildPieceDetailsSection($artType, $data);

    // Add configuration details section
    $html .= buildConfigurationSection($artType, $data);

    // Footer
    $html .= <<<HTML
        <div class="footer">
            <p><strong>Purpose:</strong> This email serves as a backup of your art piece configuration. Save this email for your records in case of system failure.</p>
            <p><strong>From:</strong> CodedArt Admin System (admin@codedart.org)</p>
        </div>
    </div>
</body>
</html>
HTML;

    return $html;
}

/**
 * Build piece details section
 * @param string $artType Art type
 * @param array $data Piece data
 * @return string HTML section
 */
function buildPieceDetailsSection($artType, $data) {
    $html = '<div class="section"><h2>Piece Details</h2><div class="config-details">';

    // Common fields
    $html .= '<div class="field"><span class="field-label">Description:</span><span class="field-value">' . htmlspecialchars($data['description'] ?? 'N/A') . '</span></div>';
    $html .= '<div class="field"><span class="field-label">File Path:</span><span class="field-value">' . htmlspecialchars($data['file_path']) . '</span></div>';
    $html .= '<div class="field"><span class="field-label">Status:</span><span class="field-value">' . htmlspecialchars($data['status'] ?? 'active') . '</span></div>';
    $html .= '<div class="field"><span class="field-label">Sort Order:</span><span class="field-value">' . ($data['sort_order'] ?? 0) . '</span></div>';
    $html .= '<div class="field"><span class="field-label">Tags:</span><span class="field-value">' . htmlspecialchars($data['tags'] ?? 'None') . '</span></div>';

    // Thumbnail
    if (!empty($data['thumbnail_url'])) {
        $html .= '<div class="field"><span class="field-label">Thumbnail URL:</span><span class="field-value"><a href="' . htmlspecialchars($data['thumbnail_url']) . '">' . htmlspecialchars($data['thumbnail_url']) . '</a></span></div>';
    }

    // Type-specific fields
    switch ($artType) {
        case 'aframe':
            $html .= '<div class="field"><span class="field-label">Scene Type:</span><span class="field-value">' . htmlspecialchars($data['scene_type'] ?? 'custom') . '</span></div>';

            if (!empty($data['texture_urls'])) {
                $textures = is_string($data['texture_urls']) ? json_decode($data['texture_urls'], true) : $data['texture_urls'];
                if (is_array($textures)) {
                    $html .= '<div class="field"><span class="field-label">Texture URLs:</span><ul>';
                    foreach ($textures as $texture) {
                        $html .= '<li><a href="' . htmlspecialchars($texture) . '">' . htmlspecialchars($texture) . '</a></li>';
                    }
                    $html .= '</ul></div>';
                }
            }
            break;

        case 'c2':
            $html .= '<div class="field"><span class="field-label">Canvas Count:</span><span class="field-value">' . ($data['canvas_count'] ?? 1) . '</span></div>';

            if (!empty($data['js_files'])) {
                $jsFiles = is_string($data['js_files']) ? json_decode($data['js_files'], true) : $data['js_files'];
                if (is_array($jsFiles)) {
                    $html .= '<div class="field"><span class="field-label">JavaScript Files:</span><ul>';
                    foreach ($jsFiles as $file) {
                        $html .= '<li>' . htmlspecialchars($file) . '</li>';
                    }
                    $html .= '</ul></div>';
                }
            }
            break;

        case 'p5':
            if (!empty($data['piece_path'])) {
                $html .= '<div class="field"><span class="field-label">Piece Path:</span><span class="field-value">' . htmlspecialchars($data['piece_path']) . '</span></div>';
            }
            if (!empty($data['screenshot_url'])) {
                $html .= '<div class="field"><span class="field-label">Screenshot URL:</span><span class="field-value"><a href="' . htmlspecialchars($data['screenshot_url']) . '">' . htmlspecialchars($data['screenshot_url']) . '</a></span></div>';
            }
            break;

        case 'threejs':
            if (!empty($data['embedded_path'])) {
                $html .= '<div class="field"><span class="field-label">Embedded Path:</span><span class="field-value">' . htmlspecialchars($data['embedded_path']) . '</span></div>';
            }
            if (!empty($data['js_file'])) {
                $html .= '<div class="field"><span class="field-label">JavaScript File:</span><span class="field-value">' . htmlspecialchars($data['js_file']) . '</span></div>';
            }
            break;
    }

    $html .= '</div></div>';
    return $html;
}

/**
 * Build configuration section with shape-by-shape breakdown
 * @param string $artType Art type
 * @param array $data Piece data
 * @return string HTML section
 */
function buildConfigurationSection($artType, $data) {
    if (empty($data['configuration'])) {
        return '';
    }

    $config = is_string($data['configuration']) ? json_decode($data['configuration'], true) : $data['configuration'];
    if (!is_array($config)) {
        return '';
    }

    $html = '<div class="section"><h2>Configuration Details</h2>';

    // Type-specific configuration display
    switch ($artType) {
        case 'aframe':
        case 'threejs':
            // Display shapes if available
            if (isset($config['shapes']) && is_array($config['shapes'])) {
                $html .= '<h3>Shapes (' . count($config['shapes']) . ' total)</h3>';
                foreach ($config['shapes'] as $index => $shape) {
                    $html .= '<div class="shape-item">';
                    $html .= '<strong>Shape ' . ($index + 1) . ':</strong>';
                    foreach ($shape as $key => $value) {
                        if (is_array($value)) {
                            $value = json_encode($value);
                        }
                        $html .= '<div class="field"><span class="field-label">' . htmlspecialchars(ucfirst($key)) . ':</span><span class="field-value">' . htmlspecialchars($value) . '</span></div>';
                    }
                    $html .= '</div>';
                }
            }

            // Display other config items
            foreach ($config as $key => $value) {
                if ($key === 'shapes') continue;
                $html .= '<div class="field"><span class="field-label">' . htmlspecialchars(ucfirst($key)) . ':</span><span class="field-value">' . htmlspecialchars(is_array($value) ? json_encode($value) : $value) . '</span></div>';
            }
            break;

        default:
            // Display generic configuration
            foreach ($config as $key => $value) {
                $html .= '<div class="field"><span class="field-label">' . htmlspecialchars(ucfirst($key)) . ':</span><span class="field-value">' . htmlspecialchars(is_array($value) ? json_encode($value) : $value) . '</span></div>';
            }
            break;
    }

    $html .= '</div>';
    return $html;
}

/**
 * Send email using configured SMTP settings
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $body Email body (HTML)
 * @return bool Success status
 */
function sendEmail($to, $subject, $body) {
    // For now, use PHP mail() function
    // In production, this should use PHPMailer with SMTP

    $headers = [
        'From: ' . (defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'CodedArt Admin') . ' <' . (defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'admin@codedart.org') . '>',
        'Reply-To: ' . (defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'admin@codedart.org'),
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8'
    ];

    $result = mail($to, $subject, $body, implode("\r\n", $headers));

    if (!$result) {
        error_log("Failed to send email to {$to}: mail() returned false");
    }

    return $result;
}

/**
 * Send email verification message
 * @param string $email User email
 * @param string $token Verification token
 * @param string $userName User name
 * @return bool Success status
 */
function sendVerificationEmail($email, $token, $userName) {
    $verificationUrl = url('admin/verify.php?token=' . urlencode($token));

    $subject = 'Verify Your CodedArt Account';

    $body = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #4a90e2; color: white; padding: 20px; text-align: center; }
        .content { padding: 30px; background-color: #f9f9f9; }
        .button { display: inline-block; padding: 12px 30px; background-color: #4a90e2; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to CodedArt!</h1>
        </div>
        <div class="content">
            <p>Hi {$userName},</p>
            <p>Thank you for registering with CodedArt. To complete your registration, please verify your email address by clicking the button below:</p>
            <p style="text-align: center;">
                <a href="{$verificationUrl}" class="button">Verify Email Address</a>
            </p>
            <p>Or copy and paste this link into your browser:</p>
            <p style="word-break: break-all; color: #666;">{$verificationUrl}</p>
            <p>This verification link will expire in 24 hours.</p>
            <p>If you didn't create this account, you can safely ignore this email.</p>
        </div>
        <div class="footer">
            <p>CodedArt Admin System</p>
            <p>admin@codedart.org</p>
        </div>
    </div>
</body>
</html>
HTML;

    return sendEmail($email, $subject, $body);
}

/**
 * Send password reset email
 * @param string $email User email
 * @param string $token Reset token
 * @param string $userName User name
 * @return bool Success status
 */
function sendPasswordResetEmail($email, $token, $userName) {
    $resetUrl = url('admin/reset-password.php?token=' . urlencode($token));

    $subject = 'Reset Your CodedArt Password';

    $body = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #4a90e2; color: white; padding: 20px; text-align: center; }
        .content { padding: 30px; background-color: #f9f9f9; }
        .button { display: inline-block; padding: 12px 30px; background-color: #4a90e2; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
        .warning { color: #d9534f; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Password Reset Request</h1>
        </div>
        <div class="content">
            <p>Hi {$userName},</p>
            <p>We received a request to reset your CodedArt account password. Click the button below to reset it:</p>
            <p style="text-align: center;">
                <a href="{$resetUrl}" class="button">Reset Password</a>
            </p>
            <p>Or copy and paste this link into your browser:</p>
            <p style="word-break: break-all; color: #666;">{$resetUrl}</p>
            <p>This password reset link will expire in 1 hour.</p>
            <p class="warning">If you didn't request a password reset, please ignore this email and your password will remain unchanged.</p>
        </div>
        <div class="footer">
            <p>CodedArt Admin System</p>
            <p>admin@codedart.org</p>
        </div>
    </div>
</body>
</html>
HTML;

    return sendEmail($email, $subject, $body);
}
