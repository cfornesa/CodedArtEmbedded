<?php
/**
 * Authentication System
 * Handles user authentication, session management, and security
 *
 * Security Features:
 * - Rate limiting on login attempts
 * - Session regeneration on privilege changes
 * - Secure password hashing with bcrypt
 * - Email verification requirement
 * - Account status checking
 * - Session timeout handling
 * - CSRF protection
 */

// Ensure config and helpers are loaded
if (!defined('DB_HOST') && !defined('DB_NAME')) {
    require_once(__DIR__ . '/../../config/config.php');
}
require_once(__DIR__ . '/../../config/environment.php');
require_once(__DIR__ . '/../../config/helpers.php');
require_once(__DIR__ . '/../../config/database.php');

/**
 * Get client IP address
 * @return string
 */
function getClientIp() {
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * Get user agent string
 * @return string
 */
function getUserAgent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
}

/**
 * Log authentication events for auditing
 *
 * @param string $eventType
 * @param int|null $userId
 * @param string|null $email
 * @param array $metadata
 * @return void
 */
function logAuthEvent($eventType, $userId = null, $email = null, $metadata = []) {
    $payload = empty($metadata) ? null : json_encode($metadata);

    try {
        dbInsert('auth_log', [
            'user_id' => $userId,
            'email' => $email,
            'event_type' => $eventType,
            'ip_address' => getClientIp(),
            'user_agent' => substr(getUserAgent(), 0, 255),
            'metadata' => $payload,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        error_log('Auth log error: ' . $e->getMessage());
    }
}

/**
 * Initialize secure session
 * Sets secure session parameters and starts session if not already started
 */
function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Set secure session parameters
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', isHttps() ? 1 : 0);
        ini_set('session.cookie_samesite', 'Strict');

        // Set session name
        session_name('CODEDART_SESSION');

        // Start session
        session_start();

        // Regenerate session ID periodically (every 30 minutes)
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    initSession();

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
        return false;
    }

    // Check session timeout
    if (isset($_SESSION['last_activity'])) {
        $sessionLifetime = defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 3600;
        if (time() - $_SESSION['last_activity'] > $sessionLifetime) {
            logout();
            return false;
        }
    }

    // Update last activity time
    $_SESSION['last_activity'] = time();

    return true;
}

/**
 * Get current logged-in user data
 * @return array|null User data or null if not logged in
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }

    $userId = $_SESSION['user_id'];
    $user = dbFetchOne("SELECT * FROM users WHERE id = ?", [$userId]);

    // Verify user still exists and is active
    if (!$user || $user['status'] !== 'active') {
        logout();
        return null;
    }

    return $user;
}

/**
 * Login user with email and password
 * Implements rate limiting and security checks
 *
 * @param string $email User email
 * @param string $password User password
 * @return array ['success' => bool, 'message' => string, 'user' => array|null]
 */
