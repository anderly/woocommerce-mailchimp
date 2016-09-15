<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * MailChimp Integration
 *
 * Allows integration with MailChimp
 *
 * @class 		SS_WC_Integration_MailChimp
 * @extends		WC_Integration
 * @version		1.4.0
 * @package		WooCommerce MailChimp
 * @author 		Saint Systems
 */
class SS_WC_Integration_MailChimp extends WC_Integration {

	/**
	 * Instance of the API class.
	 * @var Object
	 */
	private static $api_instance = null;

	private $api_key = '';
	private $debug = false;

	/**
	 * Init and hook in the integration.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {

		$this->id                 = 'mailchimp';
		$this->method_title       = __( 'MailChimp', 'ss_wc_mailchimp' );
		$this->method_description = __( 'MailChimp is a popular email marketing service.', 'ss_wc_mailchimp' );

		// Load the settings.
		$this->init_settings();

		if ( is_admin() && ! is_ajax() ) {
			// Load the settings
			$this->init_form_fields();
		}

		// Hooks
		add_action( 'admin_notices', array( $this, 'checks' ) );

		// Update the settings fields
		add_action( 'woocommerce_update_options_integration', array( $this, 'process_admin_options') );

		// Update the settings fields
		add_action( 'woocommerce_update_options_integration', array( $this, 'refresh_settings'), 10 );

		// Refresh the settings
		add_action( 'woocommerce_update_options_integration_' . $this->id, array( $this, 'refresh_settings'), 10 );

		// We would use the 'woocommerce_new_order' action but first name, last name and email address (order meta) is not yet available,
		// so instead we use the 'woocommerce_checkout_update_order_meta' action hook which fires after the checkout process on the "thank you" page
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'order_status_changed' ), 1000, 1 );

		// hook into woocommerce order status changed hook to handle the desired subscription event trigger
		add_action( 'woocommerce_order_status_changed', array( $this, 'order_status_changed' ), 10, 3 );

		// Maybe add an "opt-in" field to the checkout
		$opt_in_checkbox_display_location = !empty( $this->opt_in_checkbox_display_location() ) ? $this->opt_in_checkbox_display_location() : 'woocommerce_review_order_before_submit';

		// Old opt-in checkbox display locations
		$old_opt_in_checkbox_display_locations = array(
			'billing' => 'woocommerce_after_checkout_billing_form',
			'order' => 'woocommerce_review_order_before_submit',
		);

		// Map old billing/order checkbox display locations to new format
		if ( array_key_exists( $opt_in_checkbox_display_location, $old_opt_in_checkbox_display_locations ) ) {
			$opt_in_checkbox_display_location = $old_opt_in_checkbox_display_locations[ $opt_in_checkbox_display_location ];
		}

        add_action( $opt_in_checkbox_display_location, array( $this, 'maybe_add_checkout_fields' ) );
		add_filter( 'default_checkout_ss_wc_mailchimp_opt_in', array( $this, 'checkbox_default_status' ) );

		// Maybe save the "opt-in" field on the checkout
		add_action( 'woocommerce_checkout_update_order_meta',  array( $this, 'maybe_save_checkout_fields' ) );
	}

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

	/*
	Refreshes the settings form fields
	 */
	public function refresh_settings() {
		$this->init_form_fields();
	}

	/**
	 * Check if the user has enabled the plugin functionality, but hasn't provided an api key
	 **/
	function checks() {
		// Check required fields
		if ( $this->is_enabled() && ! $this->has_api_key() ) {
			// Show notice
			echo $this->get_message( sprintf( '%s <a href="%s">%s</a>.', 
					__( 'WooCommerce MailChimp error: Plugin is enabled but no api key provided. Please enter your api key', 'ss_wc_mailchimp'),
					WOOCOMMERCE_MAILCHIMP_SETTINGS_URL,
					__( 'here', 'ss_wc_mailchimp' ) 
				)
			);
		}
	}

