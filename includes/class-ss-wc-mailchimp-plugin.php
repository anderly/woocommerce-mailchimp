<?php 

/**
 * WooCommerce MailChimp plugin main class
 */
final class SS_WC_MailChimp_Plugin {

	private static $_instance;

	public static function version() {
		$plugin_data = get_plugin_data( SS_WC_MAILCHIMP_FILE );
		$plugin_version = $plugin_data['Version'];
		return $plugin_version;
	}

	/**
	 * Singleton instance
	 *
	 * @return SS_WC_MailChimp_Plugin   SS_WC_MailChimp_Plugin object
	 */
	public static function get_instance() {

		if ( empty( self::$_instance ) ) {
			self::$_instance = new self;
		}

		return self::$_instance;
	}

	/**
	 * Constructor
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {

		$this->id         = 'mailchimp';
		$this->namespace  = 'ss_wc_' . $this->id;
		$this->label      = __( 'MailChimp', $this->namespace );

		$this->settings_url = admin_url( 'admin.php?page=wc-settings&tab=' . $this->id );

		$this->define_constants();

		$this->includes();

		$this->init();

		$this->add_hooks();

		do_action( 'ss_wc_mailchimp_loaded' );

	} //end function __construct

	/**
	 * Define Plugin Constants.
	 */
	private function define_constants() {
		
		global $woocommerce;

		$settings_url = admin_url( 'admin.php?page=woocommerce_settings&tab=integration&section=mailchimp' );

		if ( $woocommerce->version >= '2.1' ) {
			$settings_url = admin_url( 'admin.php?page=wc-settings&tab=integration&section=mailchimp' );
		}

		$this->define( 'SS_WC_MAILCHIMP_SETTINGS_URL', $this->settings_url );

	}

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
	}

	/**
	 * Include required core plugin files
	 */
	public function includes() {

		require_once( 'class-ss-wc-mailchimp-handler.php' );

	}

	/**
	 * Initialize the plugin
	 * @return void
	 */
	private function init() {

		if ( ! class_exists( 'WC_Integration' ) )
			return;

		// Set up localization.
		$this->load_plugin_textdomain();

	}

	/**
	 * Load Localization files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 *
	 * Locales found in:
	 *      - WP_LANG_DIR/woocommerce-mailchimp/woocommerce-mailchimp-LOCALE.mo
	 *      - WP_CONTENT_DIR/plugins/woocommerce-mailchimp/languages/woocommerce-mailchimp-LOCALE.mo
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), $this->namespace );

		load_textdomain( $this->namespace, WP_LANG_DIR . '/woocommerce-mailchimp/woocommerce-mailchimp-' . $locale . '.mo' );
		load_plugin_textdomain( $this->namespace, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Add plugin hooks
	 */
	private function add_hooks() {
		// Add the "Settings" links on the Plugins administration screen
		if ( is_admin() ) {
			add_filter( 'plugin_action_links_' . plugin_basename( SS_WC_MAILCHIMP_FILE ), array( $this, 'action_links' ) );
			// add_filter( 'woocommerce_integrations', array( $this, 'add_mailchimp_integration' ) );
			add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_mailchimp_settings' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts') );

		}

		SS_WC_MailChimp_Handler::get_instance();
	}

	/**
	 * Add Settings link to plugins list
	 *
	 * @param  array $links Plugin links
	 * @return array        Modified plugin links
	 */
	public function action_links( $links ) {
		$plugin_links = array(
			'<a href="' . SS_WC_MAILCHIMP_SETTINGS_URL . '">' . __( 'Settings', $this->namespace ) . '</a>',
		);

		return array_merge( $plugin_links, $links );
	}

	/**
	 * Add the Integration to WooCommerce
	 */
	public function add_mailchimp_integration( $integrations ) {

		require_once( 'class-ss-wc-integration-mailchimp.php' );

		$integrations[] = 'SS_WC_Integration_MailChimp';

		return $integrations;
	}

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
		wp_register_script( 'woocommerce-mailchimp-admin', SS_WC_MAILCHIMP_PLUGIN_URL . '/assets/js/woocommerce-mailchimp-admin.js', array( 'jquery' ), self::version() );
		wp_register_style( 'woocommerce-mailchimp', SS_WC_MAILCHIMP_PLUGIN_URL . '/assets/css/style.css', array(), self::version() );

		// Localize javascript messages
		$translation_array = array(
			'connecting_to_mailchimp' 		=> __( 'Connecting to MailChimp', $this->namespace ),
			'error_loading_lists' 			=> __( 'Error loading lists. Please check your api key.', $this->namespace ),
			'error_loading_groups' 			=> __( 'Error loading groups. Please check your MailChimp Interest Groups for the selected list.', $this->namespace ),
			'select_groups_placeholder'		=> __( 'Select one or more groups (optional)', $this->namespace ),
			'interest_groups_not_enabled' 	=> __( 'This list does not have interest groups enabled', $this->namespace ),
		);
		wp_localize_script( 'woocommerce-mailchimp-admin', 'SS_WC_MailChimp_Messages', $translation_array );

		// Scripts
		wp_enqueue_script( 'woocommerce-mailchimp-admin' );

		// Styles
		wp_enqueue_style( 'woocommerce-mailchimp' );

	} //end function enqueue_scripts

	/**
	 * Plugin activate function.
	 *
	 * @access public
	 * @static
	 * @param mixed $network_wide
	 * @return void
	 */
	public static function activate( $network_wide = false ) {

		require_once( 'class-ss-wc-mailchimp-migrator.php' );

		SS_WC_MailChimp_Migrator::migrate( self::version() );

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

		

	} //end function deactivate

} //end final class SS_WC_MailChimp_Plugin