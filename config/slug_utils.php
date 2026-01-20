<?php
/**
 * Slug Utility Functions
 *
 * Functions for generating, validating, and managing URL slugs
 * across all art types with soft delete support.
 */

require_once __DIR__ . '/database.php';

/**
 * Generate a URL-safe slug from a string
 *
 * @param string $text Text to convert to slug
 * @return string URL-safe slug
 */
function generateSlug($text) {
    // Convert to lowercase
    $slug = strtolower($text);

    // Replace non-alphanumeric characters with hyphens
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);

    // Remove leading/trailing hyphens
    $slug = trim($slug, '-');

    // Replace multiple consecutive hyphens with single hyphen
    $slug = preg_replace('/-+/', '-', $slug);

    // Limit length to 200 characters
    $slug = substr($slug, 0, 200);

    // Remove trailing hyphen if substr cut in middle of word
    $slug = rtrim($slug, '-');

    return $slug;
}

/**
 * Check if a slug is available (not used by active or recently deleted pieces)
 *
 * @param string $slug Slug to check
 * @param string $type Art type (aframe, c2, p5, threejs)
 * @param int|null $excludeId ID to exclude from check (for updates)
 * @return bool True if available
 */
function isSlugAvailable($slug, $type, $excludeId = null) {
    $validTypes = ['aframe', 'c2', 'p5', 'threejs'];
    if (!in_array($type, $validTypes)) {
        return false;
    }

    $table = $type . '_art';
    $pdo = getDBConnection();

    // Get reservation period from config (default 30 days)
    $reservationDays = getSiteConfig('slug_reservation_days', 30);
    $reservationDate = date('Y-m-d H:i:s', strtotime("-{$reservationDays} days"));

    // Check if slug exists in active pieces OR recently deleted pieces (within reservation period)
    $sql = "SELECT id FROM {$table}
            WHERE slug = ?
            AND (deleted_at IS NULL OR deleted_at > ?)";

    $params = [$slug, $reservationDate];

    // Exclude specific ID if provided (for updates)
    if ($excludeId !== null) {
        $sql .= " AND id != ?";
        $params[] = $excludeId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Slug is available if no results found
    return $stmt->fetch() === false;
}

/**
 * Generate a unique slug by adding number suffix if needed
 *
 * @param string $baseSlug Base slug to make unique
 * @param string $type Art type
 * @param int|null $excludeId ID to exclude from check
 * @return string Unique slug
 */
function makeSlugUnique($baseSlug, $type, $excludeId = null) {
    $slug = $baseSlug;
    $counter = 2;

    // Keep trying with incremented numbers until we find an available slug
    while (!isSlugAvailable($slug, $type, $excludeId)) {
        $slug = $baseSlug . '-' . $counter;
        $counter++;

        // Safety check to prevent infinite loop
        if ($counter > 1000) {
            // Append random string if we somehow hit 1000 duplicates
            $slug = $baseSlug . '-' . substr(md5(uniqid()), 0, 6);
            break;
        }
    }

    return $slug;
}

/**
 * Generate a unique slug from text
 *
 * @param string $text Text to convert to slug
 * @param string $type Art type
 * @param int|null $excludeId ID to exclude from check
 * @return string Unique, URL-safe slug
 */
function generateUniqueSlug($text, $type, $excludeId = null) {
    $baseSlug = generateSlug($text);

    // If base slug is empty, use fallback
    if (empty($baseSlug)) {
        $baseSlug = $type . '-piece-' . time();
    }

    return makeSlugUnique($baseSlug, $type, $excludeId);
}

/**
 * Create a redirect from old slug to new slug
 *
 * @param string $type Art type
 * @param int $artId Art piece ID
 * @param string $oldSlug Old slug
 * @param string $newSlug New slug
 * @return bool Success
 */
function createSlugRedirect($type, $artId, $oldSlug, $newSlug) {
    if (empty($oldSlug) || empty($newSlug) || $oldSlug === $newSlug) {
        return false;
    }

    $pdo = getDBConnection();

    // Check if redirect already exists
    $existing = $pdo->prepare("SELECT id FROM slug_redirects WHERE art_type = ? AND old_slug = ?");
    $existing->execute([$type, $oldSlug]);

    if ($existing->fetch()) {
        // Update existing redirect
        $stmt = $pdo->prepare("UPDATE slug_redirects SET new_slug = ?, art_id = ? WHERE art_type = ? AND old_slug = ?");
        return $stmt->execute([$newSlug, $artId, $type, $oldSlug]);
    } else {
        // Create new redirect
        $stmt = $pdo->prepare("INSERT INTO slug_redirects (art_type, old_slug, new_slug, art_id) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$type, $oldSlug, $newSlug, $artId]);
    }
}

/**
 * Get redirect for a slug
 *
 * @param string $type Art type
 * @param string $slug Slug to check for redirect
 * @return string|null New slug if redirect exists, null otherwise
 */
function getSlugRedirect($type, $slug) {
    $pdo = getDBConnection();

    $stmt = $pdo->prepare("SELECT new_slug, art_id FROM slug_redirects WHERE art_type = ? AND old_slug = ?");
    $stmt->execute([$type, $slug]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        // Increment redirect counter
        $pdo->prepare("UPDATE slug_redirects SET redirect_count = redirect_count + 1 WHERE art_type = ? AND old_slug = ?")
            ->execute([$type, $slug]);

        return $result['new_slug'];
    }

    return null;
}

/**
 * Soft delete an art piece (marks as deleted but preserves data)
 *
 * @param string $type Art type
 * @param int $id Art piece ID
 * @return bool Success
 */
function softDeleteArtPiece($type, $id) {
    $validTypes = ['aframe', 'c2', 'p5', 'threejs'];
    if (!in_array($type, $validTypes)) {
        return false;
    }

    $table = $type . '_art';
    $pdo = getDBConnection();

    $stmt = $pdo->prepare("UPDATE {$table} SET deleted_at = NOW(), status = 'archived' WHERE id = ? AND deleted_at IS NULL");
    return $stmt->execute([$id]);
}

/**
 * Restore a soft-deleted art piece
 *
 * @param string $type Art type
 * @param int $id Art piece ID
 * @return bool Success
 */
function restoreArtPiece($type, $id) {
    $validTypes = ['aframe', 'c2', 'p5', 'threejs'];
    if (!in_array($type, $validTypes)) {
        return false;
    }

    $table = $type . '_art';
    $pdo = getDBConnection();

    // Check if slug is still available
    $piece = $pdo->prepare("SELECT slug FROM {$table} WHERE id = ?");
    $piece->execute([$id]);
    $data = $piece->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        return false;
    }

    // If slug is taken, generate a new one
    if (!isSlugAvailable($data['slug'], $type, $id)) {
        $newSlug = makeSlugUnique($data['slug'], $type, $id);

        // Create redirect from old to new
        createSlugRedirect($type, $id, $data['slug'], $newSlug);

        // Update with new slug
        $stmt = $pdo->prepare("UPDATE {$table} SET deleted_at = NULL, status = 'draft', slug = ? WHERE id = ?");
        return $stmt->execute([$newSlug, $id]);
    } else {
        // Restore with original slug
        $stmt = $pdo->prepare("UPDATE {$table} SET deleted_at = NULL, status = 'draft' WHERE id = ?");
        return $stmt->execute([$id]);
    }
}

/**
 * Get all soft-deleted pieces for a type
 *
 * @param string $type Art type
 * @return array List of deleted pieces
 */
function getDeletedArtPieces($type) {
    $validTypes = ['aframe', 'c2', 'p5', 'threejs'];
    if (!in_array($type, $validTypes)) {
        return [];
    }

    $table = $type . '_art';
    $pdo = getDBConnection();

    $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC");
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Permanently delete old soft-deleted pieces beyond reservation period
 *
 * @param string $type Art type (or 'all' for all types)
 * @return int Number of pieces permanently deleted
 */
function cleanupOldDeletedPieces($type = 'all') {
    $pdo = getDBConnection();

    // Get reservation period
    $reservationDays = getSiteConfig('slug_reservation_days', 30);
    $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$reservationDays} days"));

    $types = ($type === 'all') ? ['aframe', 'c2', 'p5', 'threejs'] : [$type];
    $totalDeleted = 0;

    foreach ($types as $artType) {
        $table = $artType . '_art';

        // Get count of pieces to delete
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE deleted_at IS NOT NULL AND deleted_at < ?");
        $stmt->execute([$cutoffDate]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            // Permanently delete
            $stmt = $pdo->prepare("DELETE FROM {$table} WHERE deleted_at IS NOT NULL AND deleted_at < ?");
            $stmt->execute([$cutoffDate]);
            $totalDeleted += $count;
        }
    }

    // Update last cleanup time
    updateSiteConfig('last_slug_cleanup', date('Y-m-d H:i:s'));

    return $totalDeleted;
}

/**
 * Get site configuration value
 *
 * @param string $key Configuration key
 * @param mixed $default Default value if not found
 * @return mixed Configuration value
 */
function getSiteConfig($key, $default = null) {
    $pdo = getDBConnection();

    $stmt = $pdo->prepare("SELECT setting_value, setting_type FROM site_config WHERE setting_key = ?");
    $stmt->execute([$key]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        return $default;
    }

    // Cast to appropriate type
    $value = $result['setting_value'];
    switch ($result['setting_type']) {
        case 'int':
            return (int)$value;
        case 'bool':
            return (bool)$value;
        case 'json':
            return json_decode($value, true);
        default:
            return $value;
    }
}

/**
 * Update site configuration value
 *
 * @param string $key Configuration key
 * @param mixed $value Configuration value
 * @return bool Success
 */
function updateSiteConfig($key, $value) {
    $pdo = getDBConnection();

    $stmt = $pdo->prepare("UPDATE site_config SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
    return $stmt->execute([$value, $key]);
}

/**
 * Validate slug format
 *
 * @param string $slug Slug to validate
 * @return bool True if valid format
 */
function isValidSlugFormat($slug) {
    // Must be lowercase alphanumeric with hyphens only
    // Must not start or end with hyphen
    // Must be between 1 and 200 characters
    return preg_match('/^[a-z0-9]([a-z0-9-]{0,198}[a-z0-9])?$/', $slug) === 1;
}

/**
 * Get full URL for an art piece from slug
 *
 * @param string $type Art type
 * @param string $slug Slug
 * @return string Full URL
 */
function getUrlFromSlug($type, $slug) {
    $typeMap = [
        'aframe' => '/a-frame/',
        'c2' => '/c2/',
        'p5' => '/p5/',
        'threejs' => '/three-js/'
    ];

    $basePath = $typeMap[$type] ?? '/';
    return $basePath . $slug;
}
