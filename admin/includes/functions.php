<?php
/**
 * Admin CRUD Functions
 * Shared functions for managing art pieces across all types
 *
 * Features:
 * - CRUD operations for all art types
 * - Input validation and sanitization
 * - Activity logging
 * - Email notifications
 * - Image URL validation
 * - Sort order management
 */

// Ensure dependencies are loaded
require_once(__DIR__ . '/../../config/config.php');
require_once(__DIR__ . '/../../config/helpers.php');
require_once(__DIR__ . '/../../config/database.php');
require_once(__DIR__ . '/auth.php');

/**
 * Get all art pieces for a specific type
 * @param string $type Art type (aframe, c2, p5, threejs)
 * @param string $status Filter by status (null for all)
 * @return array List of art pieces
 */
function getArtPieces($type, $status = null) {
    $validTypes = ['aframe', 'c2', 'p5', 'threejs'];
    if (!in_array($type, $validTypes)) {
        return [];
    }

    $table = $type . '_art';

    $sql = "SELECT * FROM {$table}";
    $params = [];

    if ($status !== null) {
        $sql .= " WHERE status = ?";
        $params[] = $status;
    }

    $sql .= " ORDER BY sort_order ASC, created_at DESC";

    return dbFetchAll($sql, $params);
}

/**
 * Get single art piece by ID
 * @param string $type Art type
 * @param int $id Piece ID
 * @return array|null Art piece data
 */
function getArtPiece($type, $id) {
    $validTypes = ['aframe', 'c2', 'p5', 'threejs'];
    if (!in_array($type, $validTypes)) {
        return null;
    }

    $table = $type . '_art';

    return dbFetchOne("SELECT * FROM {$table} WHERE id = ?", [$id]);
}

/**
 * Create new art piece
 * @param string $type Art type
 * @param array $data Piece data
 * @return array ['success' => bool, 'message' => string, 'id' => int|null]
 */
function createArtPiece($type, $data) {
    $user = getCurrentUser();
    if (!$user) {
        return [
            'success' => false,
            'message' => 'Not authenticated.',
            'id' => null
        ];
    }

    // Validate type
    $validTypes = ['aframe', 'c2', 'p5', 'threejs'];
    if (!in_array($type, $validTypes)) {
        return [
            'success' => false,
            'message' => 'Invalid art type.',
            'id' => null
        ];
    }

    // Validate required fields
    $validation = validateArtPieceData($type, $data);
    if (!$validation['valid']) {
        return [
            'success' => false,
            'message' => $validation['message'],
            'id' => null
        ];
    }

    // Prepare data based on type
    $pieceData = prepareArtPieceData($type, $data, $user['id']);

    try {
        $table = $type . '_art';

        // Start transaction
        dbBeginTransaction();

        // Insert piece
        $pieceId = dbInsert($table, $pieceData);

        // Log activity
        logActivity($user['id'], 'create', $type, $pieceId, $pieceData);

        // Commit transaction
        dbCommit();

        // Send email notification (async - don't block on failure)
        try {
            sendActivityNotification($user, 'create', $type, $pieceId, $pieceData);
        } catch (Exception $e) {
            error_log("Email notification failed: " . $e->getMessage());
        }

        return [
            'success' => true,
            'message' => 'Art piece created successfully!',
            'id' => $pieceId
        ];
    } catch (Exception $e) {
        dbRollback();
        error_log("Create art piece error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred while creating the art piece.',
            'id' => null
        ];
    }
}

/**
 * Update existing art piece
 * @param string $type Art type
 * @param int $id Piece ID
 * @param array $data Updated piece data
 * @return array ['success' => bool, 'message' => string]
 */
