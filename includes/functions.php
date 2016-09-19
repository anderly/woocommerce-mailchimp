<?php

/**
 * Get a service by its name
 *
 * _Example:_
 *
 * $forms = ss_wc_mailchimp('forms');
 * $api = ss_wc_mailchimp('api');
 *
 * When no service parameter is given, the entire container will be returned.
 *
 * @ignore
 * @access private
 *
 * @param string $service (optional)
 * @return mixed
 *
 * @throws Exception when service is not found
 */
// function ss_wc_mailchimp( $service = null ) {
// 	static $ss_wc_mailchimp;

// 	if ( ! $ss_wc_mailchimp ) {
// 		$ss_wc_mailchimp = new SS_WC_MailChimp_Container();
// 	}

// 	if ( $service ) {
// 		return $ss_wc_mailchimp->get( $service );
// 	}

// 	return $ss_wc_mailchimp;
// }

/**
 * Gets the WooCommerce MailChimp options from the database
 * Uses default values to prevent undefined index notices.
 *
 * @since 1.0
 * @access public
 * @static array $options
 * @return array
 */
function ss_wc_mailchimp_get_options() {
	static $options;

	if ( ! $options ) {
		$defaults = require SS_WC_MAILCHIMP_DIR . 'config/default-settings.php';
		$options = array();
		foreach ( $defaults as $key => $val ) {
			$options[ $key ] = get_option( 'ss_wc_mailchimp_' . $key );
		}
		$options = array_merge( $defaults, $options );
	}

	/**
	 * Filters the MailChimp for WordPress settings (general).
	 *
	 * @param array $options
	 */
	return apply_filters( 'ss_wc_mailchimp_settings', $options );
}

/**
 * Gets the WooCommerce MailChimp API Helper class and injects it with the API key
 *
 * @since 4.0
 * @access public
 *
 * @return SS_WC_MailChimp
 */
// function ss_wc_mailchimp_get_api( $api_key = null, $debug = null ) {
// 	$opts = ss_wc_mailchimp_get_options();
// 	$instance = new SS_WC_MailChimp( ($api_key ? $api_key : $opts['api_key']), ($debug ? $debug : $opts['debug']) );
// 	return $instance;
// }