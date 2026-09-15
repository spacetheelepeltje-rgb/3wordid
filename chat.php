<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Existing head content -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3wordid.com</title>
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Profile Icon Styles */
        .profile-icon {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 1001; /* Ensure it's above other elements */
        }

        .profile-icon i {
            font-size: 30px;
            color: #333;
            cursor: pointer;
        }

        /* Profile Menu Styles */
        .profile-menu {
            position: absolute;
            top: 50px;
            right: 0;
            background-color: #fff;
            width: 150px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            display: none;
            z-index: 1001; /* Ensure it's above other elements */
        }

        .profile-menu ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .profile-menu ul li {
            border-bottom: 1px solid #eee;
        }

        .profile-menu ul li a {
            display: block;
            padding: 10px;
            color: #333;
            text-decoration: none;
        }

        .profile-menu ul li a:hover {
            background-color: #f5f5f5;
        }

        /* Existing CSS */
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            background-color: #f5f5f5;
        }

        .text-container {
            flex: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            text-align: center;
        }

        .text-container h1 {
            font-size: 24px;
            margin: 0;
            color: #333;
        }

        .text-container p {
            font-size: 18px;
            color: #555;
            margin: 5px 0 0 0;
        }

        .chat-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: flex-end; /* Align messages at the bottom */
            padding: 10px;
            overflow-y: auto;
        }

        .message {
            max-width: 70%;
            margin-top: 10px; /* Stack messages upwards */
            padding: 10px;
            border-radius: 15px;
            position: relative;
            word-wrap: break-word;
        }

        .message.user {
            align-self: flex-end;
            background-color: #dcf8c6; /* Light green */
            color: #000;
        }

        .message.bot {
            align-self: flex-start;
            background-color: #fff; /* White */
            color: #000;
        }

        .message.user::after {
            content: '';
            position: absolute;
            top: 0;
            right: -10px;
            width: 0;
            height: 0;
            border-left: 10px solid #dcf8c6;
            border-top: 10px solid transparent;
            border-bottom: 10px solid transparent;
        }

        .message.bot::after {
            content: '';
            position: absolute;
            top: 0;
            left: -10px;
            width: 0;
            height: 0;
            border-right: 10px solid #fff;
            border-top: 10px solid transparent;
            border-bottom: 10px solid transparent;
        }

        .chat-input-container {
            position: sticky;
            bottom: 0;
            background-color: #fff;
            padding: 10px;
            border-top: 1px solid #ccc;
            box-shadow: 0 -1px 5px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
        }

        .chat-input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 20px 0 0 20px;
            font-size: 16px;
            box-sizing: border-box;
            outline: none;
            border-right: none;
        }

        .chat-submit {
            padding: 10px 15px;
            background-color: #007bff;
            border: none;
            border-radius: 0 20px 20px 0;
            cursor: pointer;
            color: white;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .chat-submit i {
            margin-left: 5px;
        }

        @media (max-width: 600px) {
            .chat-input-container {
                padding: 8px;
            }

            .chat-input {
                padding: 8px;
                font-size: 14px;
            }

            .chat-submit {
                padding: 8px 12px;
                font-size: 14px;
            }

            .text-container h1 {
                font-size: 20px;
            }

            .text-container p {
                font-size: 16px;
            }

            .profile-icon i {
                font-size: 24px;
            }
        }

        /* Styles for Contacts Icon */
        .contacts-icon {
            position: fixed;
            top: 10px;
            left: 10px;
            font-size: 24px;
            cursor: pointer;
            z-index: 1001;
        }

        /* Styles for the Sidebar */
        .sidebar {
            height: 100%;
            width: 250px;
            position: fixed;
            top: 0;
            left: -250px; /* Hidden by default */
            background-color: #111;
            overflow-x: hidden;
            transition: 0.3s;
            z-index: 1000;
        }

        .sidebar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background-color: #111;
            color: #fff;
        }

        .sidebar-header h2 {
            margin: 0;
            font-size: 24px;
        }

        .close-btn {
            font-size: 30px;
            cursor: pointer;
        }

        .contact-list {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }

        .contact-list li {
            padding: 8px 16px;
        }

        .contact-list li a {
            color: #ccc;
            text-decoration: none;
            display: block;
        }

        .contact-list li a:hover {
            color: #fff;
            background-color: #575757;
        }

        /* Overlay to dim the rest of the page when sidebar is open */
        .overlay {
            position: fixed;
            display: none;
            top: 0;
            left: 0;
            height: 100%;
            width: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 999;
        }

        /* Adjust main content when sidebar is open (optional) */
        body.sidebar-open {
            overflow: hidden; /* Prevent scrolling when sidebar is open */
        }
    </style>
