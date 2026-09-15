<?php

require_once 'login/config.php';
 
$threewords = $_POST["threewords"];

// Escape the variable to prevent SQL injection
$threewords = $conn->real_escape_string($threewords);

//error_log('checking ' . $threewords);

$query = "SELECT * FROM 3wordid WHERE threeword = ?";

$stmt = $conn->prepare($query);

// Bind the parameter to prevent SQL injection
$stmt->bind_param("s", $threewords);

// Execute the query
$stmt->execute();

// Get the result
$result = $stmt->get_result();

// Fetch the row as an associative array
$row = $result->fetch_assoc();

// Optional: Check if a row was found
if ($row) {
	if($row['enabled']) {
    echo "exists";
	} else {
	 echo "disabled";	
    }	
} else {
    echo "does not exist";
}

// Close the statement
$stmt->close();
 
 
