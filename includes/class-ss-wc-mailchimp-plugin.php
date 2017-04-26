<?php 

/**
 * WooCommerce MailChimp plugin main class
 */
final class SS_WC_MailChimp_Plugin {

	/**
	 * Plugin version
	 * @var string
	 */
	private static $version = '2.1.0';

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
	 * Plugin compatibility checker
	 * @return SS_WC_MailChimp_Compatibility
	 */
	public $compatibility;

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
	public static function get_instance() {

		if ( empty( self::$instance ) && ! ( self::$instance instanceof SS_WC_MailChimp_Plugin ) ) {

			self::$instance = new SS_WC_MailChimp_Plugin;
			self::$instance->define_constants();

			self::$instance->save_settings();
			self::$instance->settings();
			self::$instance->includes();
			self::$instance->mailchimp();
			self::$instance->handler = SS_WC_MailChimp_Handler::get_instance();
			self::$instance->compatibility = SS_WC_MailChimp_Compatibility::get_instance();
			self::$instance->admin_notices = new SS_WC_MailChimp_Admin_Notices;
			self::$instance->load_plugin_textdomain();

			//if ( self::$instance->compatibility->is_valid() ) {
				self::update();
				self::$instance->add_hooks();
				do_action( 'ss_wc_mailchimp_loaded' );
			//}

		}

		return self::$instance;

	} //end function instance

	/**
	 * Gets the plugin db settings
	 * @param  boolean $refresh refresh the settings from DB?
	 * @return array  The plugin db settings
	 */
	public function settings( $refresh = false ) {

		if ( empty( $this->settings ) || true === $refresh ) {

			$defaults = require( SS_WC_MAILCHIMP_DIR . 'config/default-settings.php' );
			$defaults = apply_filters( 'ss_wc_mailchimp_default_settings', $defaults );
			$settings = array();

			foreach ( $defaults as $key => $default_value ) {

				$setting_value = get_option( $this->namespace_prefixed( $key ) );

				$settings[ $key ] = $setting_value ? $setting_value : $default_value;
			}

			$merged_settings = apply_filters( 'ss_wc_mailchimp_settings', array_merge( $defaults, $settings ) );

			$this->settings = $merged_settings;

			$this->mailchimp( $settings['api_key'] );

		}

		return $this->settings;
	}

	/**
	 * api_key function.
	 * @return string MailChimp API Key
	 */
	public function api_key() {
		return $this->settings[ 'api_key' ];
	}

	/**
	 * is_enabled function.
	 *
	 * @access public
	 * @return boolean
	 */
	public function is_enabled() {
		return 'yes' === $this->settings[ 'enabled' ];
	}

	/**
	 * has_list function.
	 *
	 * @access public
	 * @return boolean
	 */
	public function has_list() {
		$has_list = false;

		if ( $this->get_list() ) {
			$has_list = true;
		}
		return apply_filters( 'ss_wc_mailchimp_has_list', $has_list );
	}

	/**
	 * occurs function
	 * @return string
	 */
	public function occurs() {
		return $this->settings[ 'occurs' ];
	}

	/**
	 * get_list function.
	 *
	 * @access public
	 * @return string MailChimp list ID
	 */
	public function get_list() {
		return $this->settings[ 'list' ];
	}

	/**
	 * double_opt_in function.
	 *
	 * @access public
	 * @return boolean
	 */
	public function double_opt_in() {
		return 'yes' === $this->settings[ 'double_opt_in' ];
	}

	/**
	 * display_opt_in function.
	 *
	 * @access public
	 * @return boolean
	 */
	public function display_opt_in() {
		return 'yes' === $this->settings[ 'display_opt_in' ];
	}

	/**
	 * opt_in_label function.
	 *
	 * @access public
	 * @return string
	 */
	public function opt_in_label() {
		return $this->settings[ 'opt_in_label' ];
	}

	/**
	 * opt_in_checkbox_default_status function.
	 *
	 * @access public
	 * @return string
	 */
	public function opt_in_checkbox_default_status() {
		return $this->settings[ 'opt_in_checkbox_default_status' ];
	}

	/**
	 * opt_in_checkbox_display_location function.
	 *
	 * @access public
	 * @return string
	 */
	public function opt_in_checkbox_display_location() {
		return $this->settings[ 'opt_in_checkbox_display_location' ];
	}

