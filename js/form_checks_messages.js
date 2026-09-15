	
 function containsDigitsOrSpecialCharacters(str) {
    return /\d/.test(str) || /[^a-zA-Z0-9]/.test(str);
}	

 function createTableFromJson(jsonArray) {
        var table = document.createElement('table');

        // Optional: Add table headers
        var headerRow = document.createElement('tr');
        var header1 = document.createElement('th');
        header1.textContent = 'Person 1';
        headerRow.appendChild(header1);
        var header2 = document.createElement('th');
        header2.textContent = 'Person 2';
        headerRow.appendChild(header2);
        table.appendChild(headerRow);

        for (var i = 0; i < jsonArray.length; i += 2) {
            var row = document.createElement('tr');

            // First cell
            var cell1 = document.createElement('td');
            cell1.textContent = jsonArray[i].name + " (" + jsonArray[i].age + ")";
            row.appendChild(cell1);

            // Second cell
            var cell2 = document.createElement('td');
            if (jsonArray[i + 1] !== undefined) {
                cell2.textContent = jsonArray[i + 1].name + " (" + jsonArray[i + 1].age + ")";
            } else {
                cell2.textContent = '';
            }
            row.appendChild(cell2);

            table.appendChild(row);
        }

        document.getElementById('table-container').appendChild(table);
    }

// create page
	
 $(document).ready(function() {

     $('#submitButton').on('click', function(event) {
        // Your code here
        //console.log('Submit button was clicked.');

		// check password

	    threewords = $("#threewords").val();
        password = $("#password").val();

        //console.log(threewords);
        //console.log(password);
        
        $.ajax({
            url: 'get_messages.php',  // Replace with your backend URL
            type: 'POST',
            data: {
                threewords: threewords, 
                password:password 
            },
            success: function(response) {
				
				console.log(response);
				
                var jsonObject = JSON.parse(response);
				
				outstring = "<table>";
				
				for (let key in jsonObject.content) {
				  if (jsonObject.content.hasOwnProperty(key)) {
					outstring = outstring + "<tr><td>" + key + "</td><td>" + jsonObject.content[key].message + "</td><td>" + jsonObject.content[key].timesend + "</td><tr>";
				  }
				}	
				outstring = outstring + "</table>";
		
				//console.log(outstring);
				
				$("#table-container").html(outstring);
				
				console.log("output done");
             
            },
            error: function(xhr, status, error) {
                // Handle error
                //alert('There was an error submitting the form.');
                console.log(error);
            }
        });
       
    });
	
  

	
   
	
	
});



