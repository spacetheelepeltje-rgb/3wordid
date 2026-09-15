
 $(document).ready(function() {


	
	$('#submitNoLinkthruBtn').on('click', function(event) {

        console.log("index submit no link thru");
 
	});
	
	$('#submitNoLinkthruBtn').on('click', function(event) {

        console.log("index submit no link thru");
 
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
                    window.location.href = 'public_notification.php?nolinkthru='+ nolinkthru +'&threewords=' + threewords + '&hash=' + jsonObject.hash; // Replace with the URL of the page you want to redirect to
                    $('p#helperText').css('color', 'green');
                    
					} else {
						
					//console.log('forward to create');
                    window.location.href = 'create.php?threewords=' + threewords + '&hash=' + jsonObject.hash; // Replace with the URL of the page you want to redirect to
                    $('p#helperText').css('color', 'red');	
                    $('#submitBtn').value('create');

			       }
					
                } else {

					//console.log('3wordid invalid');
                    $('p#helperText').html(jsonObject.message);
					$('p#helperText').css('color', 'green');
					//console.log("enable create")
				
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
	
