<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>MPU6050 Orientation Visualization</title>
  <style>
    body { margin: 0; font-family: Arial; }
    #3Dcontainer { width: 800px; height: 600px; margin: 20px auto; border: 1px solid #ccc; }
    .info { text-align: center; }
  </style>
  <!-- Load latest Three.js from jsDelivr CDN -->
  <script src="https://cdn.jsdelivr.net/npm/three@0.132.2/build/three.min.js"></script>
  <!-- Load OrbitControls for camera interaction -->
  <script src="https://cdn.jsdelivr.net/npm/three@0.132.2/examples/js/controls/OrbitControls.js"></script>
</head>
<body>
  <div id="3Dcontainer"></div>
  <div class="info">
    <p>Roll: <span id="roll">0</span>° | Pitch: <span id="pitch">0</span>° | Yaw: <span id="yaw">0</span>°</p>
  </div>
  <script src="script.js"></script>
</body>
</html>
