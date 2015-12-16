<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * MailChimp Integration
 *
 * Allows integration with MailChimp
 *
 * @class 		SS_WC_Integration_MailChimp
 * @extends		WC_Integration
 * @version		1.3.7
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

		if ( ! class_exists( 'MCAPI' ) ) {
			include_once( 'api/class-MCAPI.php' );
		}

		$this->id                 = 'mailchimp';
		$this->method_title       = __( 'MailChimp', 'ss_wc_mailchimp' );
		$this->method_description = __( 'MailChimp is a popular email marketing service.', 'ss_wc_mailchimp' );

		// Load the settings.
		$this->init_settings();

		// We need the API key to set up for the lists in the form fields
		$this->api_key   = $this->get_option( 'api_key' );
		$this->mailchimp = new MCAPI( $this->api_key );
		$this->enabled   = $this->get_option( 'enabled' );

		$this->init_form_fields();

		// Get setting values
		$this->occurs                           = $this->get_option( 'occurs' );
		$this->list                             = $this->get_option( 'list' );
		$this->double_optin                     = $this->get_option( 'double_optin' );
		$this->groups                           = $this->get_option( 'groups' );
		$this->display_opt_in                   = $this->get_option( 'display_opt_in' );
		$this->opt_in_label                     = $this->get_option( 'opt_in_label' );
		$this->opt_in_checkbox_default_status   = $this->get_option( 'opt_in_checkbox_default_status' );
		$this->opt_in_checkbox_display_location = $this->get_option( 'opt_in_checkbox_display_location' );
		$this->interest_groupings               = $this->get_option( 'interest_groupings' );

		// Hooks
		add_action( 'admin_notices',                                       array( $this, 'checks' ) );
		add_action( 'woocommerce_update_options_integration',              array( $this, 'process_admin_options') );
		add_action( 'woocommerce_update_options_integration_' . $this->id, array( $this, 'process_admin_options') );

		// We would use the 'woocommerce_new_order' action but first name, last name and email address (order meta) is not yet available,
		// so instead we use the 'woocommerce_checkout_update_order_meta' action hook which fires after the checkout process on the "thank you" page
		add_action( 'woocommerce_checkout_update_order_meta',  array( $this, 'order_status_changed' ), 1000, 1 );

		// hook into woocommerce order status changed hook to handle the desired subscription event trigger
		add_action( 'woocommerce_order_status_changed',        array( $this, 'order_status_changed' ), 10, 3 );

		// Maybe add an "opt-in" field to the checkout
		add_filter( 'woocommerce_checkout_fields',             array( $this, 'maybe_add_checkout_fields' ) );
		add_filter( 'default_checkout_ss_wc_mailchimp_opt_in', array( $this, 'checkbox_default_status' ) );

		// Maybe save the "opt-in" field on the checkout
		add_action( 'woocommerce_checkout_update_order_meta',  array( $this, 'maybe_save_checkout_fields' ) );
	}

	/**
	 * Check if the user has enabled the plugin functionality, but hasn't provided an api key
	 **/
	function checks() {
		// Check required fields
		if ( $this->is_enabled() && ! $this->has_api_key() ) {
			// Show notice
			echo $this->get_message( sprintf( __( 'WooCommerce MailChimp error: Plugin is enabled but no api key provided. Please enter your api key <a href="%s">here</a>.', 'ss_wc_mailchimp' ), WOOCOMMERCE_MAILCHIMP_SETTINGS_URL ) );
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
			// Get WC order
			$order = $this->wc_get_order( $id );

			// get the ss_wc_mailchimp_opt_in value from the post meta. "order_custom_fields" was removed with WooCommerce 2.1
			$subscribe_customer = get_post_meta( $id, 'ss_wc_mailchimp_opt_in', true );

			// log
			self::log( sprintf( __( 'Order Opt-In Value: %s', 'ss_wc_mailchimp' ), var_export( $subscribe_customer, true ) ) );

			// If the 'ss_wc_mailchimp_opt_in' meta value isn't set (because 'display_opt_in' wasn't enabled at the time the order
			// was placed) or the 'ss_wc_mailchimp_opt_in' is yes, subscriber the customer
			if ( ! $subscribe_customer || empty( $subscribe_customer ) || 'yes' == $subscribe_customer ) {
				// log
				self::log( sprintf( __( 'Subscribe customer (%s) to list %s', 'ss_wc_mailchimp' ), $order->billing_email, $this->list ) );

				// subscribe
				$this->subscribe( $order->id, $order->billing_first_name, $order->billing_last_name, $order->billing_email, $this->list );
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
		return $this->is_enabled() && $this->has_api_key() && $this->has_list();
	}

	/**
	 * is_enabled function.
	 *
	 * @access public
	 * @return boolean
	 */
	public function is_enabled() {
		return 'yes' === $this->enabled;
	}

	/**
	 * Initialize Settings Form Fields
	 *
	 * @access public
	 * @return void
	 */
	function init_form_fields() {
		$lists = array();

		if ( is_admin() && ! is_ajax() ) {

			if ( $this->is_enabled() && $this->has_api_key() ) {
				$user_lists = $this->get_lists();

				if ( is_array( $user_lists ) && ! empty( $user_lists ) ) {
					$lists = $user_lists;
				}
			}

			if( $this->has_api_key() ) {
				$mailchimp_lists = array_merge( array( '' => __( 'Select a list...', 'ss_wc_mailchimp' ) ), $lists );
			}
			else {
				$mailchimp_lists = array( '' => __( 'Enter your key and save to see your lists', 'ss_wc_mailchimp' ) );
			}

			$this->form_fields = array(
				'enabled' => array(
					'title'       => __( 'Enable/Disable', 'ss_wc_mailchimp' ),
					'label'       => __( 'Enable MailChimp', 'ss_wc_mailchimp' ),
					'type'        => 'checkbox',
					'description' => '',
					'default'     => 'no'
				),
				'occurs' => array(
					'title'       => __( 'Subscribe Event', 'ss_wc_mailchimp' ),
					'type'        => 'select',
					'description' => __( 'When should customers be subscribed to lists?', 'ss_wc_mailchimp' ),
					'default'     => 'pending',
					'options'     => array(
						'pending'    => __( 'Order Created', 'ss_wc_mailchimp' ),
						'processing' => __( 'Order Processing', 'ss_wc_mailchimp' ),
						'completed'  => __( 'Order Completed', 'ss_wc_mailchimp' ),
					),
				),
				'api_key' => array(
					'title'       => __( 'API Key', 'ss_wc_mailchimp' ),
					'type'        => 'text',
					'description' => __( '<a href="https://us2.admin.mailchimp.com/account/api/" target="_blank">Login to mailchimp</a> to look up your api key.', 'ss_wc_mailchimp' ),
					'default'     => ''
				),
				'list' => array(
					'title'       => __( 'Main List', 'ss_wc_mailchimp' ),
					'type'        => 'select',
					'description' => __( 'All customers will be added to this list.', 'ss_wc_mailchimp' ),
					'default'     => '',
					'options'     => $mailchimp_lists,
				),
				'interest_groupings' => array(
					'title'       => __( 'Group Name', 'ss_wc_mailchimp' ),
					'type'        => 'text',
					'description' => __( 'Optional: Enter the name of the group. Learn more about <a href="http://mailchimp.com/features/groups" target="_blank">Groups</a>', 'ss_wc_mailchimp' ),
					'default'     => '',
				),
				'groups' => array(
					'title'       => __( 'Groups', 'ss_wc_mailchimp' ),
					'type'        => 'text',
					'description' => __( 'Optional: Comma separated list of interest groups to which subscribers should be added.', 'ss_wc_mailchimp' ),
					'default'     => '',
				),
				'double_optin' => array(
					'title'       => __( 'Double Opt-In', 'ss_wc_mailchimp' ),
					'label'       => __( 'Enable Double Opt-In', 'ss_wc_mailchimp' ),
					'type'        => 'checkbox',
					'description' => __( 'If enabled, customers will receive an email prompting them to confirm their subscription to the list above.', 'ss_wc_mailchimp' ),
					'default'     => 'no'
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
					'options'     => array(
						'checked'   => __( 'Checked', 'ss_wc_mailchimp' ),
						'unchecked' => __( 'Unchecked', 'ss_wc_mailchimp' )
					)
				),
				'opt_in_checkbox_display_location' => array(
					'title'       => __( 'Opt-In Checkbox Display Location', 'ss_wc_mailchimp' ),
					'type'        => 'select',
					'description' => __( 'Where to display the opt-in checkbox on the checkout page (under Billing info or Order info).', 'ss_wc_mailchimp' ),
					'default'     => 'billing',
					'options'     => array(
						'billing'   => __( 'Billing', 'ss_wc_mailchimp' ),
						'order'     => __( 'Order', 'ss_wc_mailchimp' )
					)
				)
			);

			$this->wc_enqueue_js("
				jQuery('#woocommerce_mailchimp_display_opt_in').change(function(){
					jQuery('#mainform [id^=woocommerce_mailchimp_opt_in]').closest('tr').hide('fast');

					if ( jQuery(this).prop('checked') == true ) {
						jQuery('#mainform [id^=woocommerce_mailchimp_opt_in]').closest('tr').show('fast');
					}
					else {
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
	 * get_lists function.
	 *
	 * @access public
	 * @return void
	 */
	public function get_lists() {
		$mailchimp_lists = get_transient( 'sswcmclist_' . md5( $this->api_key ) );

		if ( ! $mailchimp_lists ) {

			$mailchimp_lists = array();
			$retval          = $this->mailchimp->lists();

			if ( $this->mailchimp->errorCode ) {

				add_action( 'admin_notices',         array( $this, 'mailchimp_api_error_msg' ) );
				add_action( 'network_admin_notices', array( $this, 'mailchimp_api_error_msg' ) );

				return false;
			}
			else {
				foreach ( $retval['data'] as $list )
					$mailchimp_lists[ $list['id'] ] = $list['name'];

				if ( sizeof( $mailchimp_lists ) > 0 )
					set_transient( 'sswcmclist_' . md5( $this->api_key ), $mailchimp_lists, 60 * 60 * 1 );
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
		echo $this->get_message(
			sprintf( __( 'Unable to load lists from MailChimp: (%s) %s. ', 'ss_wc_mailchimp' ), $this->mailchimp->errorCode, $this->mailchimp->errorMessage ) .
			sprintf( __( 'Please check your %s <a href="%s">settings</a>.', 'ss_wc_mailchimp' ), WOOCOMMERCE_MAILCHIMP_SETTINGS_URL )
		);
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

		$interest_groupings = array();
		$interest_groups    = array();
		$retval             = $this->mailchimp->listInterestGroupings( $listid );

		if ( $this->mailchimp->errorCode ) {
			echo $this->get_message( sprintf( __( 'Unable to load listInterestGroupings() from MailChimp: (%s) %s', 'ss_wc_mailchimp' ), $this->mailchimp->errorCode, $this->mailchimp->errorMessage ) );

			return false;

		}
		else {
			if ( sizeof( $retval ) > 0 ) {
				foreach ( $retval as $interest_grouping ) {
					$interest_groupings[ $interest_grouping['id'] ] = $interest_grouping['name'];

					foreach ( $interest_grouping['groups'] as $group ) {
						$interest_groups[ $group['bit'] ] = $group['name'];
					}
				}

				if ( sizeof( $interest_groupings ) > 0 ) {
					// set transients for cache
					set_transient( 'wc_mailchimp_list_' . md5( $this->api_key ) . '_' . $listid, $interest_groupings, 60 * 60 * 1 );
					set_transient( 'wc_mailchimp_list_' . md5( $this->api_key ) . '_' . $listid . '_groups', $interest_groups, 60 * 60 * 1 );

					$this->interest_groupings = $interest_groupings;
					$this->groups[$listid]    = $interest_groups;
				}
			}
		}

		return $interest_groupings;
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

		if ( 'false' == $listid )
			$listid = $this->list;

		$merge_vars = array(
			'FNAME' => $first_name,
			'LNAME' => $last_name
		);

		if ( ! empty( $this->interest_groupings ) && ! empty( $this->groups ) ) {
			$merge_vars['GROUPINGS'] = array(
				array(
					'name'   => $this->interest_groupings,
					'groups' => $this->groups
				)
			);
		}

		// Allow hooking into variables
		$vars              = apply_filters( 'ss_wc_mailchimp_subscribe_merge_vars', $merge_vars, $order_id );

		// Set subscription options
		$subscribe_options = array(
			'listid'            => $listid,
			'email'             => $email,
			'vars'              => $vars,
			'email_type'        => 'html',
			'double_optin'      => $this->double_optin == 'no' ? false : true,
			'update_existing'   => true,
			'replace_interests' => false,
			'send_welcome'      => false
		);

		// Allow hooking into subscription options
		$options           = apply_filters( 'ss_wc_mailchimp_subscribe_options', $subscribe_options );

		// Extract options into variables
		extract( $options );

		// Log
		self::log( sprintf( __( 'Calling MailChimp API listSubscribe method with the following: %s', 'ss_wc_mailchimp' ), print_r( $options, true ) ) );

		// Call API
		$api_response      = $this->mailchimp->listSubscribe( $listid, $email, $vars, $email_type, $double_optin, $update_existing, $replace_interests, $send_welcome );

		// Log api response
		self::log( sprintf( __( 'MailChimp API response: %s', 'ss_wc_mailchimp' ), $api_response ) );

		if ( $this->mailchimp->errorCode && $this->mailchimp->errorCode != 214 ) {
			// Format error message
			$error_response = sprintf( __( 'WooCommerce MailChimp subscription failed: %s (%s)', 'ss_wc_mailchimp' ), $this->mailchimp->errorMessage, $this->mailchimp->errorCode );

			// Log
			self::log( $error_response );

			// Compability to old hook
			do_action( 'ss_wc_mailchimp_subscribed', $email );

			// New hook for failing operations
			do_action( 'ss_wc_mailchimp_subscription_failed', $email, array( 'list_id' => $listid, 'order_id' => $order_id ) );

			// Email admin
			wp_mail( get_option( 'admin_email' ), __( 'WooCommerce MailChimp subscription failed', 'ss_wc_mailchimp' ), $error_response );
		}
		else {
			// Hook on success
			do_action( 'ss_wc_mailchimp_subscription_success', $email, array( 'list_id' => $listid, 'order_id' => $order_id ) );
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
		$display_location = $this->opt_in_checkbox_display_location;

		if ( empty( $display_location ) ) {
			$display_location = 'billing';
		}

		if ( 'yes' == $this->display_opt_in ) {
			$checkout_fields[$display_location]['ss_wc_mailchimp_opt_in'] = array(
				'type'    => 'checkbox',
				'label'   => esc_attr( $this->opt_in_label ),
				'default' => $this->opt_in_checkbox_default_status == 'checked' ? 1 : 0,
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
			$logger = new WC_Logger();

			if ( is_array( $message ) || is_object( $message ) ) {
				$logger->add( 'mailchimp', print_r( $message, true ) );
			}
			else {
				$logger->add( 'mailchimp', $message );
			}
		}
	}

}