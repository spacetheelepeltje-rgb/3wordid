<?php
/** DEMO: forget the demo state (plan, orders) and start again as a free account. */
require __DIR__ . '/_demo.php';

$_SESSION['paydemo'] = ['orders' => [], 'sub' => null];
header('Location: index.php');
exit;
