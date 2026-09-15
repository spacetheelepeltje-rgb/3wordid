<?php
 
    include 'php/functions.php';
    
    
	if(isset($_GET['threewords'])) {
		
		//$hash = $_GET['hash'];
		$threewords = $_GET['threewords'];
		$hash = gethash($threewords);
		$content = getcontenthash($hash[0]);

	}
	
	if(isset($_GET['status'])) {
	  
	  $status = $_GET['status'];
	  } else {
		  
	  $status = check_messages($threewords);   
	  }
	  
	

?>   
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>3wordid.com</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
     <style>
	/* create */	
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
.threefields input[type="text"], .threefields input[type="submit"]
, .threefields textarea {
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

body {
    background-color: #f0f0f0;
    margin: 0;
}

.notification {
    background-color: #ffffff;
    padding: 20px;
    margin: 20px auto;
    border-radius: 5px;
    max-width: 600px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}
    
    </style>
</head>
<body>
    <div class="container">
        <h1><a href='index.php'>3WordId.com</a></h1>
        <section>
            <h2><?=$threewords; ?></h2>
        </section>
        <section>
			<div class="notification">
            <b><?=$content['notification'];?></b>
             <br><br>
            </div> 
        </section>
           <section>
		<br><?= $status; ?>	
        </section>
         <? if($content['xdotcom'] !="") { ?>
	    <br>
         <section>
           <a href="https://www.x.com/<?=$content['xdotcom'];?>"><b>X@<?=$content['xdotcom'];?></b></a>
        </section>
        <section>
           <a href="<?=$content['linkthru'];?>"><b>To linked site</b></a>
        </section>
         
          <? }?>
         <section>
			 <br><br>
	        <a href="contact_message.php?threewords=<?=$threewords ?>&hash=<?=$hash[0] ?>">Send Message</a>  
            <a href="contact_notification.php?threewords=<?=$threewords ?>&hash=<?=$hash[0] ?>">Edit profile</a> <a href="contact_messages.php?threewords=<?=$threewords ?>&hash=<?=$hash[0] ?>">Check Messages</a> 
            <? if(isset($content['xdotcom']) && $content['xdotcom'] !="") { ?><br>
            <a href="https://twitter.com/intent/tweet?text=<?= urlencode("Check out http://www.3wordid.com and create your own publicly private three word ID! #3wid #privacy #identity");?>">Tell your X friends!</a>
            <? } ?>
        </section>
        <section>
		  <br>
          <b>Your 3WordId hash is : <?= $hash[0] ?></b>
        </section>
        <section>
		<br>	
        At this time this service is logged for IP and device. The data is not stored in encrypted format.
        </section>
       
    </div>
</body>
<script src="js/form_checks.js"></script>
</html>
