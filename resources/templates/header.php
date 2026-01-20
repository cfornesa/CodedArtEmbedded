<?php
/**
 * Unified Header Template
 * Auto-detects directory level and adjusts paths accordingly
 */

// Auto-detect the correct path prefix based on directory level
// Check if we're in a subdirectory by testing if navigation.php exists at different paths
if (file_exists('resources/templates/navigation.php')) {
    // We're at root level
    $pathPrefix = '';
} elseif (file_exists('../resources/templates/navigation.php')) {
    // We're one level deep
    $pathPrefix = '../';
} elseif (file_exists('../../resources/templates/navigation.php')) {
    // We're two levels deep (future-proofing)
    $pathPrefix = '../../';
} else {
    // Default to relative path from current template location
    $pathPrefix = '';
}

echo "<header id='alt-info'>
  <h1>$name_img - $page_name</h1>
  <p>$tagline</p><nav>";

require($pathPrefix . 'resources/templates/navigation.php');

echo "</nav></center></header>";
?>