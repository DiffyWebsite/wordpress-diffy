( function( $ ) {

	Diffy = {};

	$( document ).ready(function() {
		$('.update-message a.update-link').each(function(){
			var href=$(this).attr('href');
			if (href.indexOf('upgrade-plugin') > 0) {
				$(this).after(' or <a class="diffy-update-link" href="' + href + '">update now with Diffy visual testing</a>');
			}
		});

		// Copied from updates.js with adding extra argument to the ajax call.
		// Also class of the link to listen "diffy-update-link" instead of "update-link".
		var $bulkActionForm      = $( '#bulk-action-form' );
		$bulkActionForm.on( 'click', '[data-plugin] .diffy-update-link', function( event ) {

			// Leave a state that we are updating by using Diffy.
			Diffy.progress = 1;

			var $message   = $( event.target ),
					$pluginRow = $message.parents( 'tr' );

			event.preventDefault();

			if ( $message.hasClass( 'updating-message' ) || $message.hasClass( 'button-disabled' ) ) {
				return;
			}

			wp.updates.maybeRequestFilesystemCredentials( event );

			// Return the user to the input box of the plugin's table row after closing the modal.
			wp.updates.$elToReturnFocusToFromCredentialsModal = $pluginRow.find( '.check-column input' );
			wp.updates.updatePlugin( {
				plugin: $pluginRow.data( 'plugin' ),
				slug:   $pluginRow.data( 'slug' ),
				testing: "diffy" // This is the ONLY change from original function form updates.js
			} );
		} );

	});

  /**
   * Call backend to check the job status and display its status to the user.
   *
   * @param string jobType
   */
  showDiffyStatus = function(jobType) {
    var showDiffyStatusInterval = setInterval(function() {

      var data = {
        'action': jobType
      };
      $.post(settings.ajaxurl, data, function (response) {
        var data = response.data;

        if (response.success) {
          if (response.data.status == 'completed') {
            clearInterval(showDiffyStatusInterval);
          }
          $('.diffy-message').html(response.data.message);
        } else {

        }
      })
      .fail(function () {
        console.log("Diffy ajax error");
      });
    }, 5000);
  };

  // Triggered right before ajax request is sent to update plugin.
  $( document ).on('wp-plugin-updating', function(){
  	if (Diffy.progress == 1) {
			$('.updating-message').after('<div class="notice inline notice-alt notice-info"><p class="diffy-message">Diffy creates first set of screenshots (before update).</p></div>');

			showDiffyStatus('diffy_first_screenshots');
		}
  });

  // Triggered after plugin was successfully updated.
  $( document ).on('wp-plugin-update-success', function(){
		if (Diffy.progress == 1) {
			$('.diffy-message').html('Updates completed. Now Diffy creates second set of screenshots and comparison.');

			showDiffyStatus('diffy_second_screenshots');

			Diffy.progress = 0;
		}
  });

})( jQuery );