function login($email, $password) {
    initSession();

    // Input validation
    $email = sanitizeEmail($email);
    if (!isValidEmail($email)) {
        return [
            'success' => false,
            'message' => 'Invalid email format.',
            'user' => null
        ];
    }

    if (empty($password)) {
        return [
            'success' => false,
            'message' => 'Password is required.',
            'user' => null
        ];
    }

    // Check rate limiting
    $rateLimitResult = checkLoginRateLimit($email);
    if (!$rateLimitResult['allowed']) {
        logAuthEvent('login_blocked', null, $email, ['reason' => $rateLimitResult['message']]);
        return [
            'success' => false,
            'message' => $rateLimitResult['message'],
            'user' => null
        ];
    }

    // Fetch user from database
    $user = dbFetchOne("SELECT * FROM users WHERE email = ?", [$email]);

    if (!$user) {
        // Record failed attempt
        recordLoginAttempt($email, getClientIp(), false);
        logAuthEvent('login_failure', null, $email, ['reason' => 'user_not_found']);

        // Generic error message to prevent user enumeration
        return [
            'success' => false,
            'message' => 'Invalid email or password.',
            'user' => null
        ];
    }

    // Verify password
    if (!verifyPassword($password, $user['password_hash'])) {
        // Record failed attempt
        recordLoginAttempt($email, getClientIp(), false);
        logAuthEvent('login_failure', $user['id'], $email, ['reason' => 'invalid_password']);

        return [
            'success' => false,
            'message' => 'Invalid email or password.',
            'user' => null
        ];
    }

    // Check if email is verified (enforced in production)
    if (!$user['email_verified']) {
        if (isProduction()) {
            logAuthEvent('login_failure', $user['id'], $email, ['reason' => 'email_unverified']);
            return [
                'success' => false,
                'message' => 'Please verify your email address before logging in. Check your inbox for the verification link.',
                'user' => null
            ];
        }

        logAuthEvent('login_unverified_bypass', $user['id'], $email, ['environment' => getEnvironment()]);
    }

    // Check account status
    if ($user['status'] !== 'active') {
        $statusMessages = [
            'inactive' => 'Your account has been deactivated. Please contact support.',
            'pending' => 'Your account is pending approval. You will be notified when approved.'
        ];

        logAuthEvent('login_failure', $user['id'], $email, ['reason' => 'status_' . $user['status']]);
        return [
            'success' => false,
            'message' => $statusMessages[$user['status']] ?? 'Your account is not active.',
            'user' => null
        ];
    }

    // Successful login - set up session
    session_regenerate_id(true); // Prevent session fixation

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['last_activity'] = time();
    $_SESSION['last_regeneration'] = time();

    // Update last login time
    dbUpdate('users',
        ['last_login' => date('Y-m-d H:i:s')],
        'id = ?',
        [$user['id']]
    );

    // Record successful attempt
    recordLoginAttempt($email, getClientIp(), true);
    logAuthEvent('login_success', $user['id'], $email);

    // Clear rate limiting data for this user
    clearLoginAttempts($email);

    return [
        'success' => true,
        'message' => 'Login successful!',
        'user' => $user
    ];
}

/**
 * Logout current user
 * Destroys session and clears all session data
 */
function logout() {
    initSession();

    $userId = $_SESSION['user_id'] ?? null;
    $userEmail = $_SESSION['user_email'] ?? null;

    // Unset all session variables
    $_SESSION = [];

    // Destroy session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }

    // Destroy session
    session_destroy();
    logAuthEvent('logout', $userId, $userEmail);
}

/**
 * Register new user
 * Creates user account with email verification
 *
 * @param array $data User registration data
 * @return array ['success' => bool, 'message' => string, 'user_id' => int|null]
 */
