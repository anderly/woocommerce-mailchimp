<?php
/**
 * WooCommerce MailChimp Handler
 *
 * @author 		Saint Systems
 * @package     WooCommerce MailChimp
 * @version		2.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'SS_WC_MailChimp_Handler' ) ) {

	/**
	 * @class SS_WC_MailChimp_Handler
	 */
	class SS_WC_MailChimp_Handler {

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
				$subscribe_customer = get_post_meta( $id, $this->namespace_prefixed( 'opt_in' ), true );

				// If the 'ss_wc_mailchimp_opt_in' meta value isn't set 
				// (because 'display_opt_in' wasn't enabled at the time the order was placed) 
				// or the 'ss_wc_mailchimp_opt_in' is yes, subscriber the customer
				if ( ! $subscribe_customer || empty( $subscribe_customer ) || 'yes' === $subscribe_customer ) {
					// log
					$this->log( sprintf( __( __METHOD__ . '(): Subscribing customer (%s) to list %s', $this->namespace ), $order->billing_email, $this->list() ) );

					// subscribe
					$this->subscribe( $order->id, $order->billing_first_name, $order->billing_last_name, $order->billing_email, $this->list() );
				}
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

			add_action( 'wp_ajax_ss_wc_mailchimp_get_lists', array( $this, 'ajax_get_lists' ) );
			add_action( 'wp_ajax_ss_wc_mailchimp_get_interest_groups', array( $this, 'ajax_get_interest_groups' ) );

		} //end function ensure_tab

		/**
		 * Return all lists from MailChimp to be used in select fields
		 * 
		 * @access public
		 * @return array
		 */
		public function ajax_get_lists() {

			try {

				if ( !$_POST['data']['api_key'] || empty( $_POST['data']['api_key'] ) ) {

					return $this->toJSON( array( '' => __( 'Enter your api key above to see your lists', $this->namespace ) ) );

				}

				$api_key = $_POST['data']['api_key'];

				$lists = $this->api( $api_key )->get_lists();

				$results = array_merge( array('' => 'Select a list...'), $lists );

			}
			catch ( Exception $e ) {

				return $this->toJSON( array( 'error' => $e->getMessage() ) );

			}

			return $this->toJSON( $results );

		} //end function ajax_get_lists

		/**
		 * Return interest categories for the passed MailChimp List to be used in select fields
		 * 
		 * @access public
		 * @return array
		 */
		public function ajax_get_interest_groups() {

			try {

				if ( !$_POST['data']['api_key'] || empty( $_POST['data']['api_key'] ) ) {

					return $this->toJSON( array( '' => __( 'Enter your api key above to see your lists', 'ss_wc_mailchimp' ) ) );

				}

				if ( !$_POST['data']['list_id'] || empty( $_POST['data']['list_id'] ) ) {

					return $this->toJSON( array( '' => __( 'Please select a list from above.', 'ss_wc_mailchimp' ) ) );

				}

				$api_key = $_POST['data']['api_key'];

				$list_id = $_POST['data']['list_id'];

				$interest_groups = $this->api( $api_key )->get_interest_categories_with_interests( $list_id );

				$results = $interest_groups;

			}
			catch ( Exception $e ) {

				return $this->toJSON( array( 'error' => $e->getMessage() ) );

			}

			return $this->toJSON( $results );

		} //end function ajax_get_interest_groups

		private function toJSON( $response ) {

			// Commented out due to json_encode not preserving quotes around MailChimp ids
			// header('Content-Type: application/json');
			echo json_encode( $response );
			exit();

		} //end function toJSON

		private function namespace_prefixed( $suffix ) {
			return $this->namespace . '_' . $suffix;
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
		public function api( $api_key = null ) {
			// if ( is_null( self::$api_instance ) ) {
			// 	if ( ! $this->has_api_key() && empty( $api_key ) ) {
			// 		return false;
			// 	}
			// 	require_once( 'class-ss-wc-mailchimp-api.php' );
			// 	self::$api_instance = new SS_WC_MailChimp_API( ( $api_key ? $api_key : $this->api_key() ), $this->debug_enabled() );
			// }
			// return self::$api_instance;
			if ( ! is_null( $api_key ) ) {
				global $ss_wc_mailchimp;

				// $ss_wc_mailchimp['api'] = new SS_WC_MailChimp( $api_key , $this->debug_enabled() );
				$ss_wc_mailchimp['api'] = ss_wc_mailchimp_get_api( $api_key , $this->debug_enabled() );

				delete_transient( 'sswcmc_lists' );
			}
			return ss_wc_mailchimp('api');
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
				$interest_groups = apply_filters( $this->namespace_prefixed( 'subscribe_interest_groups' ), $interest_groups, $order_id, $email );
			}

			// Allow hooking into variables
			$merge_tags = apply_filters( $this->namespace_prefixed( 'subscribe_merge_tags' ), $merge_tags, $order_id, $email );

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
			$options = apply_filters( $this->namespace_prefixed( 'subscribe_options' ), $subscribe_options, $order_id  );

			// Extract options into variables
			extract( $options );

			// Log
			$this->log( sprintf( __( __METHOD__ . '(): Subscribing customer to MailChimp: %s', $this->namespace ), print_r( $options, true ) ) );

			// Call API
			$api_response = $this->api()->subscribe( $list_id, $email, $email_type, $merge_tags, $interest_groups, $double_optin );

			// Log api response
			$this->log( sprintf( __( __METHOD__ . '(): MailChimp API response: %s', $this->namespace ), print_r( $api_response, true ) ) );

			if ( $api_response === false ) {
				// Format error message
				$error_response = sprintf( __( __METHOD__ . '(): WooCommerce MailChimp subscription failed: %s (%s)', $this->namespace ), $this->api()->get_error_message(), $this->api()->get_error_code() );

				// Log
				$this->log( $error_response );

				// New hook for failing operations
				do_action( $this->namespace_prefixed( 'subscription_failed' ), $email, array( 'list_id' => $list_id, 'order_id' => $order_id ) );

				// Email admin
				wp_mail( get_option( 'admin_email' ), __( 'WooCommerce MailChimp subscription failed', $this->namespace ), $error_response );
			} else {
				// Hook on success
				do_action( $this->namespace_prefixed( 'subscription_success' ), $email, array( 'list_id' => $list_id, 'order_id' => $order_id ) );
			}
		}

		/**
		 * Add the opt-in checkbox to the checkout fields (to be displayed on checkout).
		 *
		 * @since 1.1
		 */
		function maybe_add_checkout_fields() {
			if ( $this->is_valid() ) {
				if ( $this->display_opt_in() ) {
					do_action( $this->namespace_prefixed( 'before_opt_in_checkbox' ) );
					echo apply_filters( $this->namespace_prefixed( 'opt_in_checkbox' ), '<p class="form-row woocommerce-mailchimp-opt-in"><label for="ss_wc_mailchimp_opt_in"><input type="checkbox" name="ss_wc_mailchimp_opt_in" id="ss_wc_mailchimp_opt_in" value="yes"' . ($this->opt_in_checkbox_default_status() == 'checked' ? ' checked="checked"' : '') . '/> ' . esc_html( $this->opt_in_label() ) . '</label></p>' . "\n", $this->opt_in_checkbox_default_status(), $this->opt_in_label() );
					do_action( $this->namespace_prefixed( 'after_opt_in_checkbox' ) );
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
				$opt_in = isset( $_POST[ $this->namespace_prefixed( 'opt_in' ) ] ) ? 'yes' : 'no';

				update_post_meta( $order_id, $this->namespace_prefixed( 'opt_in' ), $opt_in );
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

	} //end class SS_WC_MailChimp_Handler

} //end if ( ! class_exists( 'SS_WC_MailChimp_Handler' ) )