</head>
<body>

    <!-- Contacts Icon (Top Left Corner) -->
    <div class="contacts-icon" id="contacts-icon">
        &#9776; <!-- Hamburger menu icon -->
    </div>

    <!-- Left Side Pull-Out Contact List -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>Contacts</h2>
            <span class="close-btn" id="close-btn">&times;</span>
        </div>
        <ul class="contact-list">
            <!-- Example contacts as links with short names -->
            <li><a href="#">John</a></li>
            <li><a href="#">Jane</a></li>
            <li><a href="#">Bob</a></li>
            <!-- Add more contacts as needed -->
        </ul>
    </div>

    <!-- Overlay to cover the rest of the page when sidebar is open -->
    <div class="overlay" id="overlay"></div>

    <!-- Profile Icon (Top Right Corner) -->
    <div class="profile-icon" onclick="toggleProfileMenu()">
        <i class="fas fa-user-circle"></i>
        <!-- Profile Menu -->
        <div id="profile-menu" class="profile-menu">
            <ul>
                <li><a href="#">My Account</a></li>
                <li><a href="#">Settings</a></li>
                <li><a href="#">Logout</a></li>
            </ul>
        </div>
    </div>

    <!-- Existing content -->
    <div class="text-container">
        <h1><a href='index.php'>3wordid.com</a></h1>
        <p>Search for a three-word ID below<br>
        <a href='chat_about.php'><h2>How does this work?</h2></a></p>
    </div>

    <div class="chat-container" id="chat-container">
        <!-- Messages will appear here -->
    </div>

    <div class="chat-input-container">
        <input type="text" class="chat-input" placeholder="Type your message..." id="chat-input">
        <button class="chat-submit" id="chat-submit">
            <span>Send</span>
            <i>&#10148;</i>
        </button>
    </div>

    <!-- JavaScript code -->
    <script>
        // JavaScript for the sidebar (pull-out contact list)
        const sidebar = document.getElementById('sidebar');
        const contactsIcon = document.getElementById('contacts-icon');
        const closeBtn = document.getElementById('close-btn');
        const overlay = document.getElementById('overlay');

        // Function to open the sidebar
        function openSidebar() {
            sidebar.style.left = '0';
            overlay.style.display = 'block';
            document.body.classList.add('sidebar-open');
        }

        // Function to close the sidebar
        function closeSidebar() {
            sidebar.style.left = '-250px';
            overlay.style.display = 'none';
            document.body.classList.remove('sidebar-open');
        }

        // Event listeners
        contactsIcon.addEventListener('click', openSidebar);
        closeBtn.addEventListener('click', closeSidebar);
        overlay.addEventListener('click', closeSidebar);

        // Handling contact selection
        const contactLinks = document.querySelectorAll('.contact-list li a');
        const chatContainer = document.getElementById('chat-container');
        const chatInput = document.getElementById('chat-input');

        let currentChatContact = null;

        contactLinks.forEach(function(link) {
            link.addEventListener('click', function(event) {
                event.preventDefault(); // Prevent default link behavior

                // Get the contact name
                const contactName = this.textContent;

                // Close the sidebar
                closeSidebar();

                // Start chat with the selected contact
                startChatWithContact(contactName);
            });
        });

        function startChatWithContact(contactName) {
            // Clear the chat container
            chatContainer.innerHTML = '';

            // Set the current chat contact
            currentChatContact = contactName;

            // Display a welcome message in the chat
            const botMessage = document.createElement('div');
            botMessage.classList.add('message', 'bot');
            botMessage.textContent = 'You are now chatting with ' + contactName;
            chatContainer.appendChild(botMessage);

            // Update the chat input placeholder
            chatInput.placeholder = 'Message ' + contactName + '...';

            // Scroll to the bottom of the chat container
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        // Update the existing event listener for the submit button
        const chatSubmit = document.getElementById('chat-submit');

        chatSubmit.addEventListener('click', function() {
            const userInput = chatInput.value.trim();
            if (userInput !== '') {
                if (currentChatContact === null) {
                    alert('Please select a contact to chat with.');
                    return;
                }
                // Create user message balloon
                const userMessage = document.createElement('div');
                userMessage.classList.add('message', 'user');
                userMessage.textContent = userInput;
                chatContainer.appendChild(userMessage);

                // Simulate bot response
                const botMessage = document.createElement('div');
                botMessage.classList.add('message', 'bot');
                botMessage.textContent = currentChatContact + ' received your message.';
                chatContainer.appendChild(botMessage);

                // Clear the input field
                chatInput.value = '';

                // Scroll to the bottom of the chat container
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }
        });

        // Allow pressing Enter key to submit
        chatInput.addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                chatSubmit.click();
            }
        });

        // Toggle Profile Menu
        function toggleProfileMenu() {
            var menu = document.getElementById('profile-menu');
            if (menu.style.display === 'none' || menu.style.display === '') {
                menu.style.display = 'block';
            } else {
                menu.style.display = 'none';
            }
        }
    </script>

</body>
</html>
