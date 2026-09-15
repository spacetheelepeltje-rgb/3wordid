<?php

  require_once 'php/functions.php';
  require_once 'login/config.php';
   
  $client_ip = get_client_ip();
  
  error_log(' ip ' . $client_ip . ' top 10 page');
  
  // Generate Google Login URL
  $loginUrl = $client->createAuthUrl();

  $db_3wordid_list = db_3wordid_list_top_10();

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
       <div class="top-right" id="userContainer">
      
            <a href="<?php echo $loginUrl; ?>" class="login-icon" title="Log in (not all functions work on mobile devices)">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Login Icon">
				<path d="M15 21h4a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2h-4"></path>
				<polyline points="8 7 13 12 8 17"></polyline>
				<line x1="13" y1="12" x2="1" y2="12"></line>
			  </svg>
            </a>
       
		</div>
    </header>
    <main>    
		  <div class="logo">
			<center><a href='<?php echo $main_url;?>'><img width=100 src="img/3wid_big.png"></a><br> 
			<strong>Top 10 most popular 3WordIDs</strong>
            </center>  
        </div>
        <table>
		   <a href="<?php echo $loginUrl; ?>"><button type="button" id="createBtn" >Create you own</button></a><br>
           <thead>
                <tr>
                    <th>Three Word ID</th>
                    <th>Link</th>
                   
                </tr>
            </thead>
            <tbody>
				<?php
				$count_rows = 0;
				foreach($db_3wordid_list as $db_3wordid_item) {
					$count_rows++;
					
				?>
                <tr>
                    <td><a href="<?php echo $db_3wordid_item['threeword'] . '" title="'. $db_3wordid_item['id'] . ' ' . htmlspecialchars($db_3wordid_item['notification']) . ' views : ' . $db_3wordid_item['views'] . '">' . $db_3wordid_item['threeword']; ?></a></td>
                    <td>
                        <a <?php echo $count_rows; ?> href="<?php echo $db_3wordid_item['linkthru']; ?> " class="link-icon" title="Visit Forward Site : <?php echo $db_3wordid_item['linkthru']; ?>">
                            <svg width="24" class="<?php if($db_3wordid_item['linkthruflag']!= 1) {echo  "disabled";} ?>" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Link Icon" >
                                <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                                <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
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
