<?php

require('../resources/templates/name.php');
require('../config/pages.php');
require('../config/database.php');

// Get page variables from centralized registry
$pageInfo = getPageInfo();
extract($pageInfo); // Creates $page_name, $tagline, $section, $type

// Fetch active Three.js art pieces from database
try {
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT * FROM threejs_art WHERE status = ? ORDER BY sort_order ASC, created_at DESC");
    $stmt->execute(['active']);
    $artPieces = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching Three.js art pieces: " . $e->getMessage());
    $artPieces = [];
}

require('../resources/templates/head.php');
?>
  <body>
    <?php require("../resources/templates/header-level.php") ?>
    <main class="container">
      <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
          <h1>Three.js Gallery</h1>
          <p>
            Elaborate digital art scapes using digitally-rendered art pieces and photos of physical art works
            programmed by and created by <?php echo $name ?> using the Three.js framework and JavaScript.
          </p>
        </div>
      </div>

      <?php if (empty($artPieces)): ?>
        <div class="row">
          <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
            <p class="text-center">No Three.js art pieces available at this time.</p>
          </div>
        </div>
      <?php else: ?>
        <?php foreach ($artPieces as $piece): ?>
          <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
              <a href="<?php echo htmlspecialchars($piece['file_path']); ?>">
                <h2><?php echo htmlspecialchars($piece['title']); ?></h2>
              </a>

              <?php if (!empty($piece['embedded_path'])): ?>
                <iframe
                  src="<?php echo htmlspecialchars($piece['embedded_path']); ?>"
                  scrolling="no"
                  style="width: 100%; height: 500px; border: none; border-radius: 8px;"
                ></iframe>
              <?php elseif (!empty($piece['thumbnail_url'])): ?>
                <center>
                  <a href="<?php echo htmlspecialchars($piece['file_path']); ?>">
                    <img
                      src="<?php echo htmlspecialchars($piece['thumbnail_url']); ?>"
                      alt="<?php echo htmlspecialchars($piece['title']); ?>"
                      class="piece-img"
                      style="max-width: 100%; height: auto; border-radius: 8px; margin: 10px 0;"
                    />
                  </a>
                </center>
              <?php endif; ?>

              <?php if (!empty($piece['description'])): ?>
                <p><?php echo nl2br(htmlspecialchars($piece['description'])); ?></p>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </main>
    <?php echo $counter; ?>
    <?php require("../resources/templates/footer-level.php") ?>
  </body>
</html>