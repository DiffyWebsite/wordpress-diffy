( function( $ ) {
	'use strict';

	var diffyInProgress = false;
	var i18n = diffySettings.i18n;

	/**
	 * Poll the backend for the status of a Diffy job and show it to the user.
	 *
	 * @param {string} jobType AJAX action name.
	 */
	function showDiffyStatus( jobType ) {
		var failures = 0;
		var interval = setInterval( function() {
			$.post( diffySettings.ajaxurl, {
				action: jobType,
				nonce: diffySettings.nonce
			} )
				.done( function( response ) {
					var data = response.data || {};

					failures = 0;
					if ( response.success ) {
						if ( 'completed' === data.status || 'failed' === data.status ) {
							clearInterval( interval );
						}
						// Messages are escaped on the server; the completed message may contain a report link.
						$( '.diffy-message' ).html( data.message );
					} else {
						clearInterval( interval );
						$( '.diffy-message' ).text( data.error_message || '' );
					}
				} )
				.fail( function() {
					// The site is briefly in maintenance mode while core installs the update. Keep trying for a minute.
					failures++;
					if ( failures > 12 ) {
						clearInterval( interval );
					}
				} );
		}, 5000 );
	}

	$( function() {
		$( '.update-message a.update-link' ).each( function() {
			var href = $( this ).attr( 'href' );
			if ( href && href.indexOf( 'upgrade-plugin' ) > 0 ) {
				$( this ).after(
					$( '<span></span>' ).text( ' ' + i18n.or + ' ' ),
					$( '<a class="diffy-update-link"></a>' ).attr( 'href', href ).text( i18n.updateWithDiffy )
				);
			}
		} );

		// Copied from core's updates.js, with one extra argument in the AJAX call
		// and listening to "diffy-update-link" instead of "update-link".
		$( '#bulk-action-form' ).on( 'click', '[data-plugin] .diffy-update-link', function( event ) {
			var $message   = $( event.target ),
				$pluginRow = $message.parents( 'tr' );

			event.preventDefault();

			if ( $message.hasClass( 'updating-message' ) || $message.hasClass( 'button-disabled' ) ) {
				return;
			}

			diffyInProgress = true;

			wp.updates.maybeRequestFilesystemCredentials( event );

			// Return the user to the input box of the plugin's table row after closing the modal.
			wp.updates.$elToReturnFocusToFromCredentialsModal = $pluginRow.find( '.check-column input' );
			wp.updates.updatePlugin( {
				plugin: $pluginRow.data( 'plugin' ),
				slug:   $pluginRow.data( 'slug' ),
				testing: 'diffy' // The only change from core's click handler.
			} );
		} );
	} );

	// Triggered right before the AJAX request to update the plugin is sent.
	$( document ).on( 'wp-plugin-updating', function() {
		if ( diffyInProgress ) {
			$( '.updating-message' ).after(
				$( '<div class="notice inline notice-alt notice-info"><p class="diffy-message"></p></div>' )
			);
			$( '.diffy-message' ).text( i18n.firstSet );

			showDiffyStatus( 'diffy_first_screenshots' );
		}
	} );

	// Triggered after the plugin was updated.
	$( document ).on( 'wp-plugin-update-success', function() {
		if ( diffyInProgress ) {
			$( '.diffy-message' ).text( i18n.secondSet );

			showDiffyStatus( 'diffy_second_screenshots' );

			diffyInProgress = false;
		}
	} );

} )( jQuery );
