<?php

  require_once 'config.php';
  require_once '../php/functions.php';
  
  
  error_log('welcome forward ' . json_encode($_SESSION) . ' session id ' . session_id());


