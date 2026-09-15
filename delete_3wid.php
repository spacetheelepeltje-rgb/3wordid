<?php

 include 'php/functions.php';
 
 sleep(1);
 
 $threeword = $_POST['threeword'];
 $email = $_POST['email'];
 $password = $_POST['password'];
 
 echo delete_threeword($threeword,$password,$email);
