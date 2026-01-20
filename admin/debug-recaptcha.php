<?php
/**
 * reCAPTCHA Configuration Debug Tool
 *
 * SECURITY WARNING: DELETE THIS FILE after debugging!
 * This file exposes configuration details and should NEVER be deployed to production.
 */

require_once(__DIR__ . '/../config/config.php');

// Security check - only allow in development
if (!defined('ENVIRONMENT') || ENVIRONMENT !== 'development') {
    die('This debug tool is only available in development mode.');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>reCAPTCHA Debug Tool</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .debug-section {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .debug-section h2 {
            margin-top: 0;
            color: #333;
        }
        .status {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .status.ok {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .status.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        .key-display {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            word-break: break-all;
            padding: 10px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }
        .test-button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .test-button:hover {
            background: #0056b3;
        }
        #test-result {
            margin-top: 20px;
            display: none;
        }
    </style>
    <?php if (defined('RECAPTCHA_SITE_KEY') && !empty(RECAPTCHA_SITE_KEY)): ?>
    <script src="https://www.google.com/recaptcha/api.js?render=<?php echo htmlspecialchars(RECAPTCHA_SITE_KEY); ?>"></script>
    <?php endif; ?>
</head>
<body>
    <div class="debug-section">
        <h1>🔍 reCAPTCHA v3 Debug Tool</h1>
        <p style="color: #e74c3c;"><strong>⚠️ WARNING:</strong> Delete this file after debugging! It should never be deployed to production.</p>
    </div>

    <div class="debug-section">
        <h2>1. Configuration Status</h2>

        <?php
        $siteKey = defined('RECAPTCHA_SITE_KEY') ? RECAPTCHA_SITE_KEY : null;
        $secretKey = defined('RECAPTCHA_SECRET_KEY') ? RECAPTCHA_SECRET_KEY : null;
        $minScore = defined('RECAPTCHA_MIN_SCORE') ? RECAPTCHA_MIN_SCORE : 0.5;

        $siteKeyTrimmed = $siteKey ? trim($siteKey) : null;
        $secretKeyTrimmed = $secretKey ? trim($secretKey) : null;
        $hasWhitespace = false;

        if ($siteKey && $siteKey !== $siteKeyTrimmed) {
            echo '<div class="status error">⚠️ SITE KEY has leading/trailing whitespace!</div>';
            $hasWhitespace = true;
        }
        if ($secretKey && $secretKey !== $secretKeyTrimmed) {
            echo '<div class="status error">⚠️ SECRET KEY has leading/trailing whitespace!</div>';
            $hasWhitespace = true;
        }
        ?>

        <p><strong>RECAPTCHA_SITE_KEY:</strong></p>
        <div class="key-display">
            <?php
            if ($siteKey) {
                echo htmlspecialchars($siteKey);
                echo '<br><small>Length: ' . strlen($siteKey) . ' characters';
                if ($hasWhitespace) echo ' (includes whitespace!)';
                echo '</small>';
            } else {
                echo '<span style="color: #e74c3c;">NOT DEFINED</span>';
            }
            ?>
        </div>

        <p><strong>RECAPTCHA_SECRET_KEY:</strong></p>
        <div class="key-display">
            <?php
            if ($secretKey) {
                // Partially hide secret key for security
                $masked = substr($secretKey, 0, 10) . '...' . substr($secretKey, -10);
                echo htmlspecialchars($masked);
                echo '<br><small>Length: ' . strlen($secretKey) . ' characters';
                if ($hasWhitespace) echo ' (includes whitespace!)';
                echo '</small>';
            } else {
                echo '<span style="color: #e74c3c;">NOT DEFINED</span>';
            }
            ?>
        </div>

        <p><strong>RECAPTCHA_MIN_SCORE:</strong> <code><?php echo $minScore; ?></code></p>

        <?php if ($siteKey && $secretKey): ?>
            <div class="status ok">✅ Both keys are defined</div>
        <?php else: ?>
            <div class="status error">❌ Keys are missing! Check your config.php</div>
        <?php endif; ?>

        <?php if ($hasWhitespace): ?>
            <div class="status error">
                <strong>FIX:</strong> Edit config.php and remove whitespace from keys:
                <pre>define('RECAPTCHA_SITE_KEY', 'your_key_here'); // No spaces!</pre>
            </div>
        <?php endif; ?>
    </div>

    <div class="debug-section">
        <h2>2. Key Format Check</h2>
        <?php
        // v3 site keys typically start with "6L"
        // v2 site keys also start with "6L" but are typically 40 characters
        // v3 keys are typically 40 characters too, so this isn't definitive

        if ($siteKeyTrimmed) {
            $keyLength = strlen($siteKeyTrimmed);
            if ($keyLength === 40) {
                echo '<div class="status ok">✅ Site key length is 40 characters (normal for reCAPTCHA)</div>';
            } else {
                echo '<div class="status warning">⚠️ Site key length is ' . $keyLength . ' characters (expected 40)</div>';
            }

            if (strpos($siteKeyTrimmed, '6L') === 0) {
                echo '<div class="status ok">✅ Site key starts with "6L" (normal format)</div>';
            } else {
                echo '<div class="status warning">⚠️ Site key doesn\'t start with "6L" (unusual)</div>';
            }
        }

        if ($secretKeyTrimmed) {
            $secretLength = strlen($secretKeyTrimmed);
            if ($secretLength === 40) {
                echo '<div class="status ok">✅ Secret key length is 40 characters (normal for reCAPTCHA)</div>';
            } else {
                echo '<div class="status warning">⚠️ Secret key length is ' . $secretLength . ' characters (expected 40)</div>';
            }

            if (strpos($secretKeyTrimmed, '6L') === 0) {
                echo '<div class="status ok">✅ Secret key starts with "6L" (normal format)</div>';
            } else {
                echo '<div class="status warning">⚠️ Secret key doesn\'t start with "6L" (unusual)</div>';
            }
        }
        ?>
    </div>

    <?php if ($siteKey && $secretKey): ?>
    <div class="debug-section">
        <h2>3. Live reCAPTCHA v3 Test</h2>
        <p>Click the button below to test reCAPTCHA v3 with your keys:</p>
        <button onclick="testRecaptcha()" class="test-button">🧪 Test reCAPTCHA v3</button>

        <div id="test-result"></div>
    </div>
    <?php endif; ?>

    <div class="debug-section">
        <h2>4. Common Issues & Solutions</h2>

        <h3>Error: "Invalid key type"</h3>
        <ul>
            <li><strong>Cause:</strong> Using v2 keys with v3 code (or vice versa)</li>
            <li><strong>Fix:</strong> Create NEW keys at <a href="https://www.google.com/recaptcha/admin" target="_blank">google.com/recaptcha/admin</a> and select <strong>"Score based (v3)"</strong></li>
        </ul>

        <h3>Error: "Invalid domain"</h3>
        <ul>
            <li><strong>Cause:</strong> Current domain not registered in reCAPTCHA admin</li>
            <li><strong>Current domain:</strong> <code><?php echo $_SERVER['HTTP_HOST'] ?? 'unknown'; ?></code></li>
            <li><strong>Fix:</strong> Add this domain to your reCAPTCHA key settings</li>
        </ul>

        <h3>Error: "Timeout or duplicate"</h3>
        <ul>
            <li><strong>Cause:</strong> Token expired or reused</li>
            <li><strong>Fix:</strong> Tokens are single-use and expire after 2 minutes - normal behavior</li>
        </ul>
    </div>

    <script>
        function testRecaptcha() {
            const resultDiv = document.getElementById('test-result');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = '<div class="status warning">⏳ Testing reCAPTCHA...</div>';

            grecaptcha.ready(function() {
                grecaptcha.execute('<?php echo htmlspecialchars($siteKey ?? ''); ?>', {action: 'test'})
                    .then(function(token) {
                        // Send token to server for verification
                        fetch('', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'test_token=' + encodeURIComponent(token)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                resultDiv.innerHTML = `
                                    <div class="status ok">
                                        <strong>✅ SUCCESS!</strong><br>
                                        Score: ${data.score} (${data.score >= 0.5 ? 'PASS' : 'FAIL - too low'})<br>
                                        Action: ${data.action}<br>
                                        Hostname: ${data.hostname}<br>
                                        Challenge timestamp: ${data.challenge_ts}
                                    </div>
                                `;
                            } else {
                                resultDiv.innerHTML = `
                                    <div class="status error">
                                        <strong>❌ FAILED</strong><br>
                                        Error codes: ${data.error_codes.join(', ')}<br>
                                        <br>
                                        <strong>Diagnosis:</strong><br>
                                        ${diagnoseError(data.error_codes)}
                                    </div>
                                `;
                            }
                        })
                        .catch(error => {
                            resultDiv.innerHTML = `
                                <div class="status error">
                                    <strong>❌ Request failed:</strong> ${error.message}
                                </div>
                            `;
                        });
                    })
                    .catch(function(error) {
                        resultDiv.innerHTML = `
                            <div class="status error">
                                <strong>❌ reCAPTCHA execution failed:</strong><br>
                                ${error.message || error}
                            </div>
                        `;
                    });
            });
        }

        function diagnoseError(errorCodes) {
            const diagnoses = {
                'invalid-input-secret': 'Your SECRET KEY is incorrect or invalid. Check config.php.',
                'invalid-input-response': 'The token is invalid or has expired.',
                'timeout-or-duplicate': 'Token expired or was already used (this is normal for testing).',
                'invalid-keys': 'You are using v2 keys with v3 code. Create NEW v3 keys!',
                'bad-request': 'Malformed request to Google API.',
                'missing-input-secret': 'Secret key is missing from config.php.',
                'missing-input-response': 'Token was not provided.'
            };

            const messages = errorCodes.map(code => diagnoses[code] || code);
            return messages.join('<br>');
        }
    </script>

    <?php
    // Handle test request
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_token'])) {
        header('Content-Type: application/json');

        $token = $_POST['test_token'];
        $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';

        $data = [
            'secret' => $secretKey,
            'response' => $token,
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

        echo $response;
        exit;
    }
    ?>
</body>
</html>
