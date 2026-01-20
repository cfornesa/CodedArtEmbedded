<?php
/**
 * Database Seed Data Script
 *
 * Populates the database with existing art pieces from the CodedArt project.
 * This script intelligently extracts information from existing files and
 * creates database entries for all art pieces across the four frameworks.
 *
 * Run this once after database initialization to populate with existing content.
 *
 * @package CodedArt
 * @subpackage Config
 */

// Load configuration
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/environment.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';

// ==========================================
// HELPER FUNCTIONS
// ==========================================

/**
 * Output message with styling
 */
function output($message, $type = 'info') {
    $colors = [
        'success' => '#28a745',
        'error' => '#dc3545',
        'warning' => '#ffc107',
        'info' => '#17a2b8',
        'muted' => '#6c757d'
    ];
    $color = $colors[$type] ?? $colors['info'];

    if (PHP_SAPI === 'cli') {
        echo $message . "\n";
    } else {
        echo "<div style='padding: 10px; margin: 5px 0; background: " . $color . "20; border-left: 4px solid {$color}; color: #333; font-family: monospace;'>";
        echo htmlspecialchars($message);
        echo "</div>";
    }
}

/**
 * Insert art piece with duplicate check
 */
function insertArtPiece($table, $data, $pieceName) {
    try {
        // Check if piece already exists by title
        $existing = dbFetchOne(
            "SELECT id FROM {$table} WHERE title = ?",
            [$data['title']]
        );

        if ($existing) {
            output("  ⚠️  '{$pieceName}' already exists (ID: {$existing['id']}) - skipping", 'warning');
            return $existing['id'];
        }

        // Insert new piece
        $id = dbInsert($table, $data);
        output("  ✅ Created '{$pieceName}' (ID: {$id})", 'success');
        return $id;

    } catch (Exception $e) {
        output("  ❌ Error inserting '{$pieceName}': " . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * Convert relative image path to full URL
 */
function imageUrl($path) {
    if (strpos($path, 'http') === 0) {
        return $path; // Already a full URL
    }
    return url(ltrim($path, '/'));
}

// ==========================================
// START SEEDING
// ==========================================

if (PHP_SAPI !== 'cli') {
    echo "<!DOCTYPE html><html><head><title>Database Seed Data</title></head><body>";
    echo "<h1>CodedArt Database Seed Data</h1>";
    echo "<div style='max-width: 1000px; margin: 20px;'>";
}

output("🌱 Starting database seeding process...", 'info');
output("Environment: " . getEnvironment(), 'info');
output("Base URL: " . SITE_URL, 'info');

// Check database connection
try {
    $pdo = getDBConnection();
    output("✅ Database connection successful\n", 'success');
} catch (Exception $e) {
    output("❌ Database connection failed: " . $e->getMessage(), 'error');
    exit(1);
}

// ==========================================
// SEED A-FRAME ART
// ==========================================

output("\n" . str_repeat('=', 60), 'info');
output("🔷 Seeding A-Frame WebVR Art Pieces", 'info');
output(str_repeat('=', 60), 'info');

$aframeArt = [
    [
        'title' => 'Alt Piece',
        'description' => 'Elaborate digital art scapes using digitally-rendered p5.js art pieces and photos of physical art works programmed using the A-Frame WebVR framework and JavaScript. Features animated spheres, boxes, and cylinders with colorful textures in a virtual 3D space.',
        'file_path' => '/a-frame/alt-piece-ns.php',
        'thumbnail_url' => imageUrl('/img/p5/1.png'),
        'texture_urls' => jsonEncode([
            imageUrl('/img/p5/1.png'),
            imageUrl('/img/p5/2.png'),
            imageUrl('/img/p5/3.png'),
            imageUrl('/img/p5/4.png'),
            imageUrl('/img/a-frame/alt/1.png'),
            imageUrl('/img/a-frame/alt/2.png'),
            imageUrl('/img/a-frame/alt/3.png'),
            imageUrl('/img/a-frame/alt/4.png'),
            imageUrl('/img/a-frame/alt/5.png'),
            imageUrl('/img/a-frame/alt/6.png'),
            imageUrl('/img/a-frame/alt/7.png'),
            imageUrl('/img/a-frame/alt/DSC_0920.png')
        ]),
        'scene_type' => 'alt',
        'configuration' => jsonEncode([
            'version' => 'A-Frame 1.6.0',
            'audio_enabled' => false,
            'shapes' => [
                ['type' => 'sphere', 'radius' => 4, 'position' => [0, 1.25, -5], 'texture' => 'p5/1.png'],
                ['type' => 'sphere', 'radius' => 2, 'position' => [2, 1.75, -5], 'texture' => 'p5/2.png'],
                ['type' => 'sphere', 'radius' => 1, 'position' => [0, 1.25, -1], 'texture' => 'p5/3.png'],
                ['type' => 'sphere', 'radius' => 1.5, 'position' => [-1, 2.5, -3], 'texture' => 'p5/4.png'],
            ],
            'planet_clusters' => 7,
            'lighting' => [
                ['type' => 'point', 'position' => [2, 4, 4], 'intensity' => 1.25]
            ],
            'animation' => [
                'rotation' => true,
                'duration' => 10000
            ],
            'sky_texture' => imageUrl('/img/a-frame/alt/DSC_0920.png')
        ]),
        'tags' => 'WebVR, A-Frame, 3D, Virtual Reality, Animated, Spheres, p5.js textures',
        'status' => 'active',
        'sort_order' => 1
    ],
    [
        'title' => 'Alt Piece (with sound)',
        'description' => 'Same as Alt Piece but with ambient background audio. Features animated 3D shapes in a virtual environment with sound effects.',
        'file_path' => '/a-frame/alt-piece.php',
        'thumbnail_url' => imageUrl('/img/p5/2.png'),
        'texture_urls' => jsonEncode([
            imageUrl('/img/p5/1.png'),
            imageUrl('/img/p5/2.png'),
            imageUrl('/img/p5/3.png'),
            imageUrl('/img/p5/4.png'),
            imageUrl('/img/a-frame/alt/1.png'),
            imageUrl('/img/a-frame/alt/2.png'),
            imageUrl('/img/a-frame/alt/3.png'),
            imageUrl('/img/a-frame/alt/4.png'),
            imageUrl('/img/a-frame/alt/5.png'),
            imageUrl('/img/a-frame/alt/6.png'),
            imageUrl('/img/a-frame/alt/7.png'),
            imageUrl('/img/a-frame/alt/DSC_0920.png')
        ]),
        'scene_type' => 'alt',
        'configuration' => jsonEncode([
            'version' => 'A-Frame 1.6.0',
            'audio_enabled' => true,
            'audio_url' => 'https://cdn.aframe.io/basic-guide/audio/backgroundnoise.wav',
            'shapes' => [
                ['type' => 'sphere', 'radius' => 4, 'position' => [0, 1.25, -5], 'texture' => 'p5/1.png'],
                ['type' => 'sphere', 'radius' => 2, 'position' => [2, 1.75, -5], 'texture' => 'p5/2.png'],
                ['type' => 'sphere', 'radius' => 1, 'position' => [0, 1.25, -1], 'texture' => 'p5/3.png'],
                ['type' => 'sphere', 'radius' => 1.5, 'position' => [-1, 2.5, -3], 'texture' => 'p5/4.png'],
            ],
            'planet_clusters' => 7,
            'lighting' => [
                ['type' => 'point', 'position' => [2, 4, 4], 'intensity' => 1.25]
            ],
            'animation' => [
                'rotation' => true,
                'duration' => 10000
            ],
            'sky_texture' => imageUrl('/img/a-frame/alt/DSC_0920.png')
        ]),
        'tags' => 'WebVR, A-Frame, 3D, Virtual Reality, Animated, Spheres, Audio, Sound',
        'status' => 'draft',
        'sort_order' => 2
    ]
];

foreach ($aframeArt as $piece) {
    insertArtPiece('aframe_art', $piece, $piece['title']);
}

// ==========================================
// SEED C2.JS ART
// ==========================================

output("\n" . str_repeat('=', 60), 'info');
output("🔶 Seeding c2.js Art Pieces", 'info');
output(str_repeat('=', 60), 'info');

$c2Art = [
    [
        'title' => '1 - C2',
        'description' => 'Interactive generative art piece created with c2.js library. Features 4 interactive canvases with dynamic rendering in a 2x2 grid layout. Requires page refresh after window resize for optimal display.',
        'file_path' => '/c2/1.php',
        'thumbnail_url' => '', // c2.js doesn't generate static thumbnails easily
        'image_urls' => jsonEncode([]), // No external images used
        'canvas_count' => 4,
        'js_files' => jsonEncode([
            '/c2/1/1.js',
            '/c2/1/1-1.js',
            '/c2/1/1-2.js'
        ]),
        'configuration' => jsonEncode([
            'library' => 'c2.min.js',
            'canvas_ids' => [1, 2, 3, 4],
            'layout' => '2x2 grid',
            'interactive' => true,
            'framework' => 'Bootstrap responsive grid'
        ]),
        'tags' => 'c2.js, Interactive, Generative Art, Canvas, JavaScript',
        'status' => 'active',
        'sort_order' => 1
    ],
    [
        'title' => '2 - C2',
        'description' => 'Interactive generative art piece created with c2.js library. Features 4 interactive canvases with dynamic rendering in a 2x2 grid layout. Requires page refresh after window resize for optimal display.',
        'file_path' => '/c2/2.php',
        'thumbnail_url' => '',
        'image_urls' => jsonEncode([]),
        'canvas_count' => 4,
        'js_files' => jsonEncode([
            '/c2/2/2.js',
            '/c2/2/2-1.js',
            '/c2/2/2-2.js'
        ]),
        'configuration' => jsonEncode([
            'library' => 'c2.min.js',
            'canvas_ids' => [1, 2, 3, 4],
            'layout' => '2x2 grid',
            'interactive' => true,
            'framework' => 'Bootstrap responsive grid'
        ]),
        'tags' => 'c2.js, Interactive, Generative Art, Canvas, JavaScript',
        'status' => 'draft',
        'sort_order' => 2
    ]
];

foreach ($c2Art as $piece) {
    insertArtPiece('c2_art', $piece, $piece['title']);
}

// ==========================================
// SEED P5.JS ART
// ==========================================

output("\n" . str_repeat('=', 60), 'info');
output("🔵 Seeding p5.js Art Pieces", 'info');
output(str_repeat('=', 60), 'info');

$p5Art = [
    [
        'title' => '1 - p5.js',
        'description' => 'Elaborate digitally-rendered art piece using an array of shapes, lines, and experimentation programmed using the p5.js Library and JavaScript. Features a 5x5 grid of white rectangles, 99 black lines creating a fine grid pattern, and 3 gradient-filled circles.',
        'file_path' => '/p5/p5_1.php',
        'piece_path' => '/p5/piece/1.php',
        'thumbnail_url' => imageUrl('/p5/piece/1-p5.js.png'),
        'screenshot_url' => imageUrl('/p5/piece/1-p5.js.png'),
        'image_urls' => jsonEncode([]),
        'configuration' => jsonEncode([
            'library' => 'p5.js v1.9.0',
            'canvas' => [
                'responsive' => true,
                'width_desktop' => '25%',
                'width_mobile' => '70%'
            ],
            'elements' => [
                'rectangles' => ['count' => 25, 'layout' => '5x5 grid', 'color' => 'white'],
                'lines' => ['count' => 99, 'type' => 'grid', 'color' => 'black'],
                'circles' => ['count' => 3, 'fill' => 'gradient']
            ],
            'rendering' => 'static (noLoop)'
        ]),
        'tags' => 'p5.js, Generative Art, Static, Grid, Geometric',
        'status' => 'active',
        'sort_order' => 1
    ],
    [
        'title' => '2 - p5.js',
        'description' => 'Elaborate digitally-rendered art piece with 8x8 grid of rectangles, 26 diagonal lines, 99 curved organic lines, and 7 gradient circles. Features configurable colors for strokes and background.',
        'file_path' => '/p5/p5_2.php',
        'piece_path' => '/p5/piece/2.php',
        'thumbnail_url' => imageUrl('/p5/piece/2-p5.js.png'),
        'screenshot_url' => imageUrl('/p5/piece/2-p5.js.png'),
        'image_urls' => jsonEncode([]),
        'configuration' => jsonEncode([
            'library' => 'p5.js v1.9.0',
            'canvas' => [
                'responsive' => true,
                'width_desktop' => '25%',
                'width_mobile' => '70%'
            ],
            'elements' => [
                'rectangles' => ['count' => 64, 'layout' => '8x8 grid'],
                'diagonal_lines' => ['count' => 26, 'origin' => 'corners'],
                'curved_lines' => ['count' => 99, 'type' => 'organic'],
                'circles' => ['count' => 7, 'positions' => ['center (3)', 'corners (4)']]
            ],
            'colors' => [
                'configurable' => true,
                'stroke_color' => 'variable',
                'bg_color' => 'variable'
            ],
            'rendering' => 'static (noLoop)'
        ]),
        'tags' => 'p5.js, Generative Art, Static, Organic, Curves, Configurable',
        'status' => 'active',
        'sort_order' => 2
    ],
    [
        'title' => '3 - p5.js',
        'description' => 'Elaborate digitally-rendered art piece with 8x8 grid, 51 diagonal lines in 4 directions, 99 curved lines, and 7 circles with random color fills. Features dynamic color generation for an animated aesthetic.',
        'file_path' => '/p5/p5_3.php',
        'piece_path' => '/p5/piece/4.php',
        'thumbnail_url' => imageUrl('/p5/piece/3-p5.js.png'),
        'screenshot_url' => imageUrl('/p5/piece/3-p5.js.png'),
        'image_urls' => jsonEncode([]),
        'configuration' => jsonEncode([
            'library' => 'p5.js v1.9.0',
            'canvas' => [
                'responsive' => true,
                'width_desktop' => '35%',
                'width_mobile' => '70%'
            ],
            'elements' => [
                'rectangles' => ['count' => 64, 'layout' => '8x8 grid'],
                'diagonal_lines' => ['count' => 51, 'directions' => 4],
                'curved_lines' => ['count' => 99, 'type' => 'organic flow'],
                'circles' => ['count' => 7, 'fill' => 'random colors']
            ],
            'colors' => [
                'configurable' => true,
                'line_color' => 'variable',
                'bg_color' => 'variable',
                'random_fills' => true
            ],
            'rendering' => 'static (noLoop) with random colors',
            'note' => 'References piece/4.php - may need correction'
        ]),
        'tags' => 'p5.js, Generative Art, Random Colors, Dynamic, Organic',
        'status' => 'active',
        'sort_order' => 3
    ],
    [
        'title' => '4 - p5.js',
        'description' => 'Highly colorful and dynamic art piece with random colored lines in curved patterns, 5x5 grid with random colored rectangles and strokes, 99 random colored grid lines, and 7 circles with random colors. Most vibrant piece in the collection.',
        'file_path' => '/p5/p5_4.php',
        'piece_path' => '/p5/piece/4.php',
        'thumbnail_url' => imageUrl('/p5/piece/4-p5.js.png'),
        'screenshot_url' => imageUrl('/p5/piece/4-p5.js.png'),
        'image_urls' => jsonEncode([]),
        'configuration' => jsonEncode([
            'library' => 'p5.js v1.9.0',
            'canvas' => [
                'responsive' => true,
                'width_desktop' => '20-25%',
                'width_mobile' => '70%'
            ],
            'elements' => [
                'curved_lines' => ['type' => 'random colored', 'pattern' => 'height iterations'],
                'rectangles' => ['count' => 25, 'layout' => '5x5 grid', 'colors' => 'random (fill and stroke)'],
                'grid_lines' => ['count' => 99, 'colors' => 'random'],
                'circles' => ['count' => 7, 'positions' => ['center', 'corners'], 'colors' => 'random']
            ],
            'colors' => [
                'fully_random' => true,
                'vibrant' => true
            ],
            'rendering' => 'static (noLoop)',
            'note' => 'Shares piece/4.php with piece 3 - may need correction'
        ]),
        'tags' => 'p5.js, Generative Art, Random Colors, Vibrant, Dynamic, Colorful',
        'status' => 'active',
        'sort_order' => 4
    ]
];

foreach ($p5Art as $piece) {
    insertArtPiece('p5_art', $piece, $piece['title']);
}

// ==========================================
// SEED THREE.JS ART
// ==========================================

output("\n" . str_repeat('=', 60), 'info');
output("🔺 Seeding Three.js Art Pieces", 'info');
output(str_repeat('=', 60), 'info');

$threejsArt = [
    [
        'title' => 'First 3JS',
        'description' => 'Elaborate digital art scape programmed using Three.js WebGL framework and JavaScript. Features 3D rendered geometries with dynamic lighting and camera controls.',
        'file_path' => '/three-js/first.php',
        'embedded_path' => '/three-js/first-whole.php',
        'js_file' => '/three-js/first.js',
        'thumbnail_url' => '', // Three.js doesn't generate static thumbnails easily
        'texture_urls' => jsonEncode([]),
        'configuration' => jsonEncode([
            'library' => 'Three.js v104',
            'renderer' => 'WebGL',
            'files' => [
                'with_layout' => '/three-js/first.php',
                'standalone' => '/three-js/first-whole.php',
                'script' => '/three-js/first.js',
                'script_standalone' => '/three-js/first-whole.js'
            ],
            'display' => [
                'gallery_version' => 'first-whole.php (iframe)',
                'full_version' => 'first.php (with header/footer)'
            ]
        ]),
        'tags' => 'Three.js, WebGL, 3D, Interactive, JavaScript',
        'status' => 'active',
        'sort_order' => 1
    ],
    [
        'title' => 'Second 3JS',
        'description' => 'Elaborate digital art scape programmed using Three.js WebGL framework and JavaScript. Features 3D rendered geometries with dynamic lighting and camera controls.',
        'file_path' => '/three-js/second.php',
        'embedded_path' => '/three-js/second-whole.php',
        'js_file' => '/three-js/second.js',
        'thumbnail_url' => '',
        'texture_urls' => jsonEncode([]),
        'configuration' => jsonEncode([
            'library' => 'Three.js v104',
            'renderer' => 'WebGL',
            'files' => [
                'with_layout' => '/three-js/second.php',
                'standalone' => '/three-js/second-whole.php',
                'script' => '/three-js/second.js',
                'script_standalone' => '/three-js/second-whole.js'
            ],
            'display' => [
                'gallery_version' => 'Not currently in gallery',
                'full_version' => 'second.php (with header/footer)'
            ]
        ]),
        'tags' => 'Three.js, WebGL, 3D, Interactive, JavaScript',
        'status' => 'draft',
        'sort_order' => 2
    ],
    [
        'title' => 'Third 3JS',
        'description' => 'Elaborate digital art scape programmed using Three.js WebGL framework and JavaScript. Features 3D rendered geometries with dynamic lighting and camera controls.',
        'file_path' => '/three-js/third.php',
        'embedded_path' => '/three-js/third-whole.php',
        'js_file' => '/three-js/third.js',
        'thumbnail_url' => '',
        'texture_urls' => jsonEncode([]),
        'configuration' => jsonEncode([
            'library' => 'Three.js v104',
            'renderer' => 'WebGL',
            'files' => [
                'with_layout' => '/three-js/third.php',
                'standalone' => '/three-js/third-whole.php',
                'script' => '/three-js/third.js',
                'script_standalone' => '/three-js/third-whole.js'
            ],
            'display' => [
                'gallery_version' => 'third-whole.php (iframe)',
                'full_version' => 'third.php (with header/footer)'
            ]
        ]),
        'tags' => 'Three.js, WebGL, 3D, Interactive, JavaScript',
        'status' => 'active',
        'sort_order' => 3
    ]
];

foreach ($threejsArt as $piece) {
    insertArtPiece('threejs_art', $piece, $piece['title']);
}

// ==========================================
// SUMMARY
// ==========================================

output("\n" . str_repeat('=', 60), 'info');
output("✅ Database seeding complete!", 'success');
output(str_repeat('=', 60), 'info');

// Get updated statistics
output("\n📊 Updated Database Statistics:", 'info');
$stats = dbGetStats();
foreach ($stats as $table => $count) {
    if ($count !== 'Not created') {
        output("  - {$table}: {$count} records", $count > 0 ? 'success' : 'muted');
    }
}

output("\n📝 Notes:", 'info');
output("  - A-Frame: 2 pieces (1 active, 1 draft)", 'info');
output("  - c2.js: 2 pieces (1 active, 1 draft)", 'info');
output("  - p5.js: 4 pieces (all active)", 'info');
output("  - Three.js: 3 pieces (2 active, 1 draft)", 'info');
output("  - Active pieces are shown in gallery indexes", 'info');
output("  - Draft pieces are hidden but accessible via direct URL", 'info');

output("\n⚠️  Known Issues:", 'warning');
output("  - p5 pieces 3 and 4 both reference piece/4.php (may need correction)", 'warning');
output("  - c2.js and Three.js pieces don't have thumbnails yet (consider capturing)", 'warning');

output("\n🎉 Database is now populated with existing art pieces!", 'success');
output("Next steps:", 'info');
output("  1. Review pieces in admin interface (when available)", 'info');
output("  2. Add missing thumbnails for c2.js and Three.js pieces", 'info');
output("  3. Resolve p5 piece/4.php duplication issue", 'info');
output("  4. Test gallery pages with database-driven content", 'info');

if (PHP_SAPI !== 'cli') {
    echo "</div></body></html>";
}
