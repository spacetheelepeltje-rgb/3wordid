<!DOCTYPE html>
<html>
<head>
    <title>Dark Gray Background</title>
    <style>
        body {
            background-color: #333333;
        }
    </style>
    <style>
  .image-container {
    background-color: #FFFFFF; /* White background */
    width: 300px; /* Adjust width as needed */
    height: 200px; /* Adjust height as needed */
  }
</style>
</head>
<body>
<?php



$files = scandir('.');

foreach($files as $file) {
	
	if($file!='.' && $file!='..') {
		
		echo "<a href='https://3wordid.com/logos/" . $file . "'><img width=200 src='https://3wordid.com/logos/" . $file . "'></a>";
		
		}
	
	
	}
	
$files = scandir('../logos_1');

foreach($files as $file) {
	
	if($file!='.' && $file!='..') {
		
		echo "<div class='image-container'><a href='https://3wordid.com/logos_1/" . $file . "'><img width=200 src='https://3wordid.com/logos_1/" . $file . "'></a></div>";
		
		}
	
	
	}	
	
	
?>
</body>
</html>
