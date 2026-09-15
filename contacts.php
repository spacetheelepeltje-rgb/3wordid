<!DOCTYPE html>
<html lang="en">
<head>
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<link rel="stylesheet" href="css/style.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3wordid.com</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

</head>
<body>
<?


?>	
	
    <div class="container">
        <h1><a href='index.php'>3wordid.com</a></h1>
        <h2><? $treewords?></h2>h2>  
        <section>
            <form method="post" id="myForm">
				 <div class="threefields">
                   <textarea type="text" row="5" columns="40" id="myMessage" name="message">Write your general message here</textarea>   
                </div>
                <div class="threefields">
                    <input type="checkbox" name="xcom" id="myCheckbox"><input type="text" id="myX" name="xcom" placeholder="@x.com">   
                </div>
                <div class="threefields">
                    <input type="checkbox" name="instagram" id="myCheckbox"><input type="text" id="myInstagram" name="instagram" placeholder="instagram">   
                </div>
                <div class="threefields">
                    <input type="checkbox" name="tiktok" id="myCheckbox"><input type="text" id="myTiktok" name="tiktok" placeholder="tiktok">   
                </div>
                <div class="threefields">
                    <input type="checkbox" name="policy" id="myCheckbox">
                    <label for="privacy">I agree to the <a href=''>Privacy Policy</a></label>
                </div>
                <div class="threefields">
                    <input type="submit" value="Create" name="Button">
                </div>
            </form>
        </section>
    </div>

</body>
<script src="js/form_checks.js"></script>
</html>
