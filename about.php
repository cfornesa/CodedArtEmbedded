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
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 centered">
          <h2>Hi, I'm <?php echo $site_link ?> and I'm a lot of things.</h2>
          <p>But I at least usually call myself an artist, of sorts. And very much a creative.</p>
        </div>
      </div>
      <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12"><p>&nbsp;</p></div>
      </div>
      <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 centered">
          <h2>Artist</h2>
          <p>I make art, and my portfolio's located <a href="https://fornesusart.com">here</a>.</p>
        </div>
      </div>
      <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12"><p>&nbsp;</p></div>
      </div>
      <div class="row">
        <div class="col-lg-4 col-md-12 col-sm-12 col-xs-12 centered">
          <h2>Designer</h2>
          <p>I know how to design things, sometimes.</p>
          <p>You can find that <a href="https://design.fornesus.com">here</a>.</p>
        </div>
        <div class="col-lg-4 col-md-12 col-sm-12 col-xs-12 centered">
          <div>
            <img src="./img/images/kiba-drawing.jpg" alt="Drawing of Kiba Inu" width="100%" height="auto" max-width="100px">
          </div>
        </div>
        <div class="col-lg-4 col-md-12 col-sm-12 col-xs-12 centered">
          <h2>Writer</h2>
          <p>Sort of, I just have a blog.</p>
          <p>And you can it find <a href="https://fornesus.blog">here</a>.</p>
        </div>
      </div>
      <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12"><h6>&nbsp;</h6></div>
      </div>
      <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 centered">
            <h2>Content Creator</h2>
            <p>Also, only sort of.</p><p>I just make content and stream on <a href="https://twitch.tv/fornesus">Twitch</a> and <a href="https://youtube.com/@fornesus">YouTube</a> sometimes.</p>
        </div>
      </div>
    </main>
    <?php echo $counter; ?>
    <?php require("resources/templates/footer.php") ?>
  </body>
</html>