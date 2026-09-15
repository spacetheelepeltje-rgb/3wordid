<?php

  require_once 'php/functions.php';
  require_once 'login/config.php';
  
  $time = substr(time(),-4);
  
  $client_ip = get_client_ip();
  
  error_log(time() . ' ' . __FILE__  . ' ' . $client_ip);
  
  $data = check_credentials($_SESSION,$_POST,$_GET);
  
  error_log('list data test ' . json_encode($data));

if($data == NULL) {
	header('location:index.php?message=Could not log you in');
	//error_log('list var data is null');
	die('had to go');
}  

$message ="";
if(isset($_GET["message"])) {
	$message=$_GET["message"];
}

$csrf_token = bin2hex(random_bytes(32));

$_SESSION['csrf_token'] = $csrf_token; 
$_SESSION['token'] = $data["token"];
 
$id =  $data['id'];
 
$max =   $data['max_3wids'] +1;
 
 if($id != NULL) {	
	db_3wordid_set_csrf_token($id, $csrf_token);
 } else {
	 
}
 
 $db_3wordid_list = db_3wordid_list($id);

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
    <script>
        function copyToClipboard(button) {
            // Find the hidden URL in the next sibling element
            const urlToCopy = button.nextElementSibling.textContent;

            // Use Clipboard API to copy the URL
            navigator.clipboard.writeText(urlToCopy)
                .then(() => {
                    //alert('URL copied to clipboard!');
                    // Visual feedback: briefly change icon color
                    const icon = button.querySelector('.copy-icon');
                    icon.style.fill = '#28a745';
                    setTimeout(() => {
                        icon.style.fill = '#333';
                    }, 1000);
                })
                .catch(err => {
                    console.error('Failed to copy: ', err);
                    //alert('Copy failed!');
                });
        }
     </script>   
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


.row.header {
  display: flex;
  border-bottom: 2px solid #ccc;
  padding-bottom: 5px;
  margin-bottom: 10px;
}

.avatar-header, .text-header, .icons-header {
  flex-shrink: 0;
  padding: 10px 0;
  font-weight: bold;
}

.avatar-header {
  width: 50px;
}

.text-header {
  flex: 1;
}

