<?php
    
  
    include 'php/functions.php';
    
	if(isset($_GET['threewords'])) {
		
		//$hash = $_GET['hash'];
		$threewords = $_GET['threewords'];
		$hash = gethash($threewords);
		$content = getcontenthash($hash[0]);
		
		if(!isset($content['threewords'])) {
			$content['threewords'] = $threewords;
				setcontent_threewords($content,$hash);
			}

	}
	
	if(isset($_GET['status'])) {
	  
	  $status = $_GET['status'];
	  } else {
		  
	  $status = check_messages($threewords);   
	  }
	  
	if(isset($_GET["nolinkthru"]) and $_GET["nolinkthru"] =="true") {
		
		
		
		} else {
	  
		if(isset($content["linkthruflag"])) {
			
			if($content["linkthruflag"] == "on") {
				
					$header_url = 'location:' . $content["linkthru"];
			
					header($header_url);
				}
			
			}
		
	}
	
	if(file_exists('terms_and_conditions.html')) {
	
	$terms_conditions = file_get_contents('terms_and_conditions.html');
} else {
	$terms_conditions = "fail";
	}


?>   
<!DOCTYPE html>
<html lang="en">
<head>
	<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-TJWBJT8GJC"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-TJWBJT8GJC');
</script>
	<link rel="icon" type="image/png" href="3wid_1.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>3wordid.com</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
     <style>
	/* create */	
     /* General body and layout styles 3SKABA852907689  */
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

a {
    text-decoration: none;
}
 
  .settings-icon {
            transition: transform 0.5s ease;
        }
 
        /* Modal background */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgb(0,0,0); /* Fallback color */
            background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
        }

        /* Modal Content/Box */
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto; /* 15% from the top and centered */
            padding: 20px;
            border: 1px solid #888;
            width: 80%; /* Could be more or less, depending on screen size */
            max-height: 80vh;
            overflow-y: auto;
        }

        /* Close Button */
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
   
    </style>
</head>
<body>
       
    <?php echo $terms_conditions; ?>  
    
    
	<div class="settings-icon" onclick="location.href='contact_notification.php?threewords=<?=$threewords ?>&hash=<?=$hash[0] ?>';" title="edit your profile and settings">⚙</div>
    <div class="container">
        <h2><a href='index.php' title='Go to 3wordid.com homepage'><img width=100 src="img/3wid_big.png"></a></h1>
        <section>
            <h1><?=$threewords; ?></h2>
        </section>
        <section>
			<div class="notification">
            <b><?=$content['notification'];?></b>
             <br><br>
            </div> 
        </section>
        <?php if($content['xdotcom'] != "") { ?>
	    <br>
         <section>
           <a href="https://www.x.com/<?=$content['xdotcom'];?>"><b>X@<?=$content['xdotcom'];?></b></a>
        </section>
        <?php }?>

        <?php if( $content['linkthru'] != "") { ?>
        
        <section>
		   <br>	
           <b>To linked site : <a href="<?=$content['linkthru'];?>"><?=$content['linkthru'];?></b></a>
        </section>
         <?php }?>
          

       <!-- <section>
		  <br>
          <b>Your 3WordId hash is : <?= $hash[0] ?></b>
        </section>
        <section> 
        -->
       
		<br>	
        <button id="openModalBtn1">Terms & Conditions (jan 2025)</button>
        </section>
       
    </div>
</body>
<script src="js/form_checks.js"></script>
<script>
	
	console.log('<?= $hash[0] ?>');
	
	document.addEventListener('DOMContentLoaded', function() {
    // Get the modal
    var modal = document.getElementById("myModal1");

    // Get the button that opens the modal
    var btn = document.getElementById("openModalBtn1");

    // Get the <span> element that closes the modal
    var span = document.getElementsByClassName("close")[0];

    // When the user clicks on the button, open the modal 
    btn.onclick = function() {
		event.preventDefault(); // Prevent form submission
        modal.style.display = "block";
    }

    // When the user clicks on <span> (x), close the modal
    span.onclick = function() {
        modal.style.display = "none";
    }

    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
});
  </script>
</html>
