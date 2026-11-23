<?php
require_once 'config.php';
require_once __DIR__ . '/../php/session_boot.php';
start_app_session();
require_once __DIR__ . '/../php/i18n.php'; // t(), current_lang(), lang_switcher()
require_once __DIR__ . '/../php/functions.php'; // bumperchat_* helpers

// Started from Bumperchat (bumperchat/google.php)? Then every exit below stays inside /bumperchat/.
$oauth_return = is_array($_SESSION['oauth_return'] ?? null) ? $_SESSION['oauth_return'] : [];
$from_bumperchat = !empty($oauth_return['bumperchat']) && (time() - (int) ($oauth_return['at'] ?? 0)) < 3600;
unset($_SESSION['oauth_return']);

/** Leave with a message: back to the Bumperchat login (or the normal login / homepage). */
function welcome_exit(bool $bumperchat, string $lang_key, string $normal_target): void
{
    if ($bumperchat) {
        header('Location: ../bumperchat/login.php?err=' . rawurlencode($lang_key));
    } else {
        header('Location: ' . $normal_target . '?message=' . rawurlencode(t_plain($lang_key)));
    }
    exit;
}

function check_spam_emails(string $email): bool
{
    $domain = strtolower(substr(strrchr($email, '@') ?: '', 1));
    return in_array($domain, ['automisly.org'], true);
}

if (!isset($_GET['code'])) {
    // e.g. "Cancel" on the Google consent screen
    header('Location: ' . ($from_bumperchat ? '../bumperchat/login.php' : 'options.php'));
    exit;
}

$state = $_GET['state'] ?? '';
if ($state === '' || !hash_equals($_SESSION['oauth_state'] ?? '', $state)) {
    welcome_exit($from_bumperchat, 'welcome.expired', 'options.php');
}
unset($_SESSION['oauth_state']);

$tokenResponse = $client->fetchAccessTokenWithAuthCode($_GET['code']);
if (!empty($tokenResponse['error']) || empty($tokenResponse['access_token'])) {
    welcome_exit($from_bumperchat, 'welcome.failed', 'options.php');
}

$client->setAccessToken($tokenResponse['access_token']);
$google_oauth = new Google_Service_Oauth2($client);
$info = $google_oauth->userinfo->get();

$emailRaw = strtolower(trim((string)$info['email']));
if ($emailRaw === '' || check_spam_emails($emailRaw)) {
    welcome_exit($from_bumperchat, 'welcome.spam', '../index.php');
}

$emailHash      = md5($emailRaw); // same as your current column
$firstName      = (string)($info['givenName'] ?? '');
$lastName       = (string)($info['familyName'] ?? '');
$gender         = (string)($info['gender'] ?? '');
$fullName       = (string)($info['name'] ?? '');
$picture        = (string)($info['picture'] ?? '');
$verifiedEmail  = !empty($info['verifiedEmail']) ? 1 : 0;

$stmt = $conn->prepare('SELECT id FROM google_users WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $emailHash);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existing) {
    $userId = (int)$existing['id'];

    $upd = $conn->prepare(
        'UPDATE google_users
         SET first_name = ?, last_name = ?, full_name = ?, picture = ?, verifiedEmail = ?
         WHERE id = ?'
    );
    $upd->bind_param('ssssii', $firstName, $lastName, $fullName, $picture, $verifiedEmail, $userId);
    $upd->execute();
    $upd->close();
} else {
    $placeholderToken = bin2hex(random_bytes(32));
    $ins = $conn->prepare(
        'INSERT INTO google_users
            (email, first_name, last_name, gender, full_name, picture, verifiedEmail, token)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $ins->bind_param(
        'ssssssis',
        $emailHash,
        $firstName,
        $lastName,
        $gender,
        $fullName,
        $picture,
        $verifiedEmail,
        $placeholderToken
    );
    $ins->execute();
    $userId = (int)$ins->insert_id;
    $ins->close();
}

establish_user_session($conn, $userId, $emailRaw);

if ($from_bumperchat) {
    // Same as the Bumperchat email login: mark the session, then let bumperchat/login.php route
    // (claim chain, pending message, or chat). The user is logged in, so it redirects at once.
    bumperchat_mark_session(bumperchat_normalize_words((string) (bumperchat_claim_state()['words'] ?? '')));
    $bc_next = bumperchat_safe_next((string) ($oauth_return['next'] ?? ''), '');
    header('Location: ../bumperchat/login.php' . ($bc_next !== '' ? '?next=' . rawurlencode($bc_next) : ''));
    exit;
}

header('Location: ../3wid_list.php'); // 3wid_list.php sends a plate-only account on to the chat view
exit;
