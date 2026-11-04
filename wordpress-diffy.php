<?php
/**
 * Plugin Name:       Diffy Visual Regression Testing
 * Plugin URI:        https://diffy.website/wordpress/
 * Description:       Screenshots your site before and after plugin updates and shows what changed, using Diffy visual regression testing.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Diffy
 * Author URI:        https://diffy.website/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       diffy
 *
 * @package Diffy
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

define( 'DIFFY_PLUGIN_VERSION', '1.0.0' );
define( 'DIFFY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Autoload helper classes.
 */
$diffy_autoload_file = plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

if ( is_readable( $diffy_autoload_file ) ) {
	require $diffy_autoload_file;
} elseif ( ! class_exists( 'Diffy\Diffy' ) ) { // Still checking since might be site-level autoload.
	add_action( 'admin_init', 'diffy_missing_autoload', 1 );

	return;
}

// Load admin pages.
require_once __DIR__ . '/admin/class-wordpress-diffy-admin.php';
new Diffy_Admin( 'wordpress-diffy', DIFFY_PLUGIN_VERSION );

/**
 * Throw an error if the Composer autoload is missing and self-deactivate plugin.
 *
 * @return void
 */
function diffy_missing_autoload() {
	if ( is_admin() ) {
		add_action( 'admin_notices', 'diffy_missing_autoload_notice' );

		diffy_self_deactivate();
	}
}

/**
 * Returns the notice in case of missing Composer autoload.
 */
function diffy_missing_autoload_notice() {
	/* translators: 1: Plugin name, 2: Opening link tag, 3: Closing link tag. */
	$message = esc_html__( 'The %1$s plugin installation is incomplete: autoload files are missing. Please refer to the %2$sinstallation instructions%3$s.', 'diffy' );
	$message = sprintf( $message, 'Diffy', '<a href="https://github.com/DiffyWebsite/wordpress-diffy#installation">', '</a>' );
	echo '<div class="error"><p>' . esc_html__( 'Activation failed:', 'diffy' ) . ' ' . wp_kses( $message, array( 'a' => array( 'href' => array() ) ) ) . '</p></div>';
}

/**
 * The method will deactivate the plugin, but only once, done by the static $is_deactivated.
 */