function registerUser($data) {
    // Validate required fields
    $requiredFields = ['email', 'password', 'first_name', 'last_name'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            logAuthEvent('register_failure', null, $data['email'] ?? null, ['reason' => 'missing_fields']);
            return [
                'success' => false,
                'message' => 'All fields are required.',
                'user_id' => null
            ];
        }
    }

    // Validate email
    $email = sanitizeEmail($data['email']);
    if (!isValidEmail($email)) {
        logAuthEvent('register_failure', null, $email, ['reason' => 'invalid_email']);
        return [
            'success' => false,
            'message' => 'Invalid email format.',
            'user_id' => null
        ];
    }

    // Check if email already exists
    $existingUser = dbFetchOne("SELECT id FROM users WHERE email = ?", [$email]);
    if ($existingUser) {
        logAuthEvent('register_failure', $existingUser['id'], $email, ['reason' => 'email_exists']);
        return [
            'success' => false,
            'message' => 'An account with this email already exists.',
            'user_id' => null
        ];
    }

    // Validate password strength
    $passwordValidation = validatePassword($data['password']);
    if (!$passwordValidation['valid']) {
        logAuthEvent('register_failure', null, $email, ['reason' => 'weak_password']);
        return [
            'success' => false,
            'message' => implode('. ', $passwordValidation['errors']),
            'user_id' => null
        ];
    }

    // Hash password
    $passwordHash = hashPassword($data['password']);

    // Generate email verification token
    $verificationToken = bin2hex(random_bytes(32));

    // Prepare user data
    $userData = [
        'email' => $email,
        'password_hash' => $passwordHash,
        'first_name' => sanitize($data['first_name']),
        'last_name' => sanitize($data['last_name']),
        'status' => 'active', // First user is auto-activated
        'email_verified' => false,
        'verification_token' => $verificationToken,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];

    // Check if this is the first user (auto-verify and activate)
    $userCount = dbFetchOne("SELECT COUNT(*) as count FROM users");
    if ($userCount && $userCount['count'] == 0) {
        $userData['email_verified'] = true;
        $userData['verification_token'] = null;
    }

    // Auto-verify all users outside production to keep Replit/local setups usable
    if (!$userData['email_verified'] && !isProduction()) {
        $userData['email_verified'] = true;
        $userData['verification_token'] = null;
    }

    try {
        // Insert user
        $userId = dbInsert('users', $userData);

        logAuthEvent('register_success', $userId, $email);
        return [
            'success' => true,
            'message' => $userData['email_verified']
                ? 'Account created successfully! You can now log in.'
                : 'Account created! Please check your email to verify your account.',
            'user_id' => $userId,
            'verification_token' => $userData['email_verified'] ? null : $verificationToken
        ];
    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        logAuthEvent('register_failure', null, $email, ['reason' => 'exception']);
        return [
            'success' => false,
            'message' => 'An error occurred during registration. Please try again.',
            'user_id' => null
        ];
    }
}

/**
 * Verify user email with token
 * @param string $token Verification token
 * @return array ['success' => bool, 'message' => string]
 */
function verifyEmail($token) {
    if (empty($token)) {
        return [
            'success' => false,
            'message' => 'Invalid verification token.'
        ];
    }

    $user = dbFetchOne("SELECT * FROM users WHERE verification_token = ?", [$token]);

    if (!$user) {
        logAuthEvent('verify_email_failure', null, null, ['reason' => 'invalid_token']);
        return [
            'success' => false,
            'message' => 'Invalid or expired verification token.'
        ];
    }

    if ($user['email_verified']) {
        logAuthEvent('verify_email_already', $user['id'], $user['email']);
        return [
            'success' => true,
            'message' => 'Email already verified. You can log in now.'
        ];
    }

    // Update user
    dbUpdate('users',
        [
            'email_verified' => true,
            'verification_token' => null,
            'updated_at' => date('Y-m-d H:i:s')
        ],
        'id = ?',
        [$user['id']]
    );

    logAuthEvent('verify_email_success', $user['id'], $user['email']);
    return [
        'success' => true,
        'message' => 'Email verified successfully! You can now log in.'
    ];
}

/**
 * Check login rate limiting for IP and email
 * Prevents brute force attacks
 *
 * @param string $email User email
 * @return array ['allowed' => bool, 'message' => string]
 */
function checkLoginRateLimit($email) {
    $maxAttempts = defined('MAX_LOGIN_ATTEMPTS') ? MAX_LOGIN_ATTEMPTS : 5;
    $lockoutTime = defined('LOGIN_LOCKOUT_TIME') ? LOGIN_LOCKOUT_TIME : 900; // 15 minutes

    $ip = getClientIp();
    $ipResult = checkRateLimit('login_ip', $ip, $maxAttempts, $lockoutTime);
    if (!$ipResult['allowed']) {
        return [
            'allowed' => false,
            'message' => $ipResult['message']
        ];
    }

    $emailResult = checkRateLimit('login_email', $email, $maxAttempts, $lockoutTime);
    if (!$emailResult['allowed']) {
        return [
            'allowed' => false,
            'message' => $emailResult['message']
        ];
    }

    return [
        'allowed' => true,
        'message' => ''
    ];
}

/**
 * Check registration rate limiting for IP and email
 *
 * @param string $email User email
 * @return array ['allowed' => bool, 'message' => string]
 */
