<?php
/**
 * Settings page and one-click registration.
 *
 * @package Diffy
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Admin settings for the Diffy plugin.
 */
class Diffy_Admin {

	/**
	 * Plugin slug, used as the settings page slug.
	 *
	 * @var string
	 */
	protected $plugin_name;

	/**
	 * Plugin version, used for asset cache busting.
	 *
	 * @var string
	 */
	protected $version;

	/**
	 * Diffy_Admin constructor.
	 *
	 * @param string $plugin_name Plugin slug.
	 * @param string $version     Plugin version.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		add_action( 'admin_menu', array( $this, 'addPluginAdminMenu' ), 9 );
		add_action( 'admin_init', array( $this, 'registerAndBuildFields' ) );

		// Add javascript to the form.
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );
		add_action( 'wp_ajax_diffy_register', array( $this, 'diffy_register' ) );
	}

	/**
	 * Add javascript to the settings form.
	 *
	 * @param string $hook The current admin page.
	 */
	public function scripts( $hook ) {
		if ( 'toplevel_page_wordpress-diffy' !== $hook ) {
			return;
		}
		wp_enqueue_script(
			'diffy-settings-form',
			DIFFY_PLUGIN_URL . 'js/scripts.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		wp_localize_script(
			'diffy-settings-form',
			'diffySettings',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'diffy_register' ),
				'i18n'    => array(
					'registering' => __( 'Registering an account and creating a project…', 'diffy' ),
					/* translators: 1: Email address, 2: Password. */
					'created'     => __( 'Diffy account and project created with your email address %1$s and the password %2$s. Please save these credentials; you can always reset the password later.', 'diffy' ),
					'failed'      => __( 'Please open the Diffy app, create an API key and a project, and enter them below.', 'diffy' ),
					'appUrl'      => 'https://app.diffy.website/#/register',
					'appLink'     => __( 'Open the Diffy app', 'diffy' ),
				),
			)
		);
	}

	/**
	 * Register an account with Diffy.
	 */
	public function diffy_register() {
		check_ajax_referer( 'diffy_register', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'error_message' => __( 'You are not allowed to change these settings.', 'diffy' ) ), 403 );
		}

		$current_user = wp_get_current_user();
		$email        = $current_user->user_email;
		$password     = wp_generate_password();

		$email_parts      = explode( '@', $email );
		$email_first_part = (string) array_shift( $email_parts );
		$name             = preg_replace( '/[^a-z0-9]/i', '', $current_user->display_name . $email_first_part );

		// We save internally that this user is created from the WordPress plugin "one click registration".
		$location = 'https://diffy.website?utm_source=wordpress&utm_campaign=plugin';

		// Create User.
		try {
			$diffy_user = \Diffy\User::create( $name, $email, $password, $location );
		} catch ( \Exception $e ) {
			wp_send_json_error(
				array(
					'error_message' => $e->getMessage(),
				)
			);
		}

		\Diffy\Diffy::setApiToken( $diffy_user['token'] );

		// Check if site is accessible early.
		$site_url = home_url();
		try {
			$scan_urls_response = \Diffy\Project::scan( $site_url );
		} catch ( \Exception $e ) {
			wp_send_json_error(
				array(
					'error_message' => sprintf(
						/* translators: 1: Email address, 2: Password. */
						__( 'Your site is not accessible. Please make sure it can be reached from the internet. If it is a local development site, use this plugin on a hosted copy instead. We have already created a Diffy account for you with the email address %1$s and the password %2$s', 'diffy' ),
						$email,
						$password
					),
				)
			);
		}

		// Limit the project to 50 pages initially.
		$scan_urls = array_slice( (array) $scan_urls_response['urls'], 0, 50 );

		if ( count( $scan_urls ) <= 1 ) {
			wp_send_json_error(
				array(
					'error_message' => sprintf(
						/* translators: 1: Email address, 2: Password. */
						__( 'Diffy found only one page on your site, so it may not be accessible from the internet. If it is a local development site, use this plugin on a hosted copy instead. We have created a Diffy account for you with the email address %1$s and the password %2$s', 'diffy' ),
						$email,
						$password
					),
				)
			);
		}

		// Create project.
		try {
			$project_id = \Diffy\Project::create( $site_url, $scan_urls );
		} catch ( \Exception $e ) {
			wp_send_json_error(
				array(
					'error_message' => __( 'Was not able to create a Diffy project', 'diffy' ),
				)
			);
		}

		// Create API Key.
		try {
			$key_create_response = \Diffy\Key::create( 'WP-Update' );
		} catch ( \Exception $e ) {
			wp_send_json_error(
				array(
					'error_message' => __( 'Was not able to create an API key', 'diffy' ),
				)
			);
		}

		$key = array_pop( $key_create_response['keys'] )['key'];

		wp_send_json_success(
			array(
				'email'            => $email,
				'password'         => $password,
				'diffy_api_key'    => $key,
				'diffy_project_id' => $project_id,
			)
		);
	}

	/**
	 * Register site-admin nav menu elements.
	 */
	public function addPluginAdminMenu() {
		add_menu_page(
			__( 'Diffy settings', 'diffy' ),
			'Diffy',
			'manage_options',
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
		// Two settings: API Key and Project ID.
		register_setting(
			'diffy',
			'diffy_api_key',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		register_setting(
			'diffy',
			'diffy_project_id',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'diffy_validate_project_id_callback' ),
			)
		);

		// Introduction section.
		add_settings_section(
			'diffy_settings_section',
			__( 'How does this work?', 'diffy' ),
			array( $this, 'diffy_introduction_section_callback' ),
			'diffy'
		);

		add_settings_field(
			'diffy_api_key_settings_field',
			__( 'API Key', 'diffy' ),
			array( $this, 'diffy_api_key_settings_field_callback' ),
			'diffy',
			'diffy_settings_section',
			array( 'label_for' => 'diffy_api_key' )
		);
		add_settings_field(
			'diffy_project_id_settings_field',
			__( 'Project ID', 'diffy' ),
			array( $this, 'diffy_project_id_settings_field_callback' ),
			'diffy',
			'diffy_settings_section',
			array( 'label_for' => 'diffy_project_id' )
		);
	}

	/**
	 * Introduction section content.
	 */
	public function diffy_introduction_section_callback() {
		$allowed = array(
			'a'  => array(
				'href'   => array(),
				'target' => array(),
				'rel'    => array(),
			),
			'br' => array(),
			'i'  => array(),
		);
		?>
		<p><?php echo wp_kses( __( 'This plugin adds <a target="_blank" rel="noopener" href="https://diffy.website/wordpress/">visual regression testing</a> to your plugin updates: Diffy screenshots your pages before and after the update and shows you what changed.', 'diffy' ), $allowed ); ?></p>
		<p><?php echo wp_kses( __( 'The plugin uses the Diffy API, which is included in Diffy\'s <a target="_blank" rel="noopener" href="https://diffy.website/pricing/">paid plans</a>.', 'diffy' ), $allowed ); ?></p>
		<p><?php esc_html_e( 'Steps to set up:', 'diffy' ); ?></p>
		<ol>
			<li><?php echo wp_kses( __( '<a target="_blank" rel="noopener" href="https://app.diffy.website/#/register">Register an account</a>, create an API key in the Diffy app under your account settings, and paste it below.', 'diffy' ), $allowed ); ?></li>
			<li><?php esc_html_e( 'Create a project in Diffy with this site as the production environment, and add the pages you want to check.', 'diffy' ); ?></li>
			<li><?php echo wp_kses( __( 'Open the project in Diffy and copy its ID from the URL below.<br><i>(For example, if the URL is https://app.diffy.website/#/projects/1, the project ID is 1.)</i>', 'diffy' ), $allowed ); ?></li>
			<li><?php esc_html_e( 'On the Plugins screen, click "update now with Diffy visual testing" next to a plugin update. Diffy compares the before and after screenshots and emails you the results.', 'diffy' ); ?></li>
		</ol>
		<p><i><?php esc_html_e( 'This website must be reachable from the internet so Diffy can take screenshots of it.', 'diffy' ); ?></i></p>
		<?php
	}

	/**
	 * Field callback for 'diffy_api_key'.
	 */
	public function diffy_api_key_settings_field_callback() {
		$setting = get_option( 'diffy_api_key' );
		?>
		<input type="text" class="regular-text" id="diffy_api_key" name="diffy_api_key" value="<?php echo esc_attr( (string) $setting ); ?>">
		<?php
	}

	/**
	 * Field callback for 'diffy_project_id'.
	 */
	public function diffy_project_id_settings_field_callback() {
		$setting = get_option( 'diffy_project_id' );
		?>
		<input type="text" class="small-text" id="diffy_project_id" name="diffy_project_id" value="<?php echo esc_attr( (string) $setting ); ?>">
		<?php
	}

	/**
	 * Render the settings page.
	 */
	public function displayPluginAdminDashboard() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		require_once __DIR__ . '/wordpress-diffy-settings-form.php';
	}

	/**
	 * Validate the project ID and check the API key and project with Diffy.
	 *
	 * @param string $project_id Submitted project ID.
	 * @return string
	 */
	public function diffy_validate_project_id_callback( $project_id ) {
		$project_id = trim( sanitize_text_field( (string) $project_id ) );

		// The API key field is saved before the project ID, so this is the submitted key.
		$diffy_api_key = get_option( 'diffy_api_key' );

		if ( empty( $diffy_api_key ) ) {
			add_settings_error( 'diffy', 'diffy', __( 'Please add an API key.', 'diffy' ), 'error' );
			return $project_id;
		}

		if ( empty( $project_id ) ) {
			add_settings_error( 'diffy', 'diffy', __( 'Please add a project ID.', 'diffy' ), 'error' );
			return $project_id;
		}

		if ( ! ctype_digit( $project_id ) ) {
			add_settings_error( 'diffy', 'diffy', __( 'The project ID should be a number.', 'diffy' ), 'error' );
			return $project_id;
		}

		try {
			\Diffy\Diffy::setApiKey( $diffy_api_key );
		} catch ( \Exception $exception ) {
			add_settings_error( 'diffy', 'diffy', __( 'Diffy did not accept this API key.', 'diffy' ), 'error' );
			return $project_id;
		}

		$project = null;
		try {
			$project = \Diffy\Project::get( (int) $project_id );
		} catch ( \Exception $exception ) {
			$project = null;
		}

		if ( empty( $project ) ) {
			/* translators: %s: Project ID. */
			add_settings_error( 'diffy', 'diffy', sprintf( __( 'Diffy could not find project %s with this API key.', 'diffy' ), $project_id ), 'error' );
			return $project_id;
		}

		add_settings_error( 'diffy', 'diffy', __( 'We have verified your API key and project ID. You are good to go.', 'diffy' ), 'success' );

		return $project_id;
	}
}
