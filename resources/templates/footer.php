<!--
/**
 * Unified Footer Template
 * Auto-detects directory level and adjusts paths accordingly
 */
-->
<footer>
  <p>Copyright &copy; <?php
    echo date("Y") . " ";
    echo $name . " ";
    echo "- ";

    // Auto-detect the correct path prefix based on directory level
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

    require($pathPrefix . "resources/templates/navigation.php");
  ?></p>
</footer>
  