	
 function containsDigitsOrSpecialCharacters(str) {
    return /\d/.test(str) || /[^a-zA-Z0-9]/.test(str);
}	

function sleep(miliseconds) {
   var currentTime = new Date().getTime();

   while (currentTime + miliseconds >= new Date().getTime()) {
   }
}

function validate_threeword(threeword) {

		// not empty
        if (threewords.trim() === '' ) {
            
            return false;
        }
        
        // Regular expression to match words (letters only)
        var regex = /^[a-zA-Z]+$/;

        // Split the input value by spaces to get the words
        var words = threewords.trim().split(/\s+/);
        
        
        if(words.length != 3) {
			return false;    
	    }

        // Validate that there are exactly three words
		return true;
	}
    // index page

 $(document).ready(function() {

	$('#threewords').on('keyup', function() {

              threewords = $('#threewords').val();
              
              threewords = threewords.trim();

              let threewords_array = threewords.trim().split(/\s+/);
			  let nr_words = threewords_array.length;
			  
			   for (let word of threewords_array) {
					if(containsDigitsOrSpecialCharacters(word)) {
						return;
					}
					
				}
              
              console.log(threewords)
              
             
              
              if(nr_words == 3) {
					  console.log('ajax call');
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
								$("#helperText").html("3wid exists");
								$("#threewords").css("color", "green");
							} else {
								if(response =='disabled') {
									$("#helperText").html("3wid disabled");
									$("#threewords").css("color", "orange");
								} else {
									$('#helperText').html("3wid does not exist log in to create");	
								}
							}
						},
						error: function(xhr, status, error) {
							//console.error('An error occurred:', error);
							// Handle errors appropriately, maybe show user an error message
						}
					});

				  } 
                
       }); 
	

   $('#submitBtn').on('click', function(event) {
    event.preventDefault(); // Prevent the default button action

    console.log("index submit");

    // Get the form element
    let form = $(this).closest('form');
    let threewords = $('#threewords').val();

    console.log(threewords);

    // Add validation logic if needed
    let send = 1; // Replace with actual validation if desired

		if (send === 1) {
			console.log('to 3wid_forward.php');
			// Submit the form programmatically
			form.off('submit'); // Remove any existing submit handlers to prevent loops
			form.submit(); // Trigger the form submission
		}
	});
	
	 $(document).on('click', '#createBtn', function(event) {
    event.preventDefault(); // Prevent default behavior (e.g., form submission)
    var shareUrl = $(this).data('share-url');
    
    // Log for debugging
    console.log('Redirecting to:', shareUrl);
    
    // Validate URL
    if (shareUrl && typeof shareUrl === 'string' && shareUrl.trim() !== '') {
      try {
        // Ensure URL is properly encoded
        window.location.href = shareUrl;
      } catch (e) {
        console.error('Redirect failed:', e);
      }
    } else {
      console.error('Invalid or empty URL:', shareUrl);
    }
  });
    
  
});
	
