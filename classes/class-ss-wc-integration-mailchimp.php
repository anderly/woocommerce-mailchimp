<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * MailChimp Integration
 *
 * Allows integration with MailChimp
 *
 * @class 		SS_WC_Integration_MailChimp
 * @extends		WC_Integration
 * @version		1.3.2
 * @package		WooCommerce MailChimp
 * @author 		Saint Systems
 */
class SS_WC_Integration_MailChimp extends WC_Integration {

	/**
	 * Init and hook in the integration.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {

		if ( !class_exists( 'MCAPI' ) ) {
			include_once( 'api/class-MCAPI.php' );
		}

		$this->id					= 'mailchimp';
		$this->method_title     	= __( 'MailChimp', 'ss_wc_mailchimp' );
		$this->method_description	= __( 'MailChimp is a popular email marketing service.', 'ss_wc_mailchimp' );

		// Load the settings.
		$this->init_settings();

		// We need the API key to set up for the lists in the form fields
		$this->api_key = $this->get_option( 'api_key' );
		$this->mailchimp = new MCAPI( $this->api_key );
		$this->enabled = $this->get_option( 'enabled' );

		$this->init_form_fields();

		// Get setting values
		$this->occurs         = $this->get_option( 'occurs' );
		$this->list           = $this->get_option( 'list' );
		$this->double_optin   = $this->get_option( 'double_optin' );
		$this->groups         = $this->get_option( 'groups' );
		$this->display_opt_in = $this->get_option( 'display_opt_in' );
		$this->opt_in_label   = $this->get_option( 'opt_in_label' );
		$this->opt_in_checkbox_default_status = $this->get_option( 'opt_in_checkbox_default_status' );
		$this->opt_in_checkbox_display_location = $this->get_option( 'opt_in_checkbox_display_location' );
		$this->interest_groupings = $this->get_option( 'interest_groupings' );

		// Hooks
		add_action( 'admin_notices', array( &$this, 'checks' ) );
		add_action( 'woocommerce_update_options_integration', array( $this, 'process_admin_options') );
		add_action( 'woocommerce_update_options_integration_' . $this->id, array( $this, 'process_admin_options') );

		// We would use the 'woocommerce_new_order' action but first name, last name and email address (order meta) is not yet available, 
		// so instead we use the 'woocommerce_checkout_update_order_meta' action hook which fires after the checkout process on the "thank you" page
		add_action( 'woocommerce_checkout_update_order_meta', array( &$this, 'order_status_changed' ), 1000, 1 );

		// hook into woocommerce order status changed hook to handle the desired subscription event trigger
		add_action( 'woocommerce_order_status_changed', array( &$this, 'order_status_changed' ), 10, 3 );

		// Maybe add an "opt-in" field to the checkout
		add_filter( 'woocommerce_checkout_fields', array( &$this, 'maybe_add_checkout_fields' ) );
		add_filter( 'default_checkout_ss_wc_mailchimp_opt_in', array( &$this, 'checkbox_default_status' ) );

		// Maybe save the "opt-in" field on the checkout
		add_action( 'woocommerce_checkout_update_order_meta', array( &$this, 'maybe_save_checkout_fields' ) );
	}

	/**
	 * Check if the user has enabled the plugin functionality, but hasn't provided an api key
	 **/
	function checks() {
		global $woocommerce;

		if ( $this->enabled == 'yes' ) {

			// Check required fields
			if ( ! $this->api_key ) {

				echo '<div class="error"><p>' . sprintf( __('WooCommerce MailChimp error: Plugin is enabled but no api key provided. Please enter your api key <a href="%s">here</a>.', 'ss_wc_mailchimp'), WOOCOMMERCE_MAILCHIMP_SETTINGS_URL ) . '</p></div>';

				return;

			}

		}
	}

	/**
	 * order_status_changed function.
	 *
	 * @access public
	 * @return void
	 */
	public function order_status_changed( $id, $status = 'new', $new_status = 'pending' ) { 

		if ( $this->is_valid() && $new_status == $this->occurs ) {

			$order = new WC_Order( $id );

			// get the ss_wc_mailchimp_opt_in value from the post meta. "order_custom_fields" was removed with WooCommerce 2.1
			$ss_wc_mailchimp_opt_in = get_post_meta( $id, 'ss_wc_mailchimp_opt_in', true );
			self::log( '$ss_wc_mailchimp_opt_in: ' . $ss_wc_mailchimp_opt_in );

			// If the 'ss_wc_mailchimp_opt_in' meta value isn't set (because 'display_opt_in' wasn't enabled at the time the order was placed) or the 'ss_wc_mailchimp_opt_in' is yes, subscriber the customer
			if ( ! isset( $ss_wc_mailchimp_opt_in ) || empty( $ss_wc_mailchimp_opt_in ) || 'yes' == $ss_wc_mailchimp_opt_in ) {
				self::log( 'Subscribing user (' . $order->billing_email . ') to list(' . $this->list . ') ' );
				$this->subscribe( $id, $order->billing_first_name, $order->billing_last_name, $order->billing_email, $this->list );
			}

		}
	}

