<?php

/**
 * Class WP_WooCommerce_MailChimpTest
 *
 * @package Woocommerce_Mailchimp
 */

/**
 * WooCommerce MailChimp test case.
 */
class Test_SSWCMC extends SSWCMC_Unit_Test_Case {

	/**
	 * WooCommerce MailChimp instance.
	 *
	 * @var \WooCommerce instance
	 */
	protected $sswcmc;

	/**
	 * Setup test.
	 *
	 * @since 2.1.12
	 */
	public function setUp() {
		parent::setUp();

		$this->sswcmc = SSWCMC();

	}

	/**
	 * Test SSWCMC has static instance.
	 *
	 * @since 2.2
	 */
	function test_sswcmc_instance() {
		// Replace this with some actual testing code.
		$this->assertClassHasStaticAttribute( 'instance', 'SS_WC_MailChimp_Plugin' );
	}

	/**
	 * Test that all WCMC constants are set.
	 *
	 * @since 2.2
	 */
	public function test_constants() {
		// Plugin Folder URL
		$path = str_replace( 'tests/', '', plugin_dir_url( __FILE__ ) );
		$this->assertSame( SS_WC_MAILCHIMP_URL, $path );
		// Plugin Folder Path
		$path = str_replace( 'tests/', '', plugin_dir_path( __FILE__ ) );
		$path = substr( $path, 0, -1 );
		$edd  = substr( SS_WC_MAILCHIMP_DIR, 0, -1 );
		$this->assertSame( $edd, $path );
		// Plugin Root File
		$path = str_replace( 'tests/', '', plugin_dir_path( __FILE__ ) );
		$this->assertSame( SS_WC_MAILCHIMP_FILE, $path . 'woocommerce-mailchimp.php' );
	}

	/**
	 * Test class instance.
	 *
	 * @since 2.2
	 */
	public function test_wc_class_instances() {
		$this->assertInstanceOf('SS_WC_MailChimp_Plugin', $this->sswcmc );
	}
}
