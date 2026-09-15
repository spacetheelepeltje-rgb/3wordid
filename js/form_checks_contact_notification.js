

const emailRegex = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z0-9]).+$/;
    
	function validateEmail(email) {
		return emailRegex.test(String(email).toLowerCase());
	}
	
	function validatePassword(password) {	
		if(password.length < 8 || password.split(/\s+/).length !=1) {
			return false;
		} else {
			return passwordRegex.test(String(password));
		}
	}
   validemail = 0;
   validpassword = 0; 
   	
 function containsDigitsOrSpecialCharacters(str) {
    return /\d/.test(str) || /[^a-zA-Z0-9]/.test(str);
}	

	function  validate_email_password(threeword,email,password) {
		
			$.ajax({
				type: "POST",
				url: "delete_3wid.php",
				data: { threeword: threeword,
					    email:email,
					    password:password
					    },
				success: function(response) {
					// This code runs when the POST is successful
					//console.log("POST request completed successfully");
					//console.log(response);
					if(response !="Deleted") {
						alert(response);
					} else {
						alert("Your 3wordID has been deleted");
					
					}
					
					//alert(response);
					// Further operations can go here
					return response;
				},
				error: function(xhr, status, error) {
					// This code runs if there's an error
					console.log("An error occurred: " + error);
				},
				complete: function() {
					// This code runs after the request is complete, regardless of success or failure
					//console.log('response is available');
					
					//console.log("Request completed");
					//alert('forwarding to index');
					window.location.href = 'index.php';
                   
				}
			});
	
	
		return false;
	}

  function checkInput() {
		  
		  
                var inputVal = $('#myInput').val().trim();
                if (inputVal.length > 0) {
                    $('#submitBtn').attr('disabled', false);  // Enable the button
                } else {
                    $('#submitBtn').attr('disabled', true);   // Disable the button
                }
            }

// create page
	
 $(document).ready(function() {
	 
	 
	 message_string ='To save your changes or initiate subscription please provide your email and '
	 +'password below. Your password needs to have upper, lower case, a '
	 +'number and a non-letter character and longer than 8 characters.'
	 
	 $('#helpertext').html(message_string);
	 
	 $('#email').on('keyup', function() {
		  
              console.log("email keyup");
              email = $('#email').val();
              if(validateEmail(email)) {
				  console.log('good email');
				  $("#email").css("color", "green");
				  validemail = 1;
				   
				  } else {
				  console.log('bad email');
				  $("#email").css("color", "#000");
				  $("#test").fadeTo(100, 0.2);
				  validemail = 0;
				  }
                
       });   
       
    $('#password').on('keyup', function() {
		  
              console.log("password keyup");
              password = $('#password').val();

              if(validatePassword(password)) {
				  console.log('good password');
				  $("#password").css("color", "green");
				  validpassword = 1;	   
				  } else {
				  console.log('bad password');
				  $("#password").css("color", "#000");
				  validpassword = 0;
				  }
                
       });       
	   
	   
	   
    $('.subscribe_button').on('click', function() {
        console.log("subscribe_button clicked!");
        password = $("#password").val();
        threewords = $("#threewords").val();
        email = $("#email").val();
        console.log(password);
        
        if(!validpassword) {
			$("#password").focus();
			$('#helpertext').html('<strong>Your password needs to have upper, lower case, a number and a non-letter character and be 8 characters long</strong>');
	    }
	    
	    if(!validemail) {
			$("#email").focus();
			$('#helpertext').html('<strong>Please provide a valid (paypal) email address</strong>');
	    } 
        
        subscribe_url = 'subscribe.php?password=' + password +'&threewords=' + threewords + '&email=' + email;
        
        console.log(subscribe_url)
          
        if(validpassword && validemail) {      
			window.location.href = subscribe_url; // Replace with the URL of the page you want to redirect to
		}   
   });
	 
	 
      $('p#helperText').css('color', 'gray');

	
	 
	  $('#myInput').on('keyup', function() {
		  
                checkInput();
                
       }); 

    // index page

	$('.delete_button').on('click', function() {
		
		confirm_string = 'Als u op OK klikt wordt uw 3WordID direct gewist. Iemand anders kan hem dan reserveren.'
		+'Uw eventuele tegoed blijft staan. Als u een andere 3WordID registreert wordt dat herkend aan uw email'
		+' adres.\n\n'
		+'If you click OK your 3WordID will be deleted immediately. Someone else can register it.'
		+'Your credit will remain. If you register another 3WordID we can see by your email that you still have credit.';
		
		if (confirm(confirm_string)) {
			// Code to run if the user clicks "OK"
			
			email = $('#email').val();
			password = $("#password").val();
			threewords = $("#threewords").val();
					
			//console.log(email+' ' +password+' '+threewords);
			
			result = validate_email_password(threewords,email,password);
			
			
			
		} else {
			// Code to run if the user clicks "Cancel"
			console.log("User clicked Cancel");
		}
		
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
		
		if(!validpassword) {
			$("#password").focus();
			$('#helpertext').html('<strong>Your password needs to have upper, lower case, a number and a non-letter character and be 8 characters long</strong>');
			succes = false;
	    }
	    
	    if(!validemail) {
			$("#email").focus();
			$('#helpertext').html('<strong>Please provide a valid (paypal) email address</strong>');
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



