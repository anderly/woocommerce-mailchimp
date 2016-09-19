<?php 

/**
 * WooCommerce MailChimp plugin main class
 */
final class SS_WC_MailChimp_Plugin {

	/**
	 * Plugin version
	 * @var string
	 */
	private static $version = '2.0.9';

	/**
	 * Plugin singleton instance
	 * @var SS_WC_MailChimp_Plugin
	 */
	private static $instance;

	/**
	 * Plugin namespace
	 * @var string
	 */
	private $namespace = 'ss_wc_mailchimp';

	/**
	 * Plugin settings
	 * @var array
	 */
	private $settings;

	/**
	 * Plugin MailChimp helper instance
	 * @var SS_WC_MailChimp
	 */
	private $mailchimp;

	/**
	 * Returns the plugin version
	 * @return string
	 */
	public static function version() {
		return self::$version;
	}

	/**
	 * Singleton instance
	 *
	 * @return SS_WC_MailChimp_Plugin   SS_WC_MailChimp_Plugin object
	 */
	public static function instance() {

		if ( empty( self::$instance ) && ! ( self::$instance instanceof SS_WC_MailChimp_Plugin ) ) {

			self::$instance = new SS_WC_MailChimp_Plugin;
			self::$instance->define_constants();

			add_action( 'plugins_loaded', array( self::$instance, 'load_plugin_textdomain' ) );

			self::$instance->save_settings();
			self::$instance->settings();
			self::$instance->includes();
			self::$instance->mailchimp();
			self::$instance->handler = new SS_WC_MailChimp_Handler();
			self::$instance->add_hooks();

			do_action( 'ss_wc_mailchimp_loaded' );

		}

		return self::$instance;

	} //end function instance

	/**
	 * Gets the plugin db settings
	 * @param  boolean $refresh refresh the settings from DB?
	 * @return array  The plugin db settings
	 */
	public function settings( $refresh = false ) {

		if ( empty( $this->settings ) ) {

			$defaults = require( SS_WC_MAILCHIMP_DIR . 'config/default-settings.php' );
			$settings = array();

			foreach ( $defaults as $key => $val ) {
				$settings[ $key ] = get_option( 'ss_wc_mailchimp_' . $key );
			}

			$this->settings = array_merge( $defaults, $settings );

		}

		return $this->settings;
	}

	/**
	 * Saves the settings back to the DB
	 * @return void
	 */
	public function save_settings() {

		$settings = $this->settings();

		foreach ( $settings as $key => $value ) {
			update_option( $this->namespace_prefixed( $key ), $value );
		}

	} //end function save_settings

	/**
	 * Gets the MailChimp Helper
	 * @param  string  $api_key MailChimp API Key
	 * @param  boolean $debug   Debug mode enabled/disabled
	 * @return SS_WC_MailChimp  MailChimp Helper class
	 */
	public function mailchimp( $api_key = null, $debug = false ) {

		$settings = $this->settings();

		if ( empty( $this->mailchimp ) || ! is_null( $api_key ) ) {

			$api_key = $api_key ? $api_key : $settings['api_key'];
			$debug   = $debug   ? $debug   : $settings['debug'];

			$this->mailchimp = new SS_WC_MailChimp( $api_key, $debug );

			delete_transient( 'sswcmc_lists' );
		}

		return $this->mailchimp;

	} //end function mailchimp

	/**
	 * Define Plugin Constants.
	 */
	private function define_constants() {

		// Plugin version.
		$this->define( 'SS_WC_MAILCHIMP_VERSION', self::version() );

		// Plugin Folder Path.
		$this->define( 'SS_WC_MAILCHIMP_DIR', plugin_dir_path( SS_WC_MAILCHIMP_FILE ) );

		// Plugin Folder URL.
		$this->define('SS_WC_MAILCHIMP_URL', plugin_dir_url( SS_WC_MAILCHIMP_FILE ) );

		$settings_url = admin_url( 'admin.php?page=wc-settings&tab=mailchimp' );

		$this->define( 'SS_WC_MAILCHIMP_SETTINGS_URL', $settings_url );

	} //function define_constants

	/**
	 * Define constant if not already set.
	 *
	 * @param  string $name
	 * @param  string|bool $value
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	} //function define

	/**
	 * Include required core plugin files
	 */
	public function includes() {

		require_once( 'lib/class-ss-system-info.php' );

		require_once( 'class-ss-wc-mailchimp-api.php' );

		require_once( 'class-ss-wc-mailchimp.php' );

		require_once( 'class-ss-wc-mailchimp-handler.php' );

	} //end function includes