	/**
	 * has_list function.
	 *
	 * @access public
	 * @return void
	 */
	public function has_list() {
		if ( $this->list )
			return true;
	}

	/**
	 * has_api_key function.
	 *
	 * @access public
	 * @return void
	 */
	public function has_api_key() {
		if ( $this->api_key )
			return true;
	}

	/**
	 * is_valid function.
	 *
	 * @access public
	 * @return boolean
	 */
	public function is_valid() {
		if ( $this->enabled == 'yes' && $this->has_api_key() && $this->has_list() ) {
			return true;
		}
		return false;
	}
	
	/**
	 * Initialize Settings Form Fields
	 *
	 * @access public
	 * @return void
	 */
	function init_form_fields() {

		if ( is_admin() && !is_ajax() ) {

			if ( $this->enabled && $this->has_api_key() ) {
				$lists = $this->get_lists();
	 			if ($lists === false ) {
	 				$lists = array ();
	 			}
	 		}
 			
 			$mailchimp_lists = $this->has_api_key() ? array_merge( array( '' => __('Select a list...', 'ss_wc_mailchimp' ) ), $lists ) : array( '' => __( 'Enter your key and save to see your lists', 'ss_wc_mailchimp' ) );
			//$mailchimp_interest_groupings = $this->has_list() ? array_merge( array( '' => __('Select an interest grouping...', 'ss_wc_mailchimp' ) ), $this->get_interest_groupings( $this->list ) ) : array( '' => __( 'Please select a list to see your interest groupings.', 'ss_wc_mailchimp' ) );

			$this->form_fields = array(
				'enabled' => array(
								'title' => __( 'Enable/Disable', 'ss_wc_mailchimp' ),
								'label' => __( 'Enable MailChimp', 'ss_wc_mailchimp' ),
								'type' => 'checkbox',
								'description' => '',
								'default' => 'no'
							),
				'occurs' => array(
								'title' => __( 'Subscribe Event', 'ss_wc_mailchimp' ),
								'type' => 'select',
								'description' => __( 'When should customers be subscribed to lists?', 'ss_wc_mailchimp' ),
								'default' => 'pending',
								'options' => array(
									'pending' => __( 'Order Created', 'ss_wc_mailchimp' ),
									'completed'  => __( 'Order Completed', 'ss_wc_mailchimp' ),
								),
							),
				'api_key' => array(
								'title' => __( 'API Key', 'ss_wc_mailchimp' ),
								'type' => 'text',
								'description' => __( '<a href="https://us2.admin.mailchimp.com/account/api/" target="_blank">Login to mailchimp</a> to look up your api key.', 'ss_wc_mailchimp' ),
								'default' => ''
							),
				'list' => array(
								'title' => __( 'Main List', 'ss_wc_mailchimp' ),
								'type' => 'select',
								'description' => __( 'All customers will be added to this list.', 'ss_wc_mailchimp' ),
								'default' => '',
								'options' => $mailchimp_lists,
							),
				'interest_groupings' => array(
								'title' => __( 'Group Name', 'ss_wc_mailchimp' ),
								'type' => 'text',
								'description' => __( 'Optional: Enter the name of the group. Learn more about <a href="http://mailchimp.com/features/groups" target="_blank">Groups</a>', 'ss_wc_mailchimp' ),
								'default' => '',
							),
				'groups' => array(
								'title' => __( 'Groups', 'ss_wc_mailchimp' ),
								'type' => 'text',
								'description' => __( 'Optional: Comma separated list of interest groups to which subscribers should be added.', 'ss_wc_mailchimp' ),
								'default' => '',
							),
				'double_optin' => array(
								'title' => __( 'Double Opt-In', 'ss_wc_mailchimp' ),
								'label' => __( 'Enable Double Opt-In', 'ss_wc_mailchimp' ),
								'type' => 'checkbox',
								'description' => __( 'If enabled, customers will receive an email prompting them to confirm their subscription to the list above.', 'ss_wc_mailchimp' ),
								'default' => 'no'
							),
				'display_opt_in' => array(
								'title'       => __( 'Display Opt-In Field', 'ss_wc_mailchimp' ),
								'label'       => __( 'Display an Opt-In Field on Checkout', 'ss_wc_mailchimp' ),
								'type'        => 'checkbox',
								'description' => __( 'If enabled, customers will be presented with a "Opt-in" checkbox during checkout and will only be added to the list above if they opt-in.', 'ss_wc_mailchimp' ),
								'default'     => 'no',
							),
				'opt_in_label' => array(
								'title'       => __( 'Opt-In Field Label', 'ss_wc_mailchimp' ),
								'type'        => 'text',
								'description' => __( 'Optional: customize the label displayed next to the opt-in checkbox.', 'ss_wc_mailchimp' ),
								'default'     => __( 'Add me to the newsletter (we will never share your email).', 'ss_wc_mailchimp' ),
							),
				'opt_in_checkbox_default_status' => array(
								'title'       => __( 'Opt-In Checkbox Default Status', 'ss_wc_mailchimp' ),
								'type'        => 'select',
								'description' => __( 'The default state of the opt-in checkbox.', 'ss_wc_mailchimp' ),
								'default'     => 'checked',
								'options'	=> array( 'checked' => __( 'Checked', 'ss_wc_mailchimp' ), 'unchecked' => __( 'Unchecked', 'ss_wc_mailchimp' ) )
							),
				'opt_in_checkbox_display_location' => array(
								'title'       => __( 'Opt-In Checkbox Display Location', 'ss_wc_mailchimp' ),
								'type'        => 'select',
								'description' => __( 'Where to display the opt-in checkbox on the checkout page (under Billing info or Order info).', 'ss_wc_mailchimp' ),
								'default'     => 'billing',
								'options'	=> array( 'billing' => __( 'Billing', 'ss_wc_mailchimp' ), 'order' => __( 'Order', 'ss_wc_mailchimp' ) )
							),
			);

			$this->wc_enqueue_js("
				jQuery('#woocommerce_mailchimp_display_opt_in').change(function(){

					jQuery('#mainform [id^=woocommerce_mailchimp_opt_in]').closest('tr').hide('fast');

					if ( jQuery(this).prop('checked') == true ) {
						jQuery('#mainform [id^=woocommerce_mailchimp_opt_in]').closest('tr').show('fast');
					} else {
						jQuery('#mainform [id^=woocommerce_mailchimp_opt_in]').closest('tr').hide('fast');
					}

				}).change();
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
	 * get_lists function.
	 *
	 * @access public
	 * @return void
	 */
	public function get_lists() {
		if ( ! $mailchimp_lists = get_transient( 'ss_wc_mailchimp_list_' . md5( $this->api_key ) ) ) {

			$mailchimp_lists = array();
			$retval          = $this->mailchimp->lists();

			if ( $this->mailchimp->errorCode ) {

				add_action( 'admin_notices', array( $this, 'mailchimp_api_error_msg' ) );
				add_action( 'network_admin_notices', array( $this, 'mailchimp_api_error_msg' ) );
				return false;

			} else {
				foreach ( $retval['data'] as $list )
					$mailchimp_lists[ $list['id'] ] = $list['name'];

				if ( sizeof( $mailchimp_lists ) > 0 )
					set_transient( 'ss_wc_mailchimp_list_' . md5( $this->api_key ), $mailchimp_lists, 60*60*1 );
			}
		}

		return $mailchimp_lists;
	}

	/**
	 * Display message to user if there is an issue with the MailChimp API call
	 *
	 * @since 1.0
	 * @param void
	 * @return html the message for the user
	 */
	public function mailchimp_api_error_msg() {

		$html  = '<div class="error">';
		$html .= '<p>' . sprintf( __( 'Unable to load lists from MailChimp: (%s) %s.', 'ss_wc_mailchimp' ), $this->mailchimp->errorCode, $this->mailchimp->errorMessage ) . sprintf( __( ' Please check your <a href="' . WOOCOMMERCE_MAILCHIMP_SETTINGS_URL . '">settings' . '</a>', 'ss_wc_mailchimp' ) ) . '</p>';
		$html .= '</div>';
		echo $html;

	}

	/**
	 * get_interest_groupings function.
	 *
	 * @access public
	 * @return void
	 */
	public function get_interest_groupings( $listid = 'false' ) {

		if ( $listid == 'false' )
			$listid = $this->list;

		//if ( ! $mailchimp_interest_groupings = get_transient( 'wc_mailchimp_list_' . md5( $this->api_key ) . '_' . $listid ) ) {

			$mailchimp_interest_groupings = array();
			$mailchimp_interest_groups = array();
			$api       = new MCAPI( $this->api_key );
			$retval    = $api->listInterestGroupings( $listid );

			if ( $mailchimp->errorCode ) {

				echo '<div class="error"><p>' . sprintf( __( 'Unable to load listInterestGroupings() from MailChimp: (%s) %s', 'ss_wc_mailchimp' ), $mailchimp->errorCode, $mailchimp->errorMessage ) . '</p></div>';

				return false;

			} else {
				if ( sizeof( $retval ) > 0 ) {
					foreach ( $retval as $interest_grouping ) {
						$mailchimp_interest_groupings[ $interest_grouping['id'] ] = $interest_grouping['name'];
						foreach ( $interest_grouping['groups'] as $group ) {
							$mailchimp_interest_groups[ $group['bit'] ] = $group['name'];
						}
					}

					if ( sizeof( $mailchimp_interest_groupings ) > 0 ) {
						set_transient( 'wc_mailchimp_list_' . md5( $this->api_key ) . '_' . $listid, $mailchimp_interest_groupings, 60*60*1 );
						set_transient( 'wc_mailchimp_list_' . md5( $this->api_key ) . '_' . $listid . '_groups', $mailchimp_interest_groups, 60*60*1 );
						$this->interest_groupings = $mailchimp_interest_groupings;
						$this->groups[$listid] = $mailchimp_interest_groups;
					}
				}
			}
		//}

		return $mailchimp_interest_groupings;
	}

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
	public function subscribe( $order_id, $first_name, $last_name, $email, $listid = 'false' ) {

		if ( ! $email )
			return; // Email is required

		if ( $listid == 'false' )
			$listid = $this->list;

		$api = new MCAPI( $this->api_key );

		$merge_vars = array( 'FNAME' => $first_name, 'LNAME' => $last_name );

		if ( !empty( $this->interest_groupings ) && !empty( $this->groups ) ) {
			$merge_vars['GROUPINGS'] = array(
					array('name' => $this->interest_groupings, 'groups' => $this->groups),
				);
		}

		$vars = apply_filters( 'ss_wc_mailchimp_subscribe_merge_vars', $merge_vars, $order_id );

		$email_type = 'html';
		$double_optin = ( $this->double_optin == 'no' ? false : true );
		$update_existing = true;
		$replace_interests = false;
		$send_welcome = false;

		self::log( 'Calling MailChimp API listSubscribe method with the following: ' .
			'listid=' . $listid .
			', email=' . $email .
			', vars=' . print_r( $vars, true ) . 
			', email_type=' . $email_type . 
			', double_optin=' . $double_optin .
			', update_existing=' . $update_existing .
			', replace_interests=' . $replace_interests .
			', send_welcome=' . $send_welcome
		);

		$retval = $api->listSubscribe( $listid, $email, $vars, $email_type, $double_optin, $update_existing, $replace_interests, $send_welcome );

		self::log( 'MailChimp return value:' . $retval );

		if ( $api->errorCode && $api->errorCode != 214 ) {
			self::log( 'WooCommerce MailChimp subscription failed: (' . $api->errorCode . ') ' . $api->errorMessage );

			do_action( 'ss_wc_mailchimp_subscribed', $email );

			// Email admin
			wp_mail( get_option('admin_email'), __( 'WooCommerce MailChimp subscription failed', 'ss_wc_mailchimp' ), '(' . $api->errorCode . ') ' . $api->errorMessage );
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
	function maybe_add_checkout_fields( $checkout_fields ) {

		$opt_in_checkbox_display_location = $this->opt_in_checkbox_display_location;

		if ( empty( $opt_in_checkbox_display_location ) ) {
			$opt_in_checkbox_display_location = 'billing';
		}

		if ( 'yes' == $this->display_opt_in ) {
			$checkout_fields[$opt_in_checkbox_display_location]['ss_wc_mailchimp_opt_in'] = array(
				'type'    => 'checkbox',
				'label'   => esc_attr( $this->opt_in_label ),
				'default' => ( $this->opt_in_checkbox_default_status == 'checked' ? 1 : 0 ),
			);
		}

		return $checkout_fields;
	}

	/**
	 * Opt-in checkbox default support for WooCommerce 2.1
	 *
	 * @since 1.2.1
	 */
	function checkbox_default_status( $input ) {

		return $this->opt_in_checkbox_default_status == 'checked' ? 1 : 0;
	}

	/**
	 * When the checkout form is submitted, save opt-in value.
	 *
	 * @version 1.1
	 */
	function maybe_save_checkout_fields( $order_id ) {

		if ( 'yes' == $this->display_opt_in ) {
			$opt_in = isset( $_POST['ss_wc_mailchimp_opt_in'] ) ? 'yes' : 'no';
			update_post_meta( $order_id, 'ss_wc_mailchimp_opt_in', $opt_in );
		}
	}

	/**
	 * Helper log function for debugging
	 *
	 * @since 1.2.2
	 */
	static function log( $message ) {
		if ( WP_DEBUG === true ) {
			if ( is_array( $message ) || is_object( $message ) ) {
				error_log( print_r( $message, true ) );
			} else {
				error_log( $message );
			}
		}
	}

}