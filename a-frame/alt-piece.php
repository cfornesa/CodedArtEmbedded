<?php 

require("../resources/templates/name.php");

  $page_name = "Alt Piece";
  $tagline = "Code and code-generated art by " . $site_link . ".";
  require("../resources/templates/head.php");
?>
  <body>
    <?php require("../resources/templates/header-level.php") ?>
    <?php require('../resources/content/aframe/alt-piece-ns.php') ?>
    <?php require("../resources/templates/footer-level.php") ?>
  </body>
</html>