function diffy_self_deactivate() {
	static $is_deactivated;

	if ( null === $is_deactivated ) {
		$is_deactivated = true;
		deactivate_plugins( plugin_basename( __FILE__ ) );
		if ( isset( $_GET['activate'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			unset( $_GET['activate'] );
		}
	}
}

/**
 * Whether the API key and project ID are both saved.
 *
 * @return bool
 */
function diffy_is_configured() {
	return ! empty( get_option( 'diffy_api_key' ) ) && ! empty( get_option( 'diffy_project_id' ) );
}

/**
 * Authenticate the Diffy API client with the saved API key.
 *
 * @return bool True when the client is ready to use.
 */
function diffy_authenticate() {
	if ( ! diffy_is_configured() ) {
		return false;
	}

	try {
		\Diffy\Diffy::setApiKey( get_option( 'diffy_api_key' ) );
	} catch ( \Exception $exception ) {
		update_option( 'diffy_last_error', $exception->getMessage(), false );
		return false;
	}

	return true;
}

/**
 * Create the first set of screenshots before a plugin update starts.
 *
 * Runs on the core "update-plugin" AJAX request, before core's own handler
 * (priority 1). Core turns on maintenance mode while it updates an active
 * plugin, so the "before" screenshots have to be taken before that, or Diffy
 * would capture the maintenance page.
 *
 * Only runs when the update was started with the "update now with Diffy visual
 * testing" link.
 */
function diffy_create_first_screenshots() {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- The nonce is checked below.
	if ( ! isset( $_POST['testing'] ) || 'diffy' !== sanitize_key( wp_unslash( $_POST['testing'] ) ) ) {
		return;
	}

	// Core checks the same nonce and capability in its own handler and rejects the request if they fail.
	if ( ! check_ajax_referer( 'updates', false, false ) || ! current_user_can( 'update_plugins' ) ) {
		return;
	}

	// Clear the state of any earlier run.
	update_option( 'diffy_last_error', '', false );
	update_option( 'diffy_first_screenshot_id', '', false );
	update_option( 'diffy_second_screenshot_id', 0, false );
	update_option( 'diffy_diff_id', 0, false );

	if ( ! diffy_authenticate() ) {
		return;
	}

	try {
		$screenshot_id = \Diffy\Screenshot::create( (int) get_option( 'diffy_project_id' ), 'production' );
		update_option( 'diffy_first_screenshot_id', $screenshot_id, false );
		// Assume that screenshots should be created in 20 mins max.
		for ( $i = 0; $i < 240; $i++ ) {
			sleep( 5 );
			$screenshot = \Diffy\Screenshot::retrieve( (int) $screenshot_id );
			if ( $screenshot->isCompleted() ) {
				break;
			}
		}
	} catch ( \Exception $e ) {
		update_option( 'diffy_first_screenshot_id', '', false );
		update_option( 'diffy_last_error', $e->getMessage(), false );
	}
}
add_action( 'wp_ajax_update-plugin', 'diffy_create_first_screenshots', 0 );

/**
 * Runs after updates completed. Create second set of screenshots and diff.
 */
function diffy_create_second_screenshots_diff() {
	$first_screenshot_id = get_option( 'diffy_first_screenshot_id' );
	if ( empty( $first_screenshot_id ) ) {
		return;
	}

	update_option( 'diffy_first_screenshot_id', '', false );

	if ( ! diffy_authenticate() ) {
		return;
	}

	$diffy_project_id = (int) get_option( 'diffy_project_id' );
	try {
		$second_screenshot_id = \Diffy\Screenshot::create( $diffy_project_id, 'production' );
		update_option( 'diffy_second_screenshot_id', $second_screenshot_id, false );

		$diff_id = \Diffy\Diff::create( $diffy_project_id, (int) $first_screenshot_id, (int) $second_screenshot_id );
		update_option( 'diffy_diff_id', $diff_id, false );
	} catch ( \Exception $e ) {
		update_option( 'diffy_last_error', $e->getMessage(), false );
	}
}
add_action( 'upgrader_process_complete', 'diffy_create_second_screenshots_diff' );

/**
 * Add the "update now with Diffy visual testing" script to the Plugins screen.
 *
 * @param string $hook_suffix The current admin page.
 */
function diffy_plugins_page_add_javascript( $hook_suffix ) {
	if ( 'plugins.php' !== $hook_suffix || ! current_user_can( 'update_plugins' ) || ! diffy_is_configured() ) {
		return;
	}

	wp_enqueue_script(
		'diffy-plugins-page',
		DIFFY_PLUGIN_URL . 'js/plugins-scripts.js',
		array( 'jquery', 'updates' ),
		DIFFY_PLUGIN_VERSION,
		true
	);

	wp_localize_script(
		'diffy-plugins-page',
		'diffySettings',
		array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'diffy_status' ),
			'i18n'    => array(
				'updateWithDiffy' => __( 'update now with Diffy visual testing', 'diffy' ),
				'or'              => __( 'or', 'diffy' ),
				'firstSet'        => __( 'Diffy is taking the first set of screenshots (before the update).', 'diffy' ),
				'secondSet'       => __( 'Update completed. Diffy is now taking the second set of screenshots and comparing them.', 'diffy' ),
			),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'diffy_plugins_page_add_javascript' );

/**
 * Check the nonce and capability of a status request, and authenticate with Diffy.
 */
function diffy_ajax_status_preflight() {
	check_ajax_referer( 'diffy_status', 'nonce' );

	if ( ! current_user_can( 'update_plugins' ) ) {
		wp_send_json_error( array( 'error_message' => __( 'You are not allowed to update plugins.', 'diffy' ) ), 403 );
	}

	$last_error = (string) get_option( 'diffy_last_error' );
	if ( '' !== $last_error ) {
		// Drop the request method and URL that the Diffy SDK puts in front of API errors.
		$last_error = preg_replace( '/^[A-Z]+ \S+ Error: /', '', $last_error );
		update_option( 'diffy_last_error', '', false );
		wp_send_json_success(
			array(
				'status'  => 'failed',
				/* translators: %s: Error message from the Diffy API. */
				'message' => sprintf( __( 'Diffy could not run the visual test: %s', 'diffy' ), $last_error ),
			)
		);
	}

	if ( ! diffy_is_configured() ) {
		wp_send_json_error( array( 'error_message' => __( 'Diffy API key and project ID are empty.', 'diffy' ) ) );
	}

	if ( ! diffy_authenticate() ) {
		wp_send_json_error( array( 'error_message' => __( 'Could not authenticate with the Diffy API key.', 'diffy' ) ) );
	}
}

/**
 * Ajax requests to check the status of first set of screenshots.
 */
function diffy_ajax_first_screenshots_callback() {
	diffy_ajax_status_preflight();

	$first_screenshot_id = (int) get_option( 'diffy_first_screenshot_id' );
	if ( empty( $first_screenshot_id ) ) {
		wp_send_json_success(
			array(
				'status'  => 'waiting',
				'message' => __( 'Starting the first set of screenshots…', 'diffy' ),
			)
		);
	}

	try {
		$screenshot = \Diffy\Screenshot::retrieve( $first_screenshot_id );
		if ( ! $screenshot->isCompleted() ) {
			wp_send_json_success(
				array(
					'status'  => 'progress',
					/* translators: %s: Time estimate from Diffy, e.g. "about 2 minutes left". */
					'message' => sprintf( __( 'Taking the first set of screenshots. %s.', 'diffy' ), ucfirst( (string) $screenshot->getEstimate() ) ),
				)
			);
		}

		wp_send_json_success(
			array(
				'status'  => 'completed',
				'message' => __( 'First set of screenshots completed. The plugin update is in progress.', 'diffy' ),
			)
		);
	} catch ( \Exception $exception ) {
		wp_send_json_error(
			array(
				/* translators: %d: Screenshot set ID. */
				'error_message' => sprintf( __( 'Could not retrieve screenshot set %d.', 'diffy' ), $first_screenshot_id ),
			)
		);
	}
}
add_action( 'wp_ajax_diffy_first_screenshots', 'diffy_ajax_first_screenshots_callback' );

/**
 * Ajax requests to check the status of second set of screenshots and the diff.
 */
function diffy_ajax_second_screenshots_callback() {
	diffy_ajax_status_preflight();

	$second_screenshot_id = (int) get_option( 'diffy_second_screenshot_id' );
	if ( ! empty( $second_screenshot_id ) ) {
		try {
			$screenshot = \Diffy\Screenshot::retrieve( $second_screenshot_id );
			if ( ! $screenshot->isCompleted() ) {
				wp_send_json_success(
					array(
						'status'  => 'progress',
						/* translators: %s: Time estimate from Diffy, e.g. "about 2 minutes left". */
						'message' => sprintf( __( 'Taking the second set of screenshots. %s.', 'diffy' ), ucfirst( (string) $screenshot->getEstimate() ) ),
					)
				);
			}
			update_option( 'diffy_second_screenshot_id', 0, false );
		} catch ( \Exception $exception ) {
			wp_send_json_error(
				array(
					/* translators: %d: Screenshot set ID. */
					'error_message' => sprintf( __( 'Could not retrieve screenshot set %d.', 'diffy' ), $second_screenshot_id ),
				)
			);
		}
	}

	$diff_id = (int) get_option( 'diffy_diff_id' );
	if ( empty( $diff_id ) ) {
		wp_send_json_success(
			array(
				'status'  => 'waiting',
				'message' => __( 'Waiting for Diffy to start the comparison…', 'diffy' ),
			)
		);
	}

	try {
		$diff = \Diffy\Diff::retrieve( $diff_id );
		if ( ! $diff->isCompleted() ) {
			wp_send_json_success(
				array(
					'status'  => 'progress',
					/* translators: %s: Time estimate from Diffy, e.g. "about 2 minutes left". */
					'message' => sprintf( __( 'Second set completed. Comparing screenshots. %s.', 'diffy' ), ucfirst( (string) $diff->getEstimate() ) ),
				)
			);
		}
		update_option( 'diffy_diff_id', 0, false );
		wp_send_json_success(
			array(
				'status'  => 'completed',
				'message' => wp_kses(
					/* translators: %s: Result summary with a link to the report. */
					sprintf( __( 'Comparison completed. %s.', 'diffy' ), $diff->getReadableResult() ),
					array(
						'a' => array(
							'href'   => array(),
							'target' => array(),
						),
					)
				),
			)
		);
	} catch ( \Exception $exception ) {
		wp_send_json_error(
			array(
				/* translators: %d: Comparison ID. */
				'error_message' => sprintf( __( 'Could not retrieve comparison %d.', 'diffy' ), $diff_id ),
			)
		);
	}
}
add_action( 'wp_ajax_diffy_second_screenshots', 'diffy_ajax_second_screenshots_callback' );
