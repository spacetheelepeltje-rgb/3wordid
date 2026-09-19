<?php
require_once 'config.php';
require_once __DIR__ . '/../php/session_boot.php';
start_app_session();

function check_spam_emails(string $email): bool
{
    $domain = strtolower(substr(strrchr($email, '@') ?: '', 1));
    return in_array($domain, ['automisly.org'], true);
}

if (!isset($_GET['code'])) {
    header('Location: options.php');
    exit;
}

$state = $_GET['state'] ?? '';
if ($state === '' || !hash_equals($_SESSION['oauth_state'] ?? '', $state)) {
    header('Location: options.php?message=' . rawurlencode('Login expired. Try Google again.'));
    exit;
}
unset($_SESSION['oauth_state']);

$tokenResponse = $client->fetchAccessTokenWithAuthCode($_GET['code']);
if (!empty($tokenResponse['error']) || empty($tokenResponse['access_token'])) {
    header('Location: options.php?message=' . rawurlencode('Google login failed.'));
    exit;
}

$client->setAccessToken($tokenResponse['access_token']);
$google_oauth = new Google_Service_Oauth2($client);
$info = $google_oauth->userinfo->get();

$emailRaw = strtolower(trim((string)$info['email']));
if ($emailRaw === '' || check_spam_emails($emailRaw)) {
    header('Location: ../index.php?message=' . rawurlencode('Sowwy your email is on the spam abuse list!'));
    exit;
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

establish_user_session($conn, $userId);
header('Location: ../3wid_list.php');
exit;
