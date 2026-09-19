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

db_3wordid_delete($id);
header('Location: 3wid_list.php');
exit;
