<?php

  require_once 'php/functions.php';
  require_once 'login/config.php';
  
  $loginUrl = $client->createAuthUrl();
  
  if(isset($_GET["id"])) {
    $id = $_GET["id"];
  } else {
    header("location:" . $main_url);
  }
  
  $word_row = db_3wordid_get($id); 
  
  if($word_row == NULL) {  
      header("location:" . $main_url);
  }

?>  
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($word_row["threeword"]); ?> — 3WordID</title>
    <link rel="icon" type="image/png" href="3wid_1.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
      :root {
        --navy: #0F1C3F;
        --navy-2: #162756;
        --slate: #5B6478;
        --muted: #8A93A6;
        --line: #E6E3DC;
        --paper: #F7F6F3;
        --white: #FFFFFF;
        --gold: #B45309;
        --shadow: 0 10px 30px rgba(15,28,63,.08);
        --chip: #EFECE6;
      }
      * { box-sizing: border-box; }
      html, body { margin: 0; padding: 0; }
      body {
        font-family: Inter, system-ui, -apple-system, Segoe UI, sans-serif;
        background: var(--paper);
        color: var(--navy);
        min-height: 100vh;
      }
      a { color: var(--navy); }
      .site-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 16px 28px;
        background: rgba(247,246,243,.92);
        backdrop-filter: blur(10px);
        border-bottom: 1px solid var(--line);
        position: sticky;
        top: 0;
        z-index: 20;
      }
      .brand {
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        color: var(--navy);
        font-weight: 800;
        letter-spacing: -.03em;
        font-size: 20px;
      }
      .brand-mark {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        background: var(--navy);
        display: grid;
        place-items: center;
        overflow: hidden;
      }
      .brand-mark img {
        width: 35px;
        height: 35px;
        object-fit: cover;
        display: block;
      }
      .header-actions { display: flex; align-items: center; gap: 10px; }
      .btn {
        border: 0;
        cursor: pointer;
        border-radius: 10px;
        font-weight: 600;
        font-family: inherit;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 10px 14px;
        font-size: 14px;
      }
      .btn-primary { background: var(--navy); color: #fff; }
      .btn-primary:hover { background: var(--navy-2); }
      .btn-ghost { background: #fff; color: var(--navy); border: 1px solid var(--line); }
      .wrap { max-width: 720px; margin: 0 auto; padding: 48px 24px 64px; }
      .eyebrow {
        color: var(--gold);
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .14em;
        margin-bottom: 8px;
      }
      h1 {
        margin: 0 0 8px;
        font-size: clamp(32px, 5vw, 44px);
        letter-spacing: -.04em;
        font-weight: 800;
      }
      .lede { margin: 0 0 22px; color: var(--slate); font-size: 16px; }
      .panel {
        background: var(--white);
        border: 1px solid #ECEAE4;
        border-radius: 16px;
        box-shadow: var(--shadow);
        padding: 22px;
      }
      #textbox {
        color: var(--navy);
        font-size: 16px;
        line-height: 1.6;
        white-space: pre-wrap;
      }
      .buttons { margin-top: 18px; }
      .site-footer {
        border-top: 1px solid var(--line);
        padding: 18px 28px 28px;
        color: var(--muted);
        font-size: 13px;
      }
    </style>
</head>
<body>
    <header class="site-header">
        <a class="brand" href="<?php echo $main_url;?>" title="To 3wordID.com homepage, create your own!">
            <span class="brand-mark"><img width="35" src="https://3WordID.com/img/3wid_big.png" alt=""></span>
            3WordID
        </a>
        <div class="header-actions">
            <a class="btn btn-primary" href="<?php echo $main_url; ?>">To Homepage</a>
        </div>
    </header>

    <main class="wrap">
        
        <h1><?php echo htmlspecialchars($word_row["threeword"]); ?></h1>
        <p class="lede">Notification attached to this 3WordID:</p>

        <div class="panel search-container">
            <div id="textbox"><?php echo $word_row["notification"]; ?></div>
        </div>

        <!--<div class="buttons">
            <a class="btn btn-ghost" href="<?php echo $main_url; ?>">To Homepage</a>
        </div> -->
    </main>
    <footer class="site-footer"><?php echo $footer; ?></footer>
</body>
</html>
