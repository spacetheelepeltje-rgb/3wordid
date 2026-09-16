<?php

require_once 'config.php';

if (isset($_SESSION['user_token'])) {
	
  header("Location: welcome.php");

} else {
	
  echo "user_token not set";	
	
  header("Location:" . $client->createAuthUrl());
}
