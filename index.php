<?php 

require('resources/templates/name.php');
require('config/pages.php');

// Get page variables from centralized registry
$pageInfo = getPageInfo();
extract($pageInfo); // Creates $page_name, $tagline, $section, $type

require('resources/templates/head.php');
?>
  <body>
    <?php require("resources/templates/header.php") ?>
    <main class="container">
      <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
          <a href="./a-frame">
            <h2>A-Frame Exhibit</h2>
          </a>
          <iframe
            src="/a-frame/alt-piece-ns.php"
          ></iframe>
          <p>
            Elaborate digital art scapes using digitally-rendered p5.js art
            pieces and photos of physical art works programmed by and created by <?php echo $name ?> using the A-Frame WebVR framework and JavaScript.
          </p>
        </div>
      </div>
      <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12"><h6>&nbsp;</h6></div>
      </div>
      <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12"><h2><a href="/c2">c2.js Exhibit</a></h2></div>
      </div>
      <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12"><h6>&nbsp;</h6></div>
      </div>
      <div class="row">
        <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
          <center>
            <canvas id='1' class='piece-img' />
          </center>
        </div>
        <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
          <center>
            <canvas id='2' class='piece-img'/>
          </center>
        </div>
      </div>
      <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12"><h6>&nbsp;</h6></div>
      </div>
      <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
          <center>
            <a href="./p5">
              <h2>p5.js Exhibit</h2>
            </a>
          </center>
          <center>
            <img src="./p5/piece/4-p5.js.png" class="piece-img" alt="p5.js piece 1">
          </center>
          <p>
            Elaborate digitally-rendered art pieces using an array of shapes,
            lines, and experimentation programmed by <?php echo $name ?> using the p5.js
            Library and JavaScript.
          </p>
        </div>
      </div>
      <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
          <center>
            <a href="./three-js">
              <h2>Three.js Exhibit</h2>
            </a>
          </center>
          <center>
            <iframe
              src="/three-js/first-whole.php" scrolling="no"
            ></iframe>
          </center>
          <p></p>
        </div>
      </div>
    </main>
    <?php echo $counter; ?>
    <?php require("resources/templates/footer.php") ?>
    <script src="/js/c2.min.js"></script>
    <script src="/c2/1/1-1.js"></script>
    <script src="/c2/1/1-2.js"></script>
  </body>
</html>