function checkRegistrationRateLimit($email) {
    $maxAttempts = defined('MAX_REGISTRATION_ATTEMPTS') ? MAX_REGISTRATION_ATTEMPTS : 3;
    $lockoutTime = defined('REGISTRATION_LOCKOUT_TIME') ? REGISTRATION_LOCKOUT_TIME : 1800;

    $ip = getClientIp();
    $ipResult = checkRateLimit('register_ip', $ip, $maxAttempts, $lockoutTime);
    if (!$ipResult['allowed']) {
        return [
            'allowed' => false,
            'message' => $ipResult['message']
        ];
    }

    if (isValidEmail($email)) {
        $emailResult = checkRateLimit('register_email', $email, $maxAttempts, $lockoutTime);
        if (!$emailResult['allowed']) {
            return [
                'allowed' => false,
                'message' => $emailResult['message']
            ];
        }
    }

    return [
        'allowed' => true,
        'message' => ''
    ];
}

/**
 * Check password reset rate limiting for IP and email
 *
 * @param string $email User email
 * @return array ['allowed' => bool, 'message' => string]
 */
function checkPasswordResetRateLimit($email) {
    $maxAttempts = defined('MAX_PASSWORD_RESET_ATTEMPTS') ? MAX_PASSWORD_RESET_ATTEMPTS : 5;
    $lockoutTime = defined('PASSWORD_RESET_LOCKOUT_TIME') ? PASSWORD_RESET_LOCKOUT_TIME : 900;

    $ip = getClientIp();
    $ipResult = checkRateLimit('reset_ip', $ip, $maxAttempts, $lockoutTime);
    if (!$ipResult['allowed']) {
        return [
            'allowed' => false,
            'message' => $ipResult['message']
        ];
    }

    if (isValidEmail($email)) {
        $emailResult = checkRateLimit('reset_email', $email, $maxAttempts, $lockoutTime);
        if (!$emailResult['allowed']) {
            return [
                'allowed' => false,
                'message' => $emailResult['message']
            ];
        }
    }

    return [
        'allowed' => true,
        'message' => ''
    ];
}

/**
 * Record login attempt (success or failure) for both IP and email
 * @param string $email User email
 * @param string $ip Client IP
 * @param bool $success Whether login was successful
 */
function recordLoginAttempt($email, $ip, $success) {
    if ($success) {
        clearRateLimit('login_email', $email);
        clearRateLimit('login_ip', $ip);
        return;
    }

    recordRateLimitFailure('login_email', $email);
    recordRateLimitFailure('login_ip', $ip);
}

/**
 * Record registration attempt
 *
 * @param string $email User email
 * @param string $ip Client IP
 * @param bool $success Whether registration succeeded
 * @return void
 */
function recordRegistrationAttempt($email, $ip, $success) {
    if ($success) {
        if (isValidEmail($email)) {
            clearRateLimit('register_email', $email);
        }
        clearRateLimit('register_ip', $ip);
        return;
    }

    if (isValidEmail($email)) {
        recordRateLimitFailure('register_email', $email);
    }
    recordRateLimitFailure('register_ip', $ip);
}

/**
 * Record password reset attempt
 *
 * @param string $email User email
 * @param string $ip Client IP
 * @return void
 */
function recordPasswordResetAttempt($email, $ip) {
    if (isValidEmail($email)) {
        recordRateLimitFailure('reset_email', $email);
    }
    recordRateLimitFailure('reset_ip', $ip);
}

/**
 * Clear login attempts for identifier
 * @param string $email User email
 */
function clearLoginAttempts($email) {
    clearRateLimit('login_email', $email);
}

/**
 * Require authentication
 * Redirects to login page if user is not logged in
 *
 * @param string $redirectUrl URL to redirect to after login
 */
function requireAuth($redirectUrl = null) {
    if (!isLoggedIn()) {
        $currentUrl = $redirectUrl ?? $_SERVER['REQUEST_URI'];
        $_SESSION['redirect_after_login'] = $currentUrl;
        redirect(url('admin/login.php'));
        exit;
    }
}

