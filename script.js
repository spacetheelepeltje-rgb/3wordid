// Three.js variables
let scene, camera, renderer, mesh, controls;

// Orientation variables (in radians, converted to degrees for display)
let roll = 0, pitch = 0, yaw = 0;

// Complementary filter constant (0-1; higher = more trust in gyro)
const alpha = 0.98;

// Time tracking for integration (in seconds)
let lastTimestamp = Date.now() / 1000;

// Initialize Three.js scene
function init3D() {
  const container = document.getElementById('3Dcontainer');
  scene = new THREE.Scene();
  scene.background = new THREE.Color(0x87CEEB); // Sky blue for visibility check

  camera = new THREE.PerspectiveCamera(75, container.clientWidth / container.clientHeight, 0.1, 1000);
  camera.position.set(0, 0, 5);
  camera.lookAt(0, 0, 0); // Explicitly look at origin

  renderer = new THREE.WebGLRenderer({ antialias: true });
  renderer.setSize(container.clientWidth, container.clientHeight);
  container.appendChild(renderer.domElement);

  // Check WebGL support
  if (!renderer.capabilities.isWebGL2 && !renderer.extensions.has('WEBGL')) {
    console.error('WebGL not supported in this browser!');
    return;
  }

  // Add ambient light (even though MeshBasicMaterial doesn't require it, for completeness)
  const ambientLight = new THREE.AmbientLight(0xffffff, 1);
  scene.add(ambientLight);

  // Create a simple box mesh representing the MPU6050
  const geometry = new THREE.BoxGeometry(3, 1, 2); // Larger for visibility
  const materials = [
    new THREE.MeshBasicMaterial({ color: 0xFF0000 }), // Front
    new THREE.MeshBasicMaterial({ color: 0x00FF00 }), // Back
    new THREE.MeshBasicMaterial({ color: 0x0000FF }), // Top
    new THREE.MeshBasicMaterial({ color: 0xFFFF00 }), // Bottom
    new THREE.MeshBasicMaterial({ color: 0xFF00FF }), // Right
    new THREE.MeshBasicMaterial({ color: 0x00FFFF })  // Left
  ];
  mesh = new THREE.Mesh(geometry, materials);
  scene.add(mesh);

  // Add debugging helpers
  const axesHelper = new THREE.AxesHelper(5); // Red=X, Green=Y, Blue=Z
  scene.add(axesHelper);
  const gridHelper = new THREE.GridHelper(10, 10); // Floor grid
  scene.add(gridHelper);

  // Add orbit controls for manual camera movement (drag to rotate)
  controls = new THREE.OrbitControls(camera, renderer.domElement);
  controls.enableDamping = true; // Smooth movement
  controls.dampingFactor = 0.25;

  // Handle window resize
  window.addEventListener('resize', () => {
    camera.aspect = container.clientWidth / container.clientHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(container.clientWidth, container.clientHeight);
  });

  // Test static rotation on init
  mesh.rotation.x = Math.PI / 4; // 45 degrees pitch
  mesh.rotation.y = Math.PI / 6; // 30 degrees yaw
  mesh.rotation.z = Math.PI / 3; // 60 degrees roll

  console.log('Three.js initialized');
}

// Update mesh rotation based on Euler angles (in radians)
function updateVisualization() {
  mesh.rotation.order = 'XYZ';
  mesh.rotation.x = pitch;  // Pitch around X
  mesh.rotation.y = yaw;    // Yaw around Y
  mesh.rotation.z = roll;   // Roll around Z
  console.log('Updated visualization with roll:', roll, 'pitch:', pitch, 'yaw:', yaw);
}

// Animation loop for continuous rendering
function animate() {
  requestAnimationFrame(animate);
  controls.update(); // Update controls
  updateVisualization(); // Update mesh if needed (but since data is interval-based, optional here)
  renderer.render(scene, camera);
  console.log('Rendered scene');
}

// Complementary filter to compute orientation from raw data
function computeOrientation(accX, accY, accZ, gyroX, gyroY, gyroZ) {
  const now = Date.now() / 1000;
  const dt = now - lastTimestamp;
  lastTimestamp = now;

  // Normalize accelerometer vector (for gravity direction)
  const accMagnitude = Math.sqrt(accX * accX + accY * accY + accZ * accZ);
  if (accMagnitude === 0) return; // Avoid division by zero

  // Compute roll and pitch from accelerometer (in radians)
  const accRoll = Math.atan2(accY, accZ);
  const accPitch = Math.atan2(-accX, Math.sqrt(accY * accY + accZ * accZ));

  // Integrate gyroscope for all angles
  roll += gyroX * dt;
  pitch += gyroY * dt;
  yaw += gyroZ * dt;

  // Fuse with accelerometer using complementary filter (corrects gyro drift for roll/pitch; yaw drifts)
  roll = alpha * roll + (1 - alpha) * accRoll;
  pitch = alpha * pitch + (1 - alpha) * accPitch;

  // Update display (convert to degrees)
  document.getElementById('roll').textContent = (roll * 180 / Math.PI).toFixed(2);
  document.getElementById('pitch').textContent = (pitch * 180 / Math.PI).toFixed(2);
  document.getElementById('yaw').textContent = (yaw * 180 / Math.PI).toFixed(2);
}

// Simulate random data internally every 100ms
function simulateData() {
  setInterval(() => {
    // Generate random sample data
    const data = {
      accX: (Math.random() - 0.5) * 2,  // Example: -1 to 1 g
      accY: (Math.random() - 0.5) * 2,
      accZ: Math.random() + 0.5,        // Biased towards +1 for gravity
      gyroX: (Math.random() - 0.5) * 0.1,  // Small angular velocities in rad/s
      gyroY: (Math.random() - 0.5) * 0.1,
      gyroZ: (Math.random() - 0.5) * 0.1
    };

    // Process the simulated data
    computeOrientation(data.accX, data.accY, data.accZ, data.gyroX, data.gyroY, data.gyroZ);
    console.log('Simulated data:', data);
  }, 100);
}

// Initialize, start animation loop, and simulate data
init3D();
animate(); // Start continuous rendering
simulateData();
