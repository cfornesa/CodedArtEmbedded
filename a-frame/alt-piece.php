<?php

require("../resources/templates/name.php");
require('../config/pages.php');

// Get page variables from centralized registry
$pageInfo = getPageInfo();
extract($pageInfo); // Creates $page_name, $tagline, $section, $type

require("../resources/templates/head.php");
?>
  <body>
    <?php require("../resources/templates/header.php") ?>
    <?php require('../resources/content/aframe/alt-piece-ns.php') ?>
    <?php require("../resources/templates/footer.php") ?>
  </body>
</html>