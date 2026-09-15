<?php
// Get all GET parameters as key-value pairs
$data = $_GET;

// Create timestamp
$timestamp = date('Y-m-d H:i:s');

// Prepare the line to write
$line = $timestamp . ' ' . http_build_query($data) . "\n";

// Open sensor.html in read mode to get existing content
$handle = fopen('sensor.html', 'r');
$content = '';
if ($handle) {
    while (($buffer = fgets($handle, 4096)) !== false) {
        $content .= $buffer;
    }
    fclose($handle);
} else {
    $content = '';
}

// Prepend the new line to the content
$new_content = $line . $content;

// Write the new content back to the file
file_put_contents('sensor.html', $new_content);
?>