/**
 * Get redirect URL after login
 * @return string URL to redirect to
 */
function getRedirectAfterLogin() {
    initSession();

    if (isset($_SESSION['redirect_after_login'])) {
        $url = $_SESSION['redirect_after_login'];
        unset($_SESSION['redirect_after_login']);
        return $url;
    }

    return url('admin/dashboard.php');
}

/**
 * Initialize password reset request
 * @param string $email User email
 * @return array ['success' => bool, 'message' => string, 'token' => string|null]
 */
function initiatePasswordReset($email) {
    $email = sanitizeEmail($email);

    if (!isValidEmail($email)) {
        logAuthEvent('password_reset_failure', null, $email, ['reason' => 'invalid_email']);
        return [
            'success' => false,
            'message' => 'Invalid email format.',
            'token' => null
        ];
    }

    $user = dbFetchOne("SELECT * FROM users WHERE email = ?", [$email]);

    // Don't reveal whether email exists (security)
    if (!$user) {
        logAuthEvent('password_reset_request', null, $email, ['result' => 'user_not_found']);
        return [
            'success' => true,
            'message' => 'If an account with that email exists, a password reset link has been sent.',
            'token' => null
        ];
    }

    // Generate reset token
    $resetToken = bin2hex(random_bytes(32));
    $expiryTime = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Update user with reset token
    dbUpdate('users',
        [
            'reset_token' => $resetToken,
            'reset_token_expiry' => $expiryTime,
            'updated_at' => date('Y-m-d H:i:s')
        ],
        'id = ?',
        [$user['id']]
    );

    logAuthEvent('password_reset_request', $user['id'], $email, ['result' => 'token_generated']);
    return [
        'success' => true,
        'message' => 'If an account with that email exists, a password reset link has been sent.',
        'token' => $resetToken
    ];
}

/**
 * Reset password with token
 * @param string $token Reset token
 * @param string $newPassword New password
 * @return array ['success' => bool, 'message' => string]
 */
function resetPassword($token, $newPassword) {
    if (empty($token)) {
        logAuthEvent('password_reset_failure', null, null, ['reason' => 'missing_token']);
        return [
            'success' => false,
            'message' => 'Invalid reset token.'
        ];
    }

    $user = dbFetchOne(
        "SELECT * FROM users WHERE reset_token = ? AND reset_token_expiry > ?",
        [$token, date('Y-m-d H:i:s')]
    );

    if (!$user) {
        logAuthEvent('password_reset_failure', null, null, ['reason' => 'invalid_token']);
        return [
            'success' => false,
            'message' => 'Invalid or expired reset token.'
        ];
    }

    // Validate new password
    $passwordValidation = validatePassword($newPassword);
    if (!$passwordValidation['valid']) {
        logAuthEvent('password_reset_failure', $user['id'], $user['email'], ['reason' => 'weak_password']);
        return [
            'success' => false,
            'message' => implode('. ', $passwordValidation['errors'])
        ];
    }

    // Hash new password
    $passwordHash = hashPassword($newPassword);

    // Update user
    dbUpdate('users',
        [
            'password_hash' => $passwordHash,
            'reset_token' => null,
            'reset_token_expiry' => null,
            'updated_at' => date('Y-m-d H:i:s')
        ],
        'id = ?',
        [$user['id']]
    );

    logAuthEvent('password_reset_success', $user['id'], $user['email']);
    return [
        'success' => true,
        'message' => 'Password reset successfully! You can now log in with your new password.'
    ];
}

/**
 * Build rate limit identifier key
 *
 * @param string $type Rate limit type
 * @param string $identifier Identifier value (email or IP)
 * @return string
 */
function buildRateLimitKey($type, $identifier) {
    return strtolower($type . ':' . trim($identifier));
}

