// Texts for the current language come from window.T3 (set by the page, see php/i18n.php).
var tr = window.tr || function (key, fallback) {
    return (window.T3 && window.T3[key]) || fallback;
};


$(document).ready(function() {

    // Attach event listener to the form
    $('#3widForm').on('submit', function(event) {

		send=0;
		
		$('#helperText').text(tr('clicked', 'Clicked submit'));
		
        // Log to confirm the handler is firing
        console.log('Form submit handler triggered');

        // Prevent default submission
        event.preventDefault();
        send = 1;
        
        //console.log('Default submission prevented');

        // Grab form data
        let consent = $('#consent').prop('checked');
        
        
     
		if(!consent) {
			$('#helperText').text(tr('pc_need_consent', 'Please consent/agree by checking the checkbox'));
			send=0;
			return;
		} else {
			console.log('Consented!');
		}	

        if(send==1) { 
			window.location.href = '3wid_privacy_consent_process.php'; // Replace with the URL of the page you want to redirect to
			// Use jQuery to submit the form, respecting the event handler
			form.off('submit'); // Remove this handler to avoid infinite loop
			form.submit(); // Trigger jQuery's submit, which sends the form
		}
    });
	
	
});
		
