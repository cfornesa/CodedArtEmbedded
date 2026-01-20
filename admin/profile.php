<?php
/**
 * User Profile Page
 * View and update user account settings
 */

require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/includes/functions.php');

$page_title = 'My Profile';

$error = '';
$success = '';

// Get current user
$user = getCurrentUser();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } elseif ($action === 'update_profile') {
        // Update name and email
        $firstName = sanitize($_POST['first_name'] ?? '');
        $lastName = sanitize($_POST['last_name'] ?? '');
        $email = sanitizeEmail($_POST['email'] ?? '');

        if (empty($firstName) || empty($lastName) || empty($email)) {
            $error = 'All fields are required.';
        } elseif (!isValidEmail($email)) {
            $error = 'Invalid email format.';
        } else {
            // Check if email is taken by another user
            $existingUser = dbFetchOne("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $user['id']]);
            if ($existingUser) {
                $error = 'This email is already in use by another account.';
            } else {
                try {
                    dbUpdate('users',
                        [
                            'first_name' => $firstName,
                            'last_name' => $lastName,
                            'email' => $email,
                            'updated_at' => date('Y-m-d H:i:s')
                        ],
                        'id = ?',
                        [$user['id']]
                    );

                    // Update session
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_name'] = $firstName . ' ' . $lastName;

                    $success = 'Profile updated successfully!';
                    $user = getCurrentUser(); // Refresh user data
                } catch (Exception $e) {
                    error_log("Profile update error: " . $e->getMessage());
                    $error = 'An error occurred while updating your profile.';
                }
            }
        }
    } elseif ($action === 'change_password') {
        // Change password
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = 'All password fields are required.';
        } elseif (!verifyPassword($currentPassword, $user['password_hash'])) {
            $error = 'Current password is incorrect.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New passwords do not match.';
        } else {
            $passwordValidation = validatePassword($newPassword);
            if (!$passwordValidation['valid']) {
                $error = $passwordValidation['message'];
            } else {
                try {
                    $newPasswordHash = hashPassword($newPassword);

                    dbUpdate('users',
                        [
                            'password_hash' => $newPasswordHash,
                            'updated_at' => date('Y-m-d H:i:s')
                        ],
                        'id = ?',
                        [$user['id']]
                    );

                    $success = 'Password changed successfully!';
                } catch (Exception $e) {
                    error_log("Password change error: " . $e->getMessage());
                    $error = 'An error occurred while changing your password.';
                }
            }
        }
    }
}

// Generate CSRF token
$csrfToken = generateCsrfToken();

// Get user's recent activity
$recentActivity = getActivityLog($user['id'], 10);

// Include header
require_once(__DIR__ . '/includes/header.php');
?>

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

<div class="card">
    <div class="card-header">
        <h2>Profile Information</h2>
    </div>

    <form method="POST" action="" data-validate>
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="action" value="update_profile">

        <div class="form-group">
            <label for="first_name" class="form-label required">First Name</label>
            <input
                type="text"
                id="first_name"
                name="first_name"
                class="form-control"
                required
                value="<?php echo htmlspecialchars($user['first_name']); ?>"
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
                value="<?php echo htmlspecialchars($user['last_name']); ?>"
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
                value="<?php echo htmlspecialchars($user['email']); ?>"
            >
        </div>

        <div class="form-group">
            <button type="submit" class="btn btn-primary">
                Update Profile
            </button>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h2>Change Password</h2>
    </div>

    <form method="POST" action="" data-validate>
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="action" value="change_password">

        <div class="form-group">
            <label for="current_password" class="form-label required">Current Password</label>
            <input
                type="password"
                id="current_password"
                name="current_password"
                class="form-control"
                required
                autocomplete="current-password"
            >
        </div>

        <div class="form-group">
            <label for="new_password" class="form-label required">New Password</label>
            <input
                type="password"
                id="new_password"
                name="new_password"
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
            <label for="confirm_password" class="form-label required">Confirm New Password</label>
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
            <button type="submit" class="btn btn-primary">
                Change Password
            </button>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h2>Account Information</h2>
    </div>

    <div class="form-group">
        <label class="form-label">Account Status</label>
        <p>
            <span class="badge badge-<?php echo $user['status'] === 'active' ? 'success' : 'secondary'; ?>">
                <?php echo htmlspecialchars(ucfirst($user['status'])); ?>
            </span>
        </p>
    </div>

    <div class="form-group">
        <label class="form-label">Email Verified</label>
        <p>
            <span class="badge badge-<?php echo $user['email_verified'] ? 'success' : 'warning'; ?>">
                <?php echo $user['email_verified'] ? 'Verified' : 'Not Verified'; ?>
            </span>
        </p>
    </div>

    <div class="form-group">
        <label class="form-label">Member Since</label>
        <p><?php echo htmlspecialchars(formatDate($user['created_at'], 'F j, Y')); ?></p>
    </div>

    <div class="form-group">
        <label class="form-label">Last Login</label>
        <p><?php echo $user['last_login'] ? htmlspecialchars(formatDate($user['last_login'], 'F j, Y g:i A')) : 'Never'; ?></p>
    </div>
</div>

<?php if (!empty($recentActivity)): ?>
<div class="card">
    <div class="card-header">
        <h2>My Recent Activity</h2>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Action</th>
                <th>Art Type</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recentActivity as $activity): ?>
            <tr>
                <td>
                    <span class="badge badge-<?php
                        echo $activity['action_type'] === 'create' ? 'success' :
                            ($activity['action_type'] === 'delete' ? 'danger' : 'secondary');
                    ?>">
                        <?php echo htmlspecialchars(getActionDisplayName($activity['action_type'])); ?>
                    </span>
                </td>
                <td>
                    <?php echo htmlspecialchars(getArtTypeDisplayName($activity['art_type'])); ?>
                </td>
                <td>
                    <?php echo htmlspecialchars(formatDate($activity['created_at'], 'M d, Y g:i A')); ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php require_once(__DIR__ . '/includes/footer.php'); ?>
