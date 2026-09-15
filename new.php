<?php

  include 'php/functions.php';

   session_start();

   if(isset($_GET['threewords'])) {
	 $threewords =$_GET['threewords'] ;
	 } else {
	 $threewords = "";	 
     }


  
  $count = countusers();
  
 ?>

<!DOCTYPE html>
<html lang="en">
<head>
<?php
 echo $google_analytics_script; 
?>
	<link rel="icon" type="image/png" href="3wid_1.png">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

    
    
    </style>
    <title>3wordid.com</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h1><a href='index.php'title='3wordID.com'><img width=100 src="img/3wid_big.png"></a></h1>
        <section>
			<?php if ($threewords != "") {
				echo "Your 3wordid is still available.";
			} else {
			?>
				
            Choose three words to
            create your three word ID   
            <?php } ?>  
        </section>
        <section>
            <form method="post" id="FormCreate">
                <div class="threefields">
                    <input type="text" id="myThreeWordID" name="new_idg" placeholder="Three words here" class="glow-bg" value="<?=$threewords; ?>">
                    <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <p id="helperText"></p>   
                </div>      
                <div class="threefields">
                    <input type="submit" id="createbutton" value="Login/Create" name="Button">              
                </div>
            </form>
        </section>
    </div>

</body>
    <script>
        document.getElementById('createbutton').focus();
    </script>
   <script src="js/form_checks_new.js"></script>
</html>

