( function( $ ) {
	'use strict';

	function sprintf2( template, first, second ) {
		return template.replace( '%1$s', first ).replace( '%2$s', second );
	}

	function notice( type, text, link ) {
		var $notice = $( '<div class="notice settings-error is-dismissible diffy-register-notice"><p></p></div>' ).addClass( 'notice-' + type );
		$notice.find( 'p' ).text( text );
		if ( link ) {
			$notice.find( 'p' ).append( ' ' ).append( $( '<a target="_blank" rel="noopener"></a>' ).attr( 'href', link.url ).text( link.text ) );
		}
		return $notice;
	}

	$( function() {
		$( '#diffy-register' ).on( 'click', function() {
			var i18n = diffySettings.i18n;
			var $wrapper = $( '.diffy-register-wrapper' );
			var $progress = $( '<div class="notice notice-info diffy-register-progress"><p></p></div>' );

			$( '.diffy-register-notice' ).remove();
			$progress.find( 'p' ).text( i18n.registering );
			$wrapper.after( $progress ).hide();

			$.post( diffySettings.ajaxurl, {
				action: 'diffy_register',
				nonce: diffySettings.nonce
			} )
				.done( function( response ) {
					var data = response.data || {};

					if ( response.success ) {
						$wrapper.empty().append( notice( 'success', sprintf2( i18n.created, data.email, data.password ) ) );
						$( 'input[name=diffy_api_key]' ).val( data.diffy_api_key );
						$( 'input[name=diffy_project_id]' ).val( data.diffy_project_id );
					} else {
						$wrapper.append( notice( 'error', ( data.error_message || '' ) + ' ' + i18n.failed, { url: i18n.appUrl, text: i18n.appLink } ) );
					}
				} )
				.fail( function() {
					$wrapper.append( notice( 'error', i18n.failed, { url: i18n.appUrl, text: i18n.appLink } ) );
				} )
				.always( function() {
					$progress.remove();
					$wrapper.show();
				} );
		} );
	} );

} )( jQuery );
