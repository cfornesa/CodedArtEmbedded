// Initialize variables
const height = 0.72;

// The three.js scene: the 3D world where you put objects
const scene = new THREE.Scene();

// The camera
const camera = new THREE.PerspectiveCamera(
  60,
  window.innerWidth / window.innerHeight*(1 + (1 - height)),
  1,
  10000
);

// The renderer: something that draws 3D objects onto the canvas
const renderer = new THREE.WebGLRenderer({ antialias: true });
renderer.setSize(window.innerWidth, window.innerHeight*(height));
// renderer.setClearColor(0xaaaaaa, 1);
renderer.setClearColor(`rgb(${Math.round(Math.random()*255)}, ${Math.round(Math.random()*255)}, ${Math.round(Math.random()*255)})`, 1);

// Append the renderer canvas into <body>
document.body.appendChild(renderer.domElement);

// Initialize an empty array to store all the cubes
const allCubes = [];

// Function to generate a random position within a certain range
function getRandomPosition(range) {
  // Generate a range between -2 and 2
  return (Math.random() - 0.5) * 0.4 * range;
}

// Create a function to create and render a cube using an image link
function createAndRenderCube(imageUrl) {
  // Create a texture loader
  const textureLoader = new THREE.TextureLoader();

  // Load the texture
  textureLoader.load(imageUrl, function(texture) {

    // Initialize a cube with the texture
    const geometry = new THREE.BoxGeometry(1, 1, 1);
    const material = new THREE.MeshBasicMaterial({
      map: texture, // Use the texture as a map
      transparent: true, // Ensure transparency if needed
      opacity: (Math.random() * 0.65) + 0.35 // Set a random opacity
    });

    // Create the mesh with the geometry and the textured material
    const mesh = new THREE.Mesh(geometry, material);

    // Set random position for the cube
    mesh.position.set(
      getRandomPosition(10), // Random x position within a range
      getRandomPosition(10), // Random y position within a range
      getRandomPosition(10)  // Random z position within a range
    );

    // Add the cube into the scene
    scene.add(mesh);

    // Store the cube mesh for later use (e.g., rotation or manipulation)
    allCubes.push(mesh);
  });
}

// Function to render the scene
function render() {
  // Render the scene and the camera
  renderer.render(scene, camera);

  // Rotate each cube in the allCubes array every frame
  allCubes.forEach(function(cube) {
    cube.rotation.x += 0.01;
    cube.rotation.y -= 0.01;
  });

  // Make it call the render() function about every 1/60 second
  requestAnimationFrame(render);
}

// Example: Assume you have an array of image links
const cubes = [
  '../img/p5/1.png',
  '../img/p5/2.png',
  '../img/p5/3.png',
  '../img/p5/4.png',
  // Add more image links as needed
];

// Loop through each link and create a cube
cubes.forEach(createAndRenderCube);

// Set the camera position
camera.position.z = 5;

// Start the render loop
render();