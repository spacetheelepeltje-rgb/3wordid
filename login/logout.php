<?php
require_once 'config.php';
require_once __DIR__ . '/../php/session_boot.php';

require_once __DIR__ . '/../php/i18n.php';
start_app_session();
$lang = current_lang(); // the session is destroyed below, so pass the language on in the URL

destroy_app_session($conn);
header('Location: ../index.php?lang=' . rawurlencode($lang));
exit;
