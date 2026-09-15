<?php

include 'php/functions.php';

if(isset($_POST["threewords"]) && $_POST["threewords"] != "" && isset($_POST["password"]) && $_POST["password"] != "") {
	
	$threewords = $_POST["threewords"];
	$password = $_POST["password"];
	
	if(validate_password($threewords,$password)) {
		
		$hash = gethash($threewords);
		
		$content = getmessagehash($hash[0]);
			
		if(count($content['messages']) > 0) {
			
			echo json_encode(array('status'=>'messages','content'=>$content['messages']));
			
			exit();

		} else {
		// no messages		
				
			echo json_encode(array('status'=>'no messages'));
			
			exit();	
				
	    }
	
		
	}
	

}

echo json_encode(array('status'=>'no messages'));

exit();	
