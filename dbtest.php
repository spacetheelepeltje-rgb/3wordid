<?php // tLIo7_3DydQF

// Database connection details
$servername = "localhost"; // Change this to your MySQL server if not on the same machine
$username = "read3wordid"; // Replace with your database username
$password = "a6468c235796777d1834f885f5aae644"; // Replace with your database password
$database = "3wordid"; // If you want to specify a particular database, otherwise leave it

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

echo "Connected successfully";

// Perform a simple query to list databases (for testing purposes)
$result = $conn->query("SHOW DATABASES");

if ($result->num_rows > 0) {
    echo "<br>Available databases:<br>";
    // output data of each row
    while($row = $result->fetch_assoc()) {
        echo $row["Database"] . "<br>";
    }
} else {
    echo "0 results";
}

$result = $conn->query("SELECT * FROM 3wordid;");

if ($result->num_rows > 0) {
    echo "<br>Available databases:<br>";
    // output data of each row
    while($row = $result->fetch_assoc()) {
        echo $row["3wordid"] . " " . $row["hash"] . "<br>";
    }
} else {
    echo "0 results";
}

// Close connection
$conn->close();

$username = "full3wordid"; // Replace with your database username
$password = "0d3e7a7853cd10f3b50326f05e4ec050"; // Replace with your database password


// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

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

echo "Connected successfully";


$conn->close();


?>
