<?php

require_once 'login/config.php';
 
$user_id = (int) $_GET["user_id"];

if (is_int($user_id)) {

$user_id = $conn->real_escape_string($user_id); // Escaping is optional with prepared statements

$query = "SELECT JSON_ARRAYAGG(
    JSON_OBJECT(*)) as json_result FROM 3wordid WHERE user_id = ?";

//echo $query; // For debugging, remove in production

$stmt = $conn->prepare($query);

if ($stmt === false) {
    die("Prepare failed: " . $conn->error); // Add error checking
}

// Bind the parameter (user_id is likely an integer, so use "i")
$stmt->bind_param("i", $user_id); // Corrected from $threewords to $user_id

// Execute the query
$stmt->execute();

// Get the result
$result = $stmt->get_result();

// Fetch the row as an associative array
$rows = $result->fetch_all(MYSQLI_ASSOC); // Changed $rows to $row (singular, as it's one row)

// Check if a row was found and process it
foreach ($rows as $row) {
    var_dump($row);
} 

// Close the statement and result
$stmt->close();

} else {

 echo "All your base are belong to us!";

}
 
 
