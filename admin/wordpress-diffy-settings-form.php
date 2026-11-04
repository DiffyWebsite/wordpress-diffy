<?php
/**
 * Admin settings form for the plugin.
 *
 * @package Diffy
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Diffy settings', 'diffy' ); ?></h1>
	<?php settings_errors(); ?>
	<?php if ( empty( get_option( 'diffy_api_key' ) ) ) : ?>
	<p class="diffy-register-wrapper">
		<?php esc_html_e( "Don't have a Diffy account yet?", 'diffy' ); ?>
		<button type="button" id="diffy-register" class="button button-primary"><?php esc_html_e( 'One-click register', 'diffy' ); ?></button>
		<?php esc_html_e( 'with your current email address and a random password.', 'diffy' ); ?>
	</p>
	<?php endif; ?>

	<form method="POST" action="options.php">
		<?php
		settings_fields( 'diffy' );
		do_settings_sections( 'diffy' );
		submit_button();
		?>
	</form>
</div>
