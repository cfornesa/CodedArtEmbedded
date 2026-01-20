<?php

require('../resources/templates/name.php');
require('../config/pages.php');

// Get page variables from centralized registry
$pageInfo = getPageInfo();
extract($pageInfo); // Creates $page_name, $tagline, $section, $type

require('../resources/templates/head.php');
?>
  <body>
    <?php require("../resources/templates/header-level.php") ?>
    <main class="container">
      <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
          <a href="/three-js/first.php">
            <h2>First</h2>
          </a>
          <iframe
            src="/three-js/first-whole.php" scrolling="no"
          ></iframe>
          <p>
            Elaborate digital art scapes using digitally-rendered p5.js art
            pieces and photos of physical art works programmed by and created by <?php echo $name ?> using the A-Frame WebVR framework and JavaScript.
          </p>
        </div>
      </div>
      <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
          <a href="/three-js/third.php">
            <h2>Third</h2>
          </a>
          <iframe
            src="/three-js/third-whole.php" scrolling="no"
          ></iframe>
          <p>
            Elaborate digital art scapes using digitally-rendered p5.js art
            pieces and photos of physical art works programmed by and created by <?php echo $name ?> using the A-Frame WebVR framework and JavaScript.
          </p>
        </div>
      </div>
    </main>
    <?php echo $counter; ?>
    <?php require("../resources/templates/footer-level.php") ?>
  </body>
</html>