function updateArtPiece($type, $id, $data) {
    $user = getCurrentUser();
    if (!$user) {
        return [
            'success' => false,
            'message' => 'Not authenticated.'
        ];
    }

    // Validate type
    $validTypes = ['aframe', 'c2', 'p5', 'threejs'];
    if (!in_array($type, $validTypes)) {
        return [
            'success' => false,
            'message' => 'Invalid art type.'
        ];
    }

    // Check if piece exists
    $existingPiece = getArtPiece($type, $id);
    if (!$existingPiece) {
        return [
            'success' => false,
            'message' => 'Art piece not found.'
        ];
    }

    // Validate data
    $validation = validateArtPieceData($type, $data, $id);
    if (!$validation['valid']) {
        return [
            'success' => false,
            'message' => $validation['message']
        ];
    }

    // Prepare update data
    $pieceData = prepareArtPieceData($type, $data, $user['id'], true);

    try {
        $table = $type . '_art';

        // Start transaction
        dbBeginTransaction();

        // Update piece
        dbUpdate($table, $pieceData, 'id = ?', [$id]);

        // Log activity
        logActivity($user['id'], 'update', $type, $id, $pieceData);

        // Commit transaction
        dbCommit();

        // Send email notification
        try {
            sendActivityNotification($user, 'update', $type, $id, $pieceData);
        } catch (Exception $e) {
            error_log("Email notification failed: " . $e->getMessage());
        }

        return [
            'success' => true,
            'message' => 'Art piece updated successfully!'
        ];
    } catch (Exception $e) {
        dbRollback();
        error_log("Update art piece error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred while updating the art piece.'
        ];
    }
}

/**
 * Delete art piece
 * @param string $type Art type
 * @param int $id Piece ID
 * @return array ['success' => bool, 'message' => string]
 */
function deleteArtPiece($type, $id) {
    $user = getCurrentUser();
    if (!$user) {
        return [
            'success' => false,
            'message' => 'Not authenticated.'
        ];
    }

    // Validate type
    $validTypes = ['aframe', 'c2', 'p5', 'threejs'];
    if (!in_array($type, $validTypes)) {
        return [
            'success' => false,
            'message' => 'Invalid art type.'
        ];
    }

    // Get piece data before deletion (for logging and email)
    $piece = getArtPiece($type, $id);
    if (!$piece) {
        return [
            'success' => false,
            'message' => 'Art piece not found.'
        ];
    }

    try {
        $table = $type . '_art';

        // Start transaction
        dbBeginTransaction();

        // Delete piece
        dbDelete($table, 'id = ?', [$id]);

        // Log activity
        logActivity($user['id'], 'delete', $type, $id, $piece);

        // Commit transaction
        dbCommit();

        // Send email notification
        try {
            sendActivityNotification($user, 'delete', $type, $id, $piece);
        } catch (Exception $e) {
            error_log("Email notification failed: " . $e->getMessage());
        }

        return [
            'success' => true,
            'message' => 'Art piece deleted successfully!'
        ];
    } catch (Exception $e) {
        dbRollback();
        error_log("Delete art piece error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred while deleting the art piece.'
        ];
    }
}

/**
 * Validate art piece data based on type
 * @param string $type Art type
 * @param array $data Piece data to validate
 * @param int|null $existingId ID of existing piece (for updates)
 * @return array ['valid' => bool, 'message' => string]
 */
function validateArtPieceData($type, $data, $existingId = null) {
    // Title is required for all types
    if (empty($data['title'])) {
        return [
            'valid' => false,
            'message' => 'Title is required.'
        ];
    }

    // File path is NO LONGER required - it's auto-generated from slug
    // Validation removed to support auto-generation system

    // Validate thumbnail URL if provided
    if (!empty($data['thumbnail_url']) && !isValidImageUrl($data['thumbnail_url'])) {
        return [
            'valid' => false,
            'message' => 'Invalid thumbnail URL format.'
        ];
    }

    // Type-specific validation
    switch ($type) {
        case 'aframe':
            if (!empty($data['scene_type']) && !in_array($data['scene_type'], ['space', 'alt', 'custom'])) {
                return [
                    'valid' => false,
                    'message' => 'Invalid scene type.'
                ];
            }
            break;

        case 'c2':
            if (isset($data['canvas_count']) && (!is_numeric($data['canvas_count']) || $data['canvas_count'] < 1)) {
                return [
                    'valid' => false,
                    'message' => 'Canvas count must be a positive number.'
                ];
            }
            break;

        case 'p5':
            // P5-specific validation if needed
            break;

        case 'threejs':
            // Three.js-specific validation if needed
            break;
    }

    // Validate status
    if (isset($data['status']) && !in_array($data['status'], ['active', 'draft', 'archived'])) {
        return [
            'valid' => false,
            'message' => 'Invalid status value.'
        ];
    }

    return [
        'valid' => true,
        'message' => ''
    ];
}

