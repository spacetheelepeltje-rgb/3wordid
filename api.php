<?php

  include 'php/functions.php';
 
  if(isset($_GET['string']) AND $_GET['string'] != "") {
	  
	  $string = $_GET['string'];
	  
	  $hashdata = gethash($string);
	  
	  if(isset($_GET['mode'])) {
		  
		  $mode = $_GET['mode'];
	  
		  if($mode == "JSON") {
			  
			
			  
			  echo json_encode($hashdata);  
			   
			  } else {
				  
		  
	
		      echo $hashdata[0];
		      	  
			}
		} else {
	  
	    echo "You reached the 3wordid.com API. Call as https://3wordid.com/api.php?string=[your three word id string]&mode=[STRING or JSON]";
	    
	}
	  
	  
  } else {
	  
   echo "You reached the 3wordid.com API. Call as https://3wordid.com/api.php?string=[your three word id string]&mode=[STRING or JSON]";
	  
  }
