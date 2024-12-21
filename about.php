<?php 

require('resources/templates/name.php');


  $page_name = "About";
  $tagline = "Code and code-generated art by " . $site_link . ".";
  require('resources/templates/head.php');
?>
  <body>
    <?php require("resources/templates/header.php") ?>
    <?php require('resources/content/home.php') ?>
    <?php require("resources/templates/footer.php") ?>
  </body>
</html>