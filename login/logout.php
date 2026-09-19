<?php
require_once 'config.php';
require_once __DIR__ . '/../php/session_boot.php';

destroy_app_session($conn);
header('Location: ../index.php');
exit;
