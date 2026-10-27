// Texts for the current language come from window.T3 (set by the page, see php/i18n.php).
var tr = window.tr || function (key, fallback) {
    return (window.T3 && window.T3[key]) || fallback;
};


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
		
        $('#helperText').text(tr('processing', 'Processing'));

		let send = 1;
        // Grab form data
        let form = $(this);
        let email = $('#email').val();

		console.log(terms);
     
        if(email.trim() =='') {
			 $('#helperText').text(tr('su_need_email', 'Please provide your email (Google login)'));
				  send=0;
				  return;
			}

		if(!validateEmail(email)) {
					$('#helperText').text(tr('su_bad_email', 'Please provide a valid email (Google login)'));
				  send=0;
				  return;
			}
			
		if ($("#terms").is(":checked")) {
			//console.log("Checkbox is checked");
		} else {
			$('#helperText').text(tr('su_need_terms', 'Please agree with the terms (checkbox)'));
			send=0;
			return;
		}	

        if(send==1) { 
			// Use jQuery to submit the form, respecting the event handler
			form.off('submit'); // Remove this handler to avoid infinite loop
			form.submit(); // Trigger jQuery's submit, which sends the form
		} else {
			$('#helperText').text(tr('su_failed', 'Send failed'));
		}
    });
	
	
});
		
