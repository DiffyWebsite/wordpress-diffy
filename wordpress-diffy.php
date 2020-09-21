<?php
/**
 * The Diffy Plugin.
 *
 * Diffy is visual regression testing platform.
 *
 * @package Diffy Visual Regression Testing
 * @subpackage Main
 * @since 1.0.0
 */

/**
 * Plugin Name: Diffy
 * Plugin URI:  https://diffy.website/wordpress
 * Description: Diffy allows you to see if any of your updates caused visual changes on the site.
 * Author:      Diffy
 * Author URI:  https://diffy.website/
 * Version:     0.9.3
 * License:     GPLv2 or later (license.txt)
 */

/**
 * Autoload helper classes.
 */
$diffy_autoload_file = plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

if ( is_readable( $diffy_autoload_file ) ) {
  require $diffy_autoload_file;
}
elseif ( ! class_exists( 'Diffy\Diffy' ) ) { // Still checking since might be site-level autoload R.
  add_action( 'admin_init', 'diffy_missing_autoload', 1 );

  return;
}

// Load admin pages.
require_once dirname( __FILE__ ) . '/admin/class-wordpress-diffy-admin.php';
$admin = new Diffy_Admin('wordpress-diffy', '0.9.0');

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
  $message = esc_html__( 'The %1$s plugin installation is incomplete. Misses autoload files. Please refer to %2$sinstallation instructions%3$s.', 'wordpress-diffy' );
  $message = sprintf( $message, 'Diffy', '<a href="https://github.com/DiffyWebsite/wordpress-diffy#installation">', '</a>' );
  echo '<div class="error"><p>' . esc_html__( 'Activation failed:', 'wordpress-diffy' ) . ' ' . strip_tags( $message, '<a>' ) . '</p></div>';
}

/**
 * The method will deactivate the plugin, but only once, done by the static $is_deactivated.
 */
function diffy_self_deactivate() {
  static $is_deactivated;

  if ( $is_deactivated === null ) {
    $is_deactivated = true;
    deactivate_plugins( plugin_basename( __FILE__ ) );
    if ( isset( $_GET['activate'] ) ) {
      unset( $_GET['activate'] );
    }
  }
}


/**
 * Runs when we start upgrade process. Create first set of screenshots.
 */
function diffy_create_first_screenshots($options) {
  add_settings_error(
    'diffy',
    'diffy',
    __( 'Diffy started first set of screenshots. Waiting until completed and run updates.' ),
    'success'
  );

  $diffy_api_key = get_option('diffy_api_key');
  $diffy_project_id = get_option('diffy_project_id');
  if (empty($diffy_api_key) || empty($diffy_project_id)) {
    return $options;
  }
  try {
    \Diffy\Diffy::setApiKey($diffy_api_key);
  }
  catch (\Exception $exception) {
    return $options;
  }

  $screenshot_id = 0;
  try {
    $screenshot_id = \Diffy\Screenshot::create($diffy_project_id, 'production');
    update_option('diffy_first_screenshot_id', $screenshot_id);
    // Assume that screenshots should be created in 20 mins max.
    for ($i = 0; $i < 240; $i++) {
      sleep(5);
      $screenshot = \Diffy\Screenshot::retrieve($screenshot_id);
      if ($screenshot->isCompleted()) {
        break;
      }
    }
  }
  catch (\Exception $e) {
    return $options;
  }

  return $options;
}
add_filter( 'upgrader_package_options', 'diffy_create_first_screenshots' );

/**
 * Runs after updates completed. Create second set of screenshots and diff.
 */
function diffy_create_second_screenshots_diff() {
  $first_screenshot_id = get_option('diffy_first_screenshot_id');
  if (empty($first_screenshot_id)) {
    return;
  }

  $diffy_project_id = get_option('diffy_project_id');
  try {
    $second_screenshot_id = \Diffy\Screenshot::create($diffy_project_id, 'production');
    update_option('diffy_second_screenshot_id', $second_screenshot_id);

    $diff_id = \Diffy\Diff::create($diffy_project_id, $first_screenshot_id, $second_screenshot_id);
    update_option('diffy_diff_id', $diff_id);
  }
  catch (\Exception $e) {
    return;
  }
}
add_action( 'upgrader_process_complete', 'diffy_create_second_screenshots_diff' );

/**
 * Add js to the plugins listing page.
 */
