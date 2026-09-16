function containsDigitsOrSpecialCharacters(str) {
    return /\d/.test(str) || /[^a-zA-Z0-9]/.test(str);
}

function sleep(miliseconds) {
   var currentTime = new Date().getTime();

   while (currentTime + miliseconds >= new Date().getTime()) {
   }
}

function validate_threeword(threeword) {
        if (threewords.trim() === '' ) {
            return false;
        }

        var regex = /^[a-zA-Z]+$/;
        var words = threewords.trim().split(/\s+/);

        if(words.length != 3) {
            return false;    
        }

        return true;
}

function setSearchLock(state) {
    var lock = document.getElementById('searchLock');
    var shackle = document.getElementById('lockShackle');
    var input = document.getElementById('threewords');
    if (!lock || !shackle) return;

    lock.classList.remove('is-found', 'is-disabled');

    if (state === 'found') {
        shackle.setAttribute('d', 'M7 11V7a5 5 0 0 1 9.9-1');
        lock.classList.add('is-found');
        if (input) input.style.color = 'green';
    } else if (state === 'disabled') {
        shackle.setAttribute('d', 'M7 11V7a5 5 0 0 1 10 0v4');
        lock.classList.add('is-disabled');
        if (input) input.style.color = 'orange';
    } else {
        shackle.setAttribute('d', 'M7 11V7a5 5 0 0 1 10 0v4');
        if (input) input.style.color = '';
    }
}

 $(document).ready(function() {

    $('#threewords').on('keyup', function() {

              threewords = $('#threewords').val();
              
              threewords = threewords.trim();

              let threewords_array = threewords.trim().split(/\s+/);
              let nr_words = threewords_array.length;
              
               for (let word of threewords_array) {
                    if(containsDigitsOrSpecialCharacters(word)) {
                        setSearchLock('closed');
                        return;
                    }
                    
                }

              if(nr_words != 3) {
                    setSearchLock('closed');
                    return;
              }
              
              if(nr_words == 3) {
                      $.ajax({
                        type: 'POST',
                        url: '3wid_exists.php',
                        data: {
                            'threewords': threewords
                        },
                        success: function(response) {
                            if(response =='exists') {
                                $("#helperText").html("3wid exists");
                                setSearchLock('found');
                            } else {
                                if(response =='disabled') {
                                    $("#helperText").html("3wid disabled");
                                    setSearchLock('disabled');
                                } else {
                                    $('#helperText').html("3wid does not exist log in to create");
                                    setSearchLock('closed');
                                }
                            }
                        },
                        error: function(xhr, status, error) {
                        }
                    });

                  } 
                
       }); 
    

   $('#submitBtn').on('click', function(event) {
    event.preventDefault();

    let form = $(this).closest('form');
    let threewords = $('#threewords').val();
    let send = 1;

        if (send === 1) {
            form.off('submit');
            form.submit();
        }
    });
    
     $(document).on('click', '#createBtn', function(event) {
    event.preventDefault();
    var shareUrl = $(this).data('share-url');
    
    if (shareUrl && typeof shareUrl === 'string' && shareUrl.trim() !== '') {
      try {
        window.location.href = shareUrl;
      } catch (e) {
        console.error('Redirect failed:', e);
      }
    } else {
      console.error('Invalid or empty URL:', shareUrl);
    }
  });
    
  
});
