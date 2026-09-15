<?php

include 'php/functions.php';

echo "test";

 if(isset($_GET['string'])and ! $_GET['string'] != "") {
	  
	  $string = $_GET['string'];
	  
	  $hashdata = gethash($threewords);
	  
	  echo $hashdata[0];
	  
	  
  } else {
	  
  echo "You reached the 3wordid.com API. Call as https://3wordid.com/api.php?string=[your three word id string]";
	  
  }	  
