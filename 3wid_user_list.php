<?php

  require_once 'php/functions.php';
  require_once 'login/config.php';
  
  $time = substr(time(),-4);
  
  $client_ip = get_client_ip();
  
  error_log(time() . ' ' . __FILE__  . ' ' . $client_ip);

  $data = check_credentials($_SESSION,$_POST,$_GET);
  
  error_log('list users ' . json_encode($data));
 
 
 if(isset($_POST["threeword"])) {
	 
	$threeword = $_POST["threeword"];

	$row = db_3wordid_get_threeword($threeword);
	
	error_log($threeword . ' ' . json_encode($row));
	
	if($row  != NULL)  {
		header('location:3wid_edit_user.php?id=' . $row["user_id"]);
		}
		
  } 
 
 $page =0;
 $order='DESC';
 
 if(isset($_GET["page"])) {
	 $page = (int) $_GET["page"];
	 $order = (int) $_GET["order"];
	 if($order==1) $order='ASC';
	 if($order==0) $order='DESC';
 } else {
 
 $baseurl = $queryString = http_build_query($_GET);
 
 }
 
 $google_users = $db_3wordid_list = db_3wordid_get_google_users($page,50,$order);

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
       
    </header>
    <main>    
		  <div class="logo">
			<center><a href='<?php echo $main_url;?>' title="to 3wordID.com"><img width=100 src="img/3wid_big.png"></a><br>   
            </center>  
        </div>
        <form method='post' name='search' action='3wid_user_list.php'>
			<input type='text' name='threeword'>
			<input type='submit'>
			<input type='hidden' name='csrf_token' value='<?php echo $_GET['csrf_token']; ?>'>
		</form>	
        <table>
     
            <tbody>
				<?php
				
				$count_rows = 0;
				foreach($google_users as $google_user) {
					$count_rows++;		
					
				?>
                <tr>
					<?php 
					foreach($google_user as $fieldname=>$fieldvalue) {
						
						if(in_array($fieldname,['id'])) {
						?>
						<td><?php echo $fieldname ?></td><td><?php echo "<a href='3wid_edit_user.php?id=" . $fieldvalue . "'>" . $fieldvalue . "</a><a href='3wid_user_3wid_list.php?id=" . $fieldvalue . "'> 3WIDS</a></td>"; ?>
						<?php 
						}
						
						if(in_array($fieldname,['created_at','first_name','last_name','email','status'])) {
						?>
						<td><?php echo $fieldname ?></td><td><?php echo $fieldvalue ?></td>
						<?php 
						}
					}
					?>
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
