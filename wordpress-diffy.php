<?php
/**
 * The Diffy Plugin.
 *
 * Diffy is visual regression testing platform.
 *
 * @package Diffy
 * @subpackage Main
 * @since 1.0.0
 */

/**
 * Plugin Name: Diffy
 * Plugin URI:  https://diffy.website/wordpress
 * Description: Diffy allows you to see if any of your updates caused visual changes on the site.
 * Author:      Diffy
 * Author URI:  https://diffy.website/
 * Version:     0.9.0
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
  /* translators: %1$s expands to Yoast SEO, %2$s / %3$s: links to the installation manual in the Readme for the Yoast SEO code repository on GitHub */
  $message = esc_html__( 'The %1$s plugin installation is incomplete. Please refer to %2$sinstallation instructions%3$s.', 'wordpress-diffy' );
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
  file_put_contents('/tmp/started.txt', 'test');
  sleep(120);
}

add_filter( 'upgrader_package_options', 'diffy_create_first_screenshots' );

/**
 * Runs after updates completed. Create second set of screenshots and diff.
 */
function diffy_create_second_screenshots_diff() {
  file_put_contents('/tmp/stopped.txt', 'test');
}
add_action( 'upgrader_process_complete', 'diffy_create_second_screenshots_diff' );

