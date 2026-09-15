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

/* Default table styling for larger screens */
table {
    width: 100%;
    border-collapse: collapse;
}

tr {
    border-bottom: 1px solid #ddd; /* Light grey line */
}

/* Ensure cells have some padding */
td {
    padding: 10px;
    vertical-align: middle;
}

/* Mobile-specific styling */
@media (max-width: 600px) {
    table, tbody, tr, td {
        display: block;
        width: 100%;
    }

    tr {
        display: flex;
        align-items: center; /* Vertically align items */
        justify-content: space-between; /* Distribute space */
        padding: 10px 0;
        border-bottom: 1px solid #ddd; /* Continuous light grey line */
        min-height: 60px; /* Consistent height */
        box-sizing: border-box;
    }

    td {
        padding: 0;
        margin: 0;
        flex: 0 0 auto; /* Prevent stretching */
    }

    /* Image cell */
    td:first-child {
        flex: 0 0 50px; /* Fixed width for image */
        margin-right: 10px;
    }

    td:first-child img {
        width: 50px;
        height: 50px;
        object-fit: cover; /* Ensure image fits nicely */
        border-radius: 5px;
    }

    /* Title cell */
    td:nth-child(2) {
        flex: 1; /* Take remaining space */
        text-align: left;
        padding-right: 10px;
        white-space: nowrap; /* Prevent title from wrapping */
        overflow: hidden; /* Hide overflow */
        text-overflow: ellipsis; /* Add ellipsis if title is too long */
    }

    td:nth-child(2) a {
        font-size: 16px;
        text-decoration: none;
        color: #333;
        display: inline-block;
        width: 100%;
    }

    /* Icons cell */
    td:last-child {
        flex: 0 0 auto;
        display: flex;
        gap: 10px; /* Space between icons */
        align-items: center;
    }

    td:last-child a svg {
        width: 24px;
        height: 24px;
    }

    /* Styling for the "Send Message" row */
    tr:last-child {
        justify-content: space-between;
        align-items: center;
    }

    tr:last-child td:first-child {
        flex: 1;
        font-size: 14px;
        white-space: nowrap; /* Prevent wrapping */
    }

    tr:last-child td:nth-child(2) {
        flex: none; /* Remove extra space */
    }

    tr:last-child td:last-child {
        flex: 0 0 auto;
    }
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
        <table>
           
            <tbody>
				<?php
				
				$count_rows = 0;
				foreach($messages as $message) {
					$count_rows++;		
					//echo json_encode($message);
				?>
                <tr>
                    <td><img src="<?php echo $message['picture']; ?>"></td>
                    <td>
                     <a href="3wid_message_view.php?id=<?php echo $message['id']; ?>" title="<?php echo $message['message']; ?>"><?php echo $message['title']; ?></a>
						
                    </td>
                    <td>
						<a class="mail-icon" disabled  href="" title="Answer to this message">
							<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Message Icon">
								<path d="M21 4H3a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h18a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2z"></path>
								<path d="M1 6l11 7 11-7"></path>
							</svg>
						</a>
						&nbsp;	
                        <a href="3wid_message_delete.php?id=<?php echo $message['id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token'];?>" class="trash-icon" title="Delete this message">
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
                    <td>Send Message (<a href="" title="You can send one message per recipient at a time">Rules</a>)</td>
                    <td>
                     
						
                    </td>
                    <td>
                       <a class="mail-icon" href="3wid_messageform.php?user_token=<?php echo $_SESSION["user_token"]; ?>" title="Send message to 3WordID">
							<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Message Icon">
								<path d="M21 4H3a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h18a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2z"></path>
								<path d="M1 6l11 7 11-7"></path>
							</svg>
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
