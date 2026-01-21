<?php
/**
 * Slug-Enhanced CRUD Functions
 *
 * These functions extend the base CRUD operations with slug support.
 * Include this file after functions.php to override with slug functionality.
 */

require_once(__DIR__ . '/../../config/slug_utils.php');

/**
 * Enhanced create art piece with slug generation
 *
 * @param string $type Art type
 * @param array $data Piece data
 * @return array ['success' => bool, 'message' => string, 'id' => int|null, 'slug' => string|null]
 */
function createArtPieceWithSlug($type, $data) {
    $user = getCurrentUser();
    if (!$user) {
        return [
            'success' => false,
            'message' => 'Not authenticated.',
            'id' => null,
            'slug' => null
        ];
    }

    // Validate type
    $validTypes = ['aframe', 'c2', 'p5', 'threejs'];
    if (!in_array($type, $validTypes)) {
        return [
            'success' => false,
            'message' => 'Invalid art type.',
            'id' => null,
            'slug' => null
        ];
    }

    // Generate slug from title
    $slug = isset($data['slug']) && !empty(trim($data['slug']))
        ? generateSlug(trim($data['slug']))  // Use custom slug if provided
        : generateUniqueSlug($data['title'], $type, null);  // Auto-generate from title

    // Validate slug format
    if (!isValidSlugFormat($slug)) {
        return [
            'success' => false,
            'message' => 'Invalid slug format. Use only lowercase letters, numbers, and hyphens.',
            'id' => null,
            'slug' => null
        ];
    }

    // Check slug availability
    if (!isSlugAvailable($slug, $type, null)) {
        return [
            'success' => false,
            'message' => 'This slug is already in use. Please choose a different one.',
            'id' => null,
            'slug' => null
        ];
    }

    // Add slug to data
    $data['slug'] = $slug;

    // Validate required fields
    $validation = validateArtPieceData($type, $data);
    if (!$validation['valid']) {
        return [
            'success' => false,
            'message' => $validation['message'],
            'id' => null,
            'slug' => null
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
            'id' => $pieceId,
            'slug' => $slug
        ];
    } catch (Exception $e) {
        dbRollback();
        // Enhanced error logging for debugging
        $errorDetails = [
            'error' => $e->getMessage(),
            'type' => $type,
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];
        error_log("Create art piece error: " . json_encode($errorDetails));

        // Return user-friendly message with debug hint
        return [
            'success' => false,
            'message' => 'An error occurred while creating the art piece. Error: ' . $e->getMessage(),
            'id' => null,
            'slug' => null
        ];
    }
}

/**
 * Enhanced update art piece with slug management and redirects
 *
 * @param string $type Art type
 * @param int $id Piece ID
 * @param array $data Updated piece data
 * @return array ['success' => bool, 'message' => string, 'slug' => string|null]
 */
function updateArtPieceWithSlug($type, $id, $data) {
    $user = getCurrentUser();
    if (!$user) {
        return [
            'success' => false,
            'message' => 'Not authenticated.',
            'slug' => null
        ];
    }

    // Validate type
    $validTypes = ['aframe', 'c2', 'p5', 'threejs'];
    if (!in_array($type, $validTypes)) {
        return [
            'success' => false,
            'message' => 'Invalid art type.',
            'slug' => null
        ];
    }

    // Check if piece exists
    $existingPiece = getArtPiece($type, $id);
    if (!$existingPiece) {
        return [
            'success' => false,
            'message' => 'Art piece not found.',
            'slug' => null
        ];
    }

    $oldSlug = $existingPiece['slug'] ?? null;

    // Handle slug changes
    if (isset($data['slug']) && !empty(trim($data['slug']))) {
        // Custom slug provided
        $newSlug = generateSlug(trim($data['slug']));

        // Validate format
        if (!isValidSlugFormat($newSlug)) {
            return [
                'success' => false,
                'message' => 'Invalid slug format. Use only lowercase letters, numbers, and hyphens.',
                'slug' => null
            ];
        }

        // Check availability (excluding current piece)
        if ($newSlug !== $oldSlug && !isSlugAvailable($newSlug, $type, $id)) {
            return [
                'success' => false,
                'message' => 'This slug is already in use. Please choose a different one.',
                'slug' => null
            ];
        }

        $data['slug'] = $newSlug;
    } else {
        // No slug change requested - preserve existing slug
        // This ensures file_path can be regenerated correctly
        $data['slug'] = $oldSlug;
    }

    // Validate data
    $validation = validateArtPieceData($type, $data, $id);
    if (!$validation['valid']) {
        return [
            'success' => false,
            'message' => $validation['message'],
            'slug' => null
        ];
    }

    // Prepare update data
    $pieceData = prepareArtPieceData($type, $data, $user['id'], true);

    try {
        $table = $type . '_art';

        // Start transaction
        dbBeginTransaction();

        // If slug changed, create redirect from old to new
        $finalSlug = $data['slug'] ?? $oldSlug;
        if ($oldSlug && $finalSlug && $oldSlug !== $finalSlug) {
            createSlugRedirect($type, $id, $oldSlug, $finalSlug);
        }

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
            'message' => 'Art piece updated successfully!',
            'slug' => $finalSlug
        ];
    } catch (Exception $e) {
        dbRollback();
        // Enhanced error logging for debugging
        $errorDetails = [
            'error' => $e->getMessage(),
            'type' => $type,
            'id' => $id,
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];
        error_log("Update art piece error: " . json_encode($errorDetails));

        // Return user-friendly message with debug hint
        return [
            'success' => false,
            'message' => 'An error occurred while updating the art piece. Error: ' . $e->getMessage(),
            'slug' => null
        ];
    }
}

