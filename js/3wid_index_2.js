// Letters and digits are allowed (paid 3WordIDs and car chat licence plates such as
// "92 zd ng" contain digits); anything else can never be a 3WordID.
function containsSpecialCharacters(str) {
    return /[^a-zA-Z0-9]/.test(str);
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

// Texts for the current language come from window.T3 (set by the page, see php/i18n.php).
function tr(key, fallback) {
    return (window.T3 && window.T3[key]) || fallback;
}

// state: 'found' (green: the 3WordID exists), 'gold' (exists and owned by a paying account)
// or anything else (closed lock). Switched-off 3WordIDs also count as found: the search goes
// on and the visitor lands on the "not active at the moment" page.
function setSearchLock(state) {
    var lock = document.getElementById('searchLock');
    var shackle = document.getElementById('lockShackle');
    var input = document.getElementById('threewords');
    if (!lock || !shackle) return;

    lock.classList.remove('is-found', 'is-gold');
    lock.removeAttribute('title');
    lock.removeAttribute('aria-label');
    lock.removeAttribute('role');
    lock.setAttribute('aria-hidden', 'true');

    if (state === 'found' || state === 'gold') {
        var label = state === 'gold' ? tr('paying', 'Owned by a paying account') : tr('exists', '3wid exists');
        shackle.setAttribute('d', 'M7 11V7a5 5 0 0 1 9.9-1');
        lock.classList.add(state === 'gold' ? 'is-gold' : 'is-found');
        lock.setAttribute('title', label);
        lock.setAttribute('aria-label', label);
        lock.setAttribute('role', 'img');
        lock.removeAttribute('aria-hidden');
        if (input) input.style.color = state === 'gold' ? '#B8860B' : 'green';
    } else {
        shackle.setAttribute('d', 'M7 11V7a5 5 0 0 1 10 0v4');
        if (input) input.style.color = '';
    }
}

var existsRequest = 0; // only the newest answer may change the lock

 $(document).ready(function() {

    $('#threewords').on('keyup', function() {

              var mine = ++existsRequest; // any older answer is now out of date

              threewords = $('#threewords').val();
              
              threewords = threewords.trim();

              let threewords_array = threewords.trim().split(/\s+/);
              let nr_words = threewords_array.length;
              
               for (let word of threewords_array) {
                    if(containsSpecialCharacters(word)) {
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
                        dataType: 'json',
                        data: {
                            'threewords': threewords,
                            'format': 'json'
                        },
                        success: function(response) {
                            if (mine !== existsRequest) return; // an older answer
                            if (response && response.exists) {
                                $("#helperText").text(tr('exists', "3wid exists"));
                                setSearchLock(response.paying ? 'gold' : 'found');
                            } else {
                                $('#helperText').text(tr('missing', "3wid does not exist log in to create"));
                                setSearchLock('closed');
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
