<?php
/** DEMO: forget the demo state (plan, orders, mandate) and start again as a free account. */
require __DIR__ . '/_demo.php';

$_SESSION['paydemo'] = ['v' => 2, 'orders' => [], 'sub' => null];
header('Location: index.php');
exit;
