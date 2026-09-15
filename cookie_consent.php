<!-- HTML for the cookie popup -->
<div id="cookiePopup" class="cookie-consent">
    <p>We use cookies to improve your experience. By continuing, you agree to our use of cookies.</p>
    <button id="acceptCookies">Accept</button><br>
    <button id="declineCookies">Decline</button>
</div>

<style>
/* Basic CSS styling */
.cookie-consent {
    position: fixed;
    bottom: 20px;
    left: 20px;
    right: 20px;
    background: #fff;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0,0,0,0.2);
    display: none;
    z-index: 1000;
}

.cookie-consent.show {
    display: block;
}
</style>

<script>
// Function to set a cookie
function setCookie(name, value, days) {
    let expires = "";
    if (days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "") + expires + "; path=/";
}

// Function to get a cookie
function getCookie(name) {
    const nameEQ = name + "=";
    const ca = document.cookie.split(';');
    for(let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) === ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
}

// Cookie consent logic
document.addEventListener('DOMContentLoaded', function() {
    const popup = document.getElementById('cookiePopup');
    const acceptBtn = document.getElementById('acceptCookies');
    const declineBtn = document.getElementById('declineCookies');

    // Check if user has already made a choice
    if (!getCookie('cookieConsent')) {
        popup.classList.add('show');
    }

    // Handle Accept button click
    acceptBtn.addEventListener('click', function() {
        setCookie('cookieConsent', 'accepted', 365); // Store for 1 year
        popup.classList.remove('show');
        
        // Place your cookie-dependent code here
        // For example, enable analytics:
        // startAnalytics();
        // loadTrackingScripts();
    });

    // Handle Decline button click
    declineBtn.addEventListener('click', function() {
        setCookie('cookieConsent', 'declined', 365);
        popup.classList.remove('show');
        
        // Optionally clear any existing cookies
        // document.cookie = "trackingCookie=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
    });
});
</script>
