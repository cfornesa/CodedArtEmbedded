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
          <h2>Please Sign My Guestbook</h2>
          <iframe src="https://fornesus.atabook.org/" width="100%" height="auto"></iframe>
        </div>
      </div>
    </main>
    <?php echo $counter; ?>
    <?php require("resources/templates/footer.php") ?>
  </body>
</html>