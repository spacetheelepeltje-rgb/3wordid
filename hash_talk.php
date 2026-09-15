<?php
 // store a message in a hash chain with clear text and hash readable but clearly content being immutable
 // must name source (hash) and text.
 
 
 
?>  
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Page - 3WordID.com</title>
    <link rel="icon" type="image/png" href="3wid_1.png">
    <link rel="stylesheet" href="css/styles_2.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <header>
		
    </header>
    <main>
        <div class="logo">
            <center>
                <a href='<?php echo $main_url;?>'><img width=100 src="img/3wid_big.png"></a><h1>HashTalk</h1><br>         
            </center>  
        </div>
        <div class="search-container">
			<form id="3widForm" action="hash_talk.php" method="post">
			<h2>Last # : <?php echo md5('this is a hash'); ?><h2>
            
                <textarea name="notification" id="notification" class="search-bar" placeholder="Enter your message here" rows="4"></textarea>
                <input type="text" class="search-bar" name="identity" placeholder="add identifier">
                <div class="buttons" style="display: flex; align-items: center;">
					<button type="submit" id="submitBtn">Submit</button>
				</div>
				<input type='hidden' name='csrf_token' value='<?php echo $_SESSION['csrf_token']; ?>'>
		
            </form>
        </div>
    </main>
    <footer>
        <div class="footer-links">
            <a href="hash_talk_about.php">About</a>
           
        </div>
    </footer>
</body>
</html>
