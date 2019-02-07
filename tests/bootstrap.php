<?php

/**
 * WooCommerce MailChimp Unit Tests Bootstrap
 *
 * @since 2.1.12
 */
class WCMC_Unit_Tests_Bootstrap
{
	/** @var WCMC_Unit_Tests_Bootstrap instance */
	protected static $instance = null;

	/** @var string directory where wordpress-tests-lib is installed */
	public $wp_tests_dir;

	/** @var string testing directory */
	public $tests_dir;

	/** @var string plugin directory */
	public $plugin_dir;

	/**
	 * Setup the unit testing environment.
	 *
	 * @since 2.2
	 */
	public function __construct() {

		// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions, WordPress.PHP.DevelopmentFunctions
		ini_set( 'display_errors', 'on' );
		error_reporting( E_ALL );
		// phpcs:enable WordPress.PHP.DiscouragedPHPFunctions, WordPress.PHP.DevelopmentFunctions

		// Ensure server variable is set for WP email functions.
		// phpcs:disable WordPress.VIP.SuperGlobalInputUsage.AccessDetected
		if ( ! isset( $_SERVER['SERVER_NAME'] ) ) {
			$_SERVER['SERVER_NAME'] = 'localhost';
		}
		// phpcs:enable WordPress.VIP.SuperGlobalInputUsage.AccessDetected

		$this->tests_dir = dirname( __FILE__ );
		$this->plugin_dir = dirname( $this->tests_dir );
		$this->modules_dir  = dirname( dirname( $this->tests_dir ) );
		$this->wp_tests_dir = getenv( 'WP_TESTS_DIR' ) ? getenv( 'WP_TESTS_DIR' ) : '/tmp/wordpress-tests-lib';

		// load test function so tests_add_filter() is available
		require_once $this->wp_tests_dir . '/includes/functions.php';

		// load WC
		tests_add_filter( 'muplugins_loaded', array( $this, 'load_wc' ) );

		// install WC
		tests_add_filter( 'setup_theme', array( $this, 'install_wc' ) );

		// install WC
		// tests_add_filter('setup_theme', array($this, 'install_wc'));

		// load the WP testing environment
		require_once $this->wp_tests_dir . '/includes/bootstrap.php';

		// load WCMC testing framework
		$this->includes();

		// load WCMC
		$this->load_wcmc();
	}

	/**
	 * Load WooCommerce
	 *
	 * @since 2.0
	 */
	public function load_wc() {
		require_once( $this->modules_dir . '/woocommerce/woocommerce.php' );
	}
	/**
	 * Load WooCommerce for testing
	 *
	 * @since 2.0
	 */
	function install_wc() {
		echo "Installing WooCommerce..." . PHP_EOL;
		define( 'WP_UNINSTALL_PLUGIN', true );
		include( $this->modules_dir . '/woocommerce/uninstall.php' );
		WC_Install::install();
		// reload capabilities after install, see https://core.trac.wordpress.org/ticket/28374
		$GLOBALS['wp_roles']->for_site();
		WC()->init();
		echo "WooCommerce Finished Installing..." . PHP_EOL;
	}

	/**
	 * Load WooCommerce MailChimp.
	 *
	 * @since 2.1.12
	 */
	public function load_wcmc()
	{
		echo "Loading WooCommerce MailChimp..." . PHP_EOL;
		require_once $this->plugin_dir . '/woocommerce-mailchimp.php';
		echo "WooCommerce MailChimp Loaded..." . PHP_EOL;
	}

	/**
	 * Load WCMC-specific test cases and factories.
	 *
	 * @since 2.1.12
	 */
	public function includes()
	{
		// test cases
		require_once $this->tests_dir . '/framework/class-sswcmc-unit-test-case.php';
	}

	/**
	 * Get the single class instance.
	 *
	 * @since 2.1.12
	 * @return WCMC_Unit_Tests_Bootstrap
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}
WCMC_Unit_Tests_Bootstrap::instance();
