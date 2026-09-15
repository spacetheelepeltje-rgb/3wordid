
const emailRegex = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

    
function validateEmail(email) {
	return emailRegex.test(String(email).toLowerCase());
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
		
		$('#helperText').html('Clicked submit');
		
        // Log to confirm the handler is firing
        console.log('Form submit handler triggered');

        // Prevent default submission
        event.preventDefault();
        send = 1;
        
        console.log('Default submission prevented');

        // Grab form data
        let form = $(this);
        let threeword = $('#threeword').val();
        
        let linkthru = $('#linkthru').val();
        let linkthruflag = document.getElementById('linkthruflag').checked
        let email = $('#email').val();
        let emailform = false; //document.getElementById('emailform').checked
        let notification = $('#notification').val();
        
        console.log('email is ' + email);
        console.log('emailform is ' + emailform);
        console.log('linkthruflag is ' + linkthruflag);
        console.log('linkthru is ' + linkthru);
        console.log('notification is ' + notification);

        if(linkthruflag == true) {

			if(!validateURL(linkthru)) {
				 $('#helperText').html('URL missing or invalid');
				  send=0;
				 
			}

		}
			
		if(emailform==true) {
			if(!validateEmail(email)) {
				$('#helperText').html('Please provide a valid email');
				 send=0;
			}
		}

		if(linkthruflag == false  && emailform==false && notification == '') {
			$('#helperText').html('Please at least enter text in the notification box');
			send=0;
		}

        // Log form data for debugging
        console.log('Form data:', { threeword, email, linkthru, emailform });


        // If you reach here, email is valid
        console.log('send is ' +  send);
        // Add your next steps here (e.g., AJAX call)
        
        if(send==1) { 
			// Use jQuery to submit the form, respecting the event handler
			form.off('submit'); // Remove this handler to avoid infinite loop
			form.submit(); // Trigger jQuery's submit, which sends the form
		}
    });
	
	
});
		
