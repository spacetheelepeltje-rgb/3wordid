<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3WordID Description</title>
    <link rel="stylesheet" href="css/list.css">
    <style>
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            overflow: hidden; /* Prevent overflow */
            box-sizing: border-box;
        }
        .intro h1 {
            color: #333;
            font-size: 2em;
        }
        .list-view ul {
            list-style-type: disc;
            margin-left: 20px;
        }
        /* Responsive image styles */
        .image-container {
            width: 100%;
            text-align: center;
            margin: 20px 0;
        }
        .image-container img {
            max-width: 100%; /* Ensure image doesn't exceed container */
            height: auto; /* Maintain aspect ratio */
            display: block;
            margin: 0 auto; /* Center the image */
            box-sizing: border-box;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Introduction Section -->
        <section class="intro">
            <h1>3WordID.com Overview</h1>
            <p><strong>3WordID.com</strong> stands for <em>Three Word Identification</em>, a system for identifying things with three words. For paying subscribers, these can be three alphanumeric "words."</p>
            <p>I am building a web service, a search engine that couples three words (or letter-digit combinations) to lead users to a web URL, web form, or notification. This is useful because three words are easier to remember than complex URLs. It also eliminates the need for SEO, unlike Google.</p>
            <p>Google provides intelligent responses, increasingly replaced by AI, and guides users to SEO-optimized pages. However, for merchants, this is cumbersome. With 3WordID, merchants can share an exact phrase for users to find them or their products instantly, without sifting through search results.</p>
        </section>
        <div class="image-container">
            <img src="man_home_page.jpg" alt="3WordID Homepage">
        </div>
        <!-- Anonymity and Connection Section -->
        <section class="anonymity">
            <h2>Anonymity and Connection</h2>
            <p>Another inspiration for <strong>3WordID.com</strong> is to enable connection without revealing identities. For example, you can share a phrase like "dude with duffelbag" written on clothing or a label. When someone enters it on the site, they access a message form to leave a message. Logged-in users can communicate directly via 3WordID.com without revealing personal details, except for optional portraits.</p>
        </section>

        <!-- Alphanumeric Use Cases Section -->
        <section class="alphanumeric">
            <h2>Alphanumeric Use Cases</h2>
            <p>For subscribers, three alphanumeric "words" are allowed, enabling unique applications like using a car license plate as a 3WordID. People can contact you via your license plate without needing printed vehicle signage. This is valuable for businesses, car tours, or sports teams (e.g., "Chicago Bulls nr12").</p>
        </section>

        <!-- How It Works Section -->
        <section class="how-it-works">
            <h2>How It Works</h2>
            
            <!-- Homepage Subsection -->
            <div class="subsection homepage">
                <h3>3WordID.com Homepage</h3>
                <p>When you see or hear a 3WordID (e.g., "the weimar republic" indicating someone’s location), visit <strong>3WordID.com</strong> and type it in. If the creator set it as a link, it resolves to a URL like <a href="https://3wordid.com/the.weimar.republic">3wordid.com/the.weimar.republic</a>. This human-readable URL format simplifies sharing in messages and aids SEO.</p>
            </div>

            <!-- List View Subsection -->
            <div class="subsection list-view">
                <h3>List View of 3WordID.com</h3>
                <p>After logging in (currently via Google), you access the list view, displaying your created 3WordIDs and management options:</p>
                <ul>
                    <li><strong>Left Side:</strong> Shows the three words and dotted version (e.g., the.weimar.republic). If set as a link, a link icon and copy link icon appear.</li>
                    <li><strong>Right Side:</strong> Management options include:
                        <ul>
                            <li><strong>Notepad:</strong> Edit all 3WordID details (except the words themselves).</li>
                            <li><strong>Toggle Switch:</strong> Enable/disable the 3WordID to control visibility.</li>
                            <li><strong>Envelope:</strong> Access the message box tied to the 3WordID, where external users or other 3WordID users can send messages if configured.</li>
                            <li><strong>Trash Can:</strong> Delete the 3WordID (moved to a deleted table in the database for recovery; changes are logged for security).</li>
                        </ul>
                    </li>
                    <li><strong>Message Button:</strong> Send messages to other 3WordIDs when logged in.</li>
                </ul>
            </div>
            <div class="image-container">
                <img src="man_list_view_1.jpg" alt="List View 1">
            </div>
            <!-- Bottom of List View Subsection -->
            <div class="subsection bottom-list-view">
                <h3>Bottom of List View</h3>
                <p>At the bottom, a reseller code is displayed. Resellers (subscribers) can share this code to earn money (currently $ per 20 signups). After signup, the code links the new user to the reseller. The cost is $1.50 per 3WordID per month, based on allowed 3WordIDs, not active or created ones.</p>
            </div>
            <div class="image-container">
                <img src="man_list_view_2.jpg" alt="List View 2">
            </div>
            <!-- Creation Form Subsection -->
            <div class="subsection creation-form">
                <h3>3WordID Creation Form</h3>
                <p>Click "Create 3WordID" in the list view to access the creation form. Enter your desired 3WordID, with immediate feedback on availability. The submit button turns red if the 3WordID exists. Non-subscribers cannot create alphanumeric 3WordIDs.</p>
                <p>Options for a 3WordID include:</p>
                <ul>
                    <li><strong>Leave a Notification:</strong> Add a message (filtered HTML tags allowed: <code>p, br, a[href], strong, em, ul, ol, li, div, span, h1, h2, h3, h4, h5, h6</code>). Users typing the 3WordID see this notification.</li>
                    <li><strong>Link to a URL:</strong> Enter a URL and check "Use forward URL." Users are redirected to this URL when entering the 3WordID or dotted link.</li>
                    <li><strong>Link to a Message Form:</strong> Check "Link 3WordID to message form" to direct users to a web form (external or internal). Messages appear in your list view’s envelope.</li>
                </ul>
                <p>After submission, the 3WordID appears in your list view. It’s inactive until you toggle it on (red when inactive). Edit it anytime via the notepad icon.</p>
            </div>
            <div class="image-container">
                <img src="man_create_3wordid.jpg" alt="Create 3WordID Form">
            </div>
            <!-- Edit 3WordID Subsection -->
            <div class="subsection edit-3wordid">
                <h3>Edit Your 3WordID</h3>
                <p>Click the notepad icon to edit a 3WordID. Beyond the creation form fields, you’ll see a hash (e.g., <code>f34cf88ad3e7f5f0c056e770576ca728</code>) for linking without revealing the 3WordID, usable in URLs like <a href="https://3wordid.com/f34cf88ad3e7f5f0c056e770576ca728">3wordid.com/f34cf88ad3e7f5f0c056e770576ca728</a>. This aids integration with other sites. A "Get Hash" API is planned.</p>
                <p>QR code links are also provided, combining human-readable text with standard QR functionality.</p>
            </div>
            <div class="image-container">
                <img src="man_edit_3wordid_screen.jpg" alt="Edit 3WordID Screen">
            </div>
        </section>
    </div>
</body>
</html>
