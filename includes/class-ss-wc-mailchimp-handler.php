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
	final class SS_WC_MailChimp_Handler {

		/**
		 * Plugin singleton instance
		 * @var SS_WC_MailChimp_Handler
		 */
		private static $instance = null;

		/**
		 * Constructor
		 *
		 * @access public
		 * @return void
		 */
		public function __construct() {

			$this->id         = 'mailchimp';
			$this->namespace  = 'ss_wc_' . $this->id;
			$this->label      = __( 'MailChimp', 'woocommerce-mailchimp' );
			$this->sswcmc     = SSWCMC();
			$this->register_hooks();

		} //end function __construct

		/**
		 * @return SS_WC_MailChimp_Handler
		 */
		public static function get_instance() {

			if ( empty( self::$instance ) ) {
				self::$instance = new self;
			}

			return self::$instance;

		}

		/**
		 * order_status_changed function.
		 *
		 * @access public
		 * @return void
		 */
		public function order_status_changed( $id, $status = 'new', $new_status = 'pending' ) {
			if ( $this->sswcmc->is_valid() && $new_status === $this->sswcmc->occurs() ) {
				// Get WC order
				$order = $this->wc_get_order( $id );

				// get the ss_wc_mailchimp_opt_in value from the post meta. "order_custom_fields" was removed with WooCommerce 2.1
				$subscribe_customer = get_post_meta( $id, $this->namespace_prefixed( 'opt_in' ), true );

				$order_id = method_exists($order, 'get_id') ? $order->get_id(): $order->id;
				$order_billing_email = method_exists($order, 'get_billing_email') ? $order->get_billing_email(): $order->billing_email;
				$order_billing_first_name = method_exists($order, 'get_billing_first_name') ? $order->get_billing_first_name(): $order->billing_first_name;
				$order_billing_last_name = method_exists($order, 'get_billing_last_name') ? $order->get_billing_last_name(): $order->billing_last_name;

				// If the 'ss_wc_mailchimp_opt_in' meta value isn't set 
				// (because 'display_opt_in' wasn't enabled at the time the order was placed) 
				// or the 'ss_wc_mailchimp_opt_in' is yes, subscriber the customer
				if ( ! $subscribe_customer || empty( $subscribe_customer ) || 'yes' === $subscribe_customer ) {
					// log
					$this->log( sprintf( __( __METHOD__ . '(): Subscribing customer (%s) to list %s', 'woocommerce-mailchimp' ), $order_billing_email , $this->sswcmc->get_list() ) );

					// subscribe
					$this->subscribe( $order_id, $order_billing_first_name, $order_billing_last_name, $order_billing_email , $this->sswcmc->get_list() );
				}
			}
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

			$opt_in_checkbox_display_location = $this->sswcmc->opt_in_checkbox_display_location();

			// Maybe add an "opt-in" field to the checkout
			$opt_in_checkbox_display_location = !empty( $opt_in_checkbox_display_location ) ? $opt_in_checkbox_display_location : 'woocommerce_review_order_before_submit';

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

			// Maybe save the "opt-in" field on the checkout
			add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'maybe_save_checkout_fields' ) );

			add_action( 'wp_ajax_ss_wc_mailchimp_get_account', array( $this, 'ajax_get_account' ) );

			add_action( 'wp_ajax_ss_wc_mailchimp_get_lists', array( $this, 'ajax_get_lists' ) );

			add_action( 'wp_ajax_ss_wc_mailchimp_get_interest_groups', array( $this, 'ajax_get_interest_groups' ) );

			add_action( 'wp_ajax_ss_wc_mailchimp_get_merge_fields', array( $this, 'ajax_get_merge_fields' ) );

		} //end function ensure_tab

		/**
		 * Return all lists from MailChimp to be used in select fields
		 * 
		 * @access public
		 * @return array
		 */
		public function ajax_get_account() {

			try {

				if ( !$_POST['data']['api_key'] || empty( $_POST['data']['api_key'] ) ) {

					throw new Exception( __( 'Please enter an api key.', 'woocommerce-mailchimp' ) );

				}

				$api_key = $_POST['data']['api_key'];

				$account = $this->sswcmc->mailchimp()->get_account( $api_key );

				$results = $account;

			}
			catch ( Exception $e ) {

				return $this->toJSON( array( 'error' => $e->getMessage() ) );

			}

			return $this->toJSON( $results );

		} //end function ajax_get_account

		/**
		 * Return all lists from MailChimp to be used in select fields
		 * 
		 * @access public
		 * @return array
		 */
		public function ajax_get_lists() {

			try {

				if ( !$_POST['data']['api_key'] || empty( $_POST['data']['api_key'] ) ) {

					return $this->toJSON( array( '' => __( 'Enter your api key above to see your lists', 'woocommerce-mailchimp' ) ) );

				}

				$api_key = $_POST['data']['api_key'];

				$lists = $this->sswcmc->mailchimp( $api_key )->get_lists();

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

				$interest_groups = $this->sswcmc->mailchimp( $api_key )->get_interest_categories_with_interests( $list_id );

				$results = $interest_groups;

			}
			catch ( Exception $e ) {

				return $this->toJSON( array( 'error' => $e->getMessage() ) );

			}

			return $this->toJSON( $results );

		} //end function ajax_get_interest_groups

		/**
		 * Return merge fields (a.k.a. merge tags) for the passed MailChimp List
		 * 
		 * @access public
		 * @return array
		 */
		public function ajax_get_merge_fields() {

			try {

				if ( !$_POST['data']['api_key'] || empty( $_POST['data']['api_key'] ) ) {

					return $this->toJSON( array( '' => __( 'Please enter your api key above.', 'ss_wc_mailchimp' ) ) );

				}

				if ( !$_POST['data']['list_id'] || empty( $_POST['data']['list_id'] ) ) {

					return $this->toJSON( array( '' => __( 'Please select a list from above.', 'ss_wc_mailchimp' ) ) );

				}

				$api_key = $_POST['data']['api_key'];
				$list_id = $_POST['data']['list_id'];

				$merge_fields = $this->sswcmc->mailchimp( $api_key )->get_merge_fields( $list_id );

				$results = $merge_fields;

			}
			catch ( Exception $e ) {

				return $this->toJSON( array( 'error' => $e->getMessage() ) );

			}

			return $this->toJSON( $results );

		} //end function ajax_get_merge_fields

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
				$list_id = $this->sswcmc->get_list();
			}

			$merge_tags = array(
				'FNAME' => $first_name,
				'LNAME' => $last_name
			);

			$interest_groups = $this->sswcmc->interest_groups();

			if ( ! empty(  $interest_groups) ) {
				$interest_groups = array_fill_keys( $this->sswcmc->interest_groups(), true );

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
				'double_opt_in'     => $this->sswcmc->double_opt_in(),
			);

			// Allow hooking into subscription options
			$options = apply_filters( $this->namespace_prefixed( 'subscribe_options' ), $subscribe_options, $order_id  );

			// Extract options into variables
			extract( $options );

			// Log
			$this->log( sprintf( __( __METHOD__ . '(): Subscribing customer to MailChimp: %s', 'woocommerce-mailchimp' ), print_r( $options, true ) ) );

			do_action( $this->namespace_prefixed( 'before_subscribe' ), $subscribe_options, $order_id );

			// Call API
			$api_response = $this->sswcmc->mailchimp()->subscribe( $list_id, $email, $email_type, $merge_tags, $interest_groups, $double_opt_in );

			do_action( $this->namespace_prefixed( 'after_subscribe' ), $subscribe_options, $order_id );

			// Log api response
			$this->log( sprintf( __( __METHOD__ . '(): MailChimp API response: %s', 'woocommerce-mailchimp' ), print_r( $api_response, true ) ) );

			if ( $api_response === false ) {
				// Format error message
				$error_response = sprintf( __( __METHOD__ . '(): WooCommerce MailChimp subscription failed: %s (%s)', 'woocommerce-mailchimp' ), $this->sswcmc->mailchimp()->get_error_message(), $this->sswcmc->mailchimp()->get_error_code() );

				// Log
				$this->log( $error_response );

				// New hook for failing operations
				do_action( $this->namespace_prefixed( 'subscription_failed' ), $email, array( 'list_id' => $list_id, 'order_id' => $order_id ) );

				// Email admin
				$admin_email = get_option( 'admin_email' );
				$admin_email = apply_filters( $this->namespace_prefixed( 'admin_email'), $admin_email );
				wp_mail( $admin_email, __( 'WooCommerce MailChimp subscription failed', 'woocommerce-mailchimp' ), $error_response );
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
			if ( $this->sswcmc->is_valid() ) {
				if ( $this->sswcmc->display_opt_in() ) {
					do_action( $this->namespace_prefixed( 'before_opt_in_checkbox' ) );
					echo apply_filters( $this->namespace_prefixed( 'opt_in_checkbox' ), '<p class="form-row woocommerce-mailchimp-opt-in"><label for="ss_wc_mailchimp_opt_in"><input type="checkbox" name="ss_wc_mailchimp_opt_in" id="ss_wc_mailchimp_opt_in" value="yes"' . ($this->sswcmc->opt_in_checkbox_default_status() == 'checked' ? ' checked="checked"' : '') . '/> ' . esc_html( $this->sswcmc->opt_in_label() ) . '</label></p>' . "\n", $this->sswcmc->opt_in_checkbox_default_status(), $this->sswcmc->opt_in_label() );
					do_action( $this->namespace_prefixed( 'after_opt_in_checkbox' ) );
				}
			}
		}

		/**
		 * When the checkout form is submitted, save opt-in value.
		 *
		 * @version 1.1
		 */
		function maybe_save_checkout_fields( $order_id ) {
			if ( $this->sswcmc->display_opt_in() ) {
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
			if ( $this->sswcmc->debug_enabled() ) {
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