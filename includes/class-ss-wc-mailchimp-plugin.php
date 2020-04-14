<?php
/**
 * Main Plugin Class
 *
 * @package WooCommerce MailChimp
 */

/**
 * WooCommerce MailChimp plugin main class
 */
final class SS_WC_MailChimp_Plugin {

	/**
	 * Plugin version
	 *
	 * @var string
	 */
	private static $version = '2.3.12';

	/**
	 * Plugin singleton instance
	 *
	 * @var SS_WC_MailChimp_Plugin
	 */
	private static $instance;

	/**
	 * Plugin namespace
	 *
	 * @var string
	 */
	private $namespace = 'ss_wc_mailchimp';

	/**
	 * Plugin settings
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Plugin MailChimp helper instance
	 *
	 * @var SS_WC_MailChimp
	 */
	private $mailchimp;

	/**
	 * Plugin compatibility checker
	 *
	 * @var SS_WC_MailChimp_Compatibility
	 */
	public $compatibility;

	/**
	 * Returns the plugin version
	 *
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

			self::$instance = new SS_WC_MailChimp_Plugin();
			self::$instance->define_constants();

			self::$instance->save_settings();
			self::$instance->settings();
			self::$instance->includes();
			self::$instance->mailchimp();
			self::$instance->logger        = SSWCMC_Logger::get_instance();
			self::$instance->handler       = SS_WC_MailChimp_Handler::get_instance();
			self::$instance->compatibility = SS_WC_MailChimp_Compatibility::get_instance();
			self::$instance->admin_notices = new SS_WC_MailChimp_Admin_Notices();
			self::$instance->load_plugin_textdomain();

			self::update();
			self::$instance->add_hooks();
			do_action( 'ss_wc_mailchimp_loaded' );

		}

		return self::$instance;

	} //end function instance

	/**
	 * Gets the plugin db settings
	 *
	 * @param  boolean $refresh refresh the settings from DB.
	 * @return array  The plugin db settings
	 */
	public function settings( $refresh = false ) {

		if ( empty( $this->settings ) || true === $refresh ) {

			$defaults = require SS_WC_MAILCHIMP_DIR . 'config/default-settings.php';
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
	 * Returns the api key.
	 *
	 * @return string MailChimp API Key
	 */
	public function api_key() {
		return $this->settings['api_key'];
	}

	/**
	 * Whether or not the plugin functionality is enabled.
	 *
	 * @access public
	 * @return boolean
	 */
	public function is_enabled() {
		return 'yes' === $this->settings['enabled'];
	}

	/**
	 * Whether or not a main list has been selected.
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
	 * When the subscription should be triggered.
	 *
	 * @return string
	 */
	public function occurs() {
		return $this->settings['occurs'];
	}

	/**
	 * Returns the selected list.
	 *
	 * @access public
	 * @return string MailChimp list ID
	 */
	public function get_list() {
		return $this->settings['list'];
	}

	/**
	 * Whether or not double opt-in is selected.
	 *
	 * @access public
	 * @return boolean
	 */
	public function double_opt_in() {
		return 'yes' === $this->settings['double_opt_in'];
	}

	/**
	 * Whether or not to display opt-in checkbox to user.
	 *
	 * @access public
	 * @return boolean
	 */
	public function display_opt_in() {
		return 'yes' === $this->settings['display_opt_in'];
	}

	/**
	 * Opt-in label.
	 *
	 * @access public
	 * @return string
	 */
	public function opt_in_label() {
		return $this->settings['opt_in_label'];
	}

	/**
	 * Opt-in checkbox default status.
	 *
	 * @access public
	 * @return string
	 */
	public function opt_in_checkbox_default_status() {
		return $this->settings['opt_in_checkbox_default_status'];
	}

	/**
	 * Opt-in checkbox display location.
	 *
	 * @access public
	 * @return string
	 */
	public function opt_in_checkbox_display_location() {
		return $this->settings['opt_in_checkbox_display_location'];
	}

	/**
	 * Returns selected Mailchimp interest groups.
	 *
	 * @access public
	 * @return array
	 */
	public function interest_groups() {
		return $this->settings['interest_groups'];
	}

	/**
	 * Returns selected Mailchimp tags.
	 *
	 * @access public
	 * @return array
	 */
	public function tags() {
		return $this->settings['tags'];
	}

	/**
	 * Get the global subscribe options for the passed $order_id
	 *
	 * @since  2.3.2
	 * @access public
	 * @param  int $order_id The order id.
	 */
	public function get_subscribe_options_for_order( $order_id ) {

		// Get WC order.
		$order = wc_get_order( $order_id );

		$order_id   = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;
		$email      = method_exists( $order, 'get_billing_email' ) ? $order->get_billing_email() : $order->billing_email;
		$first_name = method_exists( $order, 'get_billing_first_name' ) ? $order->get_billing_first_name() : $order->billing_first_name;
		$last_name  = method_exists( $order, 'get_billing_last_name' ) ? $order->get_billing_last_name() : $order->billing_last_name;

		$list_id = $this->get_list();

		if ( ! $email ) {
			return; // Email is required.
		}

		$merge_tags = array(
			'FNAME' => $first_name,
			'LNAME' => $last_name,
		);

		$interest_groups = $this->interest_groups();

		if ( ! empty( $interest_groups ) ) {
			$interest_groups = array_fill_keys( $interest_groups, true );
		}

		$tags = $this->tags();

		$mc_tags = $this->mailchimp()->get_tags( $list_id );

		if ( ! is_array( $tags ) ) $tags = array();
		$tags = array_map( function( $tag ) use ( $mc_tags ) {
			return array(
				'name'   => $mc_tags[ $tag ],
				'status' => 'active',
			);
		}, $tags );

		// Set subscription options.
		$subscribe_options = array(
			'list_id'         => $list_id,
			'email'           => $email,
			'merge_tags'      => $merge_tags,
			'interest_groups' => $interest_groups,
			'tags'            => $tags,
			'email_type'      => 'html',
			'double_opt_in'   => $this->double_opt_in(),
		);

		return $subscribe_options;

	} //end function get_subscribe_options_for_order

	/**
	 * Whether or not an api key has been set.
	 *
	 * @access public
	 * @return boolean
	 */
	public function has_api_key() {
		$api_key = $this->api_key();
		return ! empty( $api_key );
	}

	/**
	 * Whether or not the configuration is valid.
	 *
	 * @access public
	 * @return boolean
	 */
	public function is_valid() {
		return $this->is_enabled() && $this->has_api_key();
	}

	/**
	 * Whether or not debug is enabled.
	 *
	 * @access public
	 * @return boolean
	 */
	public function debug_enabled() {
		return 'yes' === $this->settings['debug'];
	}

	/**
	 * Saves the settings back to the DB
	 *
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
	 *
	 * @param  string  $api_key MailChimp API Key.
	 * @param  boolean $debug   Debug mode enabled/disabled.
	 * @return SS_WC_MailChimp  MailChimp Helper class
	 */
	public function mailchimp( $api_key = null, $debug = false ) {

		$settings = $this->settings();

		if ( empty( $this->mailchimp ) || ! is_null( $api_key ) ) {

			$api_key = $api_key ? $api_key : $settings['api_key'];
			$debug   = $debug ? $debug : $settings['debug'];

			require_once SS_WC_MAILCHIMP_DIR . 'includes/class-ss-wc-mailchimp.php';
			$this->mailchimp = new SS_WC_MailChimp( $api_key, $debug );

			delete_transient( 'sswcmc_lists' );
		}

		return $this->mailchimp;

	} //end function mailchimp

	/**
	 * Define Plugin Constants.
	 */
	private function define_constants() {

		// Minimum supported version of WordPress.
		$this->define( 'SS_WC_MAILCHIMP_MIN_WP_VERSION', '4.7.0' );

		// Minimum supported version of WooCommerce.
		$this->define( 'SS_WC_MAILCHIMP_MIN_WC_VERSION', '3.5.0' );

		// Minimum supported version of PHP.
		$this->define( 'SS_WC_MAILCHIMP_MIN_PHP_VERSION', '5.6.0' );

		// Plugin version.
		$this->define( 'SS_WC_MAILCHIMP_VERSION', self::version() );

		// Plugin Folder Path.
		$this->define( 'SS_WC_MAILCHIMP_DIR', plugin_dir_path( SS_WC_MAILCHIMP_FILE ) );

		// Plugin Folder URL.
		$this->define( 'SS_WC_MAILCHIMP_URL', plugin_dir_url( SS_WC_MAILCHIMP_FILE ) );

		$settings_url = admin_url( 'admin.php?page=wc-settings&tab=mailchimp' );

		$this->define( 'SS_WC_MAILCHIMP_SETTINGS_URL', $settings_url );

	} //function define_constants

	/**
	 * Define constant if not already set.
	 *
	 * @param  string      $name  Constant name.
	 * @param  string|bool $value Constant value.
	 * @return void
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	} //function define

	/**
	 * Include required core plugin files
	 *
	 * @return void
	 */
	public function includes() {

		require_once SS_WC_MAILCHIMP_DIR . 'includes/lib/class-ss-system-info.php';

		require_once SS_WC_MAILCHIMP_DIR . 'includes/helper-functions.php';

		require_once SS_WC_MAILCHIMP_DIR . 'includes/class-ss-wc-mailchimp-compatibility.php';

		require_once SS_WC_MAILCHIMP_DIR . 'includes/class-ss-wc-mailchimp-admin-notices.php';

		require_once SS_WC_MAILCHIMP_DIR . 'includes/class-ss-wc-mailchimp-api.php';

		require_once SS_WC_MAILCHIMP_DIR . 'includes/class-ss-wc-mailchimp.php';

		require_once SS_WC_MAILCHIMP_DIR . 'includes/class-ss-wc-mailchimp-handler.php';

		require_once SS_WC_MAILCHIMP_DIR . 'includes/class-sswcmc-logger.php';

	} //end function includes

	/**
	 * Add plugin hooks
	 *
	 * @return void
	 */
	private function add_hooks() {

		/** Register hooks that are fired when the plugin is activated and deactivated. */
		register_activation_hook( SS_WC_MAILCHIMP_FILE, array( __CLASS__, 'activate' ) );
		register_deactivation_hook( SS_WC_MAILCHIMP_FILE, array( __CLASS__, 'deactivate' ) );

		// Add the "Settings" links on the Plugins administration screen.
		if ( is_admin() ) {

			add_filter( 'plugin_action_links_' . plugin_basename( SS_WC_MAILCHIMP_FILE ), array( $this, 'action_links' ) );

			add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );

			add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_mailchimp_settings' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			add_action( 'admin_init', array( $this, 'process_actions' ) );

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
	 *
	 * @return void
	 */
	public function load_plugin_textdomain() {

		// Set filter for plugin's languages directory.
		$woocommerce_mailchimp_lang_dir = dirname( plugin_basename( SS_WC_MAILCHIMP_FILE ) ) . '/languages/';

		// Traditional WordPress plugin locale filter.
		// get locale in {lang}_{country} format (e.g. en_US).
		$locale = apply_filters( 'plugin_locale', get_locale(), 'woocommerce-mailchimp' );

		$mofile = sprintf( '%1$s-%2$s.mo', 'woocommerce-mailchimp', $locale );

		// Look for wp-content/languages/woocommerce-mailchimp/woocommerce-mailchimp-{lang}_{country}.mo.
		$mofile_global1 = WP_LANG_DIR . '/woocommerce-mailchimp/' . $mofile;

		// Look in wp-content/languages/plugins/woocommerce-mailchimp.
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
	 * @param  array $links Plugin links.
	 * @return array       Modified plugin links
	 */
	public function action_links( $links ) {
		$plugin_links = array(
			'<a href="' . SS_WC_MAILCHIMP_SETTINGS_URL . '">' . __( 'Settings', 'woocommerce-mailchimp' ) . '</a>',
		);

		return array_merge( $plugin_links, $links );

	} //end function action_links

	/**
	 * Show row meta on the plugin screen.
	 *
	 * @param mixed $links Plugin Row Meta.
	 * @param mixed $file  Plugin Base file.
	 *
	 * @return array
	 */
	public static function plugin_row_meta( $links, $file ) {
		if ( plugin_basename( SS_WC_MAILCHIMP_FILE ) === $file ) {
			$row_meta = array(
				'docs' => '<a href="' . esc_url( apply_filters( 'ss_wc_mailchimp_docs_url', 'https://support.saintsystems.com/hc/en-us/sections/201959566' ) ) . '" aria-label="' . esc_attr__( 'View WooCommerce Mailchimp documentation', 'woocommerce-mailchimp' ) . '" target="_blank">' . esc_html__( 'Documentation', 'woocommerce-mailchimp' ) . '</a>',
			);

			if ( ! function_exists( 'SSWCMCPRO' ) ) {
				$row_meta['upgrade'] = '<a href="' . esc_url( apply_filters( 'ss_wc_mailchimp_support_url', 'https://www.saintsystems.com/products/woocommerce-mailchimp-pro/#utm_source=wp-plugin&utm_medium=woocommerce-mailchimp&utm_campaign=plugins-upgrade-link' ) ) . '" aria-label="' . esc_attr__( 'Upgrade to WooCommerce Mailchimp Pro', 'woocommerce-mailchimp' ) . '" target="_blank">' . esc_html__( 'Upgrade to Pro', 'woocommerce-mailchimp' ) . '</a>';
			}

			return array_merge( $links, $row_meta );
		}

		return (array) $links;
	}

	/**
	 * Add the MailChimp settings tab to WooCommerce
	 *
	 * @param  array $settings  MailChimp settings.
	 * @return array Settings.
	 */
	public function add_mailchimp_settings( $settings ) {

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$settings[] = require_once SS_WC_MAILCHIMP_DIR . 'includes/class-ss-wc-settings-mailchimp.php';

		return $settings;

	} //end function add_mailchimp_settings

	/**
	 * Load scripts required for admin
	 *
	 * @access public
	 * @return void
	 */
	public function enqueue_scripts() {

		// Plugin scripts and styles.
		wp_register_script( 'woocommerce-mailchimp-admin', SS_WC_MAILCHIMP_URL . 'assets/js/woocommerce-mailchimp-admin.js', array( 'jquery' ), self::version() );
		wp_register_style( 'woocommerce-mailchimp', SS_WC_MAILCHIMP_URL . 'assets/css/style.css', array(), self::version() );

		// Localize javascript messages.
		$translations = array(
			'connecting_to_mailchimp'     => __( 'Connecting to Mailchimp', 'woocommerce-mailchimp' ),
			'error_loading_account'       => __( 'Error. Please check your api key.', 'woocommerce-mailchimp' ),
			'error_loading_groups'        => __( 'Error loading groups. Please check your Mailchimp Interest Groups for the selected list.', 'woocommerce-mailchimp' ),
			'select_groups_placeholder'   => __( 'Select one or more groups (optional)', 'woocommerce-mailchimp' ),
			'interest_groups_not_enabled' => __( 'This list does not have interest groups enabled', 'woocommerce-mailchimp' ),
			'error_loading_tags'          => __( 'Error loading tags. Please check your Mailchimp tags for the selected list.', 'woocommerce-mailchimp' ),
			'select_tags_placeholder'     => __( 'Select one or more tags (optional)', 'woocommerce-mailchimp' ),
			'tags_not_enabled'            => __( 'This list does not have tags enabled', 'woocommerce-mailchimp' ),
		);

		$nonces = array(
			'get_account'         => wp_create_nonce( 'sswcmc_get_account' ),
			'get_lists'           => wp_create_nonce( 'sswcmc_get_lists' ),
			'get_interest_groups' => wp_create_nonce( 'sswcmc_get_interest_groups' ),
			'get_tags'            => wp_create_nonce( 'sswcmc_get_tags' ),
			'get_merge_fields'    => wp_create_nonce( 'sswcmc_merge_fields' ),
		);

		$sswcmc = array(
			'messages' => $translations,
			'nonces'   => $nonces,
		);

		wp_localize_script( 'woocommerce-mailchimp-admin', 'SSWCMC', $sswcmc );

		// Scripts.
		wp_enqueue_script( 'woocommerce-mailchimp-admin' );

		// Styles.
		wp_enqueue_style( 'woocommerce-mailchimp' );

	} //end function enqueue_scripts

	/**
	 * Processes all SSWCMC actions sent via POST and GET by looking for the 'sswcmc-action'
	 * request and running do_action() to call the function
	 *
	 * @since 2.1.15
	 * @access public
	 * @return void
	 */
	public function process_actions() {
		if ( isset( $_POST['sswcmc-action'] ) ) {
			$action = sanitize_key( $_POST['sswcmc-action'] );
			do_action( 'sswcmc_' . $action, $_POST );
		}

		if ( isset( $_GET['sswcmc-action'] ) ) {
			$action = sanitize_key( $_GET['sswcmc-action'] );
			do_action( 'sswcmc_' . $action, $_GET );
		}
	}

	/**
	 * Handles running plugin upgrades if necessary
	 *
	 * @return void
	 */
	public static function update() {

		require_once 'class-ss-wc-mailchimp-migrator.php';

		SS_WC_MailChimp_Migrator::migrate( self::version() );

	} //end function update

	/**
	 * Plugin activate function.
	 *
	 * @access public
	 * @static
	 * @param mixed $network_wide Network activate.
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
	 * @param mixed $network_wide Network activate.
	 * @return void
	 */
	public static function deactivate( $network_wide ) {

		// Placeholder.

	} //end function deactivate

	/**
	 * Check whether WooCommerce MailChimp is network activated
	 *
	 * @since 1.0
	 * @return bool
	 */
	public static function is_network_activated() {
		return is_multisite() && ( function_exists( 'is_plugin_active_for_network' ) && is_plugin_active_for_network( 'woocommerce-mailchimp/woocommerce-mailchimp.php' ) );
	}

	/**
	 * Returns namespace prefixed value
	 *
	 * @param  string $suffix  The suffix to prefix.
	 * @return string
	 */
	private function namespace_prefixed( $suffix ) {

		return $this->namespace . '_' . $suffix;

	} // end function namespace_prefixed

} //end final class SS_WC_MailChimp_Plugin
