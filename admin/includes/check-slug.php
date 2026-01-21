<?php
/**
 * AJAX Endpoint: Check Slug Availability
 * Returns JSON response indicating if a slug is available for a given art type
 */

require_once(__DIR__ . '/../../config/config.php');
require_once(__DIR__ . '/../../config/database.php');
require_once(__DIR__ . '/auth.php');
require_once(__DIR__ . '/slug_functions.php');

// Require authentication
requireAuth();

// Set JSON header
header('Content-Type: application/json');

// Check if required parameters are present
if (!isset($_GET['slug']) || !isset($_GET['type'])) {
    echo json_encode([
        'valid' => false,
        'available' => false,
        'message' => 'Missing required parameters'
    ]);
    exit;
}

$slug = trim($_GET['slug']);
$type = $_GET['type'];
$excludeId = isset($_GET['exclude_id']) ? (int)$_GET['exclude_id'] : null;

// Validate art type
$validTypes = ['aframe', 'c2', 'p5', 'threejs'];
if (!in_array($type, $validTypes)) {
    echo json_encode([
        'valid' => false,
        'available' => false,
        'message' => 'Invalid art type'
    ]);
    exit;
}

// Empty slug is valid (will be auto-generated)
if (empty($slug)) {
    echo json_encode([
        'valid' => true,
        'available' => true,
        'message' => 'Slug will be auto-generated from title'
    ]);
    exit;
}

// Validate slug format (lowercase letters, numbers, hyphens only)
if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
    echo json_encode([
        'valid' => false,
        'available' => false,
        'message' => 'Slug can only contain lowercase letters, numbers, and hyphens'
    ]);
    exit;
}

// Check if slug is available
$available = isSlugAvailable($slug, $type, $excludeId);

if ($available) {
    echo json_encode([
        'valid' => true,
        'available' => true,
        'message' => 'This slug is available'
    ]);
} else {
    echo json_encode([
        'valid' => false,
        'available' => false,
        'message' => 'This slug is already in use'
    ]);
}
