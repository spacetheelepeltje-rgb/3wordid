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

</style>
</head>
<body>
    
    <div class="container">
		<img width=50 src="img/3wid_big.png">
        <h1><a href='index.php'>3WordID.com</a></h1>
        <section align='left'>
		Privacy Policy
<b>Information Collection</b>:<br>
<b>Personal Information</b>: We may collect personal information such as your name, email address, and phone number when you voluntarily provide it, e.g., during registration or contact form submissions.<br>
<b>Non-Personal Information</b>: We automatically collect certain information like your IP address, browser type, and usage data through cookies or similar technologies.<br>
<b>Use of Information</b>:<br>

<b>Service Provision</b>: To fulfill or meet the reason you provided the information, like responding to your inquiries or processing your requests.<br>
<b>Improvement of Services</b>: To enhance our website and user experience, analyze trends, and understand user interactions.<br>
<b>Communication</b>: To send periodic emails regarding updates, promotions, or changes to our services, with an option to opt out.<br>
<b>Data Sharing</b>:<br>
We do not sell, trade, or otherwise transfer your personal information to outside parties except to trusted third parties who assist us in operating our website, conducting our business, or servicing you, as long as those parties agree to keep this information confidential.
<b>Data Security</b>:<br>
We implement a variety of security measures to maintain the safety of your personal information when you enter, submit, or access your personal information.
<b>Data Retention</b>:<br>
We retain your personal information only for as long as necessary to fulfill the purposes outlined in this Privacy Policy unless a longer retention period is required or permitted by law.<br>
<b>Your Rights</b>:<br>
<b>Access and Control</b>: You have the right to access, update, or delete your personal information. <br>
<b>Data Portability</b>: You can request a copy of your data in a commonly used format.<br>
<b>Opt-Ou</b>t: You can opt out of marketing communications at any time.<br>
Changes to Privacy Policy:<br>
We reserve the right to modify this privacy policy at any time, so please review it frequently. Changes and clarifications will take effect immediately upon their posting on the website.
<b>Contact Information</b>:<br>
If you have any questions or concerns about our privacy practices, please contact us at [contact email or form].

Terms of Use<br>
<b>User Consent</b>: By using our site, you consent to our Privacy Policy.<br>
Intellectual Property: All content on this site is owned by us or our licensors and is protected by copyright laws.<br>
<b>Liability</b>: We are not liable for any indirect, special, incidental, or consequential damages that may result from the use of our site.<br>
        </section>
    </div>
</body>

</html>
