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
    <title>Messages — 3WordID</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/newstyle.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
      .page {
        max-width: 1100px;
        margin: 0 auto;
        padding: 28px 26px 64px;
      }
      .page .subhead { margin-left: 0; text-align: left; }
      .btn-ghost {
        background: #fff;
        color: var(--navy);
        border: 1px solid var(--line);
        padding: 10px 16px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        border-radius: 10px;
        font-weight: 600;
        font-family: inherit;
      }
      .btn-ghost:hover { background: #f3f1ec; }
      .panel {
        background: #fff;
        border: 1px solid #eceae4;
        border-radius: 16px;
        box-shadow: var(--shadow);
        overflow: hidden;
      }
      .message-list { width: 100%; }
      .row {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
        padding: 14px 16px;
        border-top: 1px solid #f0eee8;
        width: 100%;
      }
      .row.header {
        border-top: 0;
        border-bottom: 1px solid #eceae4;
        color: var(--slate);
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
      }
      .avatar-header { width: 50px; flex-shrink: 0; }
      .text-header { flex: 1; }
      .icons-header { width: 72px; text-align: right; flex-shrink: 0; }
      .avatar, .thumbnail {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        object-fit: cover;
        flex-shrink: 0;
        cursor: pointer;
        background: #f3f1ec;
      }
      .text {
        flex: 1 1 auto;
        overflow: visible;
        white-space: normal;
        word-break: break-word;
        display: block;
        font-size: 15px;
        color: var(--navy);
        cursor: pointer;
        line-height: 1.45;
      }
      .text a { color: var(--navy); text-decoration: none; }
      .icons {
        display: flex;
        gap: 6px;
        flex-shrink: 0;
      }
      .icons a {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        border: 1px solid var(--line);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--navy);
        background: #fff;
      }
      .icons a:hover { background: #f3f1ec; }
      .icons a[title="Delete"]:hover { color: #9b1c1c; }
      .icons svg {
        width: 18px;
        height: 18px;
        stroke: currentColor;
        stroke-width: 2;
        fill: none;
      }
      .mail-icon[disabled] svg { stroke: var(--muted); }
      .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(15, 28, 63, 0.72);
        justify-content: center;
        align-items: center;
        z-index: 1000;
      }
      .modal img {
        width: 100vw;
        height: auto;
        max-height: 95vh;
        object-fit: contain;
      }
      .close {
        position: absolute;
        top: 20px;
        right: 30px;
        color: white;
        font-size: 30px;
        cursor: pointer;
      }
      @media (max-width: 800px) {
        .page { padding: 22px 16px 48px; }
        .text { font-size: 14px; }
        .row { gap: 8px; }
      }
    </style>
</head>
<script>
  function revealMessage(el, fullMessage) {
    el.innerHTML = fullMessage;
  }
</script>
<body>
   <header class="site-header">
        <a class="brand" href="<?php echo $main_url;?>">
            <span class="brand-mark"><img width='35' src='https://3WordID.com/img/3wid_big.png'></span>
            3WordID
        </a>
        <div class="header-actions" id="userContainer">
            <a class="btn-ghost" href="3wid_list.php">Back to list</a>
            <a href="<?php echo $loginUrl; ?>" class="login-icon" title="Log in (not all functions work on mobile devices)">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Login Icon">
                <path d="M15 21h4a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2h-4"></path>
                <polyline points="8 7 13 12 8 17"></polyline>
                <line x1="13" y1="12" x2="1" y2="12"></line>
              </svg>
            </a>
        </div>
   </header>
   <main class="page">
        <p class="eyebrow">Inbox</p>
        <h1>Messages for '<?php echo htmlspecialchars($threeword); ?>'</h1>
        <p class="subhead">Click a title to read the message.</p>

    <div class="panel message-list">
        <div class="row header">
            <span class="avatar-header">User</span>
            <span class="text-header">Title/Message</span>
            <span class="icons-header">Actions</span>
        </div>
        <?php foreach ($messages as $message): ?>
        <div class="row">
            <img onclick="openModal(this.src)" src="<?php echo htmlspecialchars($message['picture']); ?>" alt="User" class="thumbnail avatar" title="<?php echo $message["threeword"];?>">
            
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

             <div id="imageModal" class="modal">
                <span class="close" onclick="closeModal()">&times;</span>
                <img id="fullImage" src="" alt="Full Image">
            </div>

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
