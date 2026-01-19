<div id="sketch-container"></div>

<script>
  let sketch = (p) => {
    p.setup = () => {
      const container = document.getElementById('sketch-container');

      // Determine the appropriate canvas size based on screen width
      let canvasWidth = p.windowWidth < 992
        ? p.windowWidth * 0.7
        : p.windowWidth * 0.25;

      const canvas = p.createCanvas(canvasWidth, canvasWidth);
      canvas.parent(container);
      p.background(200);
    };

    p.draw = () => {
      p.background(200);
      p.fill("white");

      for (let posX = 0; posX < 5; posX++) {
        for (let posY = 0; posY < 5; posY++) {
          p.rect(
            posX * 0.1875 * p.width + p.width / 16,
            posY * 0.1875 * p.height + p.width / 16,
            p.width / 8,
            p.width / 8
          );
        }
      }

      p.stroke(0);
      for (let lineX = 0; lineX < 99; lineX++) {
        p.line(
          p.width / 100 + (lineX * p.width) / 100, 0,
          p.width / 100 + (lineX * p.width) / 100, p.height
        );
        p.line(
          0, p.width / 100 + (lineX * p.width) / 100,
          p.width, p.width / 100 + (lineX * p.width) / 100
        );
      }

      draw_circle(p.width / 2, p.height / 2, (0.6375 * p.width * 3) / 4, p.width / 80);
      draw_circle(p.width / 5, p.height / 5, 50, 5);
      draw_circle(p.width - p.width / 5, p.height - p.height / 5, p.width / 8, p.width / 80);

      p.noLoop();
    };

    function draw_circle(x, y, radius, iteration) {
      for (let i = 0; i < radius; i += iteration) {
        p.fill(i);
        p.circle(x, y, radius - i);
      }
    }

    p.windowResized = () => {
      const container = document.getElementById('sketch-container');
      let newWidth = p.windowWidth < 992
        ? p.windowWidth * 0.7
        : p.windowWidth * 0.25;

      p.resizeCanvas(newWidth, newWidth);
      p.redraw();
    };
  };

  new p5(sketch);
</script>