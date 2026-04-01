<?php

/**
 * Tests for the WooCommerce block-based checkout integration.
 */
class Test_SSWCMC_Blocks_Integration extends SSWCMC_Unit_Test_Case {

	protected $sswcmc;
	protected $handler;

	public function setUp(): void {
		parent::setUp();

		$this->sswcmc  = SSWCMC();
		$this->handler = SS_WC_MailChimp_Handler::get_instance();

		// Ensure the blocks integration class is loaded for tests that need it.
		$blocks_file = SS_WC_MAILCHIMP_DIR . 'includes/class-ss-wc-mailchimp-blocks-integration.php';
		if ( file_exists( $blocks_file ) ) {
			// Provide a stub IntegrationInterface if WC Blocks is not available.
			if ( ! interface_exists( 'Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface' ) ) {
				$wc_interface = WP_CONTENT_DIR . '/plugins/woocommerce/src/Blocks/Integrations/IntegrationInterface.php';
				if ( file_exists( $wc_interface ) ) {
					require_once $wc_interface;
				}
			}
			if ( interface_exists( 'Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface' ) ) {
				require_once $blocks_file;
			}
		}
	}

	// ---------------------------------------------------------------
	// Class existence
	// ---------------------------------------------------------------

	public function test_blocks_integration_class_exists() {
		$this->assertTrue(
			class_exists( 'SS_WC_MailChimp_Blocks_Integration' ),
			'SS_WC_MailChimp_Blocks_Integration class should be loadable'
		);
	}

	// ---------------------------------------------------------------
	// Block checkout opt-in save tests (via WP_REST_Request)
	// ---------------------------------------------------------------

	public function test_maybe_save_checkout_fields_blocks_saves_opt_in_yes() {
		update_option( 'ss_wc_mailchimp_display_opt_in', 'yes' );
		$this->sswcmc->settings( true );

		$order = wc_create_order();

		$request = $this->build_store_api_request( array( 'ss_wc_mailchimp_opt_in' => true ) );
		$this->handler->maybe_save_checkout_fields_blocks( $order, $request );

		$this->assertEquals( 'yes', $order->get_meta( 'ss_wc_mailchimp_opt_in', true ) );

		$order->delete( true );
	}

	public function test_maybe_save_checkout_fields_blocks_saves_opt_in_no() {
		update_option( 'ss_wc_mailchimp_display_opt_in', 'yes' );
		$this->sswcmc->settings( true );

		$order = wc_create_order();

		$request = $this->build_store_api_request( array( 'ss_wc_mailchimp_opt_in' => false ) );
		$this->handler->maybe_save_checkout_fields_blocks( $order, $request );

		$this->assertEquals( 'no', $order->get_meta( 'ss_wc_mailchimp_opt_in', true ) );

		$order->delete( true );
	}

	public function test_maybe_save_checkout_fields_blocks_no_extension_data() {
		update_option( 'ss_wc_mailchimp_display_opt_in', 'yes' );
		$this->sswcmc->settings( true );

		$order = wc_create_order();

		// Request with no extension data for our namespace.
		$request = new WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_body_params( array( 'extensions' => array() ) );

		$this->handler->maybe_save_checkout_fields_blocks( $order, $request );

		$this->assertEquals( 'no', $order->get_meta( 'ss_wc_mailchimp_opt_in', true ) );

		$order->delete( true );
	}

	public function test_maybe_save_checkout_fields_blocks_null_request_fallback() {
		update_option( 'ss_wc_mailchimp_display_opt_in', 'yes' );
		$this->sswcmc->settings( true );

		$order = wc_create_order();

		// Calling without a request object (graceful fallback).
		$this->handler->maybe_save_checkout_fields_blocks( $order );

		$this->assertEquals( 'no', $order->get_meta( 'ss_wc_mailchimp_opt_in', true ) );

		$order->delete( true );
	}

	public function test_maybe_save_checkout_fields_blocks_skipped_when_opt_in_disabled() {
		update_option( 'ss_wc_mailchimp_display_opt_in', 'no' );
		$this->sswcmc->settings( true );

		$order = wc_create_order();

		$request = $this->build_store_api_request( array( 'ss_wc_mailchimp_opt_in' => true ) );
		$this->handler->maybe_save_checkout_fields_blocks( $order, $request );

		// Should not save any meta when display_opt_in is disabled.
		$this->assertEmpty( $order->get_meta( 'ss_wc_mailchimp_opt_in', true ) );

		$order->delete( true );
	}

	// ---------------------------------------------------------------
	// Traditional (shortcode) checkout tests
	// ---------------------------------------------------------------

