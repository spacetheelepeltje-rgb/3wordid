<?php

  require_once 'php/functions.php';
  require_once 'login/config.php';
  
  $data = check_credentials($_SESSION,$_POST,$_GET);
  
  error_log('message view page');

  if(isset($_GET["id"])) {
	$id = (int)$_GET["id"];	
  } else {
	header("location:3wid_list.php");
  }
  
  $message_row = db_3wordid_get_message($id);

?>  
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Page - 3WordID.com</title>
    <link rel="icon" type="image/png" href="3wid_1.png">
    <link rel="stylesheet" href="css/styles_3.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <header>
        
    </header>
    <main>
        <div class="logo">
            <center>
                <a href='<?php echo $main_url;?>' title="To 3wordID.com homepage, create your own!"><img width=100 src="img/3wid_big.png"></a><br> 
                <h2><?php echo $message_row["title"]  ; ?></h2>       
            </center>  
        </div>
        <div class="search-container">
                <div id="textbox"><?php echo $message_row["message"]  ; ?></div>
        </div>
          <div class="buttons">
						<a class="mail-icon" disabled  href="" title="Answer to this message">
							<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Message Icon">
								<path d="M21 4H3a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h18a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2z"></path>
								<path d="M1 6l11 7 11-7"></path>
							</svg>
						</a>
						&nbsp;	
                        <a href="3wid_message_delete.php?id=<?php echo $message['id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token'];?>" class="trash-icon" title="Delete this message">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Delete Icon">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                        </a>
        </div>
    </main>
    <?php echo $footer; ?>
</body>
<script src="js/3wid_add.js"></script>
<script>
    function handleGoogleLogin() {
        let userImageURL = 'path/to/user/image.jpg';
        if (userImageURL) {
            document.getElementById('userPortrait').src = userImageURL;
            document.getElementById('userPortrait').style.display = 'block';
        }
    }

    document.querySelector('.login-icon').addEventListener('click', function(e) {
        e.preventDefault();
        handleGoogleLogin();
        setTimeout(() => {
            window.location.href = '/login/index.php';
        }, 1000);
    });
</script>
</html>
