<?php

  
   
?>  
  <html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3wordid.com</title>
    <link rel="icon" type="image/png" href="3wid_1.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/contact_notification.css">
</head>
<div class="container">
  <a href='index.php'><img width=100 src="img/3wid_big.png"></a>
 
				<h2>Set new password</h2>
				If you enter the data below we will process it and enable your new password.<br>
			    This may take a day at the moment. We can you your bank account digits for <br>
			    validation.<br><br>
			     <form method="post" action="process_feedback.php" name="formSearch" id="formSearch">
                <div class="threefields">
					My 3WordID<br>
                    <input type="text" id="myThreeWordID" name="threewords"  class="myInput" value=""><br>
                    My feedback<br>
                    <textarea id="myEmail" name="feedback" width=40></textarea>
                   
                </div>
                <div class="threefields">
                    <input type="submit" value="Submit" name="SearchLogin" id="submitBtn">
					<a href='https://3wordid.com/errors.php'>.</a><a href='threewords/create_index.php'>.</a>
                </div>

    </form>
    
 </div>   

 </body>
 </html> 
