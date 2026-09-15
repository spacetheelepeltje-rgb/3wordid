<?php

  require_once 'php/functions.php';
  
  $client_ip = get_client_ip();
  
  error_log(time() . ' ' . __FILE__  . ' ' . $client_ip);

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<?php echo $google_stats; ?>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3wordid.com</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link rel="stylesheet" href="css/list.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
    body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 20px;
}

main {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh; /* Ensure content is vertically centered */
}

.content-box {
    max-width: 800px; /* Max width for desktop */
    width: 100%; /* Full width on smaller screens */
    padding: 20px;
    text-align: left; /* Left-align text */
    box-sizing: border-box; /* Include padding in width */
    background-color: #fff; /* Optional: white background for the box */
    border: 1px solid #ddd; /* Optional: subtle border */
    border-radius: 8px; /* Optional: rounded corners */
    margin: 0 auto; /* Center the box horizontally */
}

.content-box p, .content-box ul, .content-box blockquote {
    margin: 15px 0; /* Consistent spacing */
}

.content-box ul {
    padding-left: 20px; /* Indent list items */
}

.content-box img {
    display: block; /* Ensure images are block-level for proper spacing */
    margin: 10px auto; /* Center images */
}

.content-box a {
    color: #000;
    text-decoration: none;
}

.content-box a:hover {
    text-decoration: underline;
}

@media screen and (max-width: 600px) {
    body {
        padding: 10px; /* Reduced padding for mobile */
    }

    .content-box {
        max-width: 100%; /* Full width on mobile */
        padding: 15px; /* Slightly less padding */
        border-radius: 0; /* Optional: remove rounded corners for mobile */
    }

    .content-box img {
        max-width: 100%; /* Ensure images don't overflow */
        height: auto; /* Maintain aspect ratio */
    }

    .content-box p, .content-box ul, .content-box blockquote {
        font-size: 16px; /* Slightly smaller text for mobile readability */
    }
}
    </style>
</head>
<body>
    <header>
       <div class="top-right" id="userPortrait">
       <a href="login/login.php" class="login-icon" title="Log in">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Login Icon">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
        </svg>
    </a>
    <img id="userPortrait" style="display:none;" alt="User Portrait">
</div>
    </header>
   <main>
    <div class="content-box">
        <a href="index_2.php"><img width="100" src="img/3wid_big.png" alt="3WordID Logo"></a>
        <p><u><a href="https://3wordid.com/3wid_manual.php">Manual Page</a></u></p>
        <p><strong>You can become a reseller if you log in. More on marketing 3WordID <a href="https://x.com/climatebabes/status/1915321707327349114">here</a></strong></p>
        <p>3WordID.com stands for Three Word Identification, or identifying things with three words. For paying subscribers its three alphanumeric 'words'.<br><br>
		Its a web service, a search engine that provides a way to couple three words (or letter digit combinations) to lead the user to a weburl, webform or notification. This 
		can be usefull because its easier to remember three words. It is also usefull to not have to do SEO like with Google.<br><br> Google provides a bit of intelligence in its 
		responses, which is being replace by actual AI. Then if the SEO has worked google will guide people to pages that have been made relevant. From a merchant perspective 
		this is quite cumbersome, its easier if the merchant could share an exact phrase that people could use to find them or their product without wasting any time sifting 
		through a list of results.</p>
        <script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>
        <p>The 3WordID is being developed by <a href="https://x.com/climatebabes">the Roboeconomist (X.com)</a>.</p>
        <p>You can subscribe via our <a href="https://3wordid.com/3wid_signup.php"><u>Subscription page</u></a> here</p>
        <p>To reach us via email: <a href="mailto:3wordid@climatebabes.com">3wordid@climatebabes.com</a>.</p>
        <p><b>Examples of three word IDs (type in <a href="https://3wordid.com">search bar</a>)</b></p>
        <ul>
            <li><a href="https://3wordid.com/stock%20growth%20calculator">Stock Growth Calculator</a> (online stock price calculator page)</li>
            <li><a href="https://3wordid.com/prince%20of%20peace">Prince of Peace</a> (YouTube video)</li>
            <li><a href="https://3WordID.com/92.zd.ng">92.zd.ng</a> (My car license plate, leading to a message form)</li>
            <li><a href="https://3wordid.com/demo%20of%20notification">Demo of Notification</a> (small customizable text)</li>
            <li><a href="https://3wordid.com/blond%20cabin%20girl">Blond Cabin Girl</a> (online image)</li>
        </ul>
        <p><b>Icons to use</b><br>
            <img src="img/3wid_big.png" alt="3WordID Big Icon"><br>275x279<br>
            <img src="img/3wid_1.png" alt="3WordID Small Icon"><br>32x32
        </p>
        <p><a href="index.php">Back to Homepage</a></p>
    </div>
</main>
  <?php echo $footer; ?>
</body>
  
</html>

    
