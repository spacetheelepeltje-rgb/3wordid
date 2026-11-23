<?php
// Bumperchat: "Continue with Google". Starts the SAME Google login as 3wordid.com (login/config.php
// client, login/welcome.php callback, google_users table). The only difference: a return context
// is stored in the session first, so login/welcome.php sends the visitor back into /bumperchat/
// (login.php -> claim / pending message / chat) and never to the main site list or homepage.

require_once __DIR__ . '/../php/functions.php';
require_once __DIR__ . '/../login/config.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Robots-Tag: noindex, nofollow');

$next = bumperchat_safe_next((string) ($_GET['next'] ?? ''), '');

$user = current_user_from_session($conn);
if ($user) {
    bumperchat_after_login_redirect($user, $next !== '' ? $next : 'chat.php');
}

bumperchat_ctx_remember();
$_SESSION['oauth_return'] = ['bumperchat' => 1, 'next' => $next, 'at' => time()];
$_SESSION['oauth_state'] = bin2hex(random_bytes(16));
$client->setState($_SESSION['oauth_state']);
header('Location: ' . $client->createAuthUrl());
exit;