/**
 * Check rate limit for identifier
 *
 * @param string $type Rate limit type
 * @param string $identifier Identifier value
 * @param int $maxAttempts Maximum attempts
 * @param int $lockoutTime Lockout duration in seconds
 * @return array ['allowed' => bool, 'message' => string]
 */
function checkRateLimit($type, $identifier, $maxAttempts, $lockoutTime) {
    $key = buildRateLimitKey($type, $identifier);
    $record = dbFetchOne("SELECT * FROM auth_rate_limits WHERE identifier = ?", [$key]);
    $now = time();

    if ($record && !empty($record['locked_until'])) {
        $lockedUntil = strtotime($record['locked_until']);
        if ($lockedUntil > $now) {
            return [
                'allowed' => false,
                'message' => sprintf(
                    'Too many requests. Please try again in %d minutes.',
                    ceil(($lockedUntil - $now) / 60)
                )
            ];
        }
    }

    if ($record && !empty($record['last_attempt'])) {
        $lastAttempt = strtotime($record['last_attempt']);
        if ($now - $lastAttempt > $lockoutTime) {
            clearRateLimitKey($key);
        }
    }

    return [
        'allowed' => true,
        'message' => ''
    ];
}

/**
 * Record a failed rate limit attempt
 *
 * @param string $type Rate limit type
 * @param string $identifier Identifier value
 * @return void
 */
function recordRateLimitFailure($type, $identifier) {
    $maxAttempts = match ($type) {
        'login_email', 'login_ip' => defined('MAX_LOGIN_ATTEMPTS') ? MAX_LOGIN_ATTEMPTS : 5,
        'register_email', 'register_ip' => defined('MAX_REGISTRATION_ATTEMPTS') ? MAX_REGISTRATION_ATTEMPTS : 3,
        'reset_email', 'reset_ip' => defined('MAX_PASSWORD_RESET_ATTEMPTS') ? MAX_PASSWORD_RESET_ATTEMPTS : 5,
        default => 5
    };

    $lockoutTime = match ($type) {
        'login_email', 'login_ip' => defined('LOGIN_LOCKOUT_TIME') ? LOGIN_LOCKOUT_TIME : 900,
        'register_email', 'register_ip' => defined('REGISTRATION_LOCKOUT_TIME') ? REGISTRATION_LOCKOUT_TIME : 1800,
        'reset_email', 'reset_ip' => defined('PASSWORD_RESET_LOCKOUT_TIME') ? PASSWORD_RESET_LOCKOUT_TIME : 900,
        default => 900
    };

    $key = buildRateLimitKey($type, $identifier);
    $record = dbFetchOne("SELECT * FROM auth_rate_limits WHERE identifier = ?", [$key]);
    $now = date('Y-m-d H:i:s');

    if (!$record) {
        dbInsert('auth_rate_limits', [
            'identifier' => $key,
            'attempt_count' => 1,
            'first_attempt' => $now,
            'last_attempt' => $now,
            'locked_until' => null,
            'created_at' => $now,
            'updated_at' => $now
        ]);
        return;
    }

    $count = (int) $record['attempt_count'] + 1;
    $lockedUntil = $count >= $maxAttempts ? date('Y-m-d H:i:s', strtotime('+' . $lockoutTime . ' seconds')) : null;

    dbUpdate('auth_rate_limits',
        [
            'attempt_count' => $count,
            'last_attempt' => $now,
            'locked_until' => $lockedUntil,
            'updated_at' => $now
        ],
        'identifier = ?',
        [$key]
    );
}

/**
 * Clear rate limit data for a specific identifier
 *
 * @param string $type Rate limit type
 * @param string $identifier Identifier value
 * @return void
 */
function clearRateLimit($type, $identifier) {
    $key = buildRateLimitKey($type, $identifier);
    clearRateLimitKey($key);
}

/**
 * Clear rate limit data by key
 *
 * @param string $key Identifier key
 * @return void
 */
function clearRateLimitKey($key) {
    dbDelete('auth_rate_limits', 'identifier = ?', [$key]);
}
