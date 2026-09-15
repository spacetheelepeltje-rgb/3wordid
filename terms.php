<?php
require_once 'php/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3wordid.com - Terms and Information</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link rel="stylesheet" href="css/styles_2.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Inline CSS to ensure consistency with signup page */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
            color: #333;
            line-height: 1.6;
        }
        header {
            background-color: #fff;
            padding: 10px 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        .top-right {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
        }
        .login-icon svg {
            color: #007bff;
            transition: color 0.2s;
        }
        .login-icon svg:hover {
            color: #0056b3;
        }
        #userPortrait {
            border-radius: 50%;
            width: 32px;
            height: 32px;
            object-fit: cover;
        }
        main {
            max-width: 600px;
            margin: 40px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .search-container {
            text-align: center;
        }
        .search-container img {
            margin-bottom: 20px;
        }
        .search-container p {
            margin: 10px 0;
            font-size: 16px;
        }
        .search-container ul {
            text-align: left;
            padding-left: 20px;
            margin: 20px 0;
        }
        .search-container ul li {
            margin-bottom: 10px;
        }
        .search-container a {
            color: #007bff;
            text-decoration: none;
        }
        .search-container a:hover {
            text-decoration: underline;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            font-size: 16px;
        }
        footer {
            text-align: center;
            padding: 20px;
            background-color: #fff;
            border-top: 1px solid #ddd;
            margin-top: 40px;
        }
        @media (max-width: 600px) {
            main {
                margin: 20px;
                padding: 15px;
            }
            .search-container p, .search-container ul li {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="top-right">
            <a href="login/login.php" class="login-icon" title="Log in">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Login Icon">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </a>
            <img id="userPortrait" src="" style="display:none;" alt="User Portrait">
        </div>
    </header>
    <main>
        <div class="search-container">
            <a href="index_2.php"><img width="100" src="img/3wid_big.png" alt="3wordID Logo"></a>
            <p><strong>If you don't subscribe your 3WordID will expire or can be taken by another user.</strong></p>
            <ul>
                <li>Future localization of 3WordID may be implemented by country and city.</li>
                <li>The subscription grants the right to create 3WordIDs.</li>
                <li>For larger numbers of 3WordIDs, contact us via email.</li>
                <li>For integrations or special connections to your systems, make a request.</li>
                <li>By using this site, you agree to us storing Google-supplied data and any data you submit.</li>
                <li>We use Google Auth and encrypt your email, we cannot use it to send you emails.</li>               
                <li>No rights can be derived from using this website.</li> 
                <li>We may alter these terms at any time and will notify you on login.</li>
                <li>We reserve the right to delete, revoke, or repurpose your 3WordID without explanation.</li>
            </ul>
            <p>Contact us at: <a href="mailto:3wordid@climatebabes.com">3wordid@climatebabes.com</a></p>
            <a href="index.php" class="back-link">Back to Homepage</a>
        </div>
    </main>
    <?php echo $footer; ?>
</body>
</html>