	/**
	 * Load Localization files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 *
	 * Locales found in:
	 *      - WP_LANG_DIR/plugins/woocommerce-mailchimp/woocommerce-mailchimp-{lang}_{country}.mo
	 *      - WP_CONTENT_DIR/plugins/woocommerce-mailchimp/languages/woocommerce-mailchimp-{lang}_{country}.mo
	 */
	public function load_plugin_textdomain() {

		// Set filter for plugin's languages directory.
		$woocommerce_mailchimp_lang_dir  = dirname( plugin_basename( SS_WC_MAILCHIMP_FILE ) ) . '/languages/';

		// Traditional WordPress plugin locale filter.
		// get locale in {lang}_{country} format (e.g. en_US)
		$locale = apply_filters( 'plugin_locale', get_locale(), 'woocommerce-mailchimp' );

		$mofile = sprintf( '%1$s-%2$s.mo', 'woocommerce-mailchimp', $locale );

		// Look for wp-content/languages/woocommerce-mailchimp/woocommerce-mailchimp-{lang}_{country}.mo
		$mofile_global1 = WP_LANG_DIR . '/woocommerce-mailchimp/' . $mofile;

		// Look in wp-content/languages/plugins/woocommerce-mailchimp
		$mofile_global2 = WP_LANG_DIR . '/plugins/woocommerce-mailchimp/' . $mofile;

		if ( file_exists( $mofile_global1 ) ) {

			load_textdomain( 'woocommerce-mailchimp', $mofile_global1 );

		} elseif ( file_exists( $mofile_global2 ) ) {

			load_textdomain( 'woocommerce-mailchimp', $mofile_global2 );

		} else {

			// Load the default language files.
			load_plugin_textdomain( 'woocommerce-mailchimp', false, $woocommerce_mailchimp_lang_dir );

		}

	} //end function load_plugin_textdomain

	/**
	 * Add plugin hooks
	 */
	private function add_hooks() {

		// Add the "Settings" links on the Plugins administration screen
		if ( is_admin() ) {

			add_filter( 'plugin_action_links_' . plugin_basename( SS_WC_MAILCHIMP_FILE ), array( $this, 'action_links' ) );

			add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_mailchimp_settings' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts') );

		}

	} //end function add_hooks

	/**
	 * Add Settings link to plugins list
	 *
	 * @param  array $links Plugin links
	 * @return array        Modified plugin links
	 */
	public function action_links( $links ) {
		$plugin_links = array(
			'<a href="' . SS_WC_MAILCHIMP_SETTINGS_URL . '">' . __( 'Settings', 'woocommerce-mailchimp' ) . '</a>',
		);

		return array_merge( $plugin_links, $links );

	} //end function action_links

	/**
	 * Add the MailChimp settings tab to WooCommerce
	 */
	function add_mailchimp_settings( $settings ) {

		$settings[] = require_once( 'class-ss-wc-settings-mailchimp.php' );

		return $settings;

	} //end function add_mailchimp_settings

	/**
     * Load scripts required for admin
     * 
     * @access public
     * @return void
     */
    public function enqueue_scripts() {

    	// Plugin scripts and styles
		wp_register_script( 'woocommerce-mailchimp-admin', SS_WC_MAILCHIMP_URL . 'assets/js/woocommerce-mailchimp-admin.js', array( 'jquery' ), self::version() );
		wp_register_style( 'woocommerce-mailchimp', SS_WC_MAILCHIMP_URL . 'assets/css/style.css', array(), self::version() );

		// Localize javascript messages
		$translation_array = array(
			'connecting_to_mailchimp'       => __( 'Connecting to MailChimp', 'woocommerce-mailchimp' ),
			'error_loading_account'         => __( 'Error. Please check your api key.', 'woocommerce-mailchimp' ),
			'error_loading_groups'          => __( 'Error loading groups. Please check your MailChimp Interest Groups for the selected list.', 'woocommerce-mailchimp' ),
			'select_groups_placeholder'     => __( 'Select one or more groups (optional)', 'woocommerce-mailchimp' ),
			'interest_groups_not_enabled'   => __( 'This list does not have interest groups enabled', 'woocommerce-mailchimp' ),
		);
		wp_localize_script( 'woocommerce-mailchimp-admin', 'SS_WC_MailChimp_Messages', $translation_array );

		// Scripts
		wp_enqueue_script( 'woocommerce-mailchimp-admin' );

		// Styles
		wp_enqueue_style( 'woocommerce-mailchimp' );

	} //end function enqueue_scripts

	public static function update() {
		require_once( 'class-ss-wc-mailchimp-migrator.php' );

		SS_WC_MailChimp_Migrator::migrate( self::version() );
	}

	/**
	 * Plugin activate function.
	 *
	 * @access public
	 * @static
	 * @param mixed $network_wide
	 * @return void
	 */
	public static function activate( $network_wide = false ) {

		self::update();

	} //end function activate

	/**
	 * Plugin deactivate function.
	 *
	 * @access public
	 * @static
	 * @param mixed $network_wide
	 * @return void
	 */
	public static function deactivate( $network_wide ) {

		// Placeholder

	} //end function deactivate

	/**
	 * Returns namespace prefixed value
	 * @param  string  $suffix  The suffix to prefix
	 * @return string
	 */
	private function namespace_prefixed( $suffix ) {

		return $this->namespace . '_' . $suffix;

	} // end function namespace_prefixed

} //end final class SS_WC_MailChimp_Plugin