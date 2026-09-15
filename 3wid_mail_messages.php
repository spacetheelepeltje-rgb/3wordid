<?php

  require_once 'php/functions.php';
  require_once 'login/config.php';
  
  error_log('mail messages page');

$data = check_auth();
 
$wid_id =0;
 
 if($data == NULL) { 
	// header('location:' . $main_url);
 }
 
 if(isset($_GET["wid_id"])) {
	$wid_id = $_GET["wid_id"];
 } else {
	//header('location:' . $main_url);
 }
 
 
 $db_3wordid_messages = db_3wordid_messages($wid_id);


?>   
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3wordid.com</title>
    <link rel="icon" type="image/png" href="3wid_1.png">
    <link rel="stylesheet" href="css/styles_2.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
         body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        
        header {
            position: relative;
            margin-bottom: 20px;
        }
        
        .top-right {
            position: absolute;
            top: 0;
            right: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: #f5f5f5;
        }
        
        .icon {
            display: inline-block;
            vertical-align: middle;
        }
        
        a {
            color: #000;
            text-decoration: none;
        }
        
        footer {
            margin-top: 20px;
            text-align: center;
        }
        
        .footer-links a {
            margin: 0 10px;
        }
        
        .toggle-icon.enabled svg {
            stroke: black;
        }

        .toggle-icon.disabled svg {
            stroke: red;
        }
        
         .mail-icon.disabled svg {
            stroke: gray;
        }

        /* Mobile-specific styles */
        @media screen and (max-width: 600px) {
            body {
                padding: 10px;
            }
            
            .top-right {
                right: 10px;
            }
            
            td:nth-child(3) {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                justify-content: space-between;
            }
            
            td:nth-child(3) a {
                flex: 0 0 45%; /* Two icons per row with some spacing */
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="top-right">
            <a href="login/logout.php" class="logout-icon" title="log out <?php echo $data['email']; ?>">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Logout Icon">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
            </a>
            <img id="userPortrait" style="display:none;" alt="User Portrait">
        </div>
    </header>
    <main>    
		  <div class="logo">
			<center><a href='<?php echo $main_url;?>'><img width=100 src="img/3wid_big.png"></a><br>   
            </center>  
        </div>
        <table>
           <thead>
                <tr>
                    <th>Sender</th>            
					<th>Message</th>
					<th>Actions</th>
                </tr>
            </thead>
            <tbody>
				<?php
				$count_rows = 0;
				foreach($db_3wordid_messages as $db_3wordid_item) {
					$count_rows++;
					
				?>
                <tr>
                    <td><a href="mailto:<?php echo $db_3wordid_item['email'] ?>"><?php echo $db_3wordid_item['email'] ?></a></td>
                    <td><?php echo $db_3wordid_item['title'] ?><br><br><?php echo $db_3wordid_item['message'] ?></td>    
                    <td>
						<a href="3wid_mail_delete.php?id=<?php echo $db_3wordid_item['id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token'];?>" class="trash-icon" title="Delete this message">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Delete Icon">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                        </a>
                    </td>
                </tr>
                
                <?php
				}
                ?>
            </tbody>
        </table>
    </main>
    <?php echo $footer; ?>
</body>
</html>
