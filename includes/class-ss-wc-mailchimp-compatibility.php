<?php
/**
 * Handle issues with plugin and version compatibility
 *
 * @package   WooCommerce MailChimp
 * @author    Saint Systems, LLC
 * @link      http://www.saintsystems.com
 * @copyright Copyright 2016, Saint Systems, LLC
 *
 * @since 2.0.13
 */

/**
 * Handle WooCommerce MailChimp compatibility notices and fallback shortcodes
 * @since 2.0.13
 */
final class SS_WC_MailChimp_Compatibility {

	/**
	 * Plugin singleton instance
	 * @var SS_WC_MailChimp_Compatibility
	 */
	private static $instance = null;

	/**
	 * @var bool Is WooCommerce version valid and is WooCommerce loaded?
	 */
	public static $valid_woocommerce = false;

	/**
	 * @var bool Is the WordPress installation compatible?
	 */
	public static $valid_wordpress = false;

	/**
	 * @var bool Is the server's PHP version compatible?
	 */
	public static $valid_php = false;

	/**
	 * @var array Holder for notices to be displayed in frontend shortcodes if not valid
	 */
	static private $notices = array();

	function __construct() {

		self::$valid_woocommerce = self::check_woocommerce();

		self::$valid_wordpress = self::check_wordpress();

		self::$valid_php = self::check_php();

		$this->add_hooks();
	}

	function add_hooks() {

		add_filter( 'ss_wc_mailchimp/admin/notices', array( $this, 'insert_admin_notices' ) );

	}

	/**
	 * Add the compatibility notices to the other admin notices
	 * @param array $notices
	 *
	 * @return array
	 */
	function insert_admin_notices( $notices = array() ) {

		return array_merge( $notices, self::$notices );

	} //end function insert_admin_notices

