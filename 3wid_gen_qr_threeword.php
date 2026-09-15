<?php
// Include the library
require_once 'vendor/autoload.php';
require_once 'login/config.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

if($_SESSION['csrf_token'] != $_GET["csrf_token"]) {
	  header('location:' . $main_url);
}

$string = $_GET["threeword"];
$target_url="https://www.3wordid.com/index.php?threewords=";

$qr_string = $target_url . urlencode($string);
  
error_log($qr_string);	 

// Set options (optional)
$options = new QROptions([
    'version'    => 10,           // QR code version (size)
    'outputType' => QRCode::OUTPUT_IMAGE_PNG, // PNG output
    'eccLevel'   => QRCode::ECC_L, // Error correction level
    'scale'      => 10,          // Size of each module
    'imageBase64' => false,      // Output raw image, not base64
]);

// Data to encode
$data = $qr_string;

error_log($data);

// Generate and output QR code
$qrcode = new QRCode($options);
$qrcode->render($data, 'qr_codes/qrcode_' . $string . '.png'); // Save to file
// Or output directly:
echo "<img src='qr_codes/qrcode_" . $string . ".png" . "'>";
?>
