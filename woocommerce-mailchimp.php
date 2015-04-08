<?php
/**
 * Plugin Name: WooCommerce MailChimp
 * Plugin URI: http://anderly.com/woocommerce-mailchimp
 * Description: WooCommerce MailChimp provides simple MailChimp integration for WooCommerce.
 * Author: Adam Anderly
 * Author URI: http://anderly.com
 * Version: 1.4
 * Text Domain: ss_wc_mailchimp
 * Domain Path: languages
 *
 * Copyright: © 2015 Adam Anderly
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * MailChimp Docs: http://apidocs.mailchimp.com/
 */

define('WOOCOMMERCE_MAILCHIMP_PLUGIN_NAME', plugin_basename( __FILE__ ) );
define('WOOCOMMERCE_MAILCHIMP_PLUGIN_PATH', untrailingslashit(plugin_dir_path(__FILE__)));
define('WOOCOMMERCE_MAILCHIMP_PLUGIN_URL', plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__)));
define('WOOCOMMERCE_MAILCHIMP_VERSION', '1.4');

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

				/**
				 * @class SS_WC_MailChimp
				 */
				class SS_WC_MailChimp {

					/**
					 * Constructor
					 *
					 * @access public
					 * @return void
					 */
					public function __construct() {

						global $woocommerce;

						$this->mailchimp = null;

						$this->id   = 'mailchimp';
						$this->label = __( 'MailChimp', 'ss_wc_mailchimp' );

						$this->settings_url = admin_url( 'admin.php?page=woocommerce_settings&tab=' . $this->id );
						if ( version_compare( $woocommerce->version, '2.1', '>=' ) ) {
							$this->settings_url = admin_url( 'admin.php?page=wc-settings&tab=' . $this->id );
						}

						$this->register_hooks();

					} //end function __construct

					/**
					 * Register plugin hooks
					 *
					 * @access public
					 * @return void
					 */
					public function register_hooks() {

						if ( is_admin() ) {
							// Add the "Settings" links on the Plugins administration screen
							add_filter( 'plugin_action_links_' . WOOCOMMERCE_MAILCHIMP_PLUGIN_NAME, array( $this, 'plugin_settings_link' ) );
							add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_mailchimp_settings' ) );
							add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts') );							
						}

						add_action( 'wp_ajax_woocommerce_mailchimp_get_lists', array( $this, 'ajax_get_lists' ) );
						add_action( 'wp_ajax_woocommerce_mailchimp_get_groups', array( $this, 'ajax_get_groups' ) );

					} //end function ensure_tab

					/**
					 * Add Settings link to plugins list
					 *
					 * @param  array $links Plugin links
					 * @return array Modified plugin links
					 */
					public function plugin_settings_link( $links ) {
						$plugin_links = array(
							'<a href="' . $this->settings_url . '">' . __( 'Settings', 'ss_wc_mailchimp' ) . '</a>',
						);

						return array_merge( $plugin_links, $links );

					} //end function plugin_settings_link

					/**
					 * Add the Integration to WooCommerce
					 */
					function add_mailchimp_settings( $settings ) {
						$settings[] = include( 'includes/class-ss-wc-settings-mailchimp.php' );

						return $settings;

					} //end function add_mailchimp_settings

					/**
			         * Load scripts required for admin
			         * 
			         * @access public
			         * @return void
			         */
			        public function enqueue_scripts() {
			        	// Plugin scripts and styles
						wp_register_script( 'woocommerce-mailchimp-admin', WOOCOMMERCE_MAILCHIMP_PLUGIN_URL . '/assets/js/woocommerce-mailchimp-admin.js', array( 'jquery' ), WOOCOMMERCE_MAILCHIMP_VERSION );
						wp_register_style( 'woocommerce-mailchimp', WOOCOMMERCE_MAILCHIMP_PLUGIN_URL . '/assets/css/style.css', array(), WOOCOMMERCE_MAILCHIMP_VERSION );

			            // Localize javascript messages
						$translation_array = array(
							'connecting_to_mailchimp' 		=> __( 'Connecting to MailChimp', 'ss_wc_mailchimp' ),
							'error_loading_lists' 			=> __( 'Error loading lists. Please check your api key.', 'ss_wc_mailchimp' ),
							'error_loading_groups' 			=> __( 'Error loading groups. Please check your MailChimp Interest Groups for the selected list.', 'ss_wc_mailchimp' ),
							'select_groups_placeholder'		=> __( 'Select one or more groups (optional)', 'ss_wc_mailchimp' ),
							'interest_groups_not_enabled' 	=> __( 'This list does not have interest groups enabled', 'ss_wc_mailchimp' ),
						);
						wp_localize_script( 'woocommerce-mailchimp-admin', 'SS_WC_MailChimp_Messages', $translation_array );

			            // Scripts
						wp_enqueue_script( 'woocommerce-mailchimp-admin' );

						// Styles
						wp_enqueue_style( 'woocommerce-mailchimp' );

					} //end function enqueue_scripts

					/**
			         * Return all lists from MailChimp to be used in select fields
			         * 
			         * @access public
			         * @return array
			         */
			        public function ajax_get_lists() {
			            try {
			            	if ( !$_POST['data']['api_key'] || empty( $_POST['data']['api_key'] ) ) {
			            		return $this->toJSON( array( '' => __( 'Enter your api key above to see your lists', 'ss_wc_mailchimp' ) ) );
			            	}

			            	if ( !$this->load_mailchimp( $_POST['data']['api_key'] ) ) {
			            		throw new Exception( __( 'Unable to load mailchimp api object.', 'ss_wc_mailchimp' ) );
			            	}

			                if ( !$this->mailchimp ) {
			                    throw new Exception( __( 'Unable to load lists', 'ss_wc_mailchimp' ) );
			                }

			                $lists = $this->mailchimp->get_lists();

			                if ( count( $lists ) < 1 ) {
			                    throw new Exception( __( 'No lists found', 'ss_wc_mailchimp' ) );
			                }

			                $lists = array_merge( array( '' => __( 'Select a list...', 'ss_wc_mailchimp' ) ), $lists );

			                $results = $lists;
			            }
			            catch ( Exception $e ) {
			                return $this->toJSON( array( 'error' => $e->getMessage() ) );
			            }

			            return $this->toJSON( $results );

			        } //end function get_lists

			        /**
			         * Return groups for the passed MailChimp List to be used in select fields
			         * 
			         * @access public
			         * @return array
			         */
			        public function ajax_get_groups() {
			            try {
			            	if ( !$_POST['data']['api_key'] || empty( $_POST['data']['api_key'] ) ) {
			            		return $this->toJSON( array( '' => __( 'Enter your api key above to see your lists', 'ss_wc_mailchimp' ) ) );
			            	}

			            	if ( !$_POST['data']['list_id'] || empty( $_POST['data']['list_id'] ) ) {
			            		return $this->toJSON( array( '' => __( 'Please select a list from above.', 'ss_wc_mailchimp' ) ) );
			            	}

			            	if ( !$this->load_mailchimp( $_POST['data']['api_key'] ) ) {
			            		throw new Exception( __( 'Unable to load mailchimp api object.', 'ss_wc_mailchimp' ) );
			            	}

			                if ( !$this->mailchimp ) {
			                    throw new Exception( __( 'Unable to load groups', 'ss_wc_mailchimp' ) );
			                }

			                $groups = $this->mailchimp->get_groups( $_POST['data']['list_id'] );

			                if ( count( $groups ) < 1 ) {
			                    throw new Exception( __( 'No groups found', 'ss_wc_mailchimp' ) );
			                }

			                $results = $groups;
			            }
			            catch ( Exception $e ) {
			                return $this->toJSON( array( 'error' => $e->getMessage() ) );
			            }

			            return $this->toJSON( $results );

			        } //end function get_lists


			        function toJSON( $response ) {
			        	//header('Content-Type: application/json');
			        	echo json_encode( $response );
			            die();

			        } //end function toJSON

			        /**
			         * Load MailChimp object
			         * 
			         * @access public
			         * @return mixed
			         */
			        public function load_mailchimp( $api_key = null ) {
			        	if ( !$api_key ) {
			        		$api_key = get_option( 'woocommerce_mailchimp_api_key' );
			        	}

			            if ( $this->mailchimp && $this->mailchimp->api_key == $api_key ) {
			                return true;
			            }

			            if ( empty( $api_key ) || $api_key == null ) return false;

			            // Load MailChimp class if not yet loaded
			            if ( !class_exists( 'SS_MailChimp_API' ) ) {
			                require_once WOOCOMMERCE_MAILCHIMP_PLUGIN_PATH . '/includes/class-ss-mailchimp-api.php';
			            }

			            try {
		                	$this->mailchimp = new SS_MailChimp_API( $api_key );
		                	return true;
			            } catch ( Exception $e ) {
			                return false;
			            }
			        }

			        /**
			         * Return whether or not we have an api key
			         * 
			         * @access public
			         * @return bool
			         */
			        public function has_api_key() {

			        	$api_key = get_option( 'woocommerce_mailchimp_api_key' );
			        	if ( $api_key ) {
			        		return true;
			        	}
			        	return false;
			        }

				} //end class SS_WC_Settings_MailChimp

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