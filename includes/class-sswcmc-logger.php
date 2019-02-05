<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

/**
 * Logging helper.
 *
 * @class   SSWCMC_Logger
 * @version 2.1.15
 * @package WooCommerce MailChimp
 * @author  Saint Systems
 */
final class SSWCMC_Logger {

	/**
	 * Plugin singleton instance
	 *
	 * @var SSWCMC_Logger
	 */
	private static $instance = null;

	/**
	 * Whether plugin is in debug/troubleshooting mode.
	 *
	 * @var boolean
	 */
	public $debug;

	/**
	 * The WC_Logger handle.
	 *
	 * @var string
	 */
	const WC_LOG_FILENAME = 'woocommerce-mailchimp';

	/**
	 * Constructor
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {

		$this->sswcmc = SSWCMC();
		$this->register_hooks();

	} //end function __construct

	/**
	 * Get the singleton instance
	 *
	 * @return SSWCMC_Logger
	 */
	public static function get_instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Register the class hooks.
	 * @access public
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'sswcmc_log', array( $this, 'log' ), 10, 1 );
	}

	/**
	 * Conditionally log
	 *
	 * @param  string $message The log message.
	 * @return void
	 */
	public function log( $message ) {

		if ( ! $this->sswcmc->debug_enabled() ) return;
		$log = $this->get_logger();
		if ( version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
			$log->debug( $message, array( 'source' => self::WC_LOG_FILENAME ) );
		} else {
			$log->add( self::WC_LOG_FILENAME, $message );
		}

	}

	/**
	 * Get the WC_Logger reference
	 *
	 * @var WC_Logger
	 */
	private function get_logger() {
		if ( version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
			return wc_get_logger();
		} else {
			return new WC_Logger();
		}
	}

} //end class SSWCMC_Logger
