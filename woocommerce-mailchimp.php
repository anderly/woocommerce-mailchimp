<?php
/**
 * Plugin Name: WooCommerce MailChimp
 * Plugin URI: http://www.saintsystems.com/products/woocommerce-mailchimp
 * Description: WooCommerce MailChimp provides simple MailChimp integration for WooCommerce.
 * Author: Saint Systems
 * Author URI: http://www.saintsystems.com
 * Version: 2.0
 * Text Domain: ss_wc_mailchimp
 * Domain Path: languages
 *
 * Copyright: © 2015 Saint Systems, LLC
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

/** If this file is called directly, abort. */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

define( 'WOOCOMMERCE_MAILCHIMP_PLUGIN_NAME', plugin_basename( __FILE__ ) );
define( 'WOOCOMMERCE_MAILCHIMP_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );

define( 'WOOCOMMERCE_MAILCHIMP_PATH', plugin_dir_path( __FILE__ ) );//dirname( __FILE__ ) );
define( 'WOOCOMMERCE_MAILCHIMP_PLUGIN_URL', plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) );
define( 'WOOCOMMERCE_MAILCHIMP_VERSION', '2.0' );

add_action( 'plugins_loaded', 'ss_wc_mailchimp_init', 0 );
if ( ! function_exists( 'ss_wc_mailchimp_init' ) ) {
	function ss_wc_mailchimp_init() {
		if ( ! function_exists( 'ss_woocommerce_is_active' ) ) {
			function ss_woocommerce_is_active() {
				$active_plugins = (array) get_option( 'active_plugins', array() );
				if ( is_multisite() ) {
					$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
				}
				return in_array( 'woocommerce/woocommerce.php', $active_plugins );
			}
		}

		if ( ss_woocommerce_is_active() ) {
			if ( ! class_exists( 'SS_WC_MailChimp' ) ) {

				//include( WOOCOMMERCE_MAILCHIMP_PATH . '/includes/class-ss-wc-mailchimp.php' );
				//die( file_exists( WOOCOMMERCE_MAILCHIMP_PLUGIN_PATH . '/includes/class-ss-wc-mailchimp.php' ) );
				require_once( WOOCOMMERCE_MAILCHIMP_PLUGIN_PATH . '/includes/class-ss-wc-mailchimp.php' );

				$GLOBALS['SS_WC_MailChimp'] = new SS_WC_MailChimp();

			} //end if ( ! class_exists( 'SS_WC_MailChimp' ) )

		} //end if ( ss_woocommerce_is_active() )

	} //end function ss_wc_mailchimp_init

} //end if ( ! function_exists( 'ss_wc_mailchimp_init' ) )


function woocommerce_mailchimp_init() {

	if ( ! class_exists( 'WC_Integration' ) )
		return;

	load_plugin_textdomain( 'ss_wc_mailchimp', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	global $woocommerce;

	$settings_url = admin_url( 'admin.php?page=woocommerce_settings&tab=integration&section=mailchimp' );

	if ( $woocommerce->version >= '2.1' ) {
		$settings_url = admin_url( 'admin.php?page=wc-settings&tab=integration&section=mailchimp' );
	}

	if ( ! defined( 'WOOCOMMERCE_MAILCHIMP_SETTINGS_URL' ) ) {
		define( 'WOOCOMMERCE_MAILCHIMP_SETTINGS_URL', $settings_url );
	}

	include_once( 'classes/class-ss-wc-integration-mailchimp.php' );

	/**
	 * Add the Integration to WooCommerce
	 */
	function add_mailchimp_integration($methods) {
		$methods[] = 'SS_WC_Integration_MailChimp';

		return $methods;
	}

	add_filter( 'woocommerce_integrations', 'add_mailchimp_integration' );

}
add_action( 'plugins_loaded', 'woocommerce_mailchimp_init', 0 );