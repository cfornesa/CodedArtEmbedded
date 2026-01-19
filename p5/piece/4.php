<?php
// Set default color values if not already set
$tl_color = $tl_color ?? '#ff0000'; // top-left: red
$tr_color = $tr_color ?? '#00ff00'; // top-right: green
$lr_color = $lr_color ?? '#0000ff'; // lower-right: blue
$ll_color = $ll_color ?? '#ffff00'; // lower-left: yellow
$bg_color = $bg_color ?? '#ffffff'; // background: white
?>

<div id="third-sketch-container"></div>

<script>
  const tlColor = "<?= $tl_color ?>";
  const trColor = "<?= $tr_color ?>";
  const lrColor = "<?= $lr_color ?>";
  const llColor = "<?= $ll_color ?>";
  const bgColor = "<?= $bg_color ?>";

  let thirdSketch = (p) => {
    p.setup = () => {
      let canvasWidth = p.windowWidth * 0.7;
      if (p.windowWidth > 1440) {
        canvasWidth = p.windowWidth * 0.2;
      } else if (p.windowWidth > 992) {
        canvasWidth = p.windowWidth * 0.25;
      }

      const canvas = p.createCanvas(canvasWidth, canvasWidth);
      canvas.parent('third-sketch-container');
      p.background(bgColor);
    };

    p.draw = () => {
      for (let lineX = 0; lineX < p.height; lineX++) {
        p.stroke(Math.random() * 255, Math.random() * 255, Math.random() * 255);
        p.line(
          0,
          p.height - (p.height / (p.height / 4) + lineX * (p.height / (p.height / 4))),
          p.height / (p.height / 4) + lineX * (p.height / (p.height / 4)),
          0
        );

        p.stroke(Math.random() * 255, Math.random() * 255, Math.random() * 255);
        p.line(
          p.width,
          p.height / 100 + lineX * (p.height / 100),
          p.height / 100 + lineX * (p.height / 100),
          0
        );

        p.stroke(Math.random() * 255, Math.random() * 255, Math.random() * 255);        p.line(
          0,
          p.height / 100 + lineX * (p.height / 100),
          p.height / 100 + lineX * (p.height / 100),
          p.height
        );

        p.stroke(Math.random() * 255, Math.random() * 255, Math.random() * 255);
        p.line(
          p.width,
          p.height - (p.height / 100 + lineX * (p.height / 100)),
          p.height / 100 + lineX * (p.height / 100),
          p.height
        );
      }

      // New code for grid of rectangles
        for (let posX = 0; posX < 5; posX++) {
          for (let posY = 0; posY < 5; posY++) {
            p.stroke(Math.random() * 255, Math.random() * 255, Math.random() * 255);
            p.background(Math.random() * 255, Math.random() * 255, Math.random() * 255);
            p.rect(
              posX * 0.1875 * p.width + p.width / 16,
              posY * 0.1875 * p.height + p.width / 16,
              p.width / 8,
              p.width / 8
            );
          }
        }

        // New code for grid of lines
        for (let lineX = 0; lineX < 99; lineX++) {
          p.stroke(Math.random() * 255, Math.random() * 255, Math.random() * 255);
          p.line(
            p.width / 100 + (lineX * p.width) / 100, 0,
            p.width / 100 + (lineX * p.width) / 100, p.height
          );
          p.line(
            0, p.width / 100 + (lineX * p.width) / 100,
            p.width, p.width / 100 + (lineX * p.width) / 100
          );
        }

      // Center circles
      p.stroke(Math.random() * 255, Math.random() * 255, Math.random() * 255);
      draw_circle(p.width / 2, p.height / 2, 0.69 * p.width, p.height / 80, 0.5);
      draw_circle(p.width / 2, p.height / 2, 0.5 * p.width, p.height / 80, 2);

      // Top-left
      draw_circle(p.width / 7, p.width / 7, p.width / 4, p.height / 80, 0.5);
      draw_circle(p.width / 7, p.width / 7, p.width / 5, p.height / 80, 4);

      // Top-right
      draw_circle(p.width - p.width / 7, p.height / 7, p.width / 4, p.height / 80, 0.05);
      draw_circle(p.width - p.width / 7, p.height / 7, 0.2 * p.width, p.height / 80, 4);

      // Bottom-left
      draw_circle(p.width / 7, p.height - p.width / 7, p.width / 4, p.height / 80, 0.5);
      draw_circle(p.width / 7, p.height - p.width / 7, p.width / 5, p.height / 80, 4);

      // Bottom-right
      draw_circle(p.width - p.width / 7, p.height - p.width / 7, p.width / 4, p.height / 80, 0.5);
      draw_circle(p.width - p.width / 7, p.height - p.width / 7, p.width / 5, p.height / 80, 4);

      p.noLoop();
    };

    function draw_circle(x, y, radius, iteration, factor) {
      for (let i = 0; i < radius; i += iteration) {
        p.stroke(Math.random() * 255, Math.random() * 255, Math.random() * 255);
        p.fill(Math.random() * 255, Math.random() * 255, Math.random() * 255);
        p.circle(x, y, radius - i);
      }
    }

    p.windowResized = () => {
      let canvasWidth = p.windowWidth * 0.7;
      if (p.windowWidth > 1440) {
        canvasWidth = p.windowWidth * 0.2;
      } else if (p.windowWidth > 992) {
        canvasWidth = p.windowWidth * 0.25;
      }

      p.resizeCanvas(canvasWidth, canvasWidth);
      p.background(bgColor);
      p.redraw();
    };
  };

  new p5(thirdSketch);
</script>