function diffy_plugins_page_add_javascript($plugins) {
  wp_enqueue_script(
    'diffy-plugins-page',
    plugin_dir_url(__FILE__) . 'js/plugins-scripts.js',
    array('jquery'),
    NULL,
    TRUE
  );

  // set variables for script
  wp_localize_script(
    'diffy-plugins-page',
    'settings',
    array(
      'ajaxurl' => admin_url( 'admin-ajax.php' ),
    )
  );

  return $plugins;
}
add_filter( 'all_plugins', 'diffy_plugins_page_add_javascript' );

/**
 * Ajax requests to check the status of first set of screenshots.
 */
function diffy_ajax_first_screenshots_callback() {
  $diffy_api_key = get_option('diffy_api_key');
  $diffy_project_id = get_option('diffy_project_id');
  if (empty($diffy_api_key) || empty($diffy_project_id)) {
    wp_send_json_error([
      'error_message' => 'Diffy API Key and project ID are empty',
    ]);
  }

  try {
    \Diffy\Diffy::setApiKey($diffy_api_key);
  }
  catch (\Exception $exception) {
    wp_send_json_error([
      'error_message' => 'Could not set Diffy API Key',
    ]);
  }

  $first_screenshot_id = get_option('diffy_first_screenshot_id');
  if (!empty($first_screenshot_id)) {
    try {
      $screenshot = \Diffy\Screenshot::retrieve($first_screenshot_id);
      if (!$screenshot->isCompleted()) {
        wp_send_json_success( [
          'status' => 'progress',
          'message' => sprintf('Creating first set of screenshots. %s.', ucfirst($screenshot->getEstimate())),
        ] );
      }
      else {
        wp_send_json_success( [
          'status' => 'completed',
          'message' => 'Completed creating first set of screenshots. Wordpress plugin update in progress.',
        ] );
      }
    }
    catch (\Exception $exception) {
      wp_send_json_error([
        'error_message' => sprintf('Could not retrieve Screenshot ID %d', $first_screenshot_id)
      ]);
    }
  }

}
add_action( 'wp_ajax_diffy_first_screenshots', 'diffy_ajax_first_screenshots_callback');

/**
 * Ajax requests to check the status of second set of screenshots and the diff.
 */
function diffy_ajax_second_screenshots_callback() {
  $diffy_api_key = get_option('diffy_api_key');
  $diffy_project_id = get_option('diffy_project_id');
  if (empty($diffy_api_key) || empty($diffy_project_id)) {
    wp_send_json_error([
      'error_message' => 'Diffy API Key and project ID are empty',
    ]);
  }

  try {
    \Diffy\Diffy::setApiKey($diffy_api_key);
  }
  catch (\Exception $exception) {
    wp_send_json_error([
      'error_message' => 'Could not set Diffy API Key',
    ]);
  }

  $second_screenshot_id = get_option('diffy_second_screenshot_id');
  if (!empty($second_screenshot_id)) {
    try {
      $screenshot = \Diffy\Screenshot::retrieve($second_screenshot_id);
      if (!$screenshot->isCompleted()) {
        wp_send_json_success( [
          'status' => 'progress',
          'message' => sprintf('Creating second set of screenshots. %s.', ucfirst($screenshot->getEstimate())),
        ] );
      }
      update_option('diffy_second_screenshot_id', 0);
    }
    catch (\Exception $exception) {
      wp_send_json_error([
        'error_message' => sprintf('Could not retrieve Screenshot ID %d', $second_screenshot_id)
      ]);
    }
  }


  $diff_id = get_option('diffy_diff_id');
  if (!empty($diff_id)) {
    try {
      $diff = \Diffy\Diff::retrieve($diff_id);
      if (!$diff->isCompleted()) {
        wp_send_json_success( [
          'status' => 'progress',
          'message' => sprintf('Second set completed. Comparing screenshots. %s.', ucfirst($diff->getEstimate())),
        ] );
      }
      update_option('diffy_diff_id', 0);
      wp_send_json_success( [
        'status' => 'completed',
        'message' => sprintf('Comparison completed. %s.', $diff->getReadableResult()),
      ] );
    }
    catch (\Exception $exception) {
      wp_send_json_error([
        'error_message' => sprintf('Could not retrieve Diff ID %d', $diff_id)
      ]);
    }
  }

}
add_action( 'wp_ajax_diffy_second_screenshots', 'diffy_ajax_second_screenshots_callback');
