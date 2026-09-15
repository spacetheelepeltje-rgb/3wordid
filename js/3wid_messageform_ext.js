
const emailRegex = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

    
function validateEmail(email) {
	return emailRegex.test(String(email).toLowerCase());
}

function containsDigitsOrSpecialCharacters(str) {
    return /\d/.test(str) || /[^a-zA-Z0-9]/.test(str);
}	

function validateURL(url) {
    url = url.replace(/^http:\/\//, 'https://');
    if (!url.match(/^https:\/\//)) url = 'https://' + url;
    try {
        const parsed = new URL(url);
        return parsed.href;
    } catch {
        return false;
    }
}

$(document).ready(function() {

    // Attach event listener to the form
    $('#3widForm').on('submit', function(event) {

		send=0;
		
		$('#helperText').html('Clicked submit');
		
        // Log to confirm the handler is firing
        console.log('Form submit handler triggered');

        // Prevent default submission
        event.preventDefault();
        send = 1;
        
        //console.log('Default submission prevented');

        // Grab form data
        let form = $(this);
        let threeword = $('#threeword').val();
        let title = $('#title').val();
        let message = $('#message').val();
        
        if(title.trim()=='') {
			$('#helperText').html('Please provide a message title');
			send=0;
			return;
		}
        
         if(message.trim()=='') {
			$('#helperText').html('Please provide a message');
			send=0;
			return;
		}
        
        if(threeword.trim()=='') {
			$('#helperText').html('Please provide an 3WordID');
			send=0;
			return;
		}

        // If you reach here, email is valid
        console.log('send is ' +  send);
        // Add your next steps here (e.g., AJAX call)
        
        if(send==1) { 
			window.location.href = '3wid_messageform_ext_process.php'; // Replace with the URL of the page you want to redirect to
			// Use jQuery to submit the form, respecting the event handler
			form.off('submit'); // Remove this handler to avoid infinite loop
			form.submit(); // Trigger jQuery's submit, which sends the form
		}
    });
	
	
});
		
