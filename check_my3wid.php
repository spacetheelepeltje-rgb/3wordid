<?php

session_start();

include 'php/functions.php';

echo check3wid();

exit();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['csrf_token']) && isset($_SESSION['csrf_token'])) {

		$threewords = $_POST["threewords"];

		if($threewords=="") echo '{  "status": "nok","message": "No input"}';

		if(strlen($threewords) > 80) echo '{  "status": "nok","message": "Input too long"}';

		$threewords = preg_replace('/\s+/x', ' ', $threewords); // remove double spaces

		$exploded = explode(' ',$threewords);

		if(count($exploded) != 3) echo '{  "status": "nok","message": "Too many words"}';

		$result = gethash($threewords);

		$exists = checkexists($result[0]);

		echo '{  "status": "ok","clean_3wid":"' . $result[1] . '","message": "3wordid : ' . $result[1] .  '","hash":"' . $result[0] . '","exists":"' . $exists . '"}';
	}
} else {
	 echo '{  "status": "nok","message": "No input"}';
	
}
