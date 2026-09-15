<?php

  include 'php/functions.php';
  
  if(isset($_GET["threewords"])) {
	  
		$threewords = $_GET["threewords"];
	  } else {
		  
		$threewords = "";  
	  }
  
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3wordid.com</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
     /* General body and layout styles */
body {
    margin: 0;
    padding: 0;
    font-family: Arial, sans-serif;
    background-color: #f5f5f5;
    height: 100vh;
    display: flex;
    justify-content: center; /* Center horizontally */
    align-items: center; /* Center vertically */
}

/* Container for the form, no box around it */
.container {
    text-align: center;
    width: 100%;
    max-width: 400px; /* Limit form width on large screens */
    padding: 20px;
}

/* Input and submit button styles */
.threefields input[type="text"], .threefields input[type="submit"] {
    width: calc(100% - 40px); /* Full width with margin space */
    max-width: 400px;
    padding: 10px;
    margin: 10px 20px; /* Consistent margin on both sides */
    font-size: 16px;
    border-radius: 20px;
    border: 1px solid #ccc;
    box-sizing: border-box; /* Ensure padding doesn’t affect width */
}

input[type="submit"] {
    background-color: #007bff;
    color: white;
    cursor: pointer;
}

input[type="submit"]:hover {
    background-color: #0056b3;
}

.helperText {
    margin-bottom: 15px;
    color: #555;
}

/* Mobile responsive adjustments */
@media (max-width: 600px) {
    .container {
        padding: 10px;
    }

    .threefields input[type="text"], .threefields input[type="submit"] {
        width: calc(100% - 40px); /* Consistent width and margin on mobile */
        margin: 10px 20px;
        font-size: 14px;
    }
}
.settings-icon {
    position: fixed;
    top: 10px;
    right: 10px;
    width: 40px;
    height: 40px;
    background-color: #007bff; /* Visible blue background */
    color: white; /* White color for the text/icon */
    font-size: 24px; /* Adjust font size */
    text-align: center;
    line-height: 40px; /* Center text vertically */
    border-radius: 50%; /* Circle shape */
    cursor: pointer;
    z-index: 1000; /* Ensure it stays on top */
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Optional: add shadow for visibility */
}
.settings-icon:hover {
    background-color: #0056b3; /* Darker blue on hover */
}
</style>
</head>
<body>
    
    <div class="container">
		<img width=50 src="3wid_1.png">
        <h1><a href='index.php'>3WordID.com Reselling</a></h1>
        <section align='left'>
			<p>
            You can earn money by signing people up for a 3wordId. The only thing 
            you need to do is set up a paypall account and send an email to 
            <a href='mailto:info@climatebabes.com'>info@climatebabes.com</a> when someone signs up. If we recieve the email address
            of a person that subscribes to our service we pay you 5 Euro. 
            </p>    
            <p>
				
			</p>	
        </section>
    </div>
    <!-- JavaScript to toggle profile menu -->
 
 <script src="js/form_checks_index.js"></script>
</body>

</html>
