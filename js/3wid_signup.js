
console.log('loading script');

const emailRegex = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
const threeword_regex = /^[a-zA-Z]+$/;

function validateEmail(email) {
	return emailRegex.test(String(email).toLowerCase());
}



$(document).ready(function() {
	
	
	

// Attach event listener to the form
    $('#3widSignupForm').on('submit', function(event) {
		event.preventDefault();
		
        $('#helperText').html('Processing');

		let send = 1;
        // Grab form data
        let form = $(this);
        let email = $('#email').val();

		console.log(terms);
     
        if(email.trim() =='') {
			 $('#helperText').html('Please provide your email (Google login)');
				  send=0;
				  return;
			}

		if(!validateEmail(email)) {
					$('#helperText').html('Please provide a valid email (Google login)');
				  send=0;
				  return;
			}
			
		if ($("#terms").is(":checked")) {
			//console.log("Checkbox is checked");
		} else {
			$('#helperText').html('Please agree with the terms (checkbox)');
			send=0;
			return;
		}	

        if(send==1) { 
			// Use jQuery to submit the form, respecting the event handler
			form.off('submit'); // Remove this handler to avoid infinite loop
			form.submit(); // Trigger jQuery's submit, which sends the form
		} else {
			$('#helperText').html('Send failed');
		}
    });
	
	
});
		
