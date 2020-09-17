<?php

// Exit if accessed directly.
defined('ABSPATH') || exit;

class Diffy_Admin {

  protected $plugin_name;
  protected $version;

  /**
   * Diffy_Admin constructor.
   */
  public function __construct($plugin_name, $version) {
    $this->plugin_name = $plugin_name;
    $this->version = $version;
    add_action('admin_menu', array($this, 'addPluginAdminMenu'), 9);
    add_action('admin_init', array($this, 'registerAndBuildFields'));

    // Add javascript to the form.
    add_action('admin_enqueue_scripts', array($this, 'scripts'));
    add_action( 'wp_ajax_diffy_register', array( $this, 'diffy_register' ) );
  }

  /**
   * Add javascript to the form.
   */
  public function scripts($hook) {
    if ($hook != 'toplevel_page_wordpress-diffy') {
      return;
    }
    wp_enqueue_script(
      'diffy-settings-form',
      plugin_dir_url(__FILE__) . '../js/scripts.js',
      array('jquery'),
      NULL,
      TRUE
    );

    // set variables for script
    wp_localize_script(
      'diffy-settings-form',
      'settings',
      array(
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
      )
    );
  }

  /**
   * Register an account with Diffy.
   */
  public function diffy_register() {

    $current_user = wp_get_current_user();
    $email = $current_user->user_email;
    $password = wp_generate_password();

    $email_first_part = array_shift(explode('@', $email));
    $name = preg_replace('/[^a-z0-9]/i', '', $current_user->display_name . $email_first_part);

    // We save internally that this user is created from wordpress plugin "one click registration".
    $location = 'https://diffy.website?utm_source=wordpress&utm_campaign=plugin';

    // Create User.
    try {
      $diffy_user = \Diffy\User::create($name, $email, $password, $location);
    }
    catch (\Exception $e) {
      wp_send_json_error([
        'error_message' => $e->getMessage(),
      ]);
    }

    $token = $diffy_user['token'];

    \Diffy\Diffy::setApiToken($token);

    // Check if site is accessible early.
    global $wp;
    $site_url = home_url($wp->request);
    try {
      $scan_urls_response = \Diffy\Project::scan($site_url);
    }
    catch (\Exception $e) {
      wp_send_json_error([
        'error_message' => 'Your site is not accessible. Please make sure it can be accessed from Internet. If it is local development, please use this plugin on hosting environment instead. We have already created an account for you with email address ' . $email . ' and password ' . $password,
      ]);
    }

    $scan_urls = $scan_urls_response['urls'];
    // Limit project to 50 pages initially. By default after test project
    // newly registered account has 79 pages left.
    $scan_urls = array_splice($scan_urls, 0, 50);

    if (count($scan_urls) == 1) {
      wp_send_json_error([
        'error_message' => 'Looks like your site is not accessible. We have found only one internal page. Please make sure it can be accessed from Internet. If it is local development, please use this plugin on hosting environment instead. We have created an account for you in Diffy email ' . $email . ' and password ' . $password,
      ]);
    }

    // Create project.
    try {
      $project_id = \Diffy\Project::create($site_url, $scan_urls);
    }
    catch (\Exception $e) {
      wp_send_json_error([
        'error_message' => 'Was not able to create a Diffy project',
      ]);
    }

    // Create API Key.
    try {
      $key_create_response = \Diffy\Key::create('WP-Update');
    }
    catch (\Exception $e) {
      wp_send_json_error([
        'error_message' => 'Was not able to create a API Key',
      ]);
    }

    $key = array_pop($key_create_response['keys'])['key'];

    wp_send_json_success( [
      'email' => $email,
      'password' => $password,
      'diffy_api_key' => $key,
      'diffy_project_id' => $project_id,
    ] );
  }

  /**
   * Register site-admin nav menu elements.
   */
  public function addPluginAdminMenu() {
    add_menu_page(
      $this->plugin_name,
      'Diffy',
      'administrator',
      $this->plugin_name,
      array($this, 'displayPluginAdminDashboard'),
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
    register_setting('other', 'diffy_last_screenshot_id');

    // Introduction section.
    add_settings_section(
      'diffy_settings_section',
      'How this works?',
      [$this, 'diffy_introduction_section_callback'],
      'reading'
    );

    add_settings_field(
      'diffy_api_key_settings_field',
      'API Key',
      [$this, 'diffy_api_key_settings_field_callback'],
      'reading',
      'diffy_settings_section'
    );
    add_settings_field(
      'diffy_project_id_settings_field',
      'Project ID',
      [$this, 'diffy_project_id_settings_field_callback'],
      'reading',
      'diffy_settings_section'
    );
  }

  // section content cb
  public function diffy_introduction_section_callback() {
    echo <<< EOT
        <p>This module integrates <a target="_blank" href="https://diffy.website">visual regression testing</a> to your plugins update process.</p>
        <p>Steps to set up:
          <ol>
              <li><a target="_blank" href="https://app.diffy.website">Register an account</a> and create an API key at <a target="_blank" href="https://app.diffy.website/#/keys">My Account -> Keys</a>. Copy API Key here.</li>
              <li>Create a project in Diffy and save its ID at this form.</li>
              <li>Run plugins update and see comparison of "before" and "after" screenshots created in Diffy. You will receive an email notification with the results.</li>
          </ol>
       </p>
       <p><i>Please note, this website should be publicly accessible so Diffy can take screenshots from it.</i></p>
EOT;
  }

  // Field callback for 'diffy_api_key'
  public function diffy_api_key_settings_field_callback() {
    // get the value of the setting we've registered with register_setting()
    $setting = get_option('diffy_api_key');
    // output the field
    ?>
      <input type="text" name="diffy_api_key"
             value="<?php echo isset($setting) ? esc_attr($setting) : ''; ?>">
    <?php
  }

  // Field callback for 'diffy_api_key'
  public function diffy_project_id_settings_field_callback() {
    // get the value of the setting we've registered with register_setting()
    $setting = get_option('diffy_project_id');
    // output the field
    ?>
      <input type="text" name="diffy_project_id"
             value="<?php echo isset($setting) ? esc_attr($setting) : ''; ?>">
    <?php
  }

  public function displayPluginAdminDashboard() {
    // check user capabilities
    if (!current_user_can('manage_options')) {
      return;
    }
    require_once 'wordpress-diffy-settings-form.php';
  }

}
