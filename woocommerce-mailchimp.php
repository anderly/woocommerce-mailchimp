<?php
/**
 * Plugin Name: WooCommerce MailChimp
 * Plugin URI: https://www.saintsystems.com/products/woocommerce-mailchimp/
 * Description: WooCommerce MailChimp provides simple MailChimp integration for WooCommerce.
 * Author: Saint Systems
 * Author URI: https://www.saintsystems.com
 * Version: 2.0.12
 * Text Domain: woocommerce-mailchimp
 * Domain Path: languages
 *
 * Copyright: © 2016 Saint Systems
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

/** If this file is called directly, abort. */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/** Constants */

/**
 * Full path to the WooCommerce MailChimp file
 * @define "SS_WC_MAILCHIMP_FILE" "./woocommmerce-mailchimp.php"
 */
define( 'SS_WC_MAILCHIMP_FILE', __FILE__ );

/**
 * The main plugin class (SS_WC_MailChimp_Plugin)
 */
require_once( 'includes/class-ss-wc-mailchimp-plugin.php' );

/** Register hooks that are fired when the plugin is activated and deactivated. */
if ( is_admin() ) {
	register_activation_hook( SS_WC_MAILCHIMP_FILE, array( 'SS_WC_MailChimp_Plugin', 'activate' ) );
	register_deactivation_hook( SS_WC_MAILCHIMP_FILE, array( 'SS_WC_MailChimp_Plugin', 'deactivate' ) );
}

//add_action( 'plugins_loaded', array( 'SS_WC_MailChimp_Plugin', 'instance' ), 0 );
function SSWCMC() {
	return SS_WC_MailChimp_Plugin::instance();
}

// Get WooCommerce Mailchipm Running.
SSWCMC();
