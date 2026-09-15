
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

// verify threeword ID before submit
$('#threeword').on('keyup', function() {

              threewords = $('#threeword').val();
              let nr_words = threewords.trim().split(/\s+/).length;
              let threewords_array = threewords.trim().split(/\s+/);
              
              console.log(threewords)
              
              invalid = 0;
              
              if(nr_words == 3) {
				  
				  
					  $.ajax({
						type: 'POST',
						url: '3wid_exists.php', // Replace with your server endpoint
						data: {
							'threewords': threewords
						},
						success: function(response) {
							//console.log('Post successful!');
							// Here you might want to clear the input, show a success message, or update the UI to show the new post
							
							//console.log(response); // Log the server response
							if(response =='exists') {
								$('#helperText').html('recipient exists');
								} else {
								$('#helperText').html('invalid recipient');	
								}
						},
						error: function(xhr, status, error) {
							//console.error('An error occurred:', error);
							// Handle errors appropriately, maybe show user an error message
						}
					});
				
				  
				  for (let word of threewords_array) {
					if(containsDigitsOrSpecialCharacters(word)) {
						invalid = invalid + 1;  
					}
					//console.log(word + " " + containsDigitsOrSpecialCharacters(word));
				  }
				  
				  
				  if(invalid == 0) {
					
					$("#threeword").css("color", "green");
							 
				  } else {
				
				  //console.log('error in one of the three words');
					$("#threeword").css("color", "#000");
					$('#helperText').html('3wid invalid');
				 
				  }
				  } else {
				 
					$("#threeword").css("color", "#000");
				    $('#helperText').html('3wid invalid');
				  }
                
       }); 
	

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
        
        console.log('Default submission prevented');

        // Grab form data
        let form = $(this);
        let terms = $('#terms').prop('checked');
        let email = $('#email').val();
        let title = $('#title').val();
        let mailmessage = $('#mailmessage').val();
        
        console.log('email ' + email);
        console.log('terms ' + terms);
        
        if(title.trim()=='') {
			$('#helperText').html('Please provide a message title');
			send=0;
			return;
		}
        
         if(mailmessage.trim()=='') {
			$('#helperText').html('Please provide a message');
			send=0;
			return;
		}
        
        if(email.trim()=='') {
			$('#helperText').html('Please provide an email address');
			send=0;
			return;
		}
		
		if(! validateEmail(email)) {
			$('#helperText').html('The email address is invalid');
			send=0;
			return;
			}
			
		if(!terms) {
			$('#helperText').html('Please accept the terms by checking the box');
			send=0;
			return;
			}	

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
		
