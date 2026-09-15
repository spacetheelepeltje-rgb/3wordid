<?

require_once 'login/config.php';

// Assuming $conn is a MySQLi connection
$sql = "INSERT INTO 3wordid (threeword, user_id, hash) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);

// Bind parameters (s = string, i = integer, s = string)
$stmt->bind_param("sis", $threeword, $user_id, $hash);

// Set values
$threeword = 'this is test';
$user_id = 4;
$hash = 'hashcode';

if ($stmt->execute()) {
    echo "Record inserted successfully";
} else {
    echo "Error: " . $conn->error;
}

$stmt->close();

?>