	/**
	 * @return SS_WC_MailChimp_Compatibility
	 */
	public static function get_instance() {

		if ( empty( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;

	}

	/**
	 * Is everything compatible with this version of the plugin?
	 * @return bool
	 */
	public static function is_valid() {

		return ( self::is_valid_woocommerce() 
			  && self::is_valid_wordpress() 
			  && self::is_valid_php()
		);

	}

	/**
	 * Is the version of WordPress compatible?
	 * @since 2.0.13
	 */
	static function is_valid_wordpress() {
		return self::$valid_wordpress;
	}

	/**
	 * @since 2.0.13
	 * @return bool
	 */
	static function is_valid_woocommerce() {
		return self::$valid_woocommerce;
	}

	/**
	 * @since 2.0.13
	 * @return bool
	 */
	static function is_valid_php() {
		return self::$valid_php;
	}

	/**
	 * Get admin notices
	 * @since 2.0.13
	 * @return array
	 */
	public static function get_notices() {
		return self::$notices;
	}

	/**
	 * Is the version of PHP compatible?
	 *
	 * @since 2.0.13
	 * @return boolean
	 */
	public static function check_php() {
		if ( false === version_compare( phpversion(), SS_WC_MAILCHIMP_MIN_PHP_VERSION , '>=' ) ) {

			self::$notices['php_version'] = array(
				'class' => 'notice-error',
				'message' => sprintf( __( "%sWooCommerce MailChimp requires PHP Version %s or newer.%s \n\nYou're using Version %s. Please ask your host to upgrade your server's PHP.", 'woocommerce-mailchimp' ), '<h3>', SS_WC_MAILCHIMP_MIN_PHP_VERSION, "</h3>\n\n", '<span style="font-family: Consolas, Courier, monospace;">'.phpversion().'</span>' )
			);

			return false;
		}

		return true;
	}

	/**
	 * Is WordPress compatible?
	 *
	 * @since 2.0.13
	 * @return boolean
	 */
	public static function check_wordpress() {
		global $wp_version;

		if ( version_compare( $wp_version, SS_WC_MAILCHIMP_MIN_WP_VERSION ) <= 0 ) {

			self::$notices['wp_version'] = array(
				'class' => 'notice-error',
				'message' => sprintf( __( "%sWooCommerce MailChimp requires WordPress %s or newer.%s \n\nYou're using Version %s. Please upgrade your WordPress installation.", 'woocommerce-mailchimp' ), '<h3>', SS_WC_MAILCHIMP_MIN_WP_VERSION, "</h3>\n\n", '<span style="font-family: Consolas, Courier, monospace;">'.$wp_version.'</span>' )
			);

			return false;
		}

		return true;

	} //end public static function check_wordpress()


	/**
	 * Check if WooCommerce plugin is active and show notice if not.
	 *
	 * @since 2.0.13
	 *
	 * @access public
	 * @return boolean True: checks have been passed; WooCommerce MailChimp is fine to run; False: checks have failed, don't continue loading
	 */
	public static function check_woocommerce() {

		$woocommerce_is_active = false;

		// Bypass other checks: if the class exists
		if ( class_exists( 'WooCommerce' ) ) {

			$woocommerce_is_active = true;
			$woocommerce_version = WC()->version;

		} else {

			if ( $wc_status = self::get_plugin_status( 'woocommerce/woocommerce.php' ) ) {
				if ( true === $wc_status  ) {
					$woocommerce_is_active = true;
				}
				$wc_data = get_plugin_data( WP_CONTENT_DIR . '/plugins/woocommerce/woocommerce.php' );
				$woocommerce_version = $wc_data['Version'];
			}

		}

		if ( true === $woocommerce_is_active ) {
			// and the version's right, we're good.
			if ( true === version_compare( $woocommerce_version, SS_WC_MAILCHIMP_MIN_WC_VERSION, ">=" ) ) {
				return true;
			}

			$button = function_exists('is_network_admin') && is_network_admin() ? '<strong><a href="#woocommerce">' : '<strong><a href="'. wp_nonce_url( admin_url( 'update.php?action=upgrade-plugin&plugin=woocommerce/woocommerce.php' ), 'upgrade-plugin_woocommerce/woocommerce.php') . '" class="button button-large">';

			// Or the version's wrong
			self::$notices['wc_version'] = array(
				'class' => 'notice-error',
				'message' => sprintf( __( "%sWooCommerce MailChimp requires WooCommerce Version %s or newer.%s You're using Version %s. %sUpdate WooCommerce%s to use the WooCommerce MailChimp plugin.", 'woocommerce-mailchimp' ), '<h3>', SS_WC_MAILCHIMP_MIN_WC_VERSION, "</h3>\n\n", '<span style="font-family: Consolas, Courier, monospace;">'.$woocommerce_version.'</span>', $button, '</strong></a>' )
			);

			return false;
		}

		/**
		 * The plugin is activated and yet somehow WooCommerce didn't get picked up...
		 * OR
		 * It's the Network Admin and we just don't know whether the sites have WooCommerce activated themselves.
		 */
		if ( $woocommerce_is_active || is_network_admin() ) {
			return true;
		}

		// If WooCommerce doesn't exist, assume WooCommerce not active
		$return = false;

		switch ( $wc_status ) {
			case 'inactive':

				// Required for multisite
				if( ! function_exists('wp_create_nonce') ) {
					require_once ABSPATH . WPINC . '/pluggable.php';
				}

				// Otherwise, throws an error on activation & deactivation "Use of undefined constant LOGGED_IN_COOKIE"
				if( is_multisite() ) {
					wp_cookie_constants();
				}

				$return = false;

				$button = function_exists('is_network_admin') && is_network_admin() ? '<strong><a href="#woocommerce">' : '<strong><a href="'. wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=woocommerce/woocommerce.php' ), 'activate-plugin_woocommerce/woocommerce.php') . '" class="button button-large">';

				self::$notices['wc_inactive'] = array( 'class' => 'error', 'message' => sprintf( __( '%sWooCommerce MailChimp requires WooCommerce to be active. %sActivate WooCommerce%s to use the WooCommerce MailChimp plugin.', 'woocommerce-mailchimp' ), '<h3>', "</h3>\n\n". $button, '</a></strong>' ) );
				break;
			default:
				$button = function_exists('is_network_admin') && is_network_admin() ? '<strong><a href="#woocommerce">' : '<strong><a href="'. wp_nonce_url( admin_url( 'update.php?action=install-plugin&plugin=woocommerce' ), 'install-plugin_woocommerce' ) . '" class="button button-large">';

				self::$notices['wc_installed'] = array( 'class' => 'error', 'message' => sprintf( __( '%sWooCommerce MailChimp requires WooCommerce to be installed in order to run properly. %sInstall WooCommerce%s to use the WooCommerce MailChimp plugin.', 'woocommerce-mailchimp' ), '<h3>', "</h3>\n\n". $button, '</a></strong>') );
				break;
		}

		return $return;

	} //end public static function check_woocommerce()

	/**
	 * Check if specified plugin is active, inactive or not installed
	 *
	 * @access public
	 * @static
	 * @param string $location (default: '')
	 * @return boolean|string True: plugin is active; False: plugin file doesn't exist at path; 'inactive' it's inactive
	 */
	public static function get_plugin_status( $location = '' ) {

		if ( ! function_exists('is_plugin_active') ) {
			include_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}

		if ( is_network_admin() && is_plugin_active_for_network( $location ) ) {
			return true;
		}

		if ( !is_network_admin() && is_plugin_active( $location ) ) {
			return true;
		}

		if (
			!file_exists( trailingslashit( WP_PLUGIN_DIR ) . $location ) &&
			!file_exists( trailingslashit( WPMU_PLUGIN_DIR ) . $location )
		) {
			return false;
		}

		return 'inactive';
	}

} //SS_WC_MailChimp_Compatibility
