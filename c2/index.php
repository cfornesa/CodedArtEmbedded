<?php 

require('../resources/templates/name.php');


  $page_name = "c2.js Exhibit";
  $tagline = "c2.js code-generated art by " . $site_link . ".";
  require('../resources/templates/head.php');
?>
  <body>
    <?php require("../resources/templates/header-level.php") ?>
    <main class="container">
      <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs">
          <a href="/c2/1.php"><h2>1 - C2</h2></a>
        </div>
      </div>
      <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 c2js center">&nbsp;</div>
      </div>
      <div class="row">
        <div class="col-lg-12 col-md-6 col-sm-12 col-xs-12 c2js center">
          <canvas id='1'/>
        </div>
      </div>
      <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 c2js center">&nbsp;</div>
      </div>
    </main>
    <?php require("../resources/templates/footer-level.php") ?>
    <script src="../js/c2.min.js"></script>
    <script src="/c2/1/1-1.js"></script>
  </body>
</html>