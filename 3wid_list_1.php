<?php

  require_once 'php/functions.php';
  require_once 'login/config.php';
  
  error_log('list page');
  
  if(isset($_SESSION['user_token'])) {
  
  // checking if user is already exists in database
  $sql = "SELECT * FROM google_users WHERE token ='{$_SESSION['user_token']}'";
  $result = mysqli_query($conn, $sql);
	  if (mysqli_num_rows($result) > 0) {
		// user is exists
		$userinfo = mysqli_fetch_assoc($result);
	  }

 } else {
	 
	header('location:' . $main_url); 
	
 }
 

 
 $db_3wordid_list = db_3wordid_list($userinfo['id']);
 
 $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
 
 $data = check_auth();
 
 if($data == NULL) { 
	 header('location:' . $main_url);
	 } 

 db_3wordid_get($data['id']);

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
            <a href="login/logout.php" class="logout-icon">
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
                    <th>Three Word ID</th>
                    <th>Link</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach($db_3wordid_list as $db_3wordid_item) {
                ?>
                <tr>
                    <td><?php echo $db_3wordid_item['threeword'] ?> (<a href="" title="<?php echo $db_3wordid_item['notification']; ?>">?</a>)</td>
                    <td>
                        <a href="<?php echo $db_3wordid_item['linkthru']; ?> " class="link-icon" title="Visit Forward Site : <?php echo $db_3wordid_item['linkthru']; ?>">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Link Icon" <?php if($db_3wordid_item['linkthruflag']==0) {echo  "disable";} ?>>
                                <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                                <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                            </svg>
                        </a>
                    </td>
                    <td>
                        <a href="3wid_3wid.php?id=<?php echo $db_3wordid_item['id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token'];?>" class="edit-icon" title="Edit Three Word ID" style="margin-right: 10px;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Edit Icon">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                        </a>
                      
                       <a <?php echo $db_3wordid_item['enabled']; ?> href="3wid_toggle.php?id=<?php echo $db_3wordid_item['id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token'];?>" 
                           class="toggle-icon <?php echo $db_3wordid_item['enabled'] ? 'enabled' : 'disabled'; ?>" 
                           title="<?php echo $db_3wordid_item['enabled'] ? 'Disable, 3wid is currently enabled' : 'Enable, 3wid is currently disabled'; ?>" 
                           style="margin-right: 10px;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke-width="2" aria-label="Toggle Icon">
                                <path d="M12 2v10"></path>
                                <path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path>
                            </svg>
                        </a>
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
                    <td></td>
                    <td>
                        <a href="3wid_add.php" class="plus-icon" title="Create new Three Word ID">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Plus Icon">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                        </a>
                    </td>
                </tr>
            </tbody>
        </table>
    </main>
    <footer>
        <div class="footer-links">
            <a href="#">About</a>
            <a href="#">Privacy</a>
            <a href="#">Terms</a>
        </div>
    </footer>
</body>
</html>
