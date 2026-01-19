<?php 

require('../resources/templates/name.php');


  $page_name = "p5.js Exhibit: 3";
  $tagline = "p5.js code-generated art by " . $site_link . ".";
  require('../resources/templates/head.php');
?>
  <body>
    <?php require("../resources/templates/header-level.php") ?>
    <main class="container">
      <div class="row">
              <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <center>
                  <a href="../p5">
                    <h2>3 - p5.js</h2>
                  </a>
                </center>
                <center>
      <?php require("piece/4.php") ?>
                </center>
                <p>
                  Elaborate digitally-rendered art pieces using an array of shapes,
                  lines, and experimentation programmed by <?php echo $name ?> using the p5.js
                  Library and JavaScript.
                </p>
              </div>
      </div>
    </main>
    <?php require("../resources/templates/footer-level.php") ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/p5.js/1.9.0/p5.min.js"></script>
  </body>
</html>