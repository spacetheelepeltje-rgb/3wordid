<?php

  require_once 'php/functions.php';
  require_once 'login/config.php';
  
  $time = substr(time(),-4);
  
  $client_ip = get_client_ip();
  
  error_log(time() . ' ' . __FILE__  . ' ' . $client_ip);
 
 if(isset($_GET['csrf_token'])) { 
	$csrf_token = $_GET['csrf_token'];
	if($_SESSION['csrf_token'] != $csrf_token) {
	header('location:' . $main_url);
	}
 } 
 
 if(isset($_GET['id'])) {
	 
	 $id=$_GET['id'];
	 $id = (int) $id;
	 }
 
 
 
 $db_3wordid_list = db_3wordid_list($id);

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

</style>
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
					$count_rows++;		
					$url="https://3wordid.com/index.php?threewords=" . urlencode($db_3wordid_item['threeword']);
				?>
                <tr>
                    <td><a href="3wid_forward.php?threewords=<?php echo $db_3wordid_item['threeword'] ?>" class="<?php if($db_3wordid_item['enabled']!= 1) {echo  "grayed-out";} ?>" title="<?php echo $db_3wordid_item['id'] ?>"><?php echo $db_3wordid_item['threeword'] ?></a></td>
                    <td>
                        <a  <?php echo $count_rows; ?> href="<?php echo $db_3wordid_item['linkthru']; ?> " class="link-icon" title="Visit Forward Site : <?php echo $db_3wordid_item['linkthru']; ?>">
                            <svg width="24" class="<?php if($db_3wordid_item['linkthruflag']!= 1) {echo  "disabled";} ?>" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Link Icon" >
                                <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                                <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                            </svg>
                        </a>
                    </td>
                    <td>
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

            </tbody>
        </table>
    </main>
    <?php echo $footer; ?>
</body>

 
</html>
