<?php

require_once 'login/config.php'
 
 $threewords = $_POST["threewords"];
 
 $filename = 'threewords/' . $threewords;
 
 if(file_exists($filename)) {
	 
		echo "exists";
	
	} else {
	
		echo "new";
	}
