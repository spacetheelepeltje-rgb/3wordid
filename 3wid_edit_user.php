<?php

  require_once 'login/config.php';
  require_once 'php/functions.php';
 
  if(isset($_GET['csrf_token'])) { 
	$csrf_token = $_GET['csrf_token'];
	if($_SESSION['csrf_token'] != $csrf_token) {
	header('location:' . $main_url);
	}
 } 

 if(isset($_GET["id"])) {
	$id = $_GET["id"];
 } else {
	//header('location:' . $main_url);
 }
 
 $row = db_3wordid_get_google_user($id);
 
 if(isset($_GET['token'])) {
	 
	$max_3wids = (int) $_GET['max_3wids'];
	$user_type = (int) $_GET['user_type'];
	$credit = floatval($_GET['credit']);

	db_3wid_update_credit_usertype($id, $credit, $max_3wids, $user_type);

    $row = db_3wordid_get_google_user($id);
}
   $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

?>  
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Page - 3WordID.com</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link rel="stylesheet" href="css/styles_3.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <header>
		
    </header>
    <main>
        <div class="logo">
            <center>
                <a href='<?php echo $main_url;?>'><img width=100 src="img/3wid_big.png"></a><br>         
            </center>  
        </div>
        <div class="search-container">
			<a href="3wid_edit_user.php?id=<?php echo $row['id']; ?>">clean</a><br><br> <a href="3wid_user_list.php">user list</a><br><br>
            <form id="user_update" action="3wid_edit_user.php" method="GET">
				
				<?php 
				foreach($row as $row_item => $row_value) {
					
						echo $row_item . ' : <input type="text" style="margin-top: 10px;" class="search-bar" name="' . $row_item . '" value="' . $row_value . '"><br>';
					
					
						if($row_item=='picture') {
							echo "<img style='margin-top: 10px;' src='" . $row_value . "' ><br>";
							}
					
					}
		        ?>
                <div class="buttons" style="display: flex; align-items: center;">
                    <button type="submit" id="submitBtn">Update</button>
                </div>
                <input type='hidden' name='csrf_token' value='<?php echo $_SESSION['csrf_token']; ?>'>     
            </form>
        </div>
    </main>
    <?php echo $footer; ?>
</body>
<script src="js/3wid_3wid.js"></script>

</html>
