	
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

        // Validate that there are exactly three words

	}
    // index page

 $(document).ready(function() {

	$('#textSearch').on('keyup', function() {

              threewords = $('#textSearch').val();
              let nr_words = threewords.trim().split(/\s+/).length;
              let threewords_array = threewords.trim().split(/\s+/);
              
              console.log(threewords)
              
              invalid = 0;
              
              if(nr_words == 3) {
				  
				  
					  $.ajax({
						type: 'POST',
						url: 'check_3wordid_exists.php', // Replace with your server endpoint
						data: {
							'threewords': threewords
						},
						success: function(response) {
							//console.log('Post successful!');
							// Here you might want to clear the input, show a success message, or update the UI to show the new post
							
							//console.log(response); // Log the server response
							if(response =='exists') {
								$('#helperText').html('3wid exists');
								} else {
								$('#helperText').html('3wid is new');	
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
					
					$("#textSearch").css("color", "green");
							 
				  } else {
				
				  //console.log('error in one of the three words');
					$("#textSearch").css("color", "#000");
					$('#helperText').html('3wid invalid');
				 
				  }
				  } else {
				 
					$("#textSearch").css("color", "#000");
				    $('#helperText').html('3wid invalid');
				  }
                
       }); 
	

     $('#submitBtn').on('click', function(event) {

        console.log("index submit");
 
        event.preventDefault(); // Prevent the default form submission
        
        // Get the input values
        threewords = $('#textSearch').val();
        csrf_token = $("#csrf_token").val();
 
		console.log(threewords);

        // Basic validation (can be more complex depending on requirements)
        if (threewords.trim() === '' ) {
            
            return false;
        }
        
        // Regular expression to match words (letters only)
        var regex = /^[a-zA-Z]+$/;

        // Split the input value by spaces to get the words
        var words = threewords.trim().split(/\s+/);

        // Validate that there are exactly three words
        if (words.length !== 3) {
			$('p#helperText').html("The input must consist <br>of exactly three words");
			$('p#helperText').css('color', 'red');
            //alert('The input must consist of exactly three words.');
            $('#myInput').focus(); // Set focus back to the input field
            return false;
        }
        

        // Validate each word to make sure it contains only letters
        for (var i = 0; i < words.length; i++) {
            if (!regex.test(words[i])) {
				$('p#helperText').html('Each word must contain only letters (no digits or special characters).');
			    $('p#helperText').css('color', 'red');
                //alert('Each word must contain only letters (no digits or special characters).');
                $("myThreeWordID").focus(); // Set focus back to the input field
                return false;
            }
        }
        //console.log("before ajax");
        //console.log(threewords);
        //console.log(csrf_token);
        sleep(200);

        // If all validations pass, proceed with form submission or further actions
        // alert('Input is valid. Proceeding with form submission.');
        $('p#helperText').html('Valid 3wordid');
		$('p#helperText').css('color', 'green');
			// Send the data to the backend using AJAX
		//return true;	
			
        $.ajax({
            url: 'check.php',  // Replace with your backend URL
            type: 'POST',
            data: {
                threewords: threewords,
                csrf_token:csrf_token
            },
            success: function(response) {		
				//console.log("token "+ csrf_token +" >>" + response + "<<");
				
                var jsonObject = JSON.parse(response);
                
				//console.log(jsonObject);
				//console.log(jsonObject.status);

				if (jsonObject.status === 'ok') {
					
					if(jsonObject.exists == 'known'){
						
					//console.log(nolinkthru);	
						
					//console.log('forward to public notification');
                    window.location.href = '3wid_forward.php?threewords=' + threewords; // Replace with the URL of the page you want to redirect to

					} else {
						
					//console.log('forward to create');
                    window.location.href = 'login/login.php'; // Replace with the URL of the page you want to redirect to
                   

			       }
					
                } else {

					window.location.href = 'indes_2.php'; // Replace with the URL of the page you want to redirect to
				
                }
				
                // Handle success - response contains data from the backend
                // alert('Form submitted successfully!');
                //console.log(response);
            },
            error: function(xhr, status, error) {
                // Handle error
                //alert('There was an error submitting the form.');
                //console.log(error);
            }
        });
    });   
    
    $('#formSearch').trigger('keyup');  
});
	
