<?php
require_once 'php/functions.php';
require_once 'login/config.php';

$data = current_user_from_session($conn);
if ($data == NULL) {
    header('Location: login/options.php?message=' . rawurlencode('Please log in.'));
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id < 1) {
    header('Location: 3wid_list.php');
    exit;
}

// CSRF protection: the link on 3wid_list.php carries the session action token
$token = isset($_GET['action_token']) ? $_GET['action_token'] : '';
if (!check_action_token($token)) {
    error_log('3wid_toggle.php: invalid action token for 3wid ' . $id . ' user ' . $data['id']);
    header('Location: 3wid_list.php?message=' . rawurlencode('Session expired, please try again.'));
    exit;
}

// Only the owner of the 3WordID may change it
if (!db_3wordid_owned_by($id, $data['id'])) {
    error_log('3wid_toggle.php: user ' . $data['id'] . ' does not own 3wid ' . $id);
    header('Location: 3wid_list.php');
    exit;
}

db_3wordid_toggle($id, $data['id']);
header('Location: 3wid_list.php');
exit;
