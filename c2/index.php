<?php

require('../resources/templates/name.php');
require('../config/pages.php');
require('../config/database.php');

// Get page variables from centralized registry
$pageInfo = getPageInfo();
extract($pageInfo); // Creates $page_name, $tagline, $section, $type

// Fetch active C2 art pieces from database
try {
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT * FROM c2_art WHERE status = ? ORDER BY sort_order ASC, created_at DESC");
    $stmt->execute(['active']);
    $artPieces = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching C2 art pieces: " . $e->getMessage());
    $artPieces = [];
}

require('../resources/templates/head.php');
?>
  <body>
    <?php require("../resources/templates/header.php") ?>
    <main class="container">
      <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
          <h1>C2.js Gallery</h1>
          <p>
            Creative canvas-based art pieces using the C2.js library,
            programmed by <?php echo $name ?> with JavaScript and HTML5 Canvas.
          </p>
        </div>
      </div>

      <?php if (empty($artPieces)): ?>
        <div class="row">
          <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
            <p class="text-center">No C2.js art pieces available at this time.</p>
          </div>
        </div>
      <?php else: ?>
        <?php foreach ($artPieces as $index => $piece): ?>
          <?php if ($index % 2 === 0 && $index !== 0): ?>
            </div><div class="row">
          <?php endif; ?>

          <?php if ($index === 0): ?>
            <div class="row">
          <?php endif; ?>

          <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
            <center>
              <a href="<?php echo htmlspecialchars($piece['file_path']); ?>">
                <h2><?php echo htmlspecialchars($piece['title']); ?></h2>
              </a>
            </center>

            <?php if (!empty($piece['thumbnail_url'])): ?>
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

            <?php if (!empty($piece['canvas_count'])): ?>
              <p><small><strong>Canvases:</strong> <?php echo (int)$piece['canvas_count']; ?></small></p>
            <?php endif; ?>
          </div>

          <?php if ($index === count($artPieces) - 1): ?>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>
      <?php endif; ?>
    </main>
    <?php echo $counter; ?>
    <?php require("../resources/templates/footer.php") ?>
  </body>
</html>