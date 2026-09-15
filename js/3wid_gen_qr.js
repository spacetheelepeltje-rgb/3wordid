
console.log('loading script');

const emailRegex = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
const threeword_regex = /^[a-zA-Z]+$/;

function validateEmail(email) {
	return emailRegex.test(String(email).toLowerCase());
}

 function containsDigitsOrSpecialCharacters(str) {
    return /\d/.test(str) || /[^a-zA-Z0-9]/.test(str);
}	

function validateURL(url) {
	return true;
	}

function validate_threeword(threewords) {
	
	
		console.log('validate');
        if (threewords.trim() === '' ) { 
            return false;
        }
        
        var words = threewords.trim().split(/\s+/);

        if (words.length !== 3) { 
			
			// Set to grey
			$('#submitBtn').css('background-color', 'grey');
			$('#submitBtn').css('color', 'white');

			return false;
		}	
		
		words.forEach(function(word) {
			
			if (!word.match(threeword_regex)) {
				return false;	
			}	
	    });
	    
	    console.log(threeword + ' is valid');
	    
	    return true;

}


$(document).ready(function() {
	
	
	$('#threeword').on('keyup', function() {
    let response = 'null'; // Use let instead of implicit global variable
    
    console.log('keyup');
    
    let threewords = $('#threeword').val();
    let threewords_array = threewords.trim().split(/\s+/);
    let nr_words = threewords_array.length;
    
    for (let word of threewords_array) {
		if(containsDigitsOrSpecialCharacters(word)) {
			 $('#helperText').html('The 3WordID can only contain a-z and A-Z characters');
			 return;
		}
					
	}

    console.log(threewords);
    
    if (validate_threeword(threewords)) { // Fixed variable name from 'threeword' to 'threewords'
        console.log('is valid');
        
        if (nr_words == 3) {
            // Define an async function to handle the AJAX call
            const checkThreeWord = async () => {
                try {
                    response = await $.ajax({
                        type: 'POST',
                        url: '3wid_exists.php',
                        data: {
                            'threewords': threewords
                        }
                    });
                    
                    // This code runs after the AJAX call completes
                    if (response == 'exists') {
                        console.log('exists');
                        $('#helperText').html('3 word id exists');
                        
                        $('#submitBtn').css('background-color', 'red');
						$('#submitBtn').css('color', 'white'); // Optional for text
                    } else {
                        console.log('new');
                        $('#helperText').html('3 word id is new');
                        // Set to green
						$('#submitBtn').css('background-color', 'green');
						$('#submitBtn').css('color', 'white');
                    }
                    
                    console.log('response:' + response); // Now response has the AJAX value
                } catch (error) {
                    console.error('An error occurred:', error);
                }
                
                return response;
            };
            
            // Call the async function
            response = checkThreeWord();
        } else {
			// Set to grey
			$('#submitBtn').css('background-color', 'grey');
			$('#submitBtn').css('color', 'white');
		}
		
		
    }



    
});

// Attach event listener to the form
    $('#3widForm').on('submit', function(event) {
		event.preventDefault();
        $('#helperText').html('Clicked submit');
		
        // Log to confirm the handler is firing
        console.log('Form submit handler triggered');
  
        send = 1;
        
        console.log('Default submission prevented');

        // Grab form data
        let form = $(this);
        let threeword = $('#threeword').val();
        let threewords_array = threeword.trim().split(/\s+/);
	    let nr_words = threewords_array.length;
			  
		for (let word of threewords_array) {
					if(containsDigitsOrSpecialCharacters(word)) {
						send = 0;
						$('#helperText').html('The words can only a-z and A-Z characters');
			}
					
		}
        
        let linkthru = $('#linkthru').val();
        let linkthruflag = document.getElementById('linkthruflag').checked
        let email = '';// $('#email').val();
        let emailform = false ; //document.getElementById('emailform').checked
        let notification = $('#notification').val();
        
        console.log('threeword is ' + threeword);
        console.log('email is ' + email);
        console.log('emailform is ' + emailform);
        console.log('linkthruflag is ' + linkthruflag);
        console.log('linkthru is ' + linkthru);
        console.log('notification is ' + notification);
        
        if(threeword.trim() =='') {
			 $('#helperText').html('Please provide a 3WordID');
				  send=0;
				  return;
			}

        if(linkthruflag == true) {

			if(!validateURL(linkthru)) {
				 $('#helperText').html('URL missing or invalid');
				  send=0;
				  return;
				 
			}
				
		}
		
		if(nr_words != 3) {
				  $('#helperText').html('Maximum is three words');
				  send=0;
				  return;
			}
			
		if(emailform==true) {
			if(!validateEmail(email)) {
				$('#helperText').html('Please provide a valid email');
				 send=0;
				 return;
			}
		}

		if(linkthruflag == false  && emailform==false && notification.trim() === '') {
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
		
