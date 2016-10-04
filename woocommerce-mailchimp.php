<?php
/**
 * Plugin Name: WooCommerce MailChimp
 * Plugin URI: https://www.saintsystems.com/products/woocommerce-mailchimp/
 * Description: WooCommerce MailChimp provides simple MailChimp integration for WooCommerce.
 * Author: Saint Systems
 * Author URI: https://www.saintsystems.com
 * Version: 2.0.19
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

function SSWCMC() {
	return SS_WC_MailChimp_Plugin::get_instance();
}

// Get WooCommerce Mailchimp Running.
SSWCMC();