/**
 * Prepare art piece data for database insertion/update
 * @param string $type Art type
 * @param array $data Raw input data
 * @param int $userId User ID
 * @param bool $isUpdate Whether this is an update (vs create)
 * @return array Prepared data for database
 */
function prepareArtPieceData($type, $data, $userId, $isUpdate = false) {
    // Auto-generate file_path from slug if slug is provided
    if (!empty($data['slug'])) {
        // Map art type to directory name
        $dirMap = [
            'aframe' => 'a-frame',
            'c2' => 'c2',
            'p5' => 'p5',
            'threejs' => 'three-js'
        ];

        $directory = $dirMap[$type] ?? $type;
        $data['file_path'] = "/{$directory}/view.php?slug=" . $data['slug'];
    }

    // Common fields for all types
    $prepared = [
        'title' => sanitize($data['title']),
        'description' => sanitize($data['description'] ?? ''),
        'file_path' => sanitize($data['file_path']),
        'thumbnail_url' => !empty($data['thumbnail_url']) ? sanitize($data['thumbnail_url']) : null,
        'tags' => sanitize($data['tags'] ?? ''),
        'status' => $data['status'] ?? 'active',
        'sort_order' => isset($data['sort_order']) ? (int)$data['sort_order'] : 0,
        'updated_at' => date('Y-m-d H:i:s')
    ];

    // Add slug if provided (for slug-enabled operations)
    if (!empty($data['slug'])) {
        $prepared['slug'] = $data['slug'];
    }

    // Add created_by and created_at for new pieces
    if (!$isUpdate) {
        $prepared['created_by'] = $userId;
        $prepared['created_at'] = date('Y-m-d H:i:s');
    }

    // Type-specific fields
    switch ($type) {
        case 'aframe':
            $prepared['scene_type'] = $data['scene_type'] ?? 'custom';
            $prepared['sky_color'] = $data['sky_color'] ?? '#ECECEC';
            $prepared['sky_texture'] = !empty($data['sky_texture']) ? sanitize($data['sky_texture']) : null;
            $prepared['sky_opacity'] = isset($data['sky_opacity']) ? (float)$data['sky_opacity'] : 1.0;
            $prepared['ground_color'] = $data['ground_color'] ?? '#7BC8A4';
            $prepared['ground_texture'] = !empty($data['ground_texture']) ? sanitize($data['ground_texture']) : null;
            $prepared['ground_opacity'] = isset($data['ground_opacity']) ? (float)$data['ground_opacity'] : 1.0;
            $prepared['configuration'] = !empty($data['configuration'])
                ? jsonEncode($data['configuration'])
                : null;
            break;

        case 'c2':
            $prepared['canvas_count'] = isset($data['canvas_count']) ? (int)$data['canvas_count'] : 1;
            $prepared['js_files'] = !empty($data['js_files'])
                ? jsonEncode($data['js_files'])
                : null;
            $prepared['image_urls'] = !empty($data['image_urls'])
                ? jsonEncode($data['image_urls'])
                : null;
            $prepared['configuration'] = !empty($data['configuration'])
                ? jsonEncode($data['configuration'])
                : null;
            break;

        case 'p5':
            $prepared['piece_path'] = sanitize($data['piece_path'] ?? '');
            $prepared['screenshot_url'] = !empty($data['screenshot_url']) ? sanitize($data['screenshot_url']) : null;
            $prepared['image_urls'] = !empty($data['image_urls'])
                ? jsonEncode($data['image_urls'])
                : null;
            $prepared['configuration'] = !empty($data['configuration'])
                ? jsonEncode($data['configuration'])
                : null;
            break;

        case 'threejs':
            $prepared['embedded_path'] = sanitize($data['embedded_path'] ?? '');
            $prepared['js_file'] = sanitize($data['js_file'] ?? '');
            $prepared['texture_urls'] = !empty($data['texture_urls'])
                ? jsonEncode($data['texture_urls'])
                : null;
            $prepared['configuration'] = !empty($data['configuration'])
                ? jsonEncode($data['configuration'])
                : null;
            break;
    }

    return $prepared;
}