.icons-header {
  width: 60px;
  text-align: right;
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
        <a href="https://3wordid.com/3wid_signup.php">Subscribe for permanent 3WordIDs</a><br><br>
        <div id="helpertext"><strong><?php echo $message; ?></strong></div><br>
        <table>
           <thead>
                <tr>
                    <th>Three Word ID</th>
                    <th>Link</th>
                    <th>Actions 
                </th>
                </tr>
            </thead>
            <tbody>
				<?php
				
				$count_rows = 0;
				foreach($db_3wordid_list as $db_3wordid_item) {
					
					$message_count = db_3wordid_count_messages($db_3wordid_item['id']);
					
					//error_log($message_count['count'] . ' ' . json_encode($message_count));
					
					if($message_count['count'] == 0) {
							$state='disabled';
						} else {
							$state='';
						} 
		
					$count_rows++;		
					$url="https://3wordid.com/index.php?threewords=" . urlencode($db_3wordid_item['threeword']);
				?>
                <tr>
                    <td><a href="3wid_forward.php?threewords=<?php echo $db_3wordid_item['threeword'] ?>" class="<?php if($db_3wordid_item['enabled']!= 1) {echo  "grayed-out";} ?>" title="id : <?php echo $db_3wordid_item['id'] ?> views : <?php echo $db_3wordid_item['views']; ?>"><?php echo $db_3wordid_item['threeword'] ?></a></td>
                    <td>
                        <a  <?php echo $count_rows; ?> href="<?php echo $db_3wordid_item['linkthru']; ?> " class="link-icon" title="Visit Forward Site : <?php echo $db_3wordid_item['linkthru']; ?>">
                            <svg width="24" class="<?php if($db_3wordid_item['linkthruflag']!= 1) {echo  "disabled";} ?>" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Link Icon" >
                                <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                                <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                            </svg>
                        </a>
					 <button class="copy-button" onclick="copyToClipboard(this)" aria-label="Copy URL to clipboard" style="background-color: #fff; position: relative;">
						<svg class="xcopy-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
							<path d="M15 2H9a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V3a1 1 0 0 0-1-1z"/>
						</svg>
						<span class="tooltip">Copy URL for 3WordID : <?php echo htmlspecialchars($db_3wordid_item['threeword']); ?></span>
					</button>
					<span class="hidden-url"><?php echo htmlspecialchars($url); ?></span>				
                    </td>
                    <td>
                        <a href="3wid_3wid.php?id=<?php echo $db_3wordid_item['id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token'];?>" class="edit-icon" title="Edit Three Word ID" style="margin-right: 10px;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Edit Icon">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                        </a>
                      
                       <a  <?php echo $db_3wordid_item['enabled']; ?> href="3wid_toggle.php?id=<?php echo $db_3wordid_item['id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token'];?>" 
						   class="toggle-icon <?php echo $db_3wordid_item['enabled'] ? 'enabled' : 'disabled'; ?>" 
						   title="<?php echo $db_3wordid_item['enabled'] ? 'Disable, 3wid is currently enabled' : 'Enable, 3wid is currently disabled'; ?>" 
						   style="margin-right: 10px;">
							<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke-width="2" aria-label="Toggle Icon">
								<path d="M12 2v10"></path>
								<path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path>
							</svg>
						</a>
					
						<a   
						href="3wid_list_messages.php?wid_id=<?php echo $db_3wordid_item['id']; ?>&threeword=<?php echo $db_3wordid_item['threeword']; ?>" 
						class="mail-icon <?php echo $state; ?>" 
						title="<?php echo $message_count['count']; ?> messages for this 3WordID">
							<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Message Icon">
								<path d="M21 4H3a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h18a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2z"></path>
								<path d="M1 6l11 7 11-7"></path>
							</svg>
						</a>
						&nbsp;	
                        <a href="3wid_delete.php?id=<?php echo $db_3wordid_item['id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token'];?>" class="trash-icon" title="Delete Three Word ID">
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
                <tr>
                    <td>Create New</td>
                    <td>
                       
                    </td>
                    <td>
                        <?php
							if($max-$count_rows == 0) {
								$pointer = 'style="pointer-events: none;opacity: 0.5"';
								$title = "You need to add credit for more 3WordIDs";
								$table_text = "<b>Add credit for more 3WordIDs</b>";
							} else {	
								$pointer = '';
								$title = "Create Three Word ID (" . $max-$count_rows . " in total)";
								$table_text = "Credit";
							}
						?>
                        <a href="3wid_add.php?user_token=<?php echo $_SESSION["user_token"]; ?>" class="plus-icon" <?php echo $pointer; ?> title="Create new three word ID">  
                            <button>Create 3WordID</button>
                        </a>
                        
                    </td>
                </tr>
                <tr>
                    <td>Send Message (<a href="" title="You can send one message per recipient at a time">Rules</a>)</td>
                    <td>
                     
						
                    </td>
                    <td>
                       <a class="mail-icon" href="3wid_messageform.php?user_token=0" title="Send message to 3WordID">
							<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Message Icon">
								<path d="M21 4H3a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h18a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2z"></path>
								<path d="M1 6l11 7 11-7"></path>
							</svg>
						</a>
					
                        
                    </td>
                </tr>
                  <tr>
                    <td>Reseller code : <?php echo $data["id"] . substr(trim($data["email"]), -5); ?> (<a href="3wid_reseller.php">what's this</a>)</td>
                    <td>
                       <?php 
                        if($data['id']=='4') { ?>
							<a href="3wid_signup_list.php"> subscription list</a>
							<a href="3wid_user_list.php?csrf_token=<?php echo $_SESSION['csrf_token']; ?>"> user list</a>
							<?php
							
							}
                       ?></a><br>
                      
                    </td>
                    <td>
                       	<a href="3wid_credit.php" class="plus-icon" title="Add credit to extend your 3WordID 5 days grace period not included">
						  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Wallet Icon">
							<rect x="5" y="7" width="14" height="10" rx="2"></rect>
							<path d="M5 10H19"></path>
							<circle cx="16" cy="12" r="1"></circle>
						  </svg> Add Credit
					</a>
						
                    </td>
                </tr>
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
