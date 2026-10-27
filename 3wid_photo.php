<?php
// Serves the profile photo of one 3WordID to logged-in members, only when the owner
// turned "Show my photo in chats" on. The Google picture URL never reaches the
// browser: it is fetched here (https *.googleusercontent.com only) and streamed.
// Usage (built by chat_photo_url()): 3wid_photo.php?wid=<3WordID id>&s=<session signature>

require_once 'php/functions.php';
require_once 'login/config.php';
require_once 'php/chat.php';

function photo_fail($code) {
	http_response_code($code);
	header('Content-Type: text/plain; charset=UTF-8');
	header('Cache-Control: no-store');
	exit;
}

header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex, nofollow');

// Logged-in members only; anonymous visitors never get the photo
$data = current_user_from_session($conn);
if (!$data || (int) ($data['id'] ?? 0) < 1) {
	photo_fail(403);
}

$wid_id = isset($_GET['wid']) ? (int) $_GET['wid'] : 0;
$sig = isset($_GET['s']) ? (string) $_GET['s'] : '';
// The URL must have been made for this session by one of our pages
if ($wid_id < 1 || !hash_equals(chat_photo_sig($wid_id), $sig)) {
	photo_fail(403);
}

// Photo turned on for this 3WordID, 3WordID enabled, owner has a picture
$src = db_3wordid_photo_source($wid_id);
if ($src === '') {
	photo_fail(404);
}

// No open proxy: https and Google's image host only
$parts = parse_url($src);
$host = strtolower((string) ($parts['host'] ?? ''));
if (($parts['scheme'] ?? '') !== 'https' || !preg_match('/(^|\.)googleusercontent\.com$/', $host) || isset($parts['user']) || isset($parts['port'])) {
	error_log('3wid_photo: refused picture host for 3wid ' . $wid_id);
	photo_fail(404);
}

$max = 2 * 1024 * 1024; // 2 MB
$body = '';
$type = '';

if (function_exists('curl_init')) {
	$ch = curl_init($src);
	curl_setopt_array($ch, array(
		CURLOPT_RETURNTRANSFER => false,
		CURLOPT_FOLLOWLOCATION => false,
		CURLOPT_PROTOCOLS      => CURLPROTO_HTTPS,
		CURLOPT_CONNECTTIMEOUT => 4,
		CURLOPT_TIMEOUT        => 6,
		CURLOPT_SSL_VERIFYPEER => true,
		CURLOPT_USERAGENT      => '3WordID photo',
		CURLOPT_WRITEFUNCTION  => function ($ch, $chunk) use (&$body, $max) {
			$body .= $chunk;
			return strlen($body) > $max ? 0 : strlen($chunk); // 0 aborts the transfer
		},
	));
	$ok = curl_exec($ch);
	$status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
	$type = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
	curl_close($ch);
	if (!$ok || $status !== 200) {
		photo_fail(404);
	}
} else {
	$ctx = stream_context_create(array(
		'http' => array('timeout' => 6, 'follow_location' => 0, 'ignore_errors' => false, 'user_agent' => '3WordID photo'),
		'ssl'  => array('verify_peer' => true, 'verify_peer_name' => true),
	));
	$fh = @fopen($src, 'rb', false, $ctx);
	if (!$fh) {
		photo_fail(404);
	}
	while (!feof($fh) && strlen($body) <= $max) {
		$chunk = fread($fh, 65536);
		if ($chunk === false) { break; }
		$body .= $chunk;
	}
	$meta = stream_get_meta_data($fh);
	fclose($fh);
	foreach ((array) ($meta['wrapper_data'] ?? array()) as $h) {
		if (stripos($h, 'Content-Type:') === 0) {
			$type = trim(substr($h, 13));
		}
	}
}

if ($body === '' || strlen($body) > $max) {
	photo_fail(404);
}

// Must really be a JPEG, PNG or WebP (header and content)
$allowed = array('image/jpeg', 'image/png', 'image/webp');
$type = strtolower(trim(explode(';', $type)[0]));
$info = @getimagesizefromstring($body);
$real = $info ? (string) $info['mime'] : '';
if (!in_array($type, $allowed, true) || !in_array($real, $allowed, true)) {
	photo_fail(404);
}

header('Content-Type: ' . $real);
header('Content-Length: ' . strlen($body));
header('Cache-Control: private, max-age=3600');
header('Content-Disposition: inline; filename="photo"');
echo $body;