	/**
	 * order_status_changed function.
	 *
	 * @access public
	 * @return void
	 */
	public function order_status_changed( $id, $status = 'new', $new_status = 'pending' ) {
		if ( $this->is_valid() && $new_status === $this->occurs() ) {
			// Get WC order
			$order = $this->wc_get_order( $id );

			// get the ss_wc_mailchimp_opt_in value from the post meta. "order_custom_fields" was removed with WooCommerce 2.1
			$subscribe_customer = get_post_meta( $id, 'ss_wc_mailchimp_opt_in', true );

			// If the 'ss_wc_mailchimp_opt_in' meta value isn't set 
			// (because 'display_opt_in' wasn't enabled at the time the order was placed) 
			// or the 'ss_wc_mailchimp_opt_in' is yes, subscriber the customer
			if ( ! $subscribe_customer || empty( $subscribe_customer ) || 'yes' === $subscribe_customer ) {
				// log
				$this->log( sprintf( __( __METHOD__ . '(): Subscribing customer (%s) to list %s', 'ss_wc_mailchimp' ), $order->billing_email, $this->list() ) );

				// subscribe
				$this->subscribe( $order->id, $order->billing_first_name, $order->billing_last_name, $order->billing_email, $this->list() );
			}
		}
	}

	/**
	 * Initialize Settings Form Fields
	 *
	 * @access public
	 * @return void
	 */
	function init_form_fields() {
		// $this->load_settings();

		$form_fields = array();

		$form_fields['api_key'] = array(
				'title'       => __( 'API Key', 'ss_wc_mailchimp' ),
				'type'        => 'text',
				'description' => sprintf( '<a href="https://admin.mailchimp.com/account/api/" target="_blank">%s</a> %s', 
					__( 'Login to MailChimp', 'ss_wc_mailchimp'),
					__( 'to look up your api key.', 'ss_wc_mailchimp' )
				),
				'default'     => ''
			);
		if ( !$this->has_api_key() ) {
			$form_fields['api_key']['description'] = sprintf( '%s <strong>%s</strong> %s.<br/>', 
				__( 'Paste your API key above and click', 'ss_wc_mailchimp' ),
				__( 'Save changes', 'ss_wc_mailchimp' ),
				__( 'below', 'ss_wc_mailchimp' )
			) . $form_fields['api_key']['description'];
		}

		$mailchimp_lists = $this->get_lists();

		if ( is_admin() && ! is_ajax() ) {

			if ( $this->has_api_key() && $mailchimp_lists !== false ) {

				if ( $this->has_api_key() && $this->has_list() ) {
					$interest_groups = $this->get_interest_groups();
				} else {
					$interest_groups = array();
				}
				
				$form_fields['enabled'] = array(
						'title'       => __( 'Enable/Disable', 'ss_wc_mailchimp' ),
						'label'       => __( 'Enable MailChimp Integration', 'ss_wc_mailchimp' ),
						'type'        => 'checkbox',
						'description' => __( 'Enable/disable the plugin functionality.', 'ss_wc_mailchimp' ),
						'default'     => 'yes',
					);

				$form_fields['list'] = array(
						'title'       => __( 'Main List', 'ss_wc_mailchimp' ),
						'type'        => 'select',
						'description' => __( 'All customers will be added to this list.', 'ss_wc_mailchimp' ),
						'default'     => '',
						'options'     => $mailchimp_lists,
						'class'       => 'wc-enhanced-select',
						'css'         => 'min-width: 350px;',
						'custom_attributes' => array(
							'onchange' => 'form.submit()',
						),
					);
				if ( array_key_exists( 'no_lists', $mailchimp_lists ) ) {
					$form_fields['list']['description'] = sprintf( __( 'There are no lists in your MailChimp account. <a href="%s" target="_blank">Click here</a> to create one.', 'ss_wc_mailchimp' ), 'https://admin.mailchimp.com/lists/new-list/' );
				}

				$form_fields['interest_groups'] = array(
						'title'       => __( 'Interest Groups', 'ss_wc_mailchimp' ),
						'type'        => 'multiselect',
						'description' => __( 'Optional: Interest groups to assign to subscribers.', 'ss_wc_mailchimp' ),
						'default'     => '',
						'options'     => $interest_groups,
						'class'       => 'wc-enhanced-select',
						'custom_attributes' => array( 
							'placeholder' => __( 'Select interest groups...', 'ss_wc_mailchimp' ),
						),
						'css'         => 'min-width: 350px;',
					);

				if ( is_array( $interest_groups ) && count( $interest_groups ) == 0 ) {
					// $form_fields['interest_groups']['description'] = __( 'Optional: Interest groups to assign to subscribers.', 'ss_wc_mailchimp' );
					$form_fields['interest_groups']['custom_attributes']['placeholder'] = __( 'This list has no interest groups.', 'ss_wc_mailchimp' );
					$form_fields['interest_groups']['custom_attributes']['disabled'] = 'disabled';
				} elseif ( !$this->has_list() ) {
					$form_fields['interest_groups']['custom_attributes']['placeholder'] = __( 'Select a list to see interests', 'ss_wc_mailchimp' );
					$form_fields['interest_groups']['custom_attributes']['disabled'] = 'disabled';
				}

				$form_fields['occurs'] = array(
						'title'       => __( 'Subscribe Event', 'ss_wc_mailchimp' ),
						'type'        => 'select',
						'description' => __( 'When should customers be subscribed to lists?', 'ss_wc_mailchimp' ),
						'default'     => 'pending',
						'options'     => array(
							'pending'    => __( 'Order Created', 'ss_wc_mailchimp' ),
							'processing' => __( 'Order Processing', 'ss_wc_mailchimp' ),
							'completed'  => __( 'Order Completed', 'ss_wc_mailchimp' ),
						),
					);
				
				$form_fields['double_optin'] = array(
						'title'       => __( 'Double Opt-In', 'ss_wc_mailchimp' ),
						'label'       => __( 'Enable Double Opt-In', 'ss_wc_mailchimp' ),
						'type'        => 'checkbox',
						'description' => __( 'If enabled, customers will receive an email prompting them to confirm their subscription to the list above.', 'ss_wc_mailchimp' ),
						'default'     => 'no'
					);

				$form_fields['display_opt_in'] = array(
						'title'       => __( 'Display Opt-In Field', 'ss_wc_mailchimp' ),
						'label'       => __( 'Display an Opt-In Field on Checkout', 'ss_wc_mailchimp' ),
						'type'        => 'checkbox',
						'description' => __( 'If enabled, customers will be presented with a "Opt-in" checkbox during checkout and will only be added to the list above if they opt-in.', 'ss_wc_mailchimp' ),
						'default'     => 'no',
					);

				$form_fields['opt_in_label'] = array(
						'title'       => __( 'Opt-In Field Label', 'ss_wc_mailchimp' ),
						'type'        => 'text',
						'description' => __( 'Optional: customize the label displayed next to the opt-in checkbox.', 'ss_wc_mailchimp' ),
						'default'     => __( 'Subscribe to our newsletter', 'ss_wc_mailchimp' ),
					);

				$form_fields['opt_in_checkbox_default_status'] = array(
						'title'       => __( 'Opt-In Checkbox Default Status', 'ss_wc_mailchimp' ),
						'type'        => 'select',
						'description' => __( 'The default state of the opt-in checkbox.', 'ss_wc_mailchimp' ),
						'default'     => 'checked',
						'options'     => array(
							'checked'   => __( 'Checked', 'ss_wc_mailchimp' ),
							'unchecked' => __( 'Unchecked', 'ss_wc_mailchimp' )
						)
					);

				$form_fields['opt_in_checkbox_display_location'] = array(
						'title'       => __( 'Opt-In Checkbox Display Location', 'ss_wc_mailchimp' ),
						'type'        => 'select',
						'description' => __( 'Where to display the opt-in checkbox on the checkout page.', 'ss_wc_mailchimp' ),
						'default'     => 'woocommerce_review_order_before_submit',
						'options'     => array(
							'woocommerce_checkout_before_customer_details' => __( 'Above customer details', 'ss_wc_mailchimp' ),
							'woocommerce_checkout_after_customer_details' => __( 'Below customer details', 'ss_wc_mailchimp' ),
							'woocommerce_review_order_before_submit' => __( 'Order review above submit', 'ss_wc_mailchimp' ),
							'woocommerce_review_order_after_submit' => __( 'Order review below submit', 'ss_wc_mailchimp' ),
							'woocommerce_review_order_before_order_total' => __( 'Order review above total', 'ss_wc_mailchimp' ),
							'woocommerce_checkout_billing' => __( 'Above billing details', 'ss_wc_mailchimp' ),
							'woocommerce_checkout_shipping' => __( 'Above shipping details', 'ss_wc_mailchimp' ),
							'woocommerce_after_checkout_billing_form' => __( 'Below Checkout billing form', 'ss_wc_mailchimp' ),
						)
					);

					$label = __( 'Enable Logging', 'ss_wc_mailchimp' );

					if ( defined( 'WC_LOG_DIR' ) ) {
						$debug_log_url = add_query_arg( 'tab', 'logs', add_query_arg( 'page', 'wc-status', admin_url( 'admin.php' ) ) );
						$debug_log_key = 'woocommerce-mailchimp-' . sanitize_file_name( wp_hash( 'woocommerce-mailchimp' ) ) . '-log';
						$debug_log_url = add_query_arg( 'log_file', $debug_log_key, $debug_log_url );

						$label .= ' | ' . sprintf( __( '%1$sView Log%2$s', 'ss_wc_mailchimp' ), '<a href="' . esc_url( $debug_log_url ) . '">', '</a>' );
					}

					$form_fields[ 'debug' ] = array(
						'title'       => __( 'Debug Log', 'ss_wc_mailchimp' ),
						'label'       => $label,
						'description' => __( 'Enable logging MailChimp API calls. Only enable for troubleshooting purposes.', 'ss_wc_mailchimp' ),
						'type'        => 'checkbox',
						'default'     => 'no'
					);
			}

			$this->form_fields = $form_fields;

			$this->wc_enqueue_js("
				jQuery('#woocommerce_mailchimp_display_opt_in').change(function() {
					if ( jQuery(this).prop('checked') === true ) {
						jQuery('#mainform [id^=woocommerce_mailchimp_opt_in]').closest('tr').show('fast');
					} else {
						jQuery('#mainform [id^=woocommerce_mailchimp_opt_in]').closest('tr').hide('fast');
					}

				});

				jQuery('#woocommerce_mailchimp_display_opt_in').change();
			");
		}

	} // End init_form_fields()

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
	 * WooCommerce 2.2 support for wc_get_order
	 *
	 * @since 1.2.1
	 *
	 * @access private
	 * @param int $order_id
	 * @return void
	 */
	private function wc_get_order( $order_id ) {
		if ( function_exists( 'wc_get_order' ) ) {
			return wc_get_order( $order_id );
		} else {
			return new WC_Order( $order_id );
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
		if ( is_null( self::$api_instance ) ) {
			if ( ! $this->has_api_key() ) {
				return false;
			}
			require_once( 'class-ss-wc-mailchimp-api.php' );
			self::$api_instance = new SS_WC_MailChimp_API( $this->api_key(), $this->debug_enabled() );
		}
		return self::$api_instance;
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
					'no_lists' => __( 'Oops! No lists in your MailChimp account...', 'ss_wc_mailchimp' ),
				);
				add_action( 'admin_notices', array( $this, 'mailchimp_no_lists_found' ) );
			} else {
				$default = array(
					'' => __( 'Select a list...', 'ss_wc_mailchimp' ),
				);
				set_transient( 'ss_wc_mailchimp_lists', $mailchimp_lists, 60 * 60 * 1 );
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
		echo $this->get_message( sprintf( __( 'Oops! There are no lists in your MailChimp account. <a href="%s" target="_blank">Click here</a> to create one.', 'ss_wc_mailchimp' ), 'https://admin.mailchimp.com/lists/new-list/' ) );
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
			sprintf( __( 'Unable to load lists from MailChimp: (%s) %s. ', 'ss_wc_mailchimp' ), $this->api()->get_error_code(), $this->api()->get_error_message() ) .
			sprintf( __( 'Please check your %s <a href="%s">settings</a>.', 'ss_wc_mailchimp' ), __( 'Settings', 'ss_wc_mailchimp' ), WOOCOMMERCE_MAILCHIMP_SETTINGS_URL )
		);
	} //end function mailchimp_api_error_msg 

