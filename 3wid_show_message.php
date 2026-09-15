<?php

  require_once 'php/functions.php';
  require_once 'login/config.php';
  
  $time = substr(time(),-4);
  
  $client_ip = get_client_ip();
  
  error_log(time() . ' ' . __FILE__  . ' ' . $client_ip);
  
  $data = check_credentials($_SESSION,$_POST,$_GET);
  
  if($data != NULL) {
	//header('location:' . $main_url);
  } 

  
  
  if(isset($_GET['wid_id'])) {  
	$threeword_id = $_GET['wid_id'];  
  } else {
	//header('location:' . $main_url);
  }
  
  error_log('list messages 3wid ' . $threeword_id . ' ' . json_encode($data));
  
  $threeword_id = (int) $threeword_id;

  $messages = db_3wordid_list_messages($threeword_id);

  error_log('messages found ' . json_encode($messages));

  if($messages == NULL) {
	//header('location:3wid_list.php');  
  }
 
?>   
<!DOCTYPE html>
<html lang="en">
<head>
	<?php echo $google_stats; ?>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3wordid.com</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link rel="stylesheet" href="css/styles_2.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
   
    <style>
		
        
.row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid #ddd;
  padding: 10px 0;
  gap: 10px;
  flex-wrap: nowrap;
}

.avatar {
  width: 50px;
  height: 50px;
  border-radius: 5px;
  object-fit: cover;
  flex-shrink: 0;
}

.text {
  flex: 1;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.text a {
  color: #000;
  text-decoration: none;
  font-size: 16px;
  display: inline-block;
  max-width: 100%;
}

.icons {
  display: flex;
  gap: 10px;
  flex-shrink: 0;
}

.icons svg {
  width: 24px;
  height: 24px;
  stroke: #333;
  stroke-width: 2;
}

.mail-icon[disabled] svg {
  stroke: gray;
}

/* Responsive tweaks */
@media (max-width: 600px) {
  .text {
    font-size: 14px;
  }

  .icons svg {
    width: 20px;
    height: 20px;
  }

  .row {
    gap: 6px;
  }
}

/* Make sure body and containers can expand fully */
body, html {
  margin: 0;
  padding: 0;
  width: 100%;
  box-sizing: border-box;
}

/* If you have a wrapping container, fix it */
.message-list {
  width: 100%;
  max-width: none; /* REMOVE any max-width that limits */
  padding: 0 10px;
  box-sizing: border-box;
}

/* The row must stretch fully */
.row {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  flex-wrap: nowrap;
  padding: 10px 0;
}

/* Make sure inner items shrink if needed */
.text {
  flex: 1 1 auto;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.icons {
  flex-shrink: 0;
  display: flex;
  gap: 10px;
}
    </style>
</head>
<body>
    <header>
        
    </header>
    <main>    
		  <div class="logo">
			<center><a href='<?php echo $main_url;?>' title="to 3wordID.com"><img width=100 src="img/3wid_big.png"></a><br>   
            </center>  
        </div>
        <a href="https://3wordid.com/3wid_signup.php">Subscribe for permanent 3WordIDs</a><br><br>
       <div class="message-list">
  <?php foreach ($messages as $message): ?>
    <div class="row">
  <img src="<?php echo $message['picture']; ?>" alt="User" class="avatar">
  <div class="text" onclick="revealMessage(this, '<?php echo htmlspecialchars($message['message'], ENT_QUOTES); ?>')">
  <a href="3wid_message_view.php?id=<?php echo $message['id']; ?>" title="<?php echo $message['message']; ?>">
    <?php echo $message['title']; ?>
  </a>
</div>
  <div class="icons">
    <a class="mail-icon" disabled href="#" title="Answer">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Message Icon">
								<path d="M21 4H3a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h18a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2z"></path>
								<path d="M1 6l11 7 11-7"></path>
							</svg>
    </a>
    <a href="3wid_message_delete.php?id=<?php echo $message['id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token'];?>" title="Delete">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Delete Icon">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
    </a>
  </div>
</div>
  <?php endforeach; ?>
</div>
    </main>
    <?php echo $footer; ?>
</body>
<script>
  function revealMessage(el, fullMessage) {
    el.innerHTML = fullMessage;
  }
</script>
</html>
