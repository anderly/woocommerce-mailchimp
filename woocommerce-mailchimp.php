<?php
/**
 * Plugin Name: WooCommerce MailChimp
 * Plugin URI: https://www.saintsystems.com/products/woocommerce-mailchimp/
 * Description: WooCommerce MailChimp provides simple MailChimp integration for WooCommerce.
 * Author: Saint Systems
 * Author URI: https://www.saintsystems.com
 * Version: 2.0.8
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
 * The URL to this file
 */
define( 'SS_WC_MAILCHIMP_URL', plugin_dir_url( __FILE__ ) );

/**
 * The absolute path to the plugin directory
 * @define "SS_WC_MAILCHIMP_DIR" "./"
 */
define( 'SS_WC_MAILCHIMP_DIR', plugin_dir_path( __FILE__ ) );

define( 'SS_WC_MAILCHIMP_PLUGIN_URL', plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) );

/**
 * The main plugin class (SS_WC_MailChimp_Plugin)
 */
require_once( 'includes/class-ss-wc-mailchimp-plugin.php' );

/** Register hooks that are fired when the plugin is activated and deactivated. */
if ( is_admin() ) {
	register_activation_hook( __FILE__, array( 'SS_WC_MailChimp_Plugin', 'activate' ) );
	register_deactivation_hook( __FILE__, array( 'SS_WC_MailChimp_Plugin', 'deactivate' ) );
}

add_action( 'plugins_loaded', array( 'SS_WC_MailChimp_Plugin', 'get_instance' ), 0 );
