<?php
/**
 * WooCommerce MailChimp Settings
 *
 * @author 		Saint Systems
 * @package     WooCommerce MailChimp
 * @version		2.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'SS_WC_Settings_MailChimp' ) ) {

	/**
	 * @class   SS_WC_Settings_MailChimp
	 * @extends WC_Settings_Page
	 */
	class SS_WC_Settings_MailChimp extends WC_Settings_Page  {

		private static $_instance;

		/**
		 * Singleton instance
		 *
		 * @return SS_WC_Settings_MailChimp   SS_WC_Settings_MailChimp object
		 */
		public static function get_instance() {

			if ( empty( self::$_instance ) ) {
				self::$_instance = new self;
			}

			return self::$_instance;
		}

		/**
		 * Instance of the API class.
		 * @var Object
		 */
		private static $api_instance = null;

		/**
		 * Constructor
		 *
		 * @access public
		 * @return void
		 */
		public function __construct() {

			$this->id         = 'mailchimp';
			$this->namespace  = 'ss_wc_' . $this->id;
			$this->label      = __( 'MailChimp', $this->namespace );
			
			$this->init();
			
			$this->register_hooks();

		} //end function __construct

		/**
		 * api_key function.
		 * @return string MailChimp API Key
		 */
		public function api_key() {
			return $this->get_option( 'api_key' );
		}

		/**
		 * is_enabled function.
		 *
		 * @access public
		 * @return boolean
		 */
		public function is_enabled() {
			return 'yes' === $this->get_option( 'enabled' );
		}

		/**
		 * occurs function
		 * @return string
		 */
		public function occurs() {
			return $this->get_option( 'occurs' );
		}

		/**
		 * list function.
		 *
		 * @access public
		 * @return string MailChimp list ID
		 */
		public function list() {
			return $this->get_option( 'list' );
		}

		/**
		 * double_optin function.
		 *
		 * @access public
		 * @return boolean
		 */
		public function double_optin() {
			return 'yes' === $this->get_option( 'double_optin' );
		}

		/**
		 * display_opt_in function.
		 *
		 * @access public
		 * @return boolean
		 */
		public function display_opt_in() {
			return 'yes' === $this->get_option( 'display_opt_in' );
		}

		/**
		 * opt_in_label function.
		 *
		 * @access public
		 * @return string
		 */
		public function opt_in_label() {
			return $this->get_option( 'opt_in_label' );
		}

		/**
		 * opt_in_checkbox_default_status function.
		 *
		 * @access public
		 * @return string
		 */
		public function opt_in_checkbox_default_status() {
			return $this->get_option( 'opt_in_checkbox_default_status' );
		}

		/**
		 * opt_in_checkbox_display_location function.
		 *
		 * @access public
		 * @return string
		 */
		public function opt_in_checkbox_display_location() {
			return $this->get_option( 'opt_in_checkbox_display_location' );
		}

		/**
		 * interests function.
		 *
		 * @access public
		 * @return array
		 */
		public function interest_groups() {
			return $this->get_option( 'interest_groups' );
		}

		/**
		 * has_list function.
		 *
		 * @access public
		 * @return boolean
		 */
		public function has_list() {
			if ( $this->list() ) {
				return true;
			}
			return false;
		}

		/**
		 * has_api_key function.
		 *
		 * @access public
		 * @return boolean
		 */
		public function has_api_key() {
			return !empty( $this->api_key() );
		}

		/**
		 * is_valid function.
		 *
		 * @access public
		 * @return boolean
		 */
		public function is_valid() {
			return $this->is_enabled() && $this->has_api_key() && $this->has_list();
		}

		/**
		 * debug_enabled function.
		 *
		 * @access public
		 * @return boolean
		 */
		public function debug_enabled() {
			return 'yes' === $this->get_option( 'debug' );
		}

		/**
		 * Check if the user has enabled the plugin functionality, but hasn't provided an api key
		 **/
		function checks() {
			// Check required fields
			if ( $this->is_enabled() && ! $this->has_api_key() ) {
				// Show notice
				echo $this->get_message( sprintf( '%s <a href="%s">%s</a>.', 
						__( 'WooCommerce MailChimp error: Plugin is enabled but no api key provided. Please enter your api key', $this->namespace ),
						WOOCOMMERCE_MAILCHIMP_SETTINGS_URL,
						__( 'here', $this->namespace ) 
					)
				);
			}
		}

		public function init() {

			$this->api_key  = $this->get_option( 'api_key' );
			
			$this->enabled  = $this->get_option( 'enabled' );

		}

		public function get_option( $option_suffix ) {

			return get_option( $this->namespace_prefixed( $option_suffix ) );

		}

		/**
		 * Register plugin hooks
		 *
		 * @access public
		 * @return void
		 */
		public function register_hooks() {

			// Hook in to add the MailChimp tab
			add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
			add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
			add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
			add_action( 'woocommerce_sections_' . $this->id, array( $this, 'output_sections' ) );
			add_action( 'woocommerce_settings_saved', array( $this, 'init' ) );

			// Hooks
			add_action( 'admin_notices', array( $this, 'checks' ) );

		} //end function ensure_tab

		// /**
		//  * Get sections
		//  *
		//  * @return array
		//  */
		public function get_sections() {

			$sections = array(
				''                => __( 'General', $this->namespace ),
				// 'checkout'	=> __( 'Checkout', $this->namespace ),
				// //'widget' 	=> __( 'Widget', $this->namespace ),
				// 'shortcode'	=> __( 'ShortCode', $this->namespace ),
				'troubleshooting' => __( 'Troubleshooting', $this->namespace ),
			);

			return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
		}

		/**
		 * Output the settings
		 */
		public function output() {

			global $current_section;

			$settings = $this->get_settings( $current_section );

	 		WC_Admin_Settings::output_fields( $settings );

	 		$this->wc_enqueue_js("
	 			(function($){
	 				
	 				$(document).ready(function() {
	 					SS_WC_MailChimp.init();
	 				});

	 			})(jQuery);
			");

		}

		/**
		 * Save settings
		 */
		public function save() {
			global $current_section;

			$settings = $this->get_settings( $current_section );
			WC_Admin_Settings::save_fields( $settings );
		}

		/**
		 * Get settings array
		 *
		 * @return array
		 */
		public function get_settings( $current_section = '' ) {

			if ( '' === $current_section ) {

			$settings = array(
				array(
					'title' => __( 'MailChimp', $this->namespace ),
					'type' 	=> 'title',
					'desc' 	=> __( 'Enter your MailChimp settings below to control how WooCommerce integrates with your MailChimp account.', $this->namespace ),
					'id' 	=> 'general_options',
				),
			);

			$settings[] = array(
					'id'          => $this->namespace_prefixed( 'api_key' ),
					'title'       => __( 'API Key', $this->namespace ),
					'type'        => 'text',
					'desc' => sprintf( '<br/><a href="https://admin.mailchimp.com/account/api/" target="_blank">%s</a> %s', 
						__( 'Login to MailChimp', $this->namespace ),
						__( 'to look up your api key.', $this->namespace )
					),
					'default'     => '',
					'css'         => 'min-width:350px;',
					'desc_tip'    => 'Your API Key is required for the plugin to communicate with your MailChimp account.',
				);

			$mailchimp_lists = $this->get_lists();

			// if ( ! $this->has_api_key() || $mailchimp_lists === false ) {
			// 	$settings[] = array( 'type' => 'sectionend', 'id' => 'general_options' );
			// }
			// if ( !$this->has_api_key() ) {
			// 	$form_fields['api_key']['description'] = sprintf( '%s <strong>%s</strong> %s.<br/>', 
			// 		__( 'Paste your API key above and click', $this->namespace ),
			// 		__( 'Save changes', $this->namespace ),
			// 		__( 'below', $this->namespace )
			// 	) . $form_fields['api_key']['description'];
			// }

			//if ( is_admin() && ! is_ajax() ) {

				// if ( $this->has_api_key() && $mailchimp_lists !== false ) {

					if ( $this->has_api_key() && $this->has_list() ) {
						$interest_groups = $this->get_interest_groups();
					} else {
						$interest_groups = array();
					}
					
					$settings[] = array(
							'id'          => $this->namespace_prefixed( 'enabled' ),
							'title'       => __( 'Enable/Disable', $this->namespace ),
							'label'       => __( 'Enable MailChimp Integration', $this->namespace ),
							'type'        => 'checkbox',
							'desc' => __( 'Enable/disable the plugin functionality.', $this->namespace ),
							'default'     => 'yes',
						);

					$settings[] = array(
							'id'          => $this->namespace_prefixed( 'list' ),
							'title'       => __( 'Main List', $this->namespace ),
							'type'        => 'select',
							'desc'        => __( 'All customers will be added to this list.', $this->namespace ),
							'default'     => '',
							'options'     => $mailchimp_lists,
							'class'       => 'wc-enhanced-select',
							'css'         => 'min-width: 350px;',
							// 'custom_attributes' => array(
							// 	'onchange' => 'form.submit()',
							// ),
							'desc_tip'    =>  true,
						);
					// if ( array_key_exists( 'no_lists', $mailchimp_lists ) ) {
					// 	$form_fields['list']['description'] = sprintf( __( 'There are no lists in your MailChimp account. <a href="%s" target="_blank">Click here</a> to create one.', $this->namespace ), 'https://admin.mailchimp.com/lists/new-list/' );
					// }

					$settings[] = array(
							'id'          => $this->namespace_prefixed( 'interest_groups' ),
							'title'       => __( 'Interest Groups', $this->namespace ),
							'type'        => 'multiselect',
							'desc' => __( 'Optional: Interest groups to assign to subscribers.', $this->namespace ),
							'default'     => '',
							'options'     => $interest_groups,
							'class'       => 'wc-enhanced-select',
							'custom_attributes' => array( 
								'placeholder' => __( 'Select interest groups...', $this->namespace ),
							),
							'css'         => 'min-width: 350px;',
							'desc_tip'    =>  true,
						);

					// if ( is_array( $interest_groups ) && count( $interest_groups ) == 0 ) {
					// 	// $form_fields['interest_groups']['description'] = __( 'Optional: Interest groups to assign to subscribers.', $this->namespace );
					// 	$form_fields['interest_groups']['custom_attributes']['placeholder'] = __( 'This list has no interest groups.', $this->namespace );
					// 	$form_fields['interest_groups']['custom_attributes']['disabled'] = 'disabled';
					// } elseif ( !$this->has_list() ) {
					// 	$form_fields['interest_groups']['custom_attributes']['placeholder'] = __( 'Select a list to see interests', $this->namespace );
					// 	$form_fields['interest_groups']['custom_attributes']['disabled'] = 'disabled';
					// }
					$settings[] = array( 'type' => 'sectionend', 'id' => 'general_options' );

					$settings[] = array(
						'title' => __( 'Checkout Settings', $this->namespace ),
						'type' 	=> 'title',
						'desc' 	=> '',
						'id' 	=> 'checkout_settings'
					);

					$settings[] = array(
						'id'       => $this->namespace_prefixed( 'display_opt_in' ),
						'title'    => __( 'Subscribe Customers', $this->namespace ),
						'desc'     => __( '<p>Choose <strong>Automatically</strong> to subscribe customers silently upon checkout. Caution, this is without the customer\'s consent.</p> <p>Choose <strong>Ask for permission</strong> to show an "Opt-in" checkbox during checkout. Customers will only be subscribed to the list above if they opt-in.', $this->namespace ),
						'type'     => 'select',
						'css'      => 'min-width:300px;',
						'class'    => 'wc-enhanced-select',
						'desc_tip' => true,
						'default'  => 1,
						'options'  => array(
							// '0' => __( 'Disabled', $this->namespace ),
							'no' => __( 'Automatically', $this->namespace ),
							'yes' => __( 'Ask for permission', $this->namespace )
						)
					);

					$settings[] = array(
							'id'          => $this->namespace_prefixed( 'occurs' ),
							'title'       => __( 'Subscribe Event', $this->namespace ),
							'type'        => 'select',
							'desc'        => __( 'Choose whether to subscribe customers as soon as an order is placed or after the order is processing or completed.', $this->namespace ),
							'class'       => 'wc-enhanced-select',
							'default'     => 'pending',
							'options'     => array(
								'pending'    => __( 'Order Created', $this->namespace ),
								'processing' => __( 'Order Processing', $this->namespace ),
								'completed'  => __( 'Order Completed', $this->namespace ),
							),
							'desc_tip'    =>  true,
						);
					
					$settings[] = array(
							'id'          => $this->namespace_prefixed( 'double_optin' ),
							'title'       => __( 'Double Opt-In', $this->namespace ),
							'desc'        => __( 'Enable Double Opt-In', $this->namespace ),
							'type'        => 'checkbox',
							'default'     => 'no',
							'desc_tip'    => __( 'If enabled, customers will receive an email prompting them to confirm their subscription to the list above.', $this->namespace ),
						);

					// $settings[] = array(
					// 		'id'          => $this->namespace_prefixed( 'display_opt_in' ),
					// 		'title'       => __( 'Display Opt-In Field', $this->namespace ),
					// 		'label'       => __( 'Display an Opt-In Field on Checkout', $this->namespace ),
					// 		'type'        => 'checkbox',
					// 		'desc'        => __( 'If enabled, customers will be presented with a "Opt-in" checkbox during checkout and will only be added to the list above if they opt-in.', $this->namespace ),
					// 		'default'     => 'no',
					// 	);

					$settings[] = array(
							'id'          => $this->namespace_prefixed( 'opt_in_label' ),
							'title'       => __( 'Opt-In Field Label', $this->namespace ),
							'type'        => 'text',
							'desc'        => __( 'Optional: customize the label displayed next to the opt-in checkbox.', $this->namespace ),
							'default'     => __( 'Subscribe to our newsletter', $this->namespace ),
							'css'         => 'min-width:350px;',
							'desc_tip'    =>  true,
						);

					$settings[] = array(
							'id'          => $this->namespace_prefixed( 'opt_in_checkbox_default_status' ),
							'title'       => __( 'Opt-In Checkbox Default', $this->namespace ),
							'type'        => 'select',
							'desc'        => __( 'The default state of the opt-in checkbox.', $this->namespace ),
							'class'       => 'wc-enhanced-select',
							'default'     => 'checked',
							'options'     => array(
								'checked'   => __( 'Checked', $this->namespace ),
								'unchecked' => __( 'Unchecked', $this->namespace )
							),
							'desc_tip'    =>  true,
						);

					$settings[] = array(
							'id'          => $this->namespace_prefixed( 'opt_in_checkbox_display_location' ),
							'title'       => __( 'Opt-In Checkbox Location', $this->namespace ),
							'type'        => 'select',
							'desc'        => __( 'Where to display the opt-in checkbox on the checkout page.', $this->namespace ),
							'class'       => 'wc-enhanced-select',
							'default'     => 'woocommerce_review_order_before_submit',
							'options'     => array(
								'woocommerce_checkout_before_customer_details' => __( 'Above customer details', $this->namespace ),
								'woocommerce_checkout_after_customer_details' => __( 'Below customer details', $this->namespace ),
								'woocommerce_review_order_before_submit' => __( 'Order review above submit', $this->namespace ),
								'woocommerce_review_order_after_submit' => __( 'Order review below submit', $this->namespace ),
								'woocommerce_review_order_before_order_total' => __( 'Order review above total', $this->namespace ),
								'woocommerce_checkout_billing' => __( 'Above billing details', $this->namespace ),
								'woocommerce_checkout_shipping' => __( 'Above shipping details', $this->namespace ),
								'woocommerce_after_checkout_billing_form' => __( 'Below Checkout billing form', $this->namespace ),
							),
							'desc_tip'    =>  true,
						);

					$settings[] = array( 'type' => 'sectionend', 'id' => 'checkout_settings' );
					
				// }

				$settings = apply_filters( $this->namespace_prefixed( 'settings_general' ), $settings );

			} elseif ( 'troubleshooting' === $current_section ) {

				$label = __( 'Enable Logging', $this->namespace );

				if ( defined( 'WC_LOG_DIR' ) ) {
					$debug_log_url = add_query_arg( 'tab', 'logs', add_query_arg( 'page', 'wc-status', admin_url( 'admin.php' ) ) );
					$debug_log_key = 'woocommerce-mailchimp-' . sanitize_file_name( wp_hash( 'woocommerce-mailchimp' ) ) . '-log';
					$debug_log_url = add_query_arg( 'log_file', $debug_log_key, $debug_log_url );

					$label .= ' | ' . sprintf( __( '%1$sView Log%2$s', $this->namespace ), '<a href="' . esc_url( $debug_log_url ) . '">', '</a>' );
				}

				$settings[] = array(
					'title' => __( 'Troubleshooting', $this->namespace ),
					'type' 	=> 'title',
					'desc' 	=> '',
					'id' 	=> 'troubleshooting_settings'
				);

				$settings[]  = array(
						'id'          => $this->namespace_prefixed( 'debug' ),
						'title'       => __( 'Debug Log', $this->namespace ),
						'desc'        => $label,
						'type'        => 'checkbox',
						'default'     => 'no',
						'desc_tip'    => __( 'Enable logging MailChimp API calls. Only enable for troubleshooting purposes.', $this->namespace ),
					);

				$settings[] = array( 'type' => 'sectionend', 'id' => 'troubleshooting_settings' );

				$settings = apply_filters( $this->namespace_prefixed( 'settings_troubleshooting' ), $settings );

			}

			//}

			return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings, $current_section );

		} //end function get_settings

		private function namespace_prefixed( $value ) {
			return $this->namespace . '_' . $value;
		}

		/**
		 * WooCommerce 2.1 support for wc_enqueue_js
		 *
		 * @since 1.2.1
		 *
		 * @access private
		 * @param string $code
		 * @return void
		 */
		private function wc_enqueue_js( $code ) {
			if ( function_exists( 'wc_enqueue_js' ) ) {
				wc_enqueue_js( $code );
			} else {
				global $woocommerce;
				$woocommerce->add_inline_js( $code );
			}
		}

		/**
		 * Get message
		 * @return string Error
		 */
		private function get_message( $message, $type = 'error' ) {
			ob_start();

			?>
			<div class="<?php echo $type ?>">
				<p><?php echo $message ?></p>
			</div>
			<?php
			return ob_get_clean();
		}

		/**
		 * API Instance Singleton
		 * @return Object
		 */
		public function api() {
			// if ( is_null( self::$api_instance ) ) {
			// 	if ( ! $this->has_api_key() ) {
			// 		return false;
			// 	}
			// 	require_once( 'class-ss-wc-mailchimp-api.php' );
			// 	self::$api_instance = new SS_WC_MailChimp_API( $this->api_key(), $this->debug_enabled() );
			// }
			// return self::$api_instance;
			return ss_wc_mailchimp('api');
		}

		/**
		 * get_lists function.
		 *
		 * @access public
		 * @return void
		 */
		public function get_lists() {

			// $mailchimp_lists = get_transient( 'ss_wc_mailchimp_lists' );

			// if ( ! $mailchimp_lists ) {
				if ( $this->api() ) {
					$mailchimp_lists = $this->api()->get_lists();
				} else {
					return false;
				}

				if ( $mailchimp_lists === false ) {

					add_action( 'admin_notices',         array( $this, 'mailchimp_api_error_msg' ) );
					add_action( 'network_admin_notices', array( $this, 'mailchimp_api_error_msg' ) );

					return false;

				}

				if ( count( $mailchimp_lists ) === 0 ) {
					$default = array(
						'no_lists' => __( 'Oops! No lists in your MailChimp account...', $this->namespace ),
					);
					add_action( 'admin_notices', array( $this, 'mailchimp_no_lists_found' ) );
				} else {
					$default = array(
						'' => __( 'Select a list...', $this->namespace ),
					);
					set_transient( $this->namespace_prefixed( 'lists' ), $mailchimp_lists, 60 * 60 * 1 );
				}
				$mailchimp_lists = array_merge( $default, $mailchimp_lists );

			//}

			return $mailchimp_lists;
			
		}

		/**
		 * get_interest_groups function.
		 *
		 * @access public
		 * @return void
		 */
		public function get_interest_groups() {

			if ( $this->api() && $this->has_list() ) {
				$interest_groups = $this->api()->get_interest_categories_with_interests( $this->list() );
			} else {
				return false;
			}

			if ( $interest_groups === false ) {

				add_action( 'admin_notices',         array( $this, 'mailchimp_api_error_msg' ) );
				add_action( 'network_admin_notices', array( $this, 'mailchimp_api_error_msg' ) );

				return false;

			}

			return $interest_groups;
			
		}

		/**
		 * Inform the user they don't have any MailChimp lists
		 */
		public function mailchimp_no_lists_found() {
			echo $this->get_message( sprintf( __( 'Oops! There are no lists in your MailChimp account. <a href="%s" target="_blank">Click here</a> to create one.', $this->namespace ), 'https://admin.mailchimp.com/lists/new-list/' ) );
		}

		/**
		 * Display message to user if there is an issue with the MailChimp API call
		 *
		 * @since 1.0
		 * @param void
		 * @return html the message for the user
		 */
		public function mailchimp_api_error_msg() {
			echo $this->get_message(
				sprintf( __( 'Unable to load lists from MailChimp: (%s) %s. ', $this->namespace ), $this->api()->get_error_code(), $this->api()->get_error_message() ) .
				sprintf( __( 'Please check your %s <a href="%s">settings</a>.', $this->namespace ), __( 'Settings', $this->namespace ), WOOCOMMERCE_MAILCHIMP_SETTINGS_URL )
			);
		} //end function mailchimp_api_error_msg 

		/**
		 * Helper log function for debugging
		 *
		 * @since 1.2.2
		 */
		private function log( $message ) {
			if ( $this->debug_enabled() ) {
				$logger = new WC_Logger();

				if ( is_array( $message ) || is_object( $message ) ) {
					$logger->add( 'woocommerce-mailchimp', print_r( $message, true ) );
				}
				else {
					$logger->add( 'woocommerce-mailchimp', $message );
				}
			}
		}

	} //end class SS_WC_MailChimp

	return SS_WC_Settings_MailChimp::get_instance();

} //end if ( ! class_exists( 'SS_WC_Settings_MailChimp' ) )