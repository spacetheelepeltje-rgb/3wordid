<?php

  include 'php/functions.php';

  $threewords = $_POST["paypal_data"]["threewords"];
  
  $hash = gethash($threewords);

  $_POST["timestamp"]=time();
  $_POST["hash"]=$hash[0];
  
  $serialized = serialize($_POST);
  
  $filename = './subscriptions/' . $hash[0];
  
  echo $filename;

  file_put_contents($filename,$serialized);
  

  
