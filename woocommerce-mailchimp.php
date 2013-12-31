<?php
/*
Plugin Name: WooCommerce MailChimp
Plugin URI: http://anderly.com/woocommerce-mailchimp
Description: WooCommerce MailChimp provides simple MailChimp integration for WooCommerce.
Version: 1.1.1
Author: Adam Anderly
Author URI: http://anderly.com

	Copyright: © 2013 Adam Anderly
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html

	MailChimp Docs: http://apidocs.mailchimp.com/
*/

add_action( 'plugins_loaded', 'woocommerce_mailchimp_init', 0 );

function woocommerce_mailchimp_init() {

	if ( ! class_exists( 'WC_Integration' ) )
		return;

	load_plugin_textdomain( 'ss_wc_mailchimp', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	include_once( 'classes/class-ss-wc-integration-mailchimp.php' );

	/**
 	* Add the Integration to WooCommerce
 	**/
	function add_mailchimp_integration($methods) {
    	$methods[] = 'SS_WC_Integration_MailChimp';
		return $methods;
	}

	add_filter('woocommerce_integrations', 'add_mailchimp_integration' );
	
	function action_links( $links ) {

		$plugin_links = array(
			'<a href="' . admin_url( 'admin.php?page=woocommerce_settings&tab=integration&section=mailchimp' ) . '">' . __( 'Settings', 'ss_wc_mailchimp' ) . '</a>',
		);

		return array_merge( $plugin_links, $links );
	}
	// Add the "Settings" links on the Plugins administration screen
	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'action_links' );
}
