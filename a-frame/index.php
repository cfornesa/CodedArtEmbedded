<?php 

require('../resources/templates/name.php');


  $page_name = "A-Frame Exhibit";
  $tagline = "A-Frame code-generated art by " . $site_link . ".";
  require('../resources/templates/head.php');
?>
  <body>
    <?php require("../resources/templates/header-level.php") ?>
    <main class="container">
      <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
          <a href="../a-frame">
            <h2>A-Frame Exhibit</h2>
          </a>
          <iframe
            src="../a-frame/alt-piece-ns.php"
          ></iframe>
          <p>
            Elaborate digital art scapes using digitally-rendered p5.js art
            pieces and photos of physical art works programmed by and created by <?php echo $name ?> using the A-Frame WebVR framework and JavaScript.
          </p>
        </div>
      </div>
    </main>
    <?php require("../resources/templates/footer-level.php") ?>
  </body>
</html>