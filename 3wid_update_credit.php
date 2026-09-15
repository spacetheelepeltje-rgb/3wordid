<?php
  require_once 'php/functions.php';
  require_once 'login/config.php';
  
  $return_url = "";
  $user_id = 0;
  $amount  =0;
  
  file_put_contents(CREDITLOG,'updating credit ' . time() . ' ' . json_encode($_GET) . '\n' , FILE_APPEND | LOCK_EX);
  
  if(isset($_GET["user_id"])) {
  	  $user_id=$_GET["user_id"];
  }
  if(isset($_GET["amount"])) {
  	  $amount=$_GET["amount"];
  }
  if($user_id==0 || $amount==0) {
	header('location:' . $return_url);
  }
  
  db_3wid_update_credit($user_id, $amount);
  
  $row = db_3wid_get_credit($user_id);
  
  echo json_encode($row) . "<br>";
  
  $amount = $row['credit'];
  
  $sum_to_add = 10;
  
  $new_amount = $sum_to_add + $amount;
  
  db_3wid_update_credit($user_id, $new_amount);
  
  $row = db_3wid_get_credit($user_id);
  
  echo json_encode($row) . "<br>";
  
  
  //error_log('update credit for user ' . $user_id . ' to ' . $amount);

?>
