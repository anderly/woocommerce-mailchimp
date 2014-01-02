<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * MailChimp Integration
 *
 * Allows integration with MailChimp
 *
 * @class 		SS_WC_Integration_MailChimp
 * @extends		WC_Integration
 * @version		1.1.3
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

		// We need the API key to set up for the lists in teh form fields
		$this->api_key = $this->get_option( 'api_key' );

		$this->init_form_fields();

		// Get setting values
		$this->enabled        = $this->get_option( 'enabled' );
		$this->occurs         = $this->get_option( 'occurs' );
		$this->list           = $this->get_option( 'list' );
		$this->double_optin   = $this->get_option( 'double_optin' );
		$this->groups         = $this->get_option( 'groups' );
		$this->display_opt_in = $this->get_option( 'display_opt_in' );
		$this->opt_in_label   = $this->get_option( 'opt_in_label' );
		$this->interest_groupings = $this->get_option( 'interest_groupings' );

		// Hooks
		add_action( 'admin_notices', array( &$this, 'checks' ) );
		add_action( 'woocommerce_update_options_integration', array( $this, 'process_admin_options') );
		add_action( 'woocommerce_update_options_integration_' . $this->id, array( $this, 'process_admin_options') );

		// We would use the 'woocommerce_new_order' action but first name, last name and email address (order meta) is not yet available, 
		// so instead we use the 'woocommerce_checkout_update_order_meta' action hook which fires at the end of the checkout process after the order meta has been saved
		add_action( 'woocommerce_checkout_update_order_meta', array( &$this, 'order_status_changed' ), 10, 1 );

		// hook into woocommerce order status changed hook to handle the desired subscription event trigger
		add_action( 'woocommerce_order_status_changed', array( &$this, 'order_status_changed' ), 10, 3 );

		// Maybe add an "opt-in" field to the checkout
		add_filter( 'woocommerce_checkout_fields', array( &$this, 'maybe_add_checkout_fields' ) );

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

				echo '<div class="error"><p>' . sprintf( __('MailChimp error: Please enter your api key <a href="%s">here</a>', 'ss_wc_mailchimp'), admin_url('admin.php?page=woocommerce&tab=integration&section=mailchimp' ) ) . '</p></div>';

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

			// If the 'ss_wc_mailchimp_opt_in' meta value isn't set (because 'display_opt_in' wasn't enabled at the time the order was placed) or the 'ss_wc_mailchimp_opt_in' is yes, subscriber the customer
			if ( ! isset( $order->order_custom_fields['ss_wc_mailchimp_opt_in'][0] ) || 'yes' == $order->order_custom_fields['ss_wc_mailchimp_opt_in'][0] ) {
				$this->subscribe( $order->billing_first_name, $order->billing_last_name, $order->billing_email, $this->list );
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

		if ( is_admin() ) {

			$mailchimp_lists = $this->has_api_key() ? array_merge( array( '' => __('Select a list...', 'ss_wc_mailchimp' ) ), $this->get_lists() ) : array( '' => __( 'Enter your key and save to see your lists', 'ss_wc_mailchimp' ) );
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
								'description' => __( 'Optional: Comma delimited list of interest groups to add the email to.', 'ss_wc_mailchimp' ),
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
								'default'     => 'no'
							),
				'opt_in_label' => array(
								'title'       => __( 'Opt-In Field Label', 'ss_wc_mailchimp' ),
								'type'        => 'text',
								'description' => __( 'Optional: customize the label displayed next to the opt-in checkbox.', 'ss_wc_mailchimp' ),
								'default'     => __( 'Add me to the newsletter (we will never share your email).', 'ss_wc_mailchimp' ),
							),
			);

		}

	} // End init_form_fields()

	/**
	 * get_lists function.
	 *
	 * @access public
	 * @return void
	 */
	public function get_lists() {
		if ( ! $mailchimp_lists = get_transient( 'ss_wc_mailchimp_list_' . md5( $this->api_key ) ) ) {

			$mailchimp_lists = array();
			$mailchimp       = new MCAPI( $this->api_key );
			$retval          = $mailchimp->lists();

			if ( $mailchimp->errorCode ) {

				echo '<div class="error"><p>' . sprintf( __( 'Unable to load lists() from MailChimp: (%s) %s', 'ss_wc_mailchimp' ), $mailchimp->errorCode, $mailchimp->errorMessage ) . '</p></div>';

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
	 * @param mixed $first_name
	 * @param mixed $last_name
	 * @param mixed $email
	 * @param string $listid (default: 'false')
	 * @return void
	 */
	public function subscribe( $first_name, $last_name, $email, $listid = 'false' ) {

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

		$vars = apply_filters( 'wc_mailchimp_subscribe_vars', $merge_vars );

		$retval = $api->listSubscribe( $listid, $email, $vars, 'html', ( $this->double_optin == 'no' ? false : true ) );

		if ( $api->errorCode && $api->errorCode != 214 ) {
			do_action( 'wc_mailchimp_subscribe', $email );

			// Email admin
			wp_mail( get_option('admin_email'), __( 'Email subscription failed (Mailchimp)', 'ss_wc_mailchimp' ), '(' . $api->errorCode . ') ' . $api->errorMessage );
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

		if ( 'yes' == $this->display_opt_in ) {
			$checkout_fields['order']['ss_wc_mailchimp_opt_in'] = array(
				'type'    => 'checkbox',
				'label'   => esc_attr( $this->opt_in_label ),
				'default' => true,
			);
		}

		return $checkout_fields;
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
}
