<?php
  // Set default stroke and background colors
  $strokeColor = $strokeColor ?? '#000000';   // Default: black
  $bgColor = $bgColor ?? '#ffffff';           // Default: white
?>
<div id="second-sketch-container"></div>

<script>
  const sketchColor = '<?= $strokeColor ?>';
  const sketchBackground = '<?= $bgColor ?>';

  let secondSketch = (p) => {
    p.setup = () => {
      let canvasWidth = p.windowWidth * 0.7;
      let canvasHeight = p.windowWidth * 0.7;

      if (p.windowWidth > 992) {
        canvasWidth = p.windowWidth * 0.25;
        canvasHeight = p.windowWidth * 0.25;
      }

      const canvas = p.createCanvas(canvasWidth, canvasHeight);
      canvas.parent('second-sketch-container');
    };

    p.draw = () => {
      p.fill(sketchBackground);
      p.stroke(sketchColor);

      for (let posX = 0; posX < 8; posX++) {
        for (let posY = 0; posY < 8; posY++) {
          p.rect(posX * (p.width / 8), posY * (p.width / 8), p.width / 8, p.width / 8);
        }
      }

      for (let lineX = 0; lineX < 26; lineX++) {
        p.line(lineX * (p.width / 25), 0, 0, p.height);
        p.line(p.width, p.height, lineX * (p.width / 25), 0);
        p.line(0, 0, lineX * (p.width / 25), p.height);
        p.line(p.width, 0, lineX * (p.width / 25), p.height);
      }

      for (let lineX = 0; lineX < 99; lineX++) {
        p.line(0, p.height - (p.height / 100 + lineX * (p.height / 100)), p.height / 100 + lineX * (p.height / 100), 0);
        p.line(p.width, p.height / 100 + lineX * (p.height / 100), p.height / 100 + lineX * (p.height / 100), 0);
        p.line(0, p.height / 100 + lineX * (p.height / 100), p.height / 100 + lineX * (p.height / 100), p.height);
        p.line(p.width, p.height - (p.height / 100 + lineX * (p.height / 100)), p.height / 100 + lineX * (p.height / 100), p.height);
      }

      draw_circle(p.width / 2, p.height / 2, 0.625 * p.width, p.height / 80);
      draw_circle(p.width / 2, p.height / 2, 0.375 * p.width, p.height / 80);
      draw_circle(p.width / 2, p.height / 2, p.width / 8, p.height / 80);

      draw_circle(p.width / 8, p.width / 8, 0.15 * p.width, p.height * 0.0125);
      draw_circle(p.width - p.width / 8, p.height / 8, 0.15 * p.width, p.height / 80);
      draw_circle(p.width / 8, p.height - p.width / 8, 0.15 * p.width, p.height / 80);
      draw_circle(p.width - p.width / 8, p.height - p.width / 8, 0.15 * p.width, p.height / 80);

      p.noLoop();
    };

    function draw_circle(x, y, radius, iteration) {
      for (let i = 0; i < radius; i += iteration) {
        p.fill(i);
        p.circle(x, y, radius - i);
      }
    }

    p.windowResized = () => {
      if (p.windowWidth < 992) {
        p.resizeCanvas(p.windowWidth * 0.7, p.windowWidth * 0.7);
      } else {
        p.resizeCanvas(p.windowWidth * 0.25, p.windowWidth * 0.25);
      }
      p.redraw();
    };
  };

  new p5(secondSketch);
</script>
