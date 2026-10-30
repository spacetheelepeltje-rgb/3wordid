<?php
// Log out of a chat login (member) session. POST with the session's action token.
// Afterwards the member goes to the homepage (index.php), not back to a login screen.

require_once 'php/functions.php';
require_once 'login/config.php';

header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !check_action_token($_POST['action_token'] ?? '')) {
    header('Location: ' . (chat_member_in_session() ? '3wid_chat.php' : 'index.php'));
    exit;
}

if (chat_member_in_session()) {
    chat_member_end();
}
header('Location: index.php');
exit;