	/**
	 * interests function.
	 *
	 * @access public
	 * @return array
	 */
	public function interest_groups() {
		return $this->settings[ 'interest_groups' ];
	}

	/**
	 * has_api_key function.
	 *
	 * @access public
	 * @return boolean
	 */
	public function has_api_key() {
		$api_key = $this->api_key();
		return !empty( $api_key );
	}

	/**
	 * is_valid function.
	 *
	 * @access public
	 * @return boolean
	 */
	public function is_valid() {
		return $this->is_enabled() && $this->has_api_key() && $this->has_list();
	}

	/**
	 * debug_enabled function.
	 *
	 * @access public
	 * @return boolean
	 */
	public function debug_enabled() {
		return 'yes' === $this->settings[ 'debug' ];
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

			require_once( SS_WC_MAILCHIMP_DIR . 'includes/class-ss-wc-mailchimp.php' );
			$this->mailchimp = new SS_WC_MailChimp( $api_key, $debug );

			delete_transient( 'sswcmc_lists' );
		}

		return $this->mailchimp;

	} //end function mailchimp

	/**
	 * Define Plugin Constants.
	 */
	private function define_constants() {

		// Minimum supported version of WordPress
		$this->define( 'SS_WC_MAILCHIMP_MIN_WP_VERSION', '3.5.1' );

		// Minimum supported version of WooCommerce
		$this->define( 'SS_WC_MAILCHIMP_MIN_WC_VERSION', '2.2.0' );

		// Minimum supported version of PHP
		$this->define( 'SS_WC_MAILCHIMP_MIN_PHP_VERSION', '5.4.0' );

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

		require_once( SS_WC_MAILCHIMP_DIR . 'includes/lib/class-ss-system-info.php' );

		require_once( SS_WC_MAILCHIMP_DIR . 'includes/helper-functions.php' );

		require_once( SS_WC_MAILCHIMP_DIR . 'includes/class-ss-wc-mailchimp-compatibility.php' );

		require_once( SS_WC_MAILCHIMP_DIR . 'includes/class-ss-wc-mailchimp-admin-notices.php' );

		require_once( SS_WC_MAILCHIMP_DIR . 'includes/class-ss-wc-mailchimp-api.php' );

		require_once( SS_WC_MAILCHIMP_DIR . 'includes/class-ss-wc-mailchimp.php' );

		require_once( SS_WC_MAILCHIMP_DIR . 'includes/class-ss-wc-mailchimp-handler.php' );

	} //end function includes

	/**
	 * Add plugin hooks
	 */
	private function add_hooks() {

		/** Register hooks that are fired when the plugin is activated and deactivated. */
		register_activation_hook( SS_WC_MAILCHIMP_FILE, array( __CLASS__, 'activate' ) );
		register_deactivation_hook( SS_WC_MAILCHIMP_FILE, array( __CLASS__, 'deactivate' ) );

		// Add the "Settings" links on the Plugins administration screen
		if ( is_admin() ) {

			add_filter( 'plugin_action_links_' . plugin_basename( SS_WC_MAILCHIMP_FILE ), array( $this, 'action_links' ) );

			add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_mailchimp_settings' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts') );

		}

	} //end function add_hooks

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

		if ( ! is_array( $settings ) ) {
			$settings = [];
		}

		$settings[] = require_once( SS_WC_MAILCHIMP_DIR . 'includes/class-ss-wc-settings-mailchimp.php' );

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

	/**
	 * Handles running plugin upgrades if necessary
	 * @return void
	 */
	public static function update() {

		require_once( 'class-ss-wc-mailchimp-migrator.php' );

		SS_WC_MailChimp_Migrator::migrate( self::version() );

	} //end function update

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
	 * Check whether WooCommerce MailChimp is network activated
	 * @since 1.0
	 * @return bool
	 */
	public static function is_network_activated() {
		return is_multisite() && ( function_exists( 'is_plugin_active_for_network' ) && is_plugin_active_for_network( 'woocommerce-mailchimp/woocommerce-mailchimp.php' ) );
	}

	/**
	 * Returns namespace prefixed value
	 * @param  string  $suffix  The suffix to prefix
	 * @return string
	 */
	private function namespace_prefixed( $suffix ) {

		return $this->namespace . '_' . $suffix;

	} // end function namespace_prefixed

} //end final class SS_WC_MailChimp_Plugin