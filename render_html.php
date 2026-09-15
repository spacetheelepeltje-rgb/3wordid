<?php
require_once 'vendor/autoload.php';

// Configure HTMLPurifier
$config = HTMLPurifier_Config::createDefault();
$config->set('HTML.Allowed', 'p,br,a[href],strong,em,ul,ol,li,div,span,h1,h2,h3,h4,h5,h6');
$config->set('HTML.SafeIframe', true);
$purifier = new HTMLPurifier($config);

// Get the submitted HTML
$html_content = isset($_POST['html_content']) ? $_POST['html_content'] : '';

// Sanitize the HTML
$clean_html = $purifier->purify($html_content);

// Wrap the sanitized HTML in a basic HTML structure
$full_html = <<<EOD
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Preview</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    $clean_html
</body>
</html>
EOD;

// Output the sanitized HTML
header('Content-Type: text/html');
echo $full_html;
?>
