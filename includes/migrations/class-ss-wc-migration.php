<?php

abstract class SS_WC_MailChimp_Migration_Base {

	protected $api;

	protected $settings;

	protected $settings_key = 'woocommerce_mailchimp_settings';

	public function __construct() {

		if ( ! $this->load_settings() ) return;

		if ( empty( $this->settings['api_key'] ) ) return;

		require_once( SS_WC_MAILCHIMP_DIR . 'includes/class-ss-wc-mailchimp-api.php' );
		$this->api = new SS_WC_MailChimp_API( $this->settings['api_key'] );
	}

	/**
	 * [save_settings description]
	 * @return [type] [description]
	 */
	public function load_settings() {

		if ( ! $this->settings = get_option( $this->settings_key ) ) {
			return false;
		}

		if ( !is_array( $this->settings ) ) {
			return false;
		}

		return true;

	}

	/**
	 * [save_settings description]
	 * @return [type] [description]
	 */
	public function save_settings() {
		update_option( $this->settings_key , $this->settings );
	}

}