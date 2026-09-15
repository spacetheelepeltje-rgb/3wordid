<?php

  require_once 'php/functions.php';
  require_once 'login/config.php';
  
  $time = substr(time(),-4);
  
  $client_ip = get_client_ip();
  
  //error_log('list messages ' . $client_ip . ' get ' . json_encode($_GET));
  
  $data = check_credentials($_SESSION,$_POST,$_GET);
  
    
  // Generate Google Login URL
  $loginUrl = $client->createAuthUrl();
  
  if($data != NULL) {
	//header('location:' . $main_url);
  } 
  
  if(isset($_GET['wid_id'])) {  
	$threeword_id = $_GET['wid_id'];  
  } else {
	//header('location:' . $main_url);
  }
  
  if(isset($_GET['threeword'])) {  
	$threeword = $_GET['threeword'];  
  } else {
	//header('location:' . $main_url);
  }

  //error_log('list messages 3wid ' . $threeword_id . ' ' . json_encode($data));
  
  $threeword_id = (int) $threeword_id;

  $messages = db_3wordid_list_messages($threeword_id);

  //error_log('messages found ' . json_encode($messages));

  if($messages == NULL ) {
	header('location:3wid_list.php'); 
	die('no messages'); 
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
    <link rel="stylesheet" href="css/newstyle.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
   
    <style>
body, html {
    margin: 0;
    padding: 0;
    width: 100%;
    box-sizing: border-box;
}

main {
    width: 100%;
    margin: 0;
    padding: 0;
}

.message-list {
    width: 100%;
    max-width: 1200px; /* Optional: limits width on large screens */
    margin: 0 auto; /* Centers content without restricting width */
    padding: 0 10px;
    box-sizing: border-box;
}

.row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    border-bottom: 1px solid #ddd;
    padding: 10px 0;
    gap: 10px;
    flex-wrap: nowrap;
    width: 100%;
}

.row.header {
    border-bottom: 2px solid #ccc;
    padding-bottom: 5px;
    margin-bottom: 10px;
}

.avatar {
    width: 50px;
    height: 50px;
    border-radius: 5px;
    object-fit: cover;
    flex-shrink: 0;
}

.avatar-header {
    width: 50px;
    padding: 10px 0;
    font-weight: bold;
}

.text {
    flex: 1 1 auto;
    overflow: visible;
    white-space: normal;
    word-break: break-word;
    display: block;
}

.text a {
    color: #000;
    text-decoration: none;
    font-size: 16px;
    display: inline-block;
    max-width: 100%;
}

.text-header {
    flex: 1;
    padding: 10px 0;
    font-weight: bold;
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

.icons-header {
    width: 60px;
    padding: 10px 0;
    font-weight: bold;
    text-align: right;
}

.mail-icon[disabled] svg {
    stroke: gray;
}

.logo {
    margin: 20px 0;
}

.logo h2, .logo p {
    margin: 10px 0;
}

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
    .message-list {
        padding: 0 5px;
    }
}

  .thumbnail {
            cursor: pointer;
            max-width: 200px; /* Adjust thumbnail size */
        }

        /* Modal (hidden by default) */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8); /* Semi-transparent background */
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal img {
            width: 100vw; /* Full viewport width */
            height: auto; /* Maintain aspect ratio */
            max-height: 95vh; /* Prevent overflow */
            object-fit: contain; /* Ensure proper scaling */
        }

        /* Close button */
        .close {
            position: absolute;
            top: 20px;
            right: 30px;
            color: white;
            font-size: 30px;
            cursor: pointer;
        }

    </style>
</head>
<script>
  function revealMessage(el, fullMessage) {
    el.innerHTML = fullMessage;
  }
</script>
<body>
   <header>    
	   <div class="top-right" id="userContainer">
      
            <a href="<?php echo $loginUrl; ?>" class="login-icon" title="Log in (not all functions work on mobile devices)">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Login Icon">
				<path d="M15 21h4a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2h-4"></path>
				<polyline points="8 7 13 12 8 17"></polyline>
				<line x1="13" y1="12" x2="1" y2="12"></line>
			  </svg>
            </a>
       
		</div> 
   </header>
   <main>
    <div class="logo" style="text-align: center;">
        <a href='<?php echo $main_url;?>' title="to 3wordID.com">
            <img width="100" src="img/3wid_big.png" alt="3wordID Logo">
        </a>
        <h2>Messages for <?php echo htmlspecialchars($threeword); ?></h2>
        <p>Click on the message title to read the message</p>
    </div>
   
    <div class="message-list">
        <div class="row header">
            <span class="avatar-header">User</span>
            <span class="text-header">Title/Message</span>
            <span class="icons-header">Actions</span>
        </div>
        <?php foreach ($messages as $message): ?>
        <div class="row">
            <img onclick="openModal(this.src)" src="<?php echo htmlspecialchars($message['picture']); ?>" alt="User" class="thumbnail avatar" title="<?php echo $message["threeword"];?>">
            
             <div id="imageModal" class="modal">
				<span class="close" onclick="closeModal()">&times;</span>
				<img id="fullImage" src="" alt="Full Image">
			</div>
            
            <div class="text"
                 data-title="<?php echo $message["threeword"] . " : " . htmlspecialchars($message['title'], ENT_QUOTES); ?>"
                 data-message="<?php echo htmlspecialchars($message['message'], ENT_QUOTES); ?>"
                 data-state="title">
                <?php echo $message["threeword"] . " : " . htmlspecialchars($message['title']); ?>
            </div>
            <div class="icons">
				<?php 
				// block in case of external message
				if($message["from_id"]!=25) {
				?>
                <a class="mail-icon" disabled href="3wid_messageform.php?threeword=<?php echo $message["threeword"];?>" title="Reply to <?php echo ucfirst($message["threeword"]);?>">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Message Icon">
                        <path d="M21 4H3a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h18a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2z"></path>
                        <path d="M1 6l11 7 11-7"></path>
                    </svg>
                </a>
                <?php 
                } 
                ?>
                <a href="3wid_message_delete.php?id=<?php echo $message['id']; ?>" title="Delete">
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
     // Open modal and set image source
        function openModal(src) {
            const modal = document.getElementById('imageModal');
            const fullImage = document.getElementById('fullImage');
            fullImage.src = src;
            modal.style.display = 'flex';
        }

        // Close modal
        function closeModal() {
            const modal = document.getElementById('imageModal');
            modal.style.display = 'none';
        }

        // Close modal when clicking outside the image
        window.onclick = function(event) {
            const modal = document.getElementById('imageModal');
            if (event.target === modal) {
                closeModal();
            }
        };
  
  
  $(document).ready(function () {
    $('.text').on('click', function () {
      var $el = $(this);
      var isMessage = $el.data('state') === 'message';

      if (isMessage) {
        $el.text($el.data('title'));
        $el.data('state', 'title');
      } else {
        $el.text($el.data('message'));
        $el.data('state', 'message');
      }
    });
  });

</script>
 
</html>
