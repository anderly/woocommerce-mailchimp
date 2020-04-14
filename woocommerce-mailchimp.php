<?php
/**
 * Plugin Name: WP WooCommerce Mailchimp
 * Plugin URI: https://www.saintsystems.com/products/woocommerce-mailchimp/
 * Description: WP WooCommerce Mailchimp provides simple and flexible Mailchimp integration for WooCommerce.
 * Author: Saint Systems
 * Author URI: https://www.saintsystems.com
 * Version: 2.3.12
 * WC tested up to: 4.0.1
 * Text Domain: woocommerce-mailchimp
 * Domain Path: languages
 *
 * Copyright: � 2019 Saint Systems
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

// Include Action Scheduler Library.
require_once( 'includes/lib/action-scheduler/action-scheduler.php' );

function SSWCMC() {
	return SS_WC_MailChimp_Plugin::get_instance();
}

// Get WooCommerce Mailchimp Running.
add_action( 'plugins_loaded', 'SSWCMC', 11 );
