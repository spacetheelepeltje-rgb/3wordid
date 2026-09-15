<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<link rel="stylesheet" href="css/style.css">
    <title>3wordid.com</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h1><a href='index.php'>3wordid.com</a></h1>
        <section>
            Enter the three word id below
        </section>
        <section>
            <form method="post" action="search.php">  
                <div class="threefields">
                    <input type="text" id="myInput" name="xcom" placeholder="@x.com">   
                </div>
                <div class="threefields">
                   <input type="text" id="myInput" name="instagram" placeholder="instagram">   
                </div>
                <div class="threefields">
                    <input type="text" id="myInput" name="tiktok" placeholder="">   
                </div>  
				<div class="threefields">
                <input type="submit" value="Submit" name="Button">
                </div>
         
        </section>
              
               
            </form>
        </section>
    </div>
</body>
<script src="js/form_checks.js"></script>
</html>
