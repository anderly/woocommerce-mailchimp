<?php
/**
 * WooCommerce MailChimp Handler
 *
 * @author  Saint Systems
 * @package WooCommerce MailChimp
 * @version 2.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'SS_WC_MailChimp_Handler' ) ) {

	/**
	 * Handler class.
	 *
	 * @class SS_WC_MailChimp_Handler
	 */
	final class SS_WC_MailChimp_Handler {

		/**
		 *
		 *
		 * Plugin singleton instance
		 *
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

			$this->id        = 'mailchimp';
			$this->namespace = 'ss_wc_' . $this->id;
			$this->label     = __( 'MailChimp', 'woocommerce-mailchimp' );
			$this->sswcmc    = SSWCMC();
			$this->register_hooks();

		} //end function __construct

		/**
		 * Get the instance.
		 *
		 * @return SS_WC_MailChimp_Handler
		 */
		public static function get_instance() {

			if ( empty( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;

		}

		/**
		 * Register plugin hooks
		 *
		 * @access public
		 * @return void
		 */
		public function register_hooks() {

			// We would use the 'woocommerce_new_order' action but first name, last name and email address (order meta) is not yet available,
			// so instead we use the 'woocommerce_checkout_update_order_meta' action hook which fires after the checkout process on the "thank you" page.
			add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'order_status_changed' ), 1000, 1 );

			// Hook into woocommerce order status changed hook to handle the desired subscription event trigger.
			add_action( 'woocommerce_order_status_changed', array( $this, 'order_status_changed' ), 10, 3 );

			$opt_in_checkbox_display_location = $this->sswcmc->opt_in_checkbox_display_location();

			// Maybe add an "opt-in" field to the checkout.
			$opt_in_checkbox_display_location = ! empty( $opt_in_checkbox_display_location ) ? $opt_in_checkbox_display_location : 'woocommerce_review_order_before_submit';

			// Old opt-in checkbox display locations.
			$old_opt_in_checkbox_display_locations = array(
				'billing' => 'woocommerce_after_checkout_billing_form',
				'order'   => 'woocommerce_review_order_before_submit',
			);

			// Map old billing/order checkbox display locations to new format.
			if ( array_key_exists( $opt_in_checkbox_display_location, $old_opt_in_checkbox_display_locations ) ) {
				$opt_in_checkbox_display_location = $old_opt_in_checkbox_display_locations[ $opt_in_checkbox_display_location ];
			}

			add_action( $opt_in_checkbox_display_location, array( $this, 'maybe_add_checkout_fields' ) );

			// Maybe save the "opt-in" field on the checkout.
			add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'maybe_save_checkout_fields' ) );

			add_action( 'wp_ajax_ss_wc_mailchimp_get_account', array( $this, 'ajax_get_account' ) );

			add_action( 'wp_ajax_ss_wc_mailchimp_get_lists', array( $this, 'ajax_get_lists' ) );

			add_action( 'wp_ajax_ss_wc_mailchimp_get_interest_groups', array( $this, 'ajax_get_interest_groups' ) );

			add_action( 'wp_ajax_ss_wc_mailchimp_get_tags', array( $this, 'ajax_get_tags' ) );

			add_action( 'wp_ajax_ss_wc_mailchimp_get_merge_fields', array( $this, 'ajax_get_merge_fields' ) );

			add_action( 'queue_ss_wc_mailchimp_maybe_subscribe', array( $this, 'maybe_subscribe' ), 10, 6 );

		} //end function ensure_tab

		/**
		 * Order status changed function.
		 *
		 * @access public
		 * @param  string $id         The order id.
		 * @param  string $status     The current status.
		 * @param  string $new_status The new status.
		 * @return void
		 */
		public function order_status_changed( $id, $status = 'new', $new_status = 'pending' ) {
			if ( $this->sswcmc->is_valid() && $new_status === $this->sswcmc->occurs() ) {
				// Get WC .
				$order = $this->wc_get_order( $id );

				// Get the ss_wc_mailchimp_opt_in value from the post meta ("order_custom_fields" was removed with WooCommerce 2.1).
				$subscribe_customer = get_post_meta( $id, 'ss_wc_mailchimp_opt_in', true );

				$order_id                 = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;
				$order_billing_email      = method_exists( $order, 'get_billing_email' ) ? $order->get_billing_email() : $order->billing_email;
				$order_billing_first_name = method_exists( $order, 'get_billing_first_name' ) ? $order->get_billing_first_name() : $order->billing_first_name;
				$order_billing_last_name  = method_exists( $order, 'get_billing_last_name' ) ? $order->get_billing_last_name() : $order->billing_last_name;

				$list_id = $this->sswcmc->get_list();

				$this->log( sprintf( __( '%1$s(): Queueing maybe subscribe ($subscribe_customer: %2$s) for customer (%3$s) to list %4$s for order (%5$s)', 'woocommerce-mailchimp' ), __METHOD__, $subscribe_customer, $order_billing_email, $list_id, $order_id ) );

				// Queue the subscription.
				as_schedule_single_action( time(), 'queue_ss_wc_mailchimp_maybe_subscribe', array( $order_id ), 'sswcmc' );

			}
		}

		/**
		 * Return all lists from MailChimp to be used in select fields
		 *
		 * @access public
		 * @return array
		 */
		public function ajax_get_account() {

			try {

				check_ajax_referer( 'sswcmc_get_account', 'nonce' );

				if ( ! isset( $_POST['data'] ) ) {
					throw new Exception( __( '$_POST[\'data\'] not provided.', 'woocommerce-mailchimp' ) );
				}

				if ( ! isset( $_POST['data']['api_key'] ) || empty( $_POST['data']['api_key'] ) ) {

					throw new Exception( __( 'Please enter an api key.', 'woocommerce-mailchimp' ) );

				}

				$api_key = sanitize_text_field( wp_unslash( $_POST['data']['api_key'] ) );

				$account = $this->sswcmc->mailchimp()->get_account( $api_key );

				$results = $account;

			} catch ( Exception $e ) {

				return $this->to_json( array( 'error' => $e->getMessage() ) );

			}

			return $this->to_json( $results );

		} //end function ajax_get_account

		/**
		 * Return all lists from MailChimp to be used in select fields
		 *
		 * @access public
		 * @return array
		 */
		public function ajax_get_lists() {

			try {

				check_ajax_referer( 'sswcmc_get_lists', 'nonce' );

				if ( ! isset( $_POST['data']['api_key'] ) || empty( $_POST['data']['api_key'] ) ) {

					return $this->to_json( array( '' => __( 'Enter your api key above to see your lists', 'woocommerce-mailchimp' ) ) );

				}

				$api_key = sanitize_text_field( wp_unslash( $_POST['data']['api_key'] ) );

				$lists = $this->sswcmc->mailchimp( $api_key )->get_lists();

				$results = array_merge( array( '' => 'Select a list...' ), $lists );

			} catch ( Exception $e ) {

				return $this->to_json( array( 'error' => $e->getMessage() ) );

			}

			return $this->to_json( $results );

		} //end function ajax_get_lists

		/**
		 * Return interest categories for the passed MailChimp List to be used in select fields
		 *
		 * @access public
		 * @return array
		 */
		public function ajax_get_interest_groups() {

			try {

				check_ajax_referer( 'sswcmc_get_interest_groups', 'nonce' );

				if ( ! isset( $_POST['data']['api_key'] ) || empty( $_POST['data']['api_key'] ) ) {

					return $this->to_json( array( '' => __( 'Enter your api key above to see your lists', 'ss_wc_mailchimp' ) ) );

				}

				if ( ! isset( $_POST['data']['list_id'] ) || empty( $_POST['data']['list_id'] ) ) {

					return $this->to_json( array( '' => __( 'Please select a list from above.', 'ss_wc_mailchimp' ) ) );

				}

				$api_key = sanitize_text_field( wp_unslash( $_POST['data']['api_key'] ) );
				$list_id = sanitize_text_field( wp_unslash( $_POST['data']['list_id'] ) );

				$interest_groups = $this->sswcmc->mailchimp( $api_key )->get_interest_categories_with_interests( $list_id );

				$results = $interest_groups;

			} catch ( Exception $e ) {

				return $this->to_json( array( 'error' => $e->getMessage() ) );

			}

			return $this->to_json( $results );

		} //end function ajax_get_interest_groups

		/**
		 * Return tags for the passed MailChimp List to be used in select fields
		 *
		 * @access public
		 * @return array
		 */
		public function ajax_get_tags() {

			try {

				check_ajax_referer( 'sswcmc_get_tags', 'nonce' );

				if ( ! isset( $_POST['data']['api_key'] ) || empty( $_POST['data']['api_key'] ) ) {

					return $this->to_json( array( '' => __( 'Enter your api key above to see your lists', 'ss_wc_mailchimp' ) ) );

				}

				if ( ! isset( $_POST['data']['list_id'] ) || empty( $_POST['data']['list_id'] ) ) {

					return $this->to_json( array( '' => __( 'Please select a list from above.', 'ss_wc_mailchimp' ) ) );

				}

				$api_key = sanitize_text_field( wp_unslash( $_POST['data']['api_key'] ) );
				$list_id = sanitize_text_field( wp_unslash( $_POST['data']['list_id'] ) );

				$tags = $this->sswcmc->mailchimp( $api_key )->get_tags( $list_id );

				$results = $tags;

			} catch ( Exception $e ) {

				return $this->to_json( array( 'error' => $e->getMessage() ) );

			}

			return $this->to_json( $results );

		} //end function ajax_get_tags

		/**
		 * Return merge fields (a.k.a. merge tags) for the passed MailChimp List
		 *
		 * @access public
		 * @return array
		 */
		public function ajax_get_merge_fields() {

			try {

				check_ajax_referer( 'sswcmc_get_merge_fields', 'nonce' );

				if ( ! isset( $_POST['data']['api_key'] ) || empty( $_POST['data']['api_key'] ) ) {

					return $this->to_json( array( '' => __( 'Please enter your api key above.', 'ss_wc_mailchimp' ) ) );

				}

				if ( ! isset( $_POST['data']['list_id'] ) || empty( $_POST['data']['list_id'] ) ) {

					return $this->to_json( array( '' => __( 'Please select a list from above.', 'ss_wc_mailchimp' ) ) );

				}

				$api_key = sanitize_text_field( wp_unslash( $_POST['data']['api_key'] ) );
				$list_id = sanitize_text_field( wp_unslash( $_POST['data']['list_id'] ) );

				$merge_fields = $this->sswcmc->mailchimp( $api_key )->get_merge_fields( $list_id );

				$results = $merge_fields;

			} catch ( Exception $e ) {

				return $this->to_json( array( 'error' => $e->getMessage() ) );

			}

			return $this->to_json( $results );

		} //end function ajax_get_merge_fields

		/**
		 * Send the Json back to the client.
		 *
		 * @param mixed $response The response to send.
		 */
		private function to_json( $response ) {

			// Commented out due to json_encode not preserving quotes around MailChimp ids
			// header('Content-Type: application/json');
			echo wp_json_encode( $response );
			exit();
			//wp_send_json( $response, '200' );

		} //end function to_json

		/**
		 * WooCommerce 2.2 support for wc_get_order
		 *
		 * @since 1.2.1
		 *
		 * @access private
		 * @param int $order_id The order id.
		 *
		 * @return WC_Order The order.
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
		 *
		 * @param string $message The message.
		 * @param string $type    The message type.
		 * @return string Error
		 */
		private function get_message( $message, $type = 'error' ) {
			ob_start();

			?>
			<div class="<?php echo esc_attr( $type ); ?>">
				<p><?php echo esc_html( $message ); ?></p>
			</div>
			<?php
			return ob_get_clean();
		}

		/**
		 * Subscribe function.
		 *
		 * @access public
		 * @param int $order_id The order id.
		 * @return void
		 */
		public function maybe_subscribe( $order_id ) {

			// Get the ss_wc_mailchimp_opt_in value from the post meta ("order_custom_fields" was removed with WooCommerce 2.1).
			$subscribe_customer = get_post_meta( $order_id, 'ss_wc_mailchimp_opt_in', true );

			// Get the subscribe options.
			$subscribe_options = $this->sswcmc->get_subscribe_options_for_order( $order_id );

			$email   = $subscribe_options['email'];
			$list_id = $subscribe_options['list_id'];

			$this->log( sprintf( __( '%1$s(): Processing queued maybe_subscribe ($subscribe_customer: %2$s) for customer (%3$s) to list %4$s for order (%5$s)', 'woocommerce-mailchimp' ), __METHOD__, $subscribe_customer, $email, $list_id, $order_id ) );

			if ( ! $email ) {
				return; // Email is required.
			}

			// Allow hooking into interest groups.
			$subscribe_options['interest_groups'] = apply_filters( 'ss_wc_mailchimp_subscribe_interest_groups', $subscribe_options['interest_groups'], $order_id, $email );

			// Allow hooking into tags.
			$subscribe_options['tags'] = apply_filters( 'ss_wc_mailchimp_subscribe_tags', $subscribe_options['tags'], $order_id, $email );

			// Allow hooking into variables.
			$subscribe_options['merge_tags'] = apply_filters( 'ss_wc_mailchimp_subscribe_merge_tags', $subscribe_options['merge_tags'], $order_id, $email );

			// Allow hooking into subscription options.
			$options = apply_filters( 'ss_wc_mailchimp_subscribe_options', $subscribe_options, $order_id );

			// Extract options into variables.
			extract( $options );

			// Log.
			$this->log( sprintf( __( '%1$s(): Maybe subscribing customer ($subscribe_customer: %2$s) to MailChimp: %3$s', 'woocommerce-mailchimp' ), __METHOD__, $subscribe_customer, print_r( $options, true ) ) );

			do_action( 'ss_wc_mailchimp_before_subscribe', $subscribe_customer, $subscribe_options, $order_id );

			// If the 'ss_wc_mailchimp_opt_in' meta value isn't set
			// (because 'display_opt_in' wasn't enabled at the time the order was placed)
			// or the 'ss_wc_mailchimp_opt_in' is yes, subscriber the customer.
			if ( ! empty( $list_id ) && ( ! $subscribe_customer || empty( $subscribe_customer ) || 'yes' === $subscribe_customer ) ) {
				// Call API.
				$api_response = $this->sswcmc->mailchimp()->subscribe( $list_id, $email, $email_type, $merge_tags, $interest_groups, $double_opt_in, $tags );

				// Log api response.
				$this->log( sprintf( __( '%1$s(): MailChimp API response: %2$s', 'woocommerce-mailchimp' ), __METHOD__, print_r( $api_response, true ) ) );

				if ( false === $api_response ) {
					// Format error message.
					$error_response = sprintf( __( '%1$s(): WooCommerce MailChimp subscription failed: %2$s (%3$s)', 'woocommerce-mailchimp' ), __METHOD__, $this->sswcmc->mailchimp()->get_error_message(), $this->sswcmc->mailchimp()->get_error_code() );

					// Log the error response.
					$this->log( $error_response );

					// New hook for failing operations.
					do_action( 'ss_wc_mailchimp_subscription_failed', $email, array(
						'list_id'  => $list_id,
						'order_id' => $order_id,
					) );

					// Email admin.
					$admin_email = get_option( 'admin_email' );
					$admin_email = apply_filters( 'ss_wc_mailchimp_admin_email', $admin_email );
					wp_mail( $admin_email, __( 'WooCommerce Mailchimp subscription failed', 'woocommerce-mailchimp' ), $error_response );
				} else {
					// Hook on success.
					do_action( 'ss_wc_mailchimp_subscription_success', $email, array(
						'list_id'  => $list_id,
						'order_id' => $order_id,
					) );
				}
			}

			do_action( 'ss_wc_mailchimp_after_subscribe', $subscribe_customer, $subscribe_options, $order_id );

		}

		/**
		 * Add the opt-in checkbox to the checkout fields (to be displayed on checkout).
		 *
		 * @since 1.1
		 */
		public function maybe_add_checkout_fields() {

			if ( $this->sswcmc->is_valid() ) {
				if ( $this->sswcmc->display_opt_in() ) {
					do_action( 'ss_wc_mailchimp_before_opt_in_checkbox' );

					echo apply_filters( 'ss_wc_mailchimp_opt_in_checkbox', '<p class="form-row woocommerce-mailchimp-opt-in"><label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox" for="ss_wc_mailchimp_opt_in"><input type="checkbox" name="ss_wc_mailchimp_opt_in" id="ss_wc_mailchimp_opt_in" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" value="yes"' . ( $this->sswcmc->opt_in_checkbox_default_status() === 'checked' ? ' checked="checked"' : '' ) . '/><span class="woocommerce-mailchimp-opt-in-checkbox-text">' . $this->sswcmc->opt_in_label() . '</span></label></p>' . "\n", $this->sswcmc->opt_in_checkbox_default_status(), $this->sswcmc->opt_in_label(), $this->sswcmc->opt_in_checkbox_default_status(), $this->sswcmc->opt_in_label() );

					do_action( 'ss_wc_mailchimp_after_opt_in_checkbox' );
				}
			}
		}

		/**
		 * When the checkout form is submitted, save opt-in value.
		 *
		 * @since 1.1
		 *
		 * @param string $order_id The order id.
		 */
		public function maybe_save_checkout_fields( $order_id ) {
			if ( $this->sswcmc->display_opt_in() ) {
				$opt_in = isset( $_POST['ss_wc_mailchimp_opt_in'] ) ? 'yes' : 'no';

				update_post_meta( $order_id, 'ss_wc_mailchimp_opt_in', $opt_in );
			}
		}

		/**
		 * Helper log function for debugging
		 *
		 * @since 1.2.2
		 *
		 * @param string $message The message.
		 */
		private function log( $message ) {
			if ( is_array( $message ) || is_object( $message ) ) {
				do_action( 'sswcmc_log', print_r( $message, true ) );
			} else {
				do_action( 'sswcmc_log', $message );
			}
		}

	} //end class SS_WC_MailChimp_Handler

} //end if ( ! class_exists( 'SS_WC_MailChimp_Handler' ) )
