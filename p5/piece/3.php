                  <?php
                  // Set default colors if not already defined
                  $line_color = $line_color ?? '#000000'; // default stroke color: black
                  $bg_color = $bg_color ?? '#ffffff';     // default background: white
                  ?>

                  <div id="fourth-sketch-container"></div>

                  <script>
                    const lineColor = "<?= $line_color ?>";
                    const bgColor = "<?= $bg_color ?>";

                    let fourthSketch = (p) => {
                      p.setup = () => {
                        let canvasWidth = p.windowWidth * 0.7;
                        if (p.windowWidth > 992) {
                          canvasWidth = p.windowWidth * 0.35;
                        }

                        const canvas = p.createCanvas(canvasWidth, canvasWidth);
                        canvas.parent('fourth-sketch-container');
                        p.background(bgColor);
                      };

                      p.draw = () => {
                        p.fill(bgColor);
                        p.stroke(lineColor);

                        // Grid of rectangles
                        for (let posX = 0; posX < 8; posX++) {
                          for (let posY = 0; posY < 8; posY++) {
                            p.rect(
                              posX * (p.width / 8),
                              posY * (p.width / 8),
                              p.width / 8,
                              p.width / 8
                            );
                          }
                        }

                        // Diagonal Lines
                        for (let lineX = 0; lineX < 51; lineX++) {
                          const step = p.width / 50;
                          p.line(lineX * step, 0, 0, p.height);          // bottom-left
                          p.line(p.width, p.height, lineX * step, 0);    // bottom-right
                          p.line(0, 0, lineX * step, p.height);          // top-left
                          p.line(p.width, 0, lineX * step, p.height);    // top-right
                        }

                        // Curved Lines
                        for (let lineX = 0; lineX < 99; lineX++) {
                          const offset = p.height / 100 + lineX * (p.height / 100);
                          const endpoint = offset;

                          // Top-left curve
                          p.line(0, p.height - offset, endpoint, 0);

                          // Top-right curve
                          p.line(p.width, offset, endpoint, 0);

                          // Bottom-left curve
                          p.line(0, offset, endpoint, p.height);

                          // Bottom-right curve
                          p.line(p.width, p.height - offset, endpoint, p.height);
                        }

                        // Circles (center and corners)
                        draw_circle(p.width / 2, p.height / 2, 0.625 * p.width, p.height / 80);
                        draw_circle(p.width / 2, p.height / 2, 0.375 * p.width, p.height / 80);
                        draw_circle(p.width / 2, p.height / 2, p.width / 8, p.height / 80);

                        draw_circle(p.width / 8, p.width / 8, 0.15 * p.width, p.height * 0.0125);
                        draw_circle(p.width - p.width / 8, p.height / 8, 0.15 * p.width, p.height / 80);
                        draw_circle(p.width / 8, p.height - p.width / 8, 0.15 * p.width, p.height / 80);
                        draw_circle(p.width - p.width / 8, p.height - p.width / 8, 0.15 * p.width, p.height / 80);

                        p.noLoop(); // Prevent redraw
                      };

                      function draw_circle(x, y, radius, step) {
                        for (let i = 0; i < radius; i += step) {
                          p.fill(Math.random() * 255, Math.random() * 255, Math.random() * 255);
                          p.circle(x, y, radius - i);
                        }
                      }

                      p.windowResized = () => {
                        let canvasWidth = p.windowWidth > 992 ? p.windowWidth * 0.35 : p.windowWidth * 0.7;
                        p.resizeCanvas(canvasWidth, canvasWidth);
                        p.background(bgColor);
                        p.redraw();
                      };
                    };

                    new p5(fourthSketch);
                  </script>
