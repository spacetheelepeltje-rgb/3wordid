	
 function containsDigitsOrSpecialCharacters(str) {
    return /\d/.test(str) || /[^a-zA-Z0-9]/.test(str);
}	

const emailRegex = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

function validateEmail(email) {
    return emailRegex.test(String(email).toLowerCase());
}

function process_backend(paypal_data) {
	
	$.ajax({
		type: "POST",
		url: "paypal_data.php", // Replace with your PHP endpoint URL
		data: {
			step:'pre_paypal',
			paypal_data: paypal_data // Here, we're sending the array under the key 'myArrayKey'
		},
		// If you need to send data in a different format (like JSON), you can use:
		// data: JSON.stringify({ myArrayKey: myArray }),
		// contentType: "application/json",
		success: function(response) {
			console.log("Success:", response);
		},
		error: function(xhr, status, error) {
			console.log("Error:", error);
		}
	});
	
	
	}
	
function process_paypal(paypal_data) {
	
	$.ajax({
		type: "POST",
		url: "paypal_data.php", // Replace with your PHP endpoint URL
		data: {
			step:'post_paypal',
			paypal_data: paypal_data // Here, we're sending the array under the key 'myArrayKey'
		},
		// If you need to send data in a different format (like JSON), you can use:
		// data: JSON.stringify({ myArrayKey: myArray }),
		// contentType: "application/json",
		success: function(response) {
			console.log("Success:", response);
		},
		error: function(xhr, status, error) {
			console.log("Error:", error);
		}
	});
	
	
	}	
	
function check_validthreewords() {	  
              //console.log("threewords keyup");
              threewords = $('#myThreeWordID').val();
              let nr_words = threewords.trim().split(/\s+/).length;
              let threewords_array = threewords.trim().split(/\s+/);
              invalid = 0;
              if(nr_words == 3) {
				  for (let word of threewords_array) {
					if(containsDigitsOrSpecialCharacters(word)) {
						invalid = invalid + 1;  
					}
					console.log(word + " " + containsDigitsOrSpecialCharacters(word));
				  }
				  console.log("invalid" + invalid);
				  
				  if(invalid == 0) {
					validthreeword = 1;
					$("#myThreeWordID").css("color", "green");
					check_valid_data();			 
				  } else {
				  validthreeword = 0;  
				  console.log('error in one of the three words');
				  $("#myThreeWordID").css("color", "#000");
				  $(".paypal").fadeTo(100, 0.2);
				 
				  }
				  } else {
				  validthreeword = 0;  
				  console.log('two words');
				  $("#myThreeWordID").css("color", "#000");
				  $(".paypal").fadeTo(100, 0.2);
				  }
                
       }
function check_validemail() {
	
	   console.log("email keyup");
              email = $('#email').val();
              if(validateEmail(email)) {
				  console.log('good email');
				  $("#email").css("color", "green");
				  validemail = 1;
				  check_valid_data();  
				  } else {
				  console.log('bad email');
				  $("#email").css("color", "#000");
				  $(".paypal").fadeTo(100, 0.2);
				  validemail = 0;
				  }
	
	}       	
	
var paypal_data = {"key1":"value1","key2":"value2","key3":"value3"};

$.validator.addMethod("email", function(value, element) {
    // Regular expression for email validation
    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return this.optional(element) || emailRegex.test(value);
}, "Please enter a valid email address.");
    // index page
    
function check_valid_data() {
		if($('#checkbox').is(':checked')) {
			if(validemail && validthreeword) {
			   
			   $(".paypal").fadeTo(500, 1);
			}
		}
	}    

 $(document).ready(function() {
	 

	 
	 validemail = 0;
	 validthreeword = 0;
	 
	 check_validthreewords();
	 check_validemail();
	 
	 $(".paypal").fadeTo(100, 0.2);
	 
	 // Initially, the button is disabled
    $('#paypal-button').prop('disabled', true);

    // Check the state of the checkbox on any change
    $('#checkbox').on('change', function() {
        if($(this).is(':checked')) {
			
			
			
		if(validemail && validthreeword) {
           $(".paypal").fadeTo(500, 1);
			console.log("valid entries");
            data_array = {email:email,threewords:threewords,terms_checked:'yes'};
			//console.log(data_array);	
			process_backend(data_array);	
				
           } else {
			    $(this).prop('checked', false);
			    $(".paypal").fadeTo(100, 0.2);
				alert('you still have to provide a valid three words and email address');
			   
         }
        } else {
           $(".paypal").fadeTo(100, 0.2);
          
        }
    });

	$('#myThreeWordID').on('keyup', function() {
		check_validthreewords();
		
	 });	
		    
    $('#email').on('keyup', function() {			
		check_validemail();         
     }); 
   

	
});
	
