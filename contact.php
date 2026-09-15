<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3wordid.com</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Poppins', sans-serif;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        background: linear-gradient(to right, #00c6ff, #0072ff);
    }

    .container {
        text-align: center;
        background: rgba(255, 255, 255, 0.9);
        padding: 40px;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    h1 {
        font-size: 3em;
        margin-bottom: 20px;
        color: #333;
    }

    section {
        font-size: 1.2em;
        margin-bottom: 20px;
        color: #333;
    }

    .threefields {
        display: flex;
        justify-content: space-between;
        margin-bottom: 20px;
    }

    .threefields input[type="text"] {
        width: 30%;
        padding: 15px;
        font-size: 1.2em;
        border: 2px solid #0072ff;
        border-radius: 5px;
        outline: none;
        transition: all 0.3s ease;
    }

    .threefields input[type="text"]:focus {
        border-color: #00c6ff;
        box-shadow: 0 0 10px rgba(0, 198, 255, 0.5);
    }

    input[type="submit"] {
        width: 100%;
        padding: 15px;
        font-size: 1.2em;
        border: 2px solid #0072ff;
        border-radius: 5px;
        outline: none;
        transition: all 0.3s ease;
    }

    input[type="submit"]:hover {
        background-color: #0072ff;
        color: #fff;
    }
    
    #myInput {
	  width: 100%;
	  box-sizing: border-box; /* to include padding and border in the width calculation */
	  padding: 10px;
	}
    </style>
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
                    <input type="text" id="myInput" name="whatsapp" placeholder="whatsapp">   
                </div>
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
</html>
