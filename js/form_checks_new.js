	
 function containsDigitsOrSpecialCharacters(str) {
    return /\d/.test(str) || /[^a-zA-Z0-9]/.test(str);
}	

// create page
	
 $(document).ready(function() {
	 
	  //$('p#helperText').html("Use only a-z<br> or A-Z");
      $('p#helperText').css('color', 'gray');

	  //$('#submitBtn').attr('disabled', true); 
	  
	  function checkInput() {
		  
		  
                var inputVal = $('#myInput').val().trim();
                if (inputVal.length > 0) {
                    $('#submitBtn').attr('disabled', false);  // Enable the button
                } else {
                    $('#submitBtn').attr('disabled', true);   // Disable the button
                }
            }
	 
	  $('#myInput').on('keyup', function() {
		  
                checkInput();
                
       }); 
	 
    $('#FormCreate').on('submit', function(event) {
		
        event.preventDefault(); // Prevent the default form submission

        // Get the input values
        threewords = $("#myThreeWordID").val();
        csrf_token = $("#csrf_token").val();

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

        // If all validations pass, proceed with form submission or further actions
        // alert('Input is valid. Proceeding with form submission.');
        //$('p#helperText').html('Valid 3wordid');
		$('p#helperText').css('color', 'green');
			// Send the data to the backend using AJAX
        $.ajax({
            url: 'check.php',  // Replace with your backend URL
            type: 'POST',
            data: {
                 threewords: threewords,
                 csrf_token:csrf_token
            },
            success: function(response) {
                var jsonObject = JSON.parse(response);  
				console.log(jsonObject);
				console.log(jsonObject.status);

				if (jsonObject.status === 'ok') {
                    window.location.href = 'contact_notification.php?threewords=' + threewords; // Replace with the URL of the page you want to redirect to
                    $('p#helperText').css('color', 'green');
                } else {
                    $('p#helperText').html(jsonObject.message);
					$('p#helperText').css('color', 'red');
                }
				
                // Handle success - response contains data from the backend
                // alert('Form submitted successfully!');
                console.log(response);
            },
            error: function(xhr, status, error) {
                // Handle error
                //alert('There was an error submitting the form.');
                console.log(error);
            }
        });
    });


    // index page


	
    $('#formSearch').on('submit', function(event) {

        console.log("index submit");
        
        event.preventDefault(); // Prevent the default form submission
        
        // Get the input values
        threewords = $("#myThreeWordID").val();
   

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
            },
            success: function(response) {
				
				console.log(response);
				
                var jsonObject = JSON.parse(response);
				console.log(jsonObject);
				console.log(jsonObject.status);

				if (jsonObject.status === 'ok') {
					
					if(jsonObject.exists == 'known'){
						
					console.log('forward to public notification');
                    window.location.href = 'public_notification.php?threewords=' + threewords + '&hash=' + jsonObject.hash; // Replace with the URL of the page you want to redirect to
                    $('p#helperText').css('color', 'green');
                    
					} else {
						
					console.log('forward to public notification');
                    window.location.href = 'create.php?threewords=' + threewords + '&hash=' + jsonObject.hash; // Replace with the URL of the page you want to redirect to
                    $('p#helperText').css('color', 'red');	
                    $('#submitBtn').value('create');

			       }
					
                } else {

					console.log('3wordid invalid');
                    $('p#helperText').html(jsonObject.message);
					$('p#helperText').css('color', 'green');
					console.log("enable create")
				
                }
				
                // Handle success - response contains data from the backend
                // alert('Form submitted successfully!');
                console.log(response);
            },
            error: function(xhr, status, error) {
                // Handle error
                //alert('There was an error submitting the form.');
                console.log(error);
            }
        });
    });
	
    $('#FormNotification').on('submit', function(event) {
		
		console.log("form notification");
		
		event.preventDefault(); // Prevent the default form submission
		
		succes = true;
		
		notification = $('#notification').val();
		
		console.log(notification.length);
		
		if(notification.length > 200) {
			
			succes = false;
			
		}
		
		password = $('#password').val();
		
		if(password == "") {
			console.log("no password");
				succes = false;
			}

        
        if(succes) {
			console.log("submitting form notification");
			this.submit();
		}
        
        
    });
	
	
    $('#FormMessage').on('submit', function(event) {
		

	console.log("form message");
	
	succes = true;
	
    $('#feedback').show();
    
    message = $('#message').val();

	if(message.length > 200 || message.length == 0) {
			
			succes = false;
			$('#feedback').html("Don't forget your message!");
			
	}
		
	password = $('#password').val();
		
	if(password == "") {
			console.log("no password");
			succes = false;
			$('#feedback').html("Don't forget your password!");
	}

		
	
		
		event.preventDefault(); // Prevent the default form submission
		
		
		
		threewordto = $('#threewordto').val();
		
		if(threewordto.length == 0) {
			succes = false;
			$('#feedback').html("Don't forget your addressee!");
		}
		
		
		$.ajax({
            url: 'check.php',  // Replace with your backend URL
            type: 'POST',
            data: {
                threewords: threewordto,
            },
            success: function(response) {
				
				console.log(response);
				
                var jsonObject = JSON.parse(response);
				console.log(jsonObject);
				console.log(jsonObject.status);

				if (jsonObject.status === 'ok') {
					
					if(jsonObject.exists == 'known'){
						
						console.log("known addressee");
						$('#threewordto').css('border-color', 'green');
                        $('#feedback').hide();
					    // ok this can be an addresee
                    
					} else {
						$('#threewordto').css('border-color', 'red');
                
                         $('#feedback').html("3WordID address invalid");
					// not known
					    console.log("unknown addressee");
					    
						succes = false;
			       }
					
                } else {
					$('#threewordto').css('border-color', 'red');
                   
                    $('#feedback').html("3WordID address invalid");
                    
					console.log("invalid addressee");
				    succes = false;
                }   
            },
            error: function(xhr, status, error) {
                // Handle error
                //alert('There was an error submitting the form.');
                console.log(error);
            }
        });		

        if(succes) {
			console.log("submitting");
			this.submit();
		}
        
        
    });
});