	/**
	 * subscribe function.
	 *
	 * @access public
	 * @param int $order_id
	 * @param mixed $first_name
	 * @param mixed $last_name
	 * @param mixed $email
	 * @param string $listid (default: 'false')
	 * @return void
	 */
	public function subscribe( $order_id, $first_name, $last_name, $email, $list_id = 'false' ) {
		if ( ! $email ) {
			return; // Email is required
		}

		if ( 'false' == $list_id ) {
			$list_id = $this->list();
		}

		$merge_tags = array(
			'FNAME' => $first_name,
			'LNAME' => $last_name
		);

		if ( ! empty( $this->interest_groups() ) ) {
			$interest_groups = array_fill_keys( $this->interest_groups(), true );

			// Allow hooking into variables
			$interest_groups = apply_filters( 'ss_wc_mailchimp_subscribe_interest_groups', $interest_groups, $order_id, $email );
		}

		// Allow hooking into variables
		$merge_tags = apply_filters( 'ss_wc_mailchimp_subscribe_merge_tags', $merge_tags, $order_id, $email );

		// Set subscription options
		$subscribe_options = array(
			'list_id'           => $list_id,
			'email'             => $email,
			'merge_tags'      	=> $merge_tags,
			'interest_groups'   => $interest_groups,
			'email_type'        => 'html',
			'double_optin'      => $this->double_optin(),
		);

		// Allow hooking into subscription options
		$options = apply_filters( 'ss_wc_mailchimp_subscribe_options', $subscribe_options, $order_id  );

		// Extract options into variables
		extract( $options );

		// Log
		$this->log( sprintf( __( __METHOD__ . '(): Subscribing customer to MailChimp: %s', 'ss_wc_mailchimp' ), print_r( $options, true ) ) );

		// Call API
		$api_response = $this->api()->subscribe( $list_id, $email, $email_type, $merge_fields, $interests, $double_optin );

		// Log api response
		$this->log( sprintf( __( __METHOD__ . '(): MailChimp API response: %s', 'ss_wc_mailchimp' ), $api_response ) );

		if ( $api_response === false ) {
			// Format error message
			$error_response = sprintf( __( __METHOD__ . '(): WooCommerce MailChimp subscription failed: %s (%s)', 'ss_wc_mailchimp' ), $this->api()->get_error_message(), $this->api()->get_error_code() );

			// Log
			$this->log( $error_response );

			// New hook for failing operations
			do_action( 'ss_wc_mailchimp_subscription_failed', $email, array( 'list_id' => $list_id, 'order_id' => $order_id ) );

			// Email admin
			wp_mail( get_option( 'admin_email' ), __( 'WooCommerce MailChimp subscription failed', 'ss_wc_mailchimp' ), $error_response );
		} else {
			// Hook on success
			do_action( 'ss_wc_mailchimp_subscription_success', $email, array( 'list_id' => $list_id, 'order_id' => $order_id ) );
		}
	}

