<?php
// Log out of a chat login (member) session. POST with the session's action token.

require_once 'php/functions.php';
require_once 'login/config.php';

header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !check_action_token($_POST['action_token'] ?? '')) {
    header('Location: ' . (chat_member_in_session() ? '3wid_chat.php' : '3wid_chat_login.php'));
    exit;
}

if (chat_member_in_session()) {
    chat_member_end();
}
header('Location: 3wid_chat_login.php?out=1');
exit;
