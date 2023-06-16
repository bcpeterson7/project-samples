/**
 * ajax-sample.js description: this script dismisses a tutorial that appears for first-time users. The tutorial
 * is automatically dismissed when completed, but it can also be manually skipped by clicking on the .dismiss-tutorial button.
 * When the dismiss tutorial button is clicked then the tutorial becomes semi-opaque and a loading gif appears letting the user
 * know that something is happening server-side. If the dismiss attempt is successful then the tutorial simply disappears. If the
 * dismiss attempt fails then an error message appears letting them attempt to dismiss the tutorial again.
 */

(function($) {

$(window).on('load', function() {
  if ( $('.dismiss-tutorial').length ) {
    $(document).on('click', '.dismiss-tutorial', function() {
      dismiss_tutorial();
    });
  }
});


let jqXHR = null;
function dismiss_tutorial() {
  let cv = false;
  cv = confirm("Are you sure you want to skip the tutorial?");
  if ( true === cv ) {
    // AJAX Save the value and hide the tutorials.
    jqXHR = $.ajax({
      type: 'POST',
      url: DPData.rest_url +  'dataport/v1/new_user_msg',
      data: {
        'action': 'DP_dismiss_new_user_tutorial',
        'staffID': DPData.staffID,
        'rest_nonce': DPData.rest_nonce,
      },
      beforeSend: function(xhr) {
        // Attempt to cancel previous requests if the user is spamming the .dismiss-tutorial button
        if(jqXHR != null) {
          jqXHR.abort();
        }
        // Make the .dismiss-tutorial button the user clicked become unclickable and show a loading gif (.loading-gif)
        $('.dismiss-tutorial').addClass('loading noclick').next().show();
        // Remove previous error messages
        $('#dp-new-user-msg #error').html('');
        // Verify the nonce and sanitize data
        xhr.setRequestHeader( 'X-WP-Nonce', DPData.rest_nonce );
      },
    })
    .done(function( response ) {
      // Process the response and show information to the user. Since the user only cares if the tutorial is
      // either dismissed or not dismissed I kept the messaging simple. A success action of hiding the
      // tutorial, or a failure message prompting them to try again.
      if ( response.status == "success" ) {
        // Disable the tutorial and close the dialog box
        $('#dp-new-user-msg').remove();
        $('div.ui-dialog').remove();
        $(document).find('.red-glowing-border').each(function() { $(this).removeClass('red-glowing-border') });
      } else {
        // Show error messaging
        $('#dp-new-user-msg').addClass('red-glowing-border'); // adds a red border and animated box-shadow that makes a red glowing effect
        $('#dp-new-user-msg #error').html('<div class="error-msg"><h2>ERROR</h2><p class="center">An error has occurred please try again.</p><p class="center"><a id="end-tut" class="button-colored-border" href="#close-tut">End Tutorial</a></p>');
      }
    })
    .fail(function( error ) {
      // Usually, for specific error messages I return them, from the PHP script, with a 403 header and process them here.
      // This application is really either a success or a failure so I skipped that and have a generic error message if there
      // is some type of nonce or connection failure.
      alert("There was a connection issue. Please try again!");
    })
    .always(function( data ) {
      // hide the loading gif and make the dismiss tutorial button clickable again
      $('.dismiss-tutorial').removeClass('loading noclick').next().hide();
    });
  }
} // dismiss_tutorial()


})(jQuery)
