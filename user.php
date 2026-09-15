Here's a webpage with form fields (textarea, text, and checkboxes) styled similarly to your provided example:
html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Page - 3WordID.com</title>
    <link rel="icon" type="image/png" href="3wid_1.png">
    <link rel="stylesheet" href="css/styles_2.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <header>
        <div class="top-right" id="userPortrait">
            <a href="#" class="login-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Login Icon">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </a>
            <img id="userPortrait" style="display:none;" alt="User Portrait">
        </div>
    </header>
    <main>
        <div class="logo">
            <center>
                <a href='index_2.php'><img width=100 src="img/3wid_big.png"></a><br>   
                <h1>3WordID.com</h1><br>
                <div id="helperText">Fill out the form below</div><br>
            </center>  
        </div>
        <div class="search-container">
            <form id="sampleForm" action="/submit" method="post">
                <textarea name="description" class="search-bar" placeholder="Enter your description here" rows="4"></textarea>
                <input type="text" name="title" class="search-bar" placeholder="Enter title" style="margin-top: 10px;">
                
                <div class="checkbox-group" style="margin: 20px 0;">
                    <label><input type="checkbox" name="options" value="option1"> Option 1</label>
                    <label><input type="checkbox" name="options" value="option2"> Option 2</label>
                    <label><input type="checkbox" name="options" value="option3"> Option 3</label>
                </div>
                
                <div class="buttons">
                    <button type="submit" id="submitBtn">Submit</button>
                </div>
            </form>
        </div>
    </main>
    <footer>
        <div class="footer-links">
            <a href="#">About</a>
            <a href="#">Privacy</a>
            <a href="#">Terms</a>
        </div>
    </footer>
</body>
<script src="js/form_checks_index_2.js"></script>
<script>
    function handleGoogleLogin() {
        let userImageURL = 'path/to/user/image.jpg';
        if (userImageURL) {
            document.getElementById('userPortrait').src = userImageURL;
            document.getElementById('userPortrait').style.display = 'block';
        }
    }

    document.querySelector('.login-icon').addEventListener('click', function(e) {
        e.preventDefault();
        handleGoogleLogin();
        setTimeout(() => {
            window.location.href = '/login/index.php';
        }, 1000);
    });
</script>
</html>
Key changes and additions:
Added a <form> element wrapping the new input fields
Included a <textarea> for multi-line text input
Added a text <input> field for a title
Created a checkbox group with three sample options
Kept the same styling classes (search-bar, buttons) to maintain consistency with your original design
Added a submit button styled like the original search button
Maintained the same header, logo section, and footer structure
Added some basic inline styling for spacing (you might want to move this to your CSS file)
This assumes your existing styles_2.css has appropriate styling for:
.search-container
.search-bar
.buttons
button elements
If you need to adjust the styling, you might want to add these CSS rules to your styles_2.css:
css
.checkbox-group {
    text-align: left;
    padding: 0 10px;
}

.checkbox-group label {
    display: block;
    margin: 5px 0;
    cursor: pointer;
}

textarea.search-bar {
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    padding: 10px;
}
The form will work with your existing CSS and JavaScript structure while adding the requested form elements. The action attribute in the form (/submit) is a placeholder - you'll need to update it to point to your actual form processing endpoint.
