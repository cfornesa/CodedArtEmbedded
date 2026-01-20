<?php
/**
 * Page Registry - Centralized Page Configuration
 *
 * Eliminates duplicate $page_name and $tagline variable definitions across all 23 PHP files.
 * Provides a single source of truth for all page metadata.
 *
 * Usage in PHP files:
 *   require_once 'config/pages.php'; // or '../config/pages.php' for subdirectories
 *   $pageInfo = getPageInfo(); // Auto-detects current page
 *   $page_name = $pageInfo['page_name'];
 *   $tagline = $pageInfo['tagline'];
 *
 * @package CodedArt
 * @subpackage Config
 */

// Ensure site link is available (from resources/templates/name.php)
if (!isset($site_link)) {
    $site_link = '<a href="https://codedart.org">CodedArt</a>';
}

/**
 * Centralized Page Registry
 *
 * Contains all page metadata for the entire site.
 * Keys are matched against the current script path.
 */
$PAGE_REGISTRY = [
    // ==========================================
    // ROOT LEVEL PAGES
    // ==========================================
    '/index.php' => [
        'page_name' => 'Coded Art',
        'tagline' => 'Code and code-generated art by ' . $site_link . '.',
        'section' => 'home'
    ],
    '/about.php' => [
        'page_name' => 'About',
        'tagline' => 'Code and code-generated art by ' . $site_link . '.',
        'section' => 'about'
    ],
    '/blog.php' => [
        'page_name' => 'Blog', // Note: Was "Guestbook" but displays blog content
        'tagline' => 'Code and code-generated art by ' . $site_link . '.',
        'section' => 'blog'
    ],
    '/guestbook.php' => [
        'page_name' => 'Guestbook',
        'tagline' => 'Code and code-generated art by ' . $site_link . '.',
        'section' => 'guestbook'
    ],

    // ==========================================
    // A-FRAME PAGES
    // ==========================================
    '/a-frame/index.php' => [
        'page_name' => 'A-Frame Exhibit',
        'tagline' => 'A-Frame code-generated art by ' . $site_link . '.',
        'section' => 'aframe',
        'type' => 'gallery'
    ],
    '/a-frame/alt.php' => [
        'page_name' => 'Alt',
        'tagline' => 'Code and code-generated art by ' . $site_link . '.',
        'section' => 'aframe',
        'type' => 'piece'
    ],
    '/a-frame/alt-piece.php' => [
        'page_name' => 'Alt Piece',
        'tagline' => 'Code and code-generated art by ' . $site_link . '.',
        'section' => 'aframe',
        'type' => 'piece'
    ],
    '/a-frame/alt-piece-ns.php' => [
        'page_name' => 'Alt Piece',
        'tagline' => 'Code and code-generated art by ' . $site_link . '.',
        'section' => 'aframe',
        'type' => 'piece'
    ],

    // ==========================================
    // C2.JS PAGES
    // ==========================================
    '/c2/index.php' => [
        'page_name' => 'c2.js Exhibit',
        'tagline' => 'c2.js code-generated art by ' . $site_link . '.',
        'section' => 'c2',
        'type' => 'gallery'
    ],
    '/c2/1.php' => [
        'page_name' => 'c2.js Exhibit / 1 - C2',
        'piece_name' => '1 - C2',
        'tagline' => 'c2.js code-generated art by ' . $site_link . '.',
        'section' => 'c2',
        'type' => 'piece'
    ],
    '/c2/2.php' => [
        'page_name' => 'c2.js Exhibit / 2 - C2',
        'piece_name' => '2 - C2',
        'tagline' => 'c2.js code-generated art by ' . $site_link . '.',
        'section' => 'c2',
        'type' => 'piece'
    ],

    // ==========================================
    // P5.JS PAGES
    // ==========================================
    '/p5/index.php' => [
        'page_name' => 'p5.js Exhibit',
        'tagline' => 'p5.js code-generated art by ' . $site_link . '.',
        'section' => 'p5',
        'type' => 'gallery'
    ],
    '/p5/p5_1.php' => [
        'page_name' => 'p5.js Exhibit: 1',
        'tagline' => 'p5.js code-generated art by ' . $site_link . '.',
        'section' => 'p5',
        'type' => 'piece'
    ],
    '/p5/p5_2.php' => [
        'page_name' => 'p5.js Exhibit: 2',
        'tagline' => 'p5.js code-generated art by ' . $site_link . '.',
        'section' => 'p5',
        'type' => 'piece'
    ],
    '/p5/p5_3.php' => [
        'page_name' => 'p5.js Exhibit: 3',
        'tagline' => 'p5.js code-generated art by ' . $site_link . '.',
        'section' => 'p5',
        'type' => 'piece'
    ],
    '/p5/p5_4.php' => [
        'page_name' => 'p5.js Exhibit: 4',
        'tagline' => 'p5.js code-generated art by ' . $site_link . '.',
        'section' => 'p5',
        'type' => 'piece'
    ],

    // ==========================================
    // THREE.JS PAGES
    // ==========================================
    '/three-js/index.php' => [
        'page_name' => 'Three.js Exhibit',
        'tagline' => 'Three.js code-generated art by ' . $site_link . '.',
        'section' => 'threejs',
        'type' => 'gallery'
    ],
    '/three-js/first.php' => [
        'page_name' => 'First 3JS',
        'tagline' => 'Code and code-generated art by ' . $site_link . '.',
        'section' => 'threejs',
        'type' => 'piece'
    ],
    '/three-js/first-whole.php' => [
        'page_name' => 'First 3JS',
        'tagline' => 'Code and code-generated art by ' . $site_link . '.',
        'section' => 'threejs',
        'type' => 'piece-embedded'
    ],
    '/three-js/second.php' => [
        'page_name' => 'Second 3JS',
        'tagline' => 'Code and code-generated art by ' . $site_link . '.',
        'section' => 'threejs',
        'type' => 'piece'
    ],
    '/three-js/second-whole.php' => [
        'page_name' => 'Second 3JS',
        'tagline' => 'Code and code-generated art by ' . $site_link . '.',
        'section' => 'threejs',
        'type' => 'piece-embedded'
    ],
    '/three-js/third.php' => [
        'page_name' => 'Third 3JS',
        'tagline' => 'Code and code-generated art by ' . $site_link . '.',
        'section' => 'threejs',
        'type' => 'piece'
    ],
    '/three-js/third-whole.php' => [
        'page_name' => 'Third 3JS',
        'tagline' => 'Code and code-generated art by ' . $site_link . '.',
        'section' => 'threejs',
        'type' => 'piece-embedded'
    ],
];