	public function test_traditional_checkout_saves_opt_in_yes() {
		update_option( 'ss_wc_mailchimp_display_opt_in', 'yes' );
		$this->sswcmc->settings( true );

		$order    = wc_create_order();
		$order_id = $order->get_id();

		$_POST['ss_wc_mailchimp_opt_in'] = 'yes';

		$this->handler->maybe_save_checkout_fields( $order_id );

		$order = wc_get_order( $order_id );
		$this->assertEquals( 'yes', $order->get_meta( 'ss_wc_mailchimp_opt_in', true ) );

		$order->delete( true );
	}

	public function test_traditional_checkout_unchecked() {
		update_option( 'ss_wc_mailchimp_display_opt_in', 'yes' );
		$this->sswcmc->settings( true );

		$order    = wc_create_order();
		$order_id = $order->get_id();

		unset( $_POST['ss_wc_mailchimp_opt_in'] );

		$this->handler->maybe_save_checkout_fields( $order_id );

		$order = wc_get_order( $order_id );
		$this->assertEquals( 'no', $order->get_meta( 'ss_wc_mailchimp_opt_in', true ) );

		$order->delete( true );
	}

	// ---------------------------------------------------------------
	// IntegrationInterface tests
	// ---------------------------------------------------------------

	public function test_blocks_integration_get_name() {
		if ( ! class_exists( 'SS_WC_MailChimp_Blocks_Integration' ) ) {
			$this->markTestSkipped( 'Blocks Integration class not available' );
		}

		$integration = new SS_WC_MailChimp_Blocks_Integration( $this->sswcmc );
		$this->assertEquals( 'woocommerce-mailchimp', $integration->get_name() );
	}

	public function test_blocks_integration_script_data_opt_in_enabled() {
		if ( ! class_exists( 'SS_WC_MailChimp_Blocks_Integration' ) ) {
			$this->markTestSkipped( 'Blocks Integration class not available' );
		}

		update_option( 'ss_wc_mailchimp_display_opt_in', 'yes' );
		update_option( 'ss_wc_mailchimp_opt_in_label', 'Subscribe to newsletter' );
		update_option( 'ss_wc_mailchimp_opt_in_checkbox_default_status', 'checked' );
		$this->sswcmc->settings( true );

		$integration = new SS_WC_MailChimp_Blocks_Integration( $this->sswcmc );
		$script_data = $integration->get_script_data();

		$this->assertTrue( $script_data['displayOptIn'] );
		$this->assertEquals( 'Subscribe to newsletter', $script_data['optInLabel'] );
		$this->assertEquals( 'checked', $script_data['optInDefaultStatus'] );
	}

	public function test_blocks_integration_script_data_opt_in_disabled() {
		if ( ! class_exists( 'SS_WC_MailChimp_Blocks_Integration' ) ) {
			$this->markTestSkipped( 'Blocks Integration class not available' );
		}

		update_option( 'ss_wc_mailchimp_display_opt_in', 'no' );
		$this->sswcmc->settings( true );

		$integration = new SS_WC_MailChimp_Blocks_Integration( $this->sswcmc );
		$script_data = $integration->get_script_data();

		$this->assertFalse( $script_data['displayOptIn'] );
	}

	public function test_blocks_integration_script_handles() {
		if ( ! class_exists( 'SS_WC_MailChimp_Blocks_Integration' ) ) {
			$this->markTestSkipped( 'Blocks Integration class not available' );
		}

		$integration = new SS_WC_MailChimp_Blocks_Integration( $this->sswcmc );

		$handles = $integration->get_script_handles();
		$this->assertContains( 'wc-mailchimp-blocks-integration', $handles );

		$editor_handles = $integration->get_editor_script_handles();
		$this->assertContains( 'wc-mailchimp-blocks-integration', $editor_handles );
	}

	// ---------------------------------------------------------------
	// Store API endpoint data registration
	// ---------------------------------------------------------------

	public function test_store_api_endpoint_data_registration_method_exists() {
		$this->assertTrue(
			method_exists( $this->handler, 'register_store_api_endpoint_data' ),
			'Handler should have register_store_api_endpoint_data method'
		);
	}

	public function test_register_checkout_block_integration_method_exists() {
		$this->assertTrue(
			method_exists( $this->handler, 'register_checkout_block_integration' ),
			'Handler should have register_checkout_block_integration method'
		);
	}

	// ---------------------------------------------------------------
	// Helpers
	// ---------------------------------------------------------------

	/**
	 * Build a mock WP_REST_Request with extension data.
	 *
	 * @param  array $extension_data Data to place under extensions['woocommerce-mailchimp'].
	 * @return WP_REST_Request
	 */
	private function build_store_api_request( $extension_data ) {
		$request = new WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_body_params( array(
			'extensions' => array(
				'woocommerce-mailchimp' => $extension_data,
			),
		) );
		return $request;
	}

	public function tearDown(): void {
		parent::tearDown();
		unset( $_POST['extensions'] );
		unset( $_POST['ss_wc_mailchimp_opt_in'] );
	}
}