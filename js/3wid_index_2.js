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

// Could the three words be a licence plate? Every word 1 to 3 letters / digits and at least
// one digit ("1 kbb 00", "gz 123 k"; not "3456 as x"). The same rule as car_plate_pattern() in
// php/car_chat.php; only then does 3wid_exists.php ask RDW (server side) and answer "plate".
function looksLikePlate(words) {
    if (words.length !== 3 || !/[0-9]/.test(words.join(''))) return false;
    for (var i = 0; i < words.length; i++) {
        if (!/^[a-zA-Z0-9]{1,3}$/.test(words[i])) return false;
    }
    return true;
}

// state: 'found' (green: the 3WordID exists), 'gold' (exists and owned by a paying account)
// or anything else (closed lock). Switched-off 3WordIDs also count as found: the search goes
// on and the visitor lands on the "not active at the moment" page.
// plate: true when RDW knows the words as a Dutch licence plate: a car instead of the lock,
// green / gold when the 3WordID exists, blue when it does not exist (yet).
function setSearchLock(state, plate) {
    var lock = document.getElementById('searchLock');
    var shackle = document.getElementById('lockShackle');
    var input = document.getElementById('threewords');
    if (!lock || !shackle) return;

    lock.classList.remove('is-found', 'is-gold', 'is-car', 'is-plate');
    lock.removeAttribute('title');
    lock.removeAttribute('aria-label');
    lock.removeAttribute('role');
    lock.setAttribute('aria-hidden', 'true');

    var found = (state === 'found' || state === 'gold');
    var label = '';
    if (found) {
        label = state === 'gold' ? tr('paying', 'Owned by a paying account') : tr('exists', '3wid exists');
        shackle.setAttribute('d', 'M7 11V7a5 5 0 0 1 9.9-1');
        lock.classList.add(state === 'gold' ? 'is-gold' : 'is-found');
        if (input) input.style.color = state === 'gold' ? '#B8860B' : 'green';
    } else {
        shackle.setAttribute('d', 'M7 11V7a5 5 0 0 1 10 0v4');
        if (input) input.style.color = '';
    }
    if (plate === true) {
        var plateLabel = tr('plate', 'Dutch licence plate');
        label = label ? plateLabel + ' \u00b7 ' + label : plateLabel;
        lock.classList.add('is-car');
        if (!found) lock.classList.add('is-plate');
    }
    if (label) {
        lock.setAttribute('title', label);
        lock.setAttribute('aria-label', label);
        lock.setAttribute('role', 'img');
        lock.removeAttribute('aria-hidden');
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
                            'format': 'json',
                            'plate': looksLikePlate(threewords_array) ? '1' : '0'
                        },
                        success: function(response) {
                            if (mine !== existsRequest) return; // an older answer
                            var plate = looksLikePlate(threewords_array) && !!(response && response.plate === true);
                            if (response && response.exists) {
                                $("#helperText").text(tr('exists', "3wid exists"));
                                setSearchLock(response.paying ? 'gold' : 'found', plate);
                            } else {
                                $('#helperText').text(tr('missing', "3wid does not exist log in to create"));
                                setSearchLock('closed', plate);
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