	/**
	 * Admin Panel Options
	 */
	function admin_options() {
		?>
		<h3><?php _e( 'MailChimp', 'ss_wc_mailchimp' ); ?></h3>
		<p><?php _e( 'Enter your MailChimp settings below to control how WooCommerce integrates with your MailChimp lists.', 'ss_wc_mailchimp' ); ?></p>
			<table class="form-table">
				<?php $this->generate_settings_html(); ?>
			</table><!--/.form-table-->
		<?php
	}

	/**
	 * Add the opt-in checkbox to the checkout fields (to be displayed on checkout).
	 *
	 * @since 1.1
	 */
	function maybe_add_checkout_fields() {
		if ( $this->is_valid() ) {
			if ( $this->display_opt_in() ) {
				do_action( 'ss_wc_mailchimp_before_opt_in_checkbox' );
				echo apply_filters('ss_wc_mailchimp_opt_in_checkbox', '<p class="form-row woocommerce-mailchimp-opt-in"><label for="ss_wc_mailchimp_opt_in"><input type="checkbox" name="ss_wc_mailchimp_opt_in" id="ss_wc_mailchimp_opt_in" value="yes"' . ($this->opt_in_checkbox_default_status() == 'checked' ? ' checked="checked"' : '') . '/> ' . esc_html( $this->opt_in_label() ) . '</label></p>' . "\n", $this->opt_in_checkbox_default_status(), $this->opt_in_label() );
				do_action( 'ss_wc_mailchimp_after_opt_in_checkbox' );
			}
		}
	}

	/**
	 * Opt-in checkbox default support for WooCommerce 2.1
	 *
	 * @since 1.2.1
	 */
	function checkbox_default_status( $input ) {
		return $this->opt_in_checkbox_default_status === 'checked' ? 1 : 0;
	}

	/**
	 * When the checkout form is submitted, save opt-in value.
	 *
	 * @version 1.1
	 */
	function maybe_save_checkout_fields( $order_id ) {
		if ( $this->display_opt_in() ) {
			$opt_in = isset( $_POST['ss_wc_mailchimp_opt_in'] ) ? 'yes' : 'no';

			update_post_meta( $order_id, 'ss_wc_mailchimp_opt_in', $opt_in );
		}
	}

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

}
