<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

class Diffy_Admin {

  protected $plugin_name;
  protected $version;

  /**
   * Diffy_Admin constructor.
   */
  public function __construct($plugin_name, $version) {
    $this->plugin_name = $plugin_name;
    $this->version = $version;
    add_action('admin_menu', array( $this, 'addPluginAdminMenu' ), 9);
    add_action('admin_init', array( $this, 'registerAndBuildFields' ));
  }

  /**
   * Register site-admin nav menu elements.
   */
  public function addPluginAdminMenu() {
    //add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
    add_menu_page(
      $this->plugin_name,
      'Diffy',
      'administrator',
      $this->plugin_name,
      array( $this, 'displayPluginAdminDashboard' ),
      'dashicons-chart-area',
      26
    );
  }

  /**
   * Register fields with Settings API.
   */
  public function registerAndBuildFields() {
    // Two settings API Key and Project ID.
    register_setting('reading', 'diffy_api_key');
    register_setting('reading', 'diffy_project_id');

    // Introduction section.
    add_settings_section(
      'diffy_settings_section',
      'How this works?',
      [$this, 'diffy_introduction_section_callback'],
      'reading'
    );

    add_settings_field(
      'diffy_api_key_settings_field',
      'API Key', [$this, 'diffy_api_key_settings_field_callback'],
      'reading',
      'diffy_settings_section'
    );
    add_settings_field(
      'diffy_project_id_settings_field',
      'Project ID', [$this, 'diffy_project_id_settings_field_callback'],
      'reading',
      'diffy_settings_section'
    );
  }

  // section content cb
  public function diffy_introduction_section_callback() {
    echo '<p>This module integrates <a href="https://diffy.website">visual regression testing</a> to your plugins update process.</p>';
  }

  // Field callback for 'diffy_api_key'
  public function diffy_api_key_settings_field_callback() {
    // get the value of the setting we've registered with register_setting()
    $setting = get_option('diffy_api_key');
    // output the field
    ?>
    <input type="text" name="diffy_api_key" value="<?php echo isset( $setting ) ? esc_attr( $setting ) : ''; ?>">
    <?php
  }

  // Field callback for 'diffy_api_key'
  public function diffy_project_id_settings_field_callback() {
    // get the value of the setting we've registered with register_setting()
    $setting = get_option('diffy_project_id');
    // output the field
    ?>
    <input type="text" name="diffy_project_id" value="<?php echo isset( $setting ) ? esc_attr( $setting ) : ''; ?>">
    <?php
  }

  public function displayPluginAdminDashboard() {
    // check user capabilities
    if ( ! current_user_can( 'manage_options' ) ) {
      return;
    }
    require_once 'wordpress-diffy-settings-form.php';
  }

}
