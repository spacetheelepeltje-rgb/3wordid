<?php

exit(1);

include 'php/functions.php';

$threewords = $_POST["threewords"];

$hash = gethash($threewords);

$files = glob('3ids/' . $hash[0]);

if (count($files) > 0) {
    //echo "File found: " . $files[0];
    echo file_get_contents($files[0]);
} else {
    echo json_encode(array("status"=>Null));
}



