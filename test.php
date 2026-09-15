<?php 
 include 'php/functions.php';
 
 $db = DB_connect();

 DB_insert_3wordid($db,123,'three word id','quite a long text but short in this case','https://www.3wordid.com',1,'frits@rincker.nl');
