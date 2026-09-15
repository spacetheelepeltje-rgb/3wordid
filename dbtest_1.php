<?php
// Database connection details
$servername = "localhost"; // or your server's address
$username = "read3wordid";
$password = "a6468c235796777d1834f885f5aae644";
$dbname = "3wordid";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

// SQL to select all from google_users table
$sql = "SELECT * FROM google_users";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // output data of each row
    while($row = $result->fetch_assoc()) {
        echo "id: " . $row["id"]. " - Name: " . $row["name"]. "<br>";
    }
} else {
    echo "0 results";
}

// Close connection
$conn->close();

$username = "full3wordid";
$password = "0d3e7a7853cd10f3b50326f05e4ec050";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

// SQL to select all from google_users table
$query = "INSERT INTO google_users (email, first_name, last_name, gender, full_name, picture, verifiedEmail, token)
VALUES (
    CONCAT('user', FLOOR(1 + RAND() * 10000), '@example.com'), -- Random email
    ELT(1 + FLOOR(RAND() * 10), 'John', 'Jane', 'Emily', 'Michael', 'David', 'Sarah', 'Robert', 'Lisa', 'Daniel', 'Emma'), -- Random first name
    ELT(1 + FLOOR(RAND() * 10), 'Doe', 'Smith', 'Johnson', 'Brown', 'Jones', 'Garcia', 'Miller', 'Wilson', 'Taylor', 'Anderson'), -- Random last name
    ELT(1 + FLOOR(RAND() * 3), 'male', 'female', 'other'), -- Random gender
    CONCAT(
        ELT(1 + FLOOR(RAND() * 10), 'John', 'Jane', 'Emily', 'Michael', 'David', 'Sarah', 'Robert', 'Lisa', 'Daniel', 'Emma'),
        ' ',
        ELT(1 + FLOOR(RAND() * 10), 'Doe', 'Smith', 'Johnson', 'Brown', 'Jones', 'Garcia', 'Miller', 'Wilson', 'Taylor', 'Anderson')
    ), -- Full name constructed from first and last name
    CONCAT('image', FLOOR(1 + RAND() * 1000), '.jpg'), -- Random picture filename
    FLOOR(RAND() * 2), -- Random 0 or 1 for verifiedEmail
    MD5(RANDOM_BYTES(16)) -- Random token using MD5 of random bytes
);";

$result = $conn->query($query);

if ($result->num_rows > 0) {
    // output data of each row
    while($row = $result->fetch_assoc()) {
        echo "id: " . $row["id"]. " - Name: " . $row["name"]. "<br>";
    }
} else {
    echo "0 results";
}

// Close connection
$conn->close();
?>

