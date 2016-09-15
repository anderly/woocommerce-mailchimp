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

		$this->define( 'WOOCOMMERCE_MAILCHIMP_SETTINGS_URL', $settings_url);

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

		require_once( 'class-ss-wc-integration-mailchimp.php' );

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
		$locale = apply_filters( 'plugin_locale', get_locale(), 'ss_wc_mailchimp' );

		load_textdomain( 'ss_wc_mailchimp', WP_LANG_DIR . '/woocommerce-mailchimp/woocommerce-mailchimp-' . $locale . '.mo' );
		load_plugin_textdomain( 'ss_wc_mailchimp', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Add plugin hooks
	 */
	private function add_hooks() {
		// Add the "Settings" links on the Plugins administration screen
		add_filter( 'plugin_action_links_' . plugin_basename( SS_WC_MAILCHIMP_FILE ), array( $this, 'action_links' ) );
		add_filter( 'woocommerce_integrations', array( $this, 'add_mailchimp_integration' ) );
	}

	/**
	 * Add Settings link to plugins list
	 *
	 * @param  array $links Plugin links
	 * @return array        Modified plugin links
	 */
	public function action_links( $links ) {
		$plugin_links = array(
			'<a href="' . WOOCOMMERCE_MAILCHIMP_SETTINGS_URL . '">' . __( 'Settings', 'ss_wc_mailchimp' ) . '</a>',
		);

		return array_merge( $plugin_links, $links );
	}

	/**
	 * Add the Integration to WooCommerce
	 */
	public function add_mailchimp_integration( $integrations ) {
		$integrations[] = 'SS_WC_Integration_MailChimp';

		return $integrations;
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