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
require_once(__DIR__ . '/../../config/helpers.php');
require_once(__DIR__ . '/../../config/database.php');

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
        recordLoginAttempt($email, false);

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
        recordLoginAttempt($email, false);

        return [
            'success' => false,
            'message' => 'Invalid email or password.',
            'user' => null
        ];
    }

    // Check if email is verified
    if (!$user['email_verified']) {
        return [
            'success' => false,
            'message' => 'Please verify your email address before logging in. Check your inbox for the verification link.',
            'user' => null
        ];
    }

    // Check account status
    if ($user['status'] !== 'active') {
        $statusMessages = [
            'inactive' => 'Your account has been deactivated. Please contact support.',
            'pending' => 'Your account is pending approval. You will be notified when approved.'
        ];

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
    recordLoginAttempt($email, true);

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

    // Unset all session variables
    $_SESSION = [];

    // Destroy session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }

    // Destroy session
    session_destroy();
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
        return [
            'success' => false,
            'message' => 'Invalid email format.',
            'user_id' => null
        ];
    }

    // Check if email already exists
    $existingUser = dbFetchOne("SELECT id FROM users WHERE email = ?", [$email]);
    if ($existingUser) {
        return [
            'success' => false,
            'message' => 'An account with this email already exists.',
            'user_id' => null
        ];
    }

    // Validate password strength
    $passwordValidation = validatePassword($data['password']);
    if (!$passwordValidation['valid']) {
        return [
            'success' => false,
            'message' => $passwordValidation['message'],
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

    try {
        // Insert user
        $userId = dbInsert('users', $userData);

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
        return [
            'success' => false,
            'message' => 'Invalid or expired verification token.'
        ];
    }

    if ($user['email_verified']) {
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

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    // Check attempts from this IP
    $ipAttempts = getLoginAttempts($ip);

    // Check attempts for this email
    $emailAttempts = getLoginAttempts($email);

    if ($ipAttempts >= $maxAttempts) {
        return [
            'allowed' => false,
            'message' => sprintf(
                'Too many login attempts from your IP address. Please try again in %d minutes.',
                ceil($lockoutTime / 60)
            )
        ];
    }

    if ($emailAttempts >= $maxAttempts) {
        return [
            'allowed' => false,
            'message' => sprintf(
                'Too many login attempts for this account. Please try again in %d minutes.',
                ceil($lockoutTime / 60)
            )
        ];
    }

    return [
        'allowed' => true,
        'message' => ''
    ];
}

/**
 * Get number of failed login attempts
 * @param string $identifier IP address or email
 * @return int Number of attempts
 */
function getLoginAttempts($identifier) {
    initSession();

    $key = 'login_attempts_' . md5($identifier);

    if (!isset($_SESSION[$key])) {
        return 0;
    }

    $data = $_SESSION[$key];
    $lockoutTime = defined('LOGIN_LOCKOUT_TIME') ? LOGIN_LOCKOUT_TIME : 900;

    // Clear old attempts
    if (time() - $data['timestamp'] > $lockoutTime) {
        unset($_SESSION[$key]);
        return 0;
    }

    return $data['count'];
}

/**
 * Record login attempt (success or failure)
 * @param string $identifier IP address or email
 * @param bool $success Whether login was successful
 */
function recordLoginAttempt($identifier, $success) {
    initSession();

    $key = 'login_attempts_' . md5($identifier);

    if ($success) {
        // Clear attempts on successful login
        unset($_SESSION[$key]);
        return;
    }

    // Record failed attempt
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'count' => 0,
            'timestamp' => time()
        ];
    }

    $_SESSION[$key]['count']++;
}

/**
 * Clear login attempts for identifier
 * @param string $identifier IP address or email
 */
function clearLoginAttempts($identifier) {
    initSession();
    $key = 'login_attempts_' . md5($identifier);
    unset($_SESSION[$key]);
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
        return [
            'success' => false,
            'message' => 'Invalid email format.',
            'token' => null
        ];
    }

    $user = dbFetchOne("SELECT * FROM users WHERE email = ?", [$email]);

    // Don't reveal whether email exists (security)
    if (!$user) {
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
        return [
            'success' => false,
            'message' => 'Invalid or expired reset token.'
        ];
    }

    // Validate new password
    $passwordValidation = validatePassword($newPassword);
    if (!$passwordValidation['valid']) {
        return [
            'success' => false,
            'message' => $passwordValidation['message']
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

    return [
        'success' => true,
        'message' => 'Password reset successfully! You can now log in with your new password.'
    ];
}