/**
 * Enhanced delete with soft delete (marks as deleted, preserves data)
 *
 * @param string $type Art type
 * @param int $id Piece ID
 * @param bool $permanent If true, permanently delete (use with caution)
 * @return array ['success' => bool, 'message' => string]
 */
function deleteArtPieceWithSlug($type, $id, $permanent = false) {
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

        if ($permanent) {
            // Permanent delete (use with caution)
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("DELETE FROM {$table} WHERE id = ?");
            $stmt->execute([$id]);

            $action = 'permanently_delete';
            $message = 'Art piece permanently deleted. This cannot be undone.';
        } else {
            // Soft delete (recommended)
            if (!softDeleteArtPiece($type, $id)) {
                throw new Exception("Failed to soft delete piece");
            }

            $action = 'delete';
            $message = 'Art piece deleted successfully. You can restore it within ' .
                      getSiteConfig('slug_reservation_days', 30) . ' days.';
        }

        // Log activity
        logActivity($user['id'], $action, $type, $id, $piece);

        // Commit transaction
        dbCommit();

        // Send email notification
        try {
            sendActivityNotification($user, $action, $type, $id, $piece);
        } catch (Exception $e) {
            error_log("Email notification failed: " . $e->getMessage());
        }

        return [
            'success' => true,
            'message' => $message
        ];
    } catch (Exception $e) {
        dbRollback();
        // Enhanced error logging for debugging
        $errorDetails = [
            'error' => $e->getMessage(),
            'type' => $type,
            'id' => $id,
            'permanent' => $permanent,
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];
        error_log("Delete art piece error: " . json_encode($errorDetails));

        // Return user-friendly message with debug hint
        return [
            'success' => false,
            'message' => 'An error occurred while deleting the art piece. Error: ' . $e->getMessage()
        ];
    }
}

/**
 * Get art pieces excluding soft-deleted ones
 *
 * @param string $type Art type
 * @param string $status Filter by status (null or 'all' for all statuses, or specific status)
 * @return array List of art pieces
 */
function getActiveArtPieces($type, $status = null) {
    $validTypes = ['aframe', 'c2', 'p5', 'threejs'];
    if (!in_array($type, $validTypes)) {
        return [];
    }

    $table = $type . '_art';

    $sql = "SELECT * FROM {$table} WHERE deleted_at IS NULL";
    $params = [];

    // Handle status filtering - 'all' means no status filter
    if ($status !== null && $status !== 'all') {
        $sql .= " AND status = ?";
        $params[] = $status;
    }

    $sql .= " ORDER BY sort_order ASC, created_at DESC";

    return dbFetchAll($sql, $params);
}

/**
 * Get art piece by slug (supports redirects)
 *
 * @param string $type Art type
 * @param string $slug Slug
 * @return array|null Art piece data, null if not found
 */
function getArtPieceBySlug($type, $slug) {
    $validTypes = ['aframe', 'c2', 'p5', 'threejs'];
    if (!in_array($type, $validTypes)) {
        return null;
    }

    $table = $type . '_art';

    // Try direct slug match first
    $piece = dbFetchOne("SELECT * FROM {$table} WHERE slug = ? AND deleted_at IS NULL", [$slug]);

    if ($piece) {
        return $piece;
    }

    // Check if there's a redirect
    $newSlug = getSlugRedirect($type, $slug);
    if ($newSlug) {
        // Follow redirect
        return dbFetchOne("SELECT * FROM {$table} WHERE slug = ? AND deleted_at IS NULL", [$newSlug]);
    }

    return null;
}

/**
 * Preview slug from title
 *
 * @param string $title Title text
 * @param string $type Art type
 * @param int|null $excludeId ID to exclude from uniqueness check
 * @return string Generated slug
 */
function previewSlug($title, $type, $excludeId = null) {
    return generateUniqueSlug($title, $type, $excludeId);
}
