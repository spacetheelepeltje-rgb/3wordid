<?php

  require_once 'php/functions.php';
  require_once 'login/config.php';
  
  $time = substr(time(),-4);
  
  $client_ip = get_client_ip();
  
  error_log(time() . ' ' . __FILE__  . ' ' . $client_ip);

 $data = check_credentials($_SESSION,$_POST,$_GET);
 /*
 if($data == NULL) {
	 
	 $data = check_session();
 
}
  
 if($data == NULL) { 
	 
	$data = check_csrf_token($csrf_token);

 } 
 */
 
if($data['id'] != 4) {

	header('location:index.php?message=Could not log you in');
	//error_log('list var data is null');
	die('had to go');
}

 $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

 $list = db_3wordid_get_signups(1);
 
 if($list == NULL) {

		$client_ip = get_client_ip();

		error_log('signup list null ' . time() . ' ' . __FILE__  . ' ' . $client_ip);
		error_log(json_encode($_POST));
		header('location:' . $main_url);
		die('invalid signup list request');
 }
 
 //error_log(json_encode($list));
?>   
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3wordid.com</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
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
        
        a.grayed-out {
			color: #000;
			text-decoration: none;
			opacity: 0.5; /* Makes it look faded */
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
        
       .copy-button {
        padding: 8px;
        background-color: #fff;
        cursor: pointer;
    }
    .xcopy-icon {
        width: 24px;
        height: 24px;
        stroke: #333;
    }
    .tooltip {
        display: none;
        position: absolute;
        top: -30px;
        left: 50%;
        transform: translateX(-50%);
        background-color: #ffffe0; /* Light yellow, like browser tooltips */
        color: #000;
        padding: 2px 6px;
        font-family: sans-serif;
        font-size: 12px;
        white-space: nowrap;
        border: 1px solid #ccc;
        z-index: 10;
    }
    .copy-button:hover .tooltip {
        display: block;
    }
    .hidden-url {
        display: none;
    }
    
    .plus-icon {
  display: inline-block;
  line-height: 24px; /* Match SVG height */
}

.plus-icon svg {
  vertical-align: middle;
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
        </div>
    </header>
    <main>    
		  <div class="logo">
			<center><a href='<?php echo $main_url;?>' title="to 3wordID.com"><img width=100 src="img/3wid_big.png"></a><br>   
            </center>  
        </div>
       
        <table>
           <thead>
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                    <th>Code</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
				<?php
				
				$count_rows = 0;
				foreach($list as $item) {
					
					$count_rows++;		
					$google_user = db_3wordid_get_google_email($item['email']);
					
					if ($google_user ==NULL) {
						$user_status ='not active';			
						} else {
						$user_status ='active';		
						}
				?>
                <tr>
                    <td><?php echo $item['id'] ;?></td>
                    <td><a href='3wid_edit_user.php?id=<?php 
                    if(isset($google_user['id'])) { 
						echo $google_user['id'];
						} else { 
						echo "";
						} 
						echo  "&csrf_token=" . $_SESSION['csrf_token'] . "' title='" . $item['message']  . "'>" .  $item['email'] ;?></a></td>
                    <td><?php echo $item['reseller_code'] ;?></td>
                    <td><a href="3wid_user_list.php?id=<?php 
                    if(isset($google_user['id'])) { 
						echo $google_user['id'];
						} else { 
						echo "";
						} 
						?>&csrf_token=<?php echo  $_SESSION['csrf_token'] ;?>"><?php echo $user_status; ?></a></td>
                </tr>
                <?php
				}
                ?>
               
            </tbody>
        </table>
    </main>
    <?php echo $footer; ?>
</body>
<script>
    function copyToClipboard(button) {
        const urlToCopy = button.nextElementSibling.textContent;
        navigator.clipboard.writeText(urlToCopy)
            .then(() => {
                //alert('URL copied!');
            })
            .catch(err => {
                console.error('Copy failed: ', err);
                //alert('Copy failed!');
            });
    }
</script>
 
</html>