/**
 * Log activity to database
 * @param int $userId User ID
 * @param string $action Action type (create, update, delete)
 * @param string $artType Art type
 * @param int $artId Art piece ID
 * @param array $configuration Configuration snapshot
 */
function logActivity($userId, $action, $artType, $artId, $configuration) {
    try {
        dbInsert('activity_log', [
            'user_id' => $userId,
            'action_type' => $action,
            'art_type' => $artType,
            'art_id' => $artId,
            'configuration_snapshot' => jsonEncode($configuration),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        error_log("Activity logging error: " . $e->getMessage());
    }
}

/**
 * Send email notification for activity
 * @param array $user User data
 * @param string $action Action type
 * @param string $artType Art type
 * @param int $artId Art piece ID
 * @param array $data Piece data
 */
function sendActivityNotification($user, $action, $artType, $artId, $data) {
    // Check if email notifications are enabled
    if (!defined('SEND_EMAIL_NOTIFICATIONS') || !SEND_EMAIL_NOTIFICATIONS) {
        return;
    }

    require_once(__DIR__ . '/email-notifications.php');
    sendArtPieceNotification($user, $action, $artType, $artId, $data);
}

/**
 * Update sort order for art pieces
 * @param string $type Art type
 * @param array $sortData Array of ['id' => sort_order] pairs
 * @return array ['success' => bool, 'message' => string]
 */
function updateSortOrder($type, $sortData) {
    $validTypes = ['aframe', 'c2', 'p5', 'threejs'];
    if (!in_array($type, $validTypes)) {
        return [
            'success' => false,
            'message' => 'Invalid art type.'
        ];
    }

    try {
        $table = $type . '_art';

        dbBeginTransaction();

        foreach ($sortData as $id => $sortOrder) {
            dbUpdate($table, ['sort_order' => (int)$sortOrder], 'id = ?', [$id]);
        }

        dbCommit();

        return [
            'success' => true,
            'message' => 'Sort order updated successfully!'
        ];
    } catch (Exception $e) {
        dbRollback();
        error_log("Sort order update error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred while updating sort order.'
        ];
    }
}

/**
 * Get activity log entries
 * @param int|null $userId Filter by user ID (null for all)
 * @param int $limit Number of entries to retrieve
 * @return array Activity log entries
 */
function getActivityLog($userId = null, $limit = 50) {
    $sql = "SELECT a.*, u.first_name, u.last_name, u.email
            FROM activity_log a
            LEFT JOIN users u ON a.user_id = u.id";

    $params = [];

    if ($userId !== null) {
        $sql .= " WHERE a.user_id = ?";
        $params[] = $userId;
    }

    $sql .= " ORDER BY a.created_at DESC LIMIT ?";
    $params[] = $limit;

    return dbFetchAll($sql, $params);
}

/**
 * Get art type display name
 * @param string $type Art type
 * @return string Display name
 */
function getArtTypeDisplayName($type) {
    $names = [
        'aframe' => 'A-Frame',
        'c2' => 'C2.js',
        'p5' => 'P5.js',
        'threejs' => 'Three.js'
    ];

    return $names[$type] ?? ucfirst($type);
}

/**
 * Get action display name
 * @param string $action Action type
 * @return string Display name
 */
function getActionDisplayName($action) {
    $names = [
        'create' => 'Created',
        'update' => 'Updated',
        'delete' => 'Deleted'
    ];

    return $names[$action] ?? ucfirst($action);
}
