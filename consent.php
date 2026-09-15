<!DOCTYPE html>
<html lang="en">
<head>
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
        align-items: center;
        margin-bottom: 20px;
    }

    .threefields input[type="text"] {
        width: 70%;
        padding: 15px;
        font-size: 1.2em;
        border: 2px solid #0072ff;
        border-radius: 5px;
        outline: none;
        transition: all 0.3s ease;
        margin-left: 10px;
    }

    .threefields input[type="text"]:focus {
        border-color: #00c6ff;
        box-shadow: 0 0 10px rgba(0, 198, 255, 0.5);
    }

    input[type="checkbox"] {
        margin-right: 10px;
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
    
     #myCheckbox {
            width: 20px;
            height: 20px;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-color: #fff;
            border: 2px solid #000;
            cursor: pointer;
            display: inline-block;
            position: relative;
        }

        /* Custom checkmark when the checkbox is checked */
        #myCheckbox:checked {
            background-color: #fff;
        }

        /* Add the checkmark */
        #myCheckbox:checked::after {
            content: '';
            position: absolute;
            top: 2px;
            left: 6px;
            width: 5px;
            height: 10px;
            border: solid #000;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }
    
    #myInput {
	  width: 100%;
	  box-sizing: border-box; /* to include padding and border in the width calculation */
	  padding: 10px;
	}
	
	   #helperText {
        font-size: 14px;
        padding: 15px;
        color: gray;
        margin-top: 5px; /* Adds some space above the text */
    }
	
	    </style>
</head>
<body>
    <div class="container">
        <h1><a href='index.php'>3wordid.com</a></h1>
        <section>
            <p>
            By providing your email address and phone number, you consent to us storing this information in our secure 
            database. We will use this data to [describe the purpose, e.g., send you updates, provide customer support, 
            notify you of special offers, etc.]. We value your privacy and will not share your information with third 
            parties without your explicit consent. You can withdraw your consent at any time by deleting your data.
            </p>
          
          
            </form>
        </section>
    </div>

</body>
<script src="js/form_checks.js"></script>
</html>