/**
 * Get page information for the current page
 *
 * @param string|null $scriptPath Optional script path (defaults to current script)
 * @return array Page information (page_name, tagline, section, type, etc.)
 */
function getPageInfo($scriptPath = null) {
    global $PAGE_REGISTRY;

    if ($scriptPath === null) {
        // Auto-detect current script path
        $scriptPath = $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '';
    }

    // Normalize path (remove leading document root if present)
    $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    if (!empty($docRoot) && strpos($scriptPath, $docRoot) === 0) {
        $scriptPath = substr($scriptPath, strlen($docRoot));
    }

    // Look up page in registry
    if (isset($PAGE_REGISTRY[$scriptPath])) {
        return $PAGE_REGISTRY[$scriptPath];
    }

    // Fallback: Try to extract from filename
    $filename = basename($scriptPath);
    $directory = dirname($scriptPath);

    // Default fallback values
    return [
        'page_name' => ucfirst(str_replace(['.php', '-', '_'], ['', ' ', ' '], $filename)),
        'tagline' => 'Code and code-generated art.',
        'section' => basename($directory),
        'type' => 'unknown'
    ];
}

/**
 * Get page variable (shorthand)
 *
 * @param string $key Variable key (page_name, tagline, etc.)
 * @param string|null $scriptPath Optional script path
 * @return string|null Variable value or null if not found
 */
function getPageVar($key, $scriptPath = null) {
    $pageInfo = getPageInfo($scriptPath);
    return $pageInfo[$key] ?? null;
}

/**
 * Check if current page is in a specific section
 *
 * @param string $section Section name (home, aframe, c2, p5, threejs, etc.)
 * @return bool True if current page is in the specified section
 */
function isSection($section) {
    $pageInfo = getPageInfo();
    return ($pageInfo['section'] ?? '') === $section;
}

/**
 * Check if current page is a gallery index
 *
 * @return bool True if current page is a gallery index
 */
function isGallery() {
    $pageInfo = getPageInfo();
    return ($pageInfo['type'] ?? '') === 'gallery';
}

/**
 * Check if current page is an art piece
 *
 * @return bool True if current page is an art piece
 */
function isPiece() {
    $pageInfo = getPageInfo();
    return in_array($pageInfo['type'] ?? '', ['piece', 'piece-embedded']);
}

/**
 * Get all pages in a section
 *
 * @param string $section Section name
 * @return array Array of page information for all pages in section
 */
function getPagesInSection($section) {
    global $PAGE_REGISTRY;

    $pages = [];
    foreach ($PAGE_REGISTRY as $path => $info) {
        if (($info['section'] ?? '') === $section) {
            $pages[$path] = $info;
        }
    }

    return $pages;
}

/**
 * Get all gallery pages
 *
 * @return array Array of gallery page information
 */
function getGalleryPages() {
    global $PAGE_REGISTRY;

    $galleries = [];
    foreach ($PAGE_REGISTRY as $path => $info) {
        if (($info['type'] ?? '') === 'gallery') {
            $galleries[$path] = $info;
        }
    }

    return $galleries;
}
