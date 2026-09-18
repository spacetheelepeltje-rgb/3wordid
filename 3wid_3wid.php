<?php

  require_once 'login/config.php';
  require_once 'php/functions.php';
  
  $loginUrl = $client->createAuthUrl();
  
 if(isset($_GET['csrf_token'])) { 
    $csrf_token = $_GET['csrf_token']; 
 } else {
    header('location:' . $main_url);
 }
 
 $data = check_csrf_token($csrf_token);
 
 if($data == NULL) {
    error_log('auth is null');
    $data = check_session();
 }
  
 if($data == NULL) { 
    $data = check_csrf_token($csrf_token);
     
     if($data == NULL) {
     
     header('location:' . $main_url);
     
    }
  } 
  
  
 
 $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

 if(isset($_GET["id"])) {
    $id = $_GET["id"];
 } else {
    //header('location:' . $main_url);
 }
 
 if($data["id"]==4) {
        $row = db_3wordid_get($id);
     } else {
        $row = db_3wordid_get_user($id,$data["id"]);
    }
 
 if($row == NULL) {
     header('location:' . $main_url . "?message=This is not the correct 3wid id for this user");
     die('fool');
     }

  $threeword_url = implode('.', explode(' ', $row["threeword"]));

?>  
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit 3WordID</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
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
        --well: #F3F1EC;
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
        color: #fff;
        display: grid;
        place-items: center;
        font-size: 11px;
        font-weight: 800;
        line-height: 1;
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
        gap: 8px;
        padding: 10px 14px;
        font-size: 14px;
      }
      .btn-primary { background: var(--navy); color: #fff; }
      .btn-primary:hover { background: var(--navy-2); }
      .btn-ghost { background: #fff; color: var(--navy); border: 1px solid var(--line); }
      .login-icon { color: var(--navy); display: inline-flex; padding: 6px; }
      .wrap { max-width: 720px; margin: 0 auto; padding: 28px 24px 64px; }
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
      .id-box {
        background: var(--well);
        border-radius: 12px;
        padding: 14px 16px;
        margin-bottom: 18px;
      }
      .id-box strong { display: block; font-size: 18px; }
      .id-box code {
        display: inline-block;
        margin-top: 6px;
        background: var(--chip);
        border-radius: 6px;
        padding: 2px 6px;
        font-family: inherit;
        font-weight: 600;
        font-size: 13px;
        color: var(--slate);
      }
      .meta { color: var(--muted); font-size: 13px; margin: 10px 0 16px; }
      .qr-links { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; }
      label.field {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: var(--slate);
        margin: 0 0 8px;
      }
      .search-bar, textarea.search-bar {
        width: 100%;
        border: 1px solid var(--line);
        background: #fff;
        border-radius: 10px;
        padding: 12px 14px;
        font: inherit;
        color: var(--navy);
        outline: none;
      }
      textarea.search-bar { resize: vertical; min-height: 110px; }
      .search-bar:focus, textarea.search-bar:focus { border-color: var(--navy); }
      .checks { display: grid; gap: 10px; margin: 16px 0 20px; }
      .checks label {
        display: flex;
        align-items: center;
        gap: 10px;
        color: var(--navy);
        font-size: 14px;
      }
      .form-actions {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
      }
      #helperText { color: var(--slate); font-size: 13px; }
      .site-footer {
        border-top: 1px solid var(--line);
        padding: 18px 28px 28px;
        color: var(--muted);
        font-size: 13px;
      }
      @media (max-width: 800px) {
        .wrap { padding-top: 20px; }
      }
    </style>
</head>
<body>
    <header class="site-header">
        <a class="brand" href="<?php echo $main_url;?>">
            <span class="brand-mark"><img width='35' src='https://3WordID.com/img/3wid_big.png'></span>
            3WordID
        </a>
        <div class="header-actions" id="userPortrait">
            <a class="btn btn-ghost" href="3wid_list.php?user_token=<?php echo $data['token']; ?>">Back to list</a>
            <button type="submit" form="3widForm" id="submitBtn" class="btn btn-primary">Update</button>           
        </div>
    </header>

    <main class="wrap">
        
        <h1>Edit this 3WordID</h1>
        <p class="lede">Change the message, destination URL, and visibility. The three-word name remains the same.</p>

        <div class="panel search-container">
            <form id="3widForm" action="3wid_update.php" method="post">
                <div class="id-box">
                    <strong><?php echo htmlspecialchars($row["threeword"]); ?></strong>
                    <code>URL is www.3WordID.com/<?php echo htmlspecialchars($threeword_url); ?></code>
                </div>

                <input disabled type="text" name="threeword" class="search-bar" placeholder="Your Three Word ID" value="<?= htmlspecialchars($row["threeword"]); ?>" style="display:none;">

                <div class="meta">hash # <?php echo htmlspecialchars($row["hash"]); ?></div>
                <div class="qr-links">
                    <a class="btn btn-ghost" href="3wid_gen_qr.php?csrf_token=<?php echo $_SESSION['csrf_token'];?>&hash=<?= $row["hash"]; ?>" title='so https://www.3wordid.com/index.php?hash=<?= $row["hash"]; ?>'>Create Hashed QR</a>
                    <a class="btn btn-ghost" href="3wid_gen_qr_threeword.php?csrf_token=<?php echo $_SESSION['csrf_token'];?>&threeword=<?= $row["threeword"]; ?>" title='so https://www.3wordid.com/index.php?threeword=<?= $row["threeword"]; ?>'>Create Readable QR</a>
                </div>

                <label class="field" for="notification">Message</label>
                <textarea name="notification" id="notification" class="search-bar" placeholder="Enter your message here" rows="4"><?= $row["notification"]; ?></textarea>

                <label class="field" for="linkthru" style="margin-top:16px;">Forward URL</label>
                <input type="text" name="linkthru" id="linkthru" class="search-bar" placeholder="Enter forward URL with HTTPS://" value="<?= $row["linkthru"]; ?>">

                <div class="checks">
                    <label><input <?php echo $row["linkthruflag"]== 1 ?'checked':''; ?> type="checkbox" id="linkthruflag" name="linkthruflag"> Use forward URL</label>
                    <label><input <?php echo $row["emailform"]== 1 ?'checked':''; ?> type="checkbox" id="emailform" name="emailform"> Link 3WordID to message form</label>
                    <label><input <?php echo $row["private"]== 1 ?'checked':''; ?> type="checkbox" id="private" name="private"> Keep 3WordID private</label>
                </div>

                <div class="form-actions buttons">
                    <button type="submit" class="btn btn-primary">Update</button>
                    <div id="helperText"></div>
                </div>

                <input type='hidden' name='csrf_token' value='<?php echo $_SESSION['csrf_token']; ?>'>
                <input type='hidden' name='id' value='<?= $row["id"]; ?>'>
            </form>
        </div>
    </main>
    <footer class="site-footer"></footer>
</body>
<script src="js/3wid_3wid.js"></script>
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
