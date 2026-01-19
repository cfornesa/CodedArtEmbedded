// Function to generate a random RGB color string
function getRandomRGB() {
  const r = Math.floor(Math.random() * 256);
  const g = Math.floor(Math.random() * 256);
  const b = Math.floor(Math.random() * 256);
  return `rgb(${r}, ${g}, ${b})`;
}

// Function to generate a random length array with random values
function generateRandomArray() {
    // Generate a random length between 1 and 20
    const length = Math.round(Math.random() * 39) + 1;
    const array = [];

    for (let i = 0; i < length; i++) {
        // Generate a random value between 0 and 20
        const randomValue = Math.round(Math.random() * 39) + 1;
        array.push(randomValue);
    }

    return array;
}

// Function to handle canvas click/touch events
function handleCanvasInteraction(event, canvas, rects, animationFlags) {
  event.preventDefault();

  let clientX, clientY;

  // Detect if it's a touch event or a mouse event
  if (event.type === "touchstart") {
    clientX = event.touches[0].clientX;
    clientY = event.touches[0].clientY;
    console.log("Touch event detected at:", clientX, clientY);
  } else {
    clientX = event.clientX;
    clientY = event.clientY;
    console.log("Mouse event detected at:", clientX, clientY);
  }

  // Get mouse or touch position relative to the canvas
  let rect = canvas.getBoundingClientRect();
  let point = new c2.Point(clientX - rect.left, clientY - rect.top);

  // Loop through all rectangles to determine which one was clicked/touched
  for (let i = 0; i < rects.length; i++) {
    if (rects[i].contains(point)) {
      // Toggle the animation flag for the clicked/touched rectangle
      animationFlags[i] = !animationFlags[i];
      // Stop all other rectangles from animating
      for (let j = 0; j < animationFlags.length; j++) {
        if (j !== i) {
          animationFlags[j] = false;
        }
      }
      console.log(`Rectangle ${i} touched. Animation flag set to:`, animationFlags[i]);
      break;
    }
  }
}

// Function to draw the shapes on the canvas
function drawShape(timestamp, renderer, rects, animationFlags, rectColors, lastColorChange, colorChangeInterval) {
  renderer.clear();

  for (let i = 0; i < rects.length; i++) {
    let rect = rects[i];
    if (animationFlags[i]) {
      if (timestamp - lastColorChange[i] > colorChangeInterval[i]) {
        rectColors[i] = getRandomRGB();
        lastColorChange[i] = timestamp;
      }
      renderer.fill(rectColors[i]);
    } else {
      renderer.fill(rectColors[i]);
    }
    renderer.rect(rect);
  }
}

// Function to animate the shapes continuously using requestAnimationFrame
function animateShape(renderer, rects, animationFlags, rectColors, lastColorChange, colorChangeInterval) {
  function animate(timestamp) {
    drawShape(timestamp, renderer, rects, animationFlags, rectColors, lastColorChange, colorChangeInterval);
    requestAnimationFrame(animate);
  }
  requestAnimationFrame(animate);
}

// Function to resize the canvas and update the renderer
function resizeCanvas(canvas, renderer) {
  const windowWidth = window.innerWidth;
  let newSize;

  if (windowWidth <= 768) {
    newSize = windowWidth * 0.75;
  } else if (windowWidth <= 1200) {
    newSize = windowWidth / 4;
  } else {
    newSize = windowWidth / 8;
  }

  canvas.width = newSize;
  canvas.height = newSize;

  renderer.size(canvas.width, canvas.height);
}

// Setup function for each canvas
function setupCanvas(canvasId) {
  let canvas = document.getElementById(canvasId);
  if (!canvas) {
    console.error(`Canvas with ID ${canvasId} not found.`);
    return;
  }

  let renderer = new c2.Renderer(canvas);

  let rects, rectColors, animationFlags, lastColorChange, colorChangeInterval;

  function initializeCanvas() {
    resizeCanvas(canvas, renderer);
    renderer.background(getRandomRGB());

    let rect = new c2.Rect(0, 0, canvas.width, canvas.height);
    rects = rect.split(generateRandomArray(), 'squarify');

    rectColors = rects.map(() => getRandomRGB());
    animationFlags = new Array(rects.length).fill(false);
    lastColorChange = new Array(rects.length).fill(0);
    colorChangeInterval = rects.map(() => (Math.random() * 900) + 100);
  }

  // Initial call to set up the canvas
  initializeCanvas();

  // Add click and touch event listeners
  canvas.addEventListener('click', (event) => {
    handleCanvasInteraction(event, canvas, rects, animationFlags);
  });

  canvas.addEventListener('touchstart', (event) => {
    handleCanvasInteraction(event, canvas, rects, animationFlags);
  });

  // Resize event listener to make the canvas responsive
  window.addEventListener('resize', () => {
    const currentAnimationFlags = [...animationFlags];
    initializeCanvas();
    animationFlags = currentAnimationFlags;
  });

  // Scroll event listener to re-attach focus to canvas to maintain interactivity
  window.addEventListener('scroll', () => {
    canvas.focus();
  });

  // Start the animation loop for the canvas
  animateShape(renderer, rects, animationFlags, rectColors, lastColorChange, colorChangeInterval);
}

// Setup each canvas using the `setupCanvas` function
document.addEventListener('DOMContentLoaded', () => {
  setupCanvas('1');
  setupCanvas('2');
  setupCanvas('3');
  setupCanvas('4');
});
