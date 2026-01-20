<?php

require('../resources/templates/name.php');
require('../config/pages.php');

// Get page variables from centralized registry
$pageInfo = getPageInfo();
extract($pageInfo); // Creates $page_name, $piece_name, $tagline, $section, $type

require('../resources/templates/head.php');
?>
  <body>
    <?php require("../resources/templates/header.php") ?>
    <main class="container">
      <div class="row">
        <div class="col-lg-12 col-md-6 col-sm-12 col-xs-12">
          <h2><?php echo $piece_name; ?></h2>
        </div>
      </div>
      <div class="row">
        <div class="col-lg-12 col-md-6 col-sm-12 col-xs-12">
          <h6>&nbsp;</h6>
        </div>
      </div>
      <div class="row">
        <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12 c2js right">
          <canvas id='1'/>
        </div>
        <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12 c2js left">
          <canvas id='2'/>
        </div>
      </div>
      <div class="row">
        <div class="col-lg-12 col-md-6 col-sm-12 col-xs-12 c2js center">
          <h6>&nbsp;</h6>
        </div>
      </div>
      <div class="row">
        <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12 c2js right">
          <canvas id='3'/>
        </div>
        <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12 c2js left">
          <canvas id='4'/>
        </div>
      </div>
      <div class="row">
        <div class="col-lg-12 col-md-6 col-sm-12 col-xs-12">
          <h6>&nbsp;</h6>
        </div>
      </div>
      <div class="row">
        <div class="col-lg-12 col-md-6 col-sm-12 col-xs-12">
          <p>
            Elaborate digital art scapes using digitally-rendered p5.js art
            pieces and photos of physical art works programmed by and created by <?php echo $name ?> using the A-Frame WebVR framework and JavaScript.
          </p>
          <p>NOTE: Please refresh the window on your device after resizing.  Due to limitations with the c2.js library, resizing your window will result in losing interactivity for each canvas.</p>
        </div>
      </div>
    </main>
    <?php require("../resources/templates/footer.php") ?>
    <script src="../js/c2.min.js"></script>
    <script src="/c2/2/2.js"></script>
  </body>
</html>