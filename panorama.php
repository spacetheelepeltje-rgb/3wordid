<?php
// panorama.php
$image = $_GET['img'] ?? 'your_equirect_image.jpg'; // e.g. test_equirect.jpg

// Optional: basic security - only allow images from the same folder
$allowedDir = __DIR__;
$imagePath = realpath($allowedDir . '/' . basename($image));
if (!$imagePath || !str_starts_with($imagePath, $allowedDir) || !file_exists($imagePath)) {
    die('Invalid or missing image.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>360° Panorama Viewer</title>
    <style>
        body { margin: 0; overflow: hidden; background: #000; font-family: Arial, sans-serif; }
        #container { width: 100vw; height: 100vh; }
        #info {
            position: absolute; top: 10px; left: 10px; color: white; background: rgba(0,0,0,0.5);
            padding: 10px; border-radius: 5px; pointer-events: none;
        }
    </style>
</head>
<body>
    <div id="container"></div>
    <div id="info">
        Drag with mouse to look around • Scroll to zoom • Double-click to reset
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r134/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.134.0/examples/js/controls/OrbitControls.js"></script>

    <script>
        let scene, camera, renderer, controls;
        let panoramaMesh;

        function init() {
            // Scene setup
            scene = new THREE.Scene();

            camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
            camera.position.set(0, 0, 0.1); // Very close to center

            renderer = new THREE.WebGLRenderer({ antialias: true });
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.setPixelRatio(window.devicePixelRatio);
            document.getElementById('container').appendChild(renderer.domElement);

            // Controls (mouse drag, zoom, etc.)
            controls = new THREE.OrbitControls(camera, renderer.domElement);
            controls.enableDamping = true;
            controls.dampingFactor = 0.05;
            controls.enableZoom = true;
            controls.enablePan = false;
            controls.rotateSpeed = 0.8;
            controls.minDistance = 0.1;
            controls.maxDistance = 0.5;   // Prevent going too far inside the sphere

            // Create sphere geometry (inside-out)
            const geometry = new THREE.SphereGeometry(500, 64, 32);
            geometry.scale(-1, 1, 1); // Flip normals so we see the inside

            // Load the equirectangular texture
            const textureLoader = new THREE.TextureLoader();
            const texture = textureLoader.load('<?= htmlspecialchars($image) ?>', () => {
                console.log('Panorama loaded successfully');
            });

            const material = new THREE.MeshBasicMaterial({
                map: texture,
                side: THREE.DoubleSide
            });

            panoramaMesh = new THREE.Mesh(geometry, material);
            scene.add(panoramaMesh);

            // Handle window resize
            window.addEventListener('resize', onWindowResize);

            animate();
        }

        function onWindowResize() {
            camera.aspect = window.innerWidth / window.innerHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(window.innerWidth, window.innerHeight);
        }

        function animate() {
            requestAnimationFrame(animate);
            controls.update(); // Required for damping
            renderer.render(scene, camera);
        }

        // Auto-start
        init();
    </script>
</body>
</html>
