<?php 

require('../resources/templates/name.php');


  $page_name = "p5.js Exhibit";
  $tagline = "p5.js code-generated art by " . $site_link . ".";
  require('../resources/templates/head.php');
?>
  <body>
    <?php require("../resources/templates/header-level.php") ?>
    <main class="container">
      <div class="row">
        <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
          <center>
            <a href="./p5/p5_1.php">
              <h2>1 - p5.js</h2>
            </a>
          </center>
          <center>
<img src="./p5/piece/1-p5.js.png" alt="p5.js art piece 1" class="piece-img" />
          </center>
          <p>
            Elaborate digitally-rendered art pieces using an array of shapes,
            lines, and experimentation programmed by <?php echo $name ?> using the p5.js
            Library and JavaScript.
          </p>
        </div>

        <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
          <center>
            <a href="./p5/p5_2.php">
              <h2>2 - p5.js</h2>
            </a>
          </center>
          <center>
<img src="./p5/piece/2-p5.js.png" alt="p5.js art piece 2" class="piece-img" />
          </center>
          <p>
            Elaborate digitally-rendered art pieces using an array of shapes,
            lines, and experimentation programmed by <?php echo $name ?> using the p5.js
            Library and JavaScript.
          </p>
        </div>
      </div>
      <div class="row">
        <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
          <center>
            <a href="./p5/p5_3.php">
              <h2>3 - p5.js</h2>
            </a>
          </center>
          <center>
<img src="./p5/piece/3-p5.js.png" alt="p5.js art piece 3" class="piece-img" />
          </center>
          <p>
            Elaborate digitally-rendered art pieces using an array of shapes,
            lines, and experimentation programmed by <?php echo $name ?> using the p5.js
            Library and JavaScript.
          </p>
        </div>

        <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
          <center>
            <a href="./p5/p5_4.php">
              <h2>4 - p5.js</h2>
            </a>
          </center>
          <center>
<img src="./p5/piece/4-p5.js.png" alt="p5.js art piece 4" class="piece-img" />
          </center>
          <p>
            Elaborate digitally-rendered art pieces using an array of shapes,
            lines, and experimentation programmed by <?php echo $name ?> using the p5.js
            Library and JavaScript.
          </p>
        </div>
      </div>
    </main>
    <?php echo $counter; ?>
    <?php require("../resources/templates/footer-level.php") ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/p5.js/1.9.0/p5.min.js"></script>
    <script src="./js/portfolio.js"></script>
  </body>
</html>