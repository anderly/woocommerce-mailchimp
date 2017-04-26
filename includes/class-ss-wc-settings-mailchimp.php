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

		private static $instance;

		/**
		 * Singleton instance
		 *
		 * @return SS_WC_Settings_MailChimp   SS_WC_Settings_MailChimp object
		 */
		public static function get_instance() {

			if ( empty( self::$instance ) ) {
				self::$instance = new self;
			}

			return self::$instance;
		}

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
		 * get_list function.
		 *
		 * @access public
		 * @return string MailChimp list ID
		 */
		public function get_list() {
			return $this->get_option( 'list' );
		}

		/**
		 * double_opt_in function.
		 *
		 * @access public
		 * @return boolean
		 */
		public function double_opt_in() {
			return 'yes' === $this->get_option( 'double_opt_in' );
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
			if ( $this->get_list() ) {
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
			$api_key = $this->api_key();
			return !empty( $api_key );
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
				echo $this->get_message( sprintf( __( 'WooCommerce MailChimp error: Plugin is enabled but no api key provided. Please enter your api key <a href="%s">here</a>.', 'woocommerce-mailchimp' ), WOOCOMMERCE_MAILCHIMP_SETTINGS_URL )
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
			add_action( 'woocommerce_admin_field_sysinfo', array( $this, 'sysinfo_field' ), 10, 1 );

		} //end function ensure_tab

		// /**
		//  * Get sections
		//  *
		//  * @return array
		//  */
		public function get_sections() {

			$sections = array();

			$sections[''] = __( 'General', 'woocommerce-mailchimp' );

			if ( $this->has_api_key() ) {
				$sections['troubleshooting'] = __( 'Troubleshooting', 'woocommerce-mailchimp' );
			}

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

			do_action( 'ss_wc_mailchimp_after_settings_enqueue_js' );

		}

		/**
		 * Save settings
		 */
		public function save() {
			global $current_section;

			$settings = $this->get_settings( $current_section );

			WC_Admin_Settings::save_fields( $settings );

			if ( !isset( $_POST[ 'ss_wc_mailchimp_api_key' ] ) || empty( $_POST[ 'ss_wc_mailchimp_api_key' ] )  ) {
				delete_transient( 'sswcmc_lists' );
			}

			$sswcmc = SSWCMC();
			// Trigger reload of plugin settings
			$settings = $sswcmc->settings( true );
			
		}

		/**
		 * Get settings array
		 *
		 * @return array
		 */
		public function get_settings( $current_section = '' ) {

			$settings = array();

			if ( '' === $current_section ) {

				$settings = array(
					array(
						'title' => __( 'MailChimp', 'woocommerce-mailchimp' ),
						'type' 	=> 'title',
						'desc' 	=> __( 'Enter your MailChimp settings below to control how WooCommerce integrates with your MailChimp account.', 'woocommerce-mailchimp' ),
						'id' 	=> 'general_options',
					),
				);

				$settings[] = array(
						'id'          => $this->namespace_prefixed( 'api_key' ),
						'title'       => __( 'API Key', 'woocommerce-mailchimp' ),
						'type'        => 'text',
						'desc' => sprintf( __( '%sLogin to MailChimp%s to look up your api key.', 'woocommerce-mailchimp' ), '<br/><a href="https://admin.mailchimp.com/account/api/" target="_blank">', '</a>'
						),
						'placeholder' => __( 'Paste your MailChimp API key here', 'woocommerce-mailchimp' ),
						'default'     => '',
						'css'         => 'min-width:350px;',
						'desc_tip'    => 'Your API Key is required for the plugin to communicate with your MailChimp account.',
					);

				$settings[] = array(
						'id'          => $this->namespace_prefixed( 'enabled' ),
						'title'       => __( 'Enable/Disable', 'woocommerce-mailchimp' ),
						'label'       => __( 'Enable MailChimp Integration', 'woocommerce-mailchimp' ),
						'type'        => 'checkbox',
						'desc' => __( 'Enable/disable the plugin functionality.', 'woocommerce-mailchimp' ),
						'default'     => 'yes',
					);

				$mailchimp_lists = $this->get_lists();

				if ( $this->has_api_key() && $this->has_list() ) {
					$interest_groups = $this->get_interest_groups();
				} else {
					$interest_groups = array();
				}

				$settings[] = array(
						'id'          => $this->namespace_prefixed( 'list' ),
						'title'       => __( 'Main List', 'woocommerce-mailchimp' ),
						'type'        => 'select',
						'desc'        => __( 'All customers will be added to this list.', 'woocommerce-mailchimp' ),
						'default'     => '',
						'options'     => $mailchimp_lists,
						'class'       => 'wc-enhanced-select',
						'css'         => 'min-width: 350px;',
						'desc_tip'    =>  true,
					);

				$settings[] = array(
						'id'          => $this->namespace_prefixed( 'interest_groups' ),
						'title'       => __( 'Interest Groups', 'woocommerce-mailchimp' ),
						'type'        => 'multiselect',
						'desc' => __( 'Optional: Interest groups to assign to subscribers.', 'woocommerce-mailchimp' ),
						'default'     => '',
						'options'     => $interest_groups,
						'class'       => 'wc-enhanced-select',
						'custom_attributes' => array( 
							'placeholder' => __( 'Select interest groups...', 'woocommerce-mailchimp' ),
						),
						'css'         => 'min-width: 350px;',
						'desc_tip'    =>  true,
					);

				$settings = apply_filters( $this->namespace_prefixed( 'settings_general_after_interest_groups' ), $settings );

				$settings[] = array(
						'id'          => $this->namespace_prefixed( 'occurs' ),
						'title'       => __( 'Subscribe Event', 'woocommerce-mailchimp' ),
						'type'        => 'select',
						'desc'        => __( 'Choose whether to subscribe customers as soon as an order is placed or after the order is processing or completed.', 'woocommerce-mailchimp' ),
						'class'       => 'wc-enhanced-select',
						'default'     => 'pending',
						'options'     => array(
							'pending'    => __( 'Order Created', 'woocommerce-mailchimp' ),
							'processing' => __( 'Order Processing', 'woocommerce-mailchimp' ),
							'completed'  => __( 'Order Completed', 'woocommerce-mailchimp' ),
						),
						'desc_tip'    =>  true,
					);
				
				$settings[] = array(
						'id'          => $this->namespace_prefixed( 'double_opt_in' ),
						'title'       => __( 'Double Opt-In', 'woocommerce-mailchimp' ),
						'desc'        => __( 'Enable Double Opt-In', 'woocommerce-mailchimp' ),
						'type'        => 'checkbox',
						'default'     => 'no',
						'desc_tip'    => __( 'If enabled, customers will receive an email prompting them to confirm their subscription to the list above.', 'woocommerce-mailchimp' ),
					);

				$settings[] = array(
					'id'       => $this->namespace_prefixed( 'display_opt_in' ),
					'title'    => __( 'Subscribe Customers', 'woocommerce-mailchimp' ),
					'desc'     => __( '<p>Choose <strong>Ask for permission</strong> to show an "Opt-in" checkbox during checkout. Customers will only be subscribed to the list above if they opt-in. <p>Choose <strong>Automatically</strong> to subscribe customers silently upon checkout. Caution, this is without the customer\'s consent.</p>', 'woocommerce-mailchimp' ),
					'type'     => 'select',
					'css'      => 'min-width:300px;',
					'class'    => 'wc-enhanced-select',
					'desc_tip' => true,
					'default'  => 'yes',
					'options'  => array(
						// '0' => __( 'Disabled', 'woocommerce-mailchimp' ),
						'yes' => __( 'Ask for permission', 'woocommerce-mailchimp' ),
						'no' => __( 'Automatically', 'woocommerce-mailchimp' ),
					)
				);

				$settings[] = array(
						'id'          => $this->namespace_prefixed( 'opt_in_label' ),
						'title'       => __( 'Opt-In Field Label', 'woocommerce-mailchimp' ),
						'type'        => 'text',
						'desc'        => __( 'Optional: customize the label displayed next to the opt-in checkbox.', 'woocommerce-mailchimp' ),
						'default'     => __( 'Subscribe to our newsletter', 'woocommerce-mailchimp' ),
						'css'         => 'min-width:350px;',
						'desc_tip'    =>  true,
					);

				$settings[] = array(
						'id'          => $this->namespace_prefixed( 'opt_in_checkbox_default_status' ),
						'title'       => __( 'Opt-In Checkbox Default', 'woocommerce-mailchimp' ),
						'type'        => 'select',
						'desc'        => __( 'The default state of the opt-in checkbox.', 'woocommerce-mailchimp' ),
						'class'       => 'wc-enhanced-select',
						'default'     => 'checked',
						'options'     => array(
							'checked'   => __( 'Checked', 'woocommerce-mailchimp' ),
							'unchecked' => __( 'Unchecked', 'woocommerce-mailchimp' )
						),
						'desc_tip'    =>  true,
					);

				$settings[] = array(
						'id'          => $this->namespace_prefixed( 'opt_in_checkbox_display_location' ),
						'title'       => __( 'Opt-In Checkbox Location', 'woocommerce-mailchimp' ),
						'type'        => 'select',
						'desc'        => __( 'Where to display the opt-in checkbox on the checkout page.', 'woocommerce-mailchimp' ),
						'class'       => 'wc-enhanced-select',
						'default'     => 'woocommerce_review_order_before_submit',
						'options'     => array(
							'woocommerce_checkout_before_customer_details' => __( 'Above customer details', 'woocommerce-mailchimp' ),
							'woocommerce_checkout_after_customer_details' => __( 'Below customer details', 'woocommerce-mailchimp' ),
							'woocommerce_review_order_before_submit' => __( 'Order review above submit', 'woocommerce-mailchimp' ),
							'woocommerce_review_order_after_submit' => __( 'Order review below submit', 'woocommerce-mailchimp' ),
							'woocommerce_review_order_before_order_total' => __( 'Order review above total', 'woocommerce-mailchimp' ),
							'woocommerce_checkout_billing' => __( 'Above billing details', 'woocommerce-mailchimp' ),
							'woocommerce_checkout_shipping' => __( 'Above shipping details', 'woocommerce-mailchimp' ),
							'woocommerce_after_checkout_billing_form' => __( 'Below Checkout billing form', 'woocommerce-mailchimp' ),
							'woocommerce_checkout_before_terms_and_conditions' => __( 'Above Checkout Terms and Conditions', 'woocommerce-mailchimp' ),
							'woocommerce_checkout_after_terms_and_conditions' => __( 'Below Checkout Terms and Conditions', 'woocommerce-mailchimp' ),
						),
						'desc_tip'    =>  true,
					);

				$settings = apply_filters( $this->namespace_prefixed( 'settings_general' ), $settings );

				$settings[] = array( 'type' => 'sectionend', 'id' => 'general_options' );

			} elseif ( 'troubleshooting' === $current_section ) {

				$label = __( 'Enable Logging', 'woocommerce-mailchimp' );

				if ( defined( 'WC_LOG_DIR' ) ) {
					$debug_log_url = add_query_arg( 'tab', 'logs', add_query_arg( 'page', 'wc-status', admin_url( 'admin.php' ) ) );
					$debug_log_key = 'woocommerce-mailchimp-' . sanitize_file_name( wp_hash( 'woocommerce-mailchimp' ) ) . '-log';
					$debug_log_url = add_query_arg( 'log_file', $debug_log_key, $debug_log_url );

					$label .= ' | ' . sprintf( __( '%1$sView Log%2$s', 'woocommerce-mailchimp' ), '<a href="' . esc_url( $debug_log_url ) . '">', '</a>' );
				}

				$settings[] = array(
					'title' => __( 'Troubleshooting', 'woocommerce-mailchimp' ),
					'type' 	=> 'title',
					'desc' 	=> '',
					'id' 	=> 'troubleshooting_settings'
				);

				$settings[]  = array(
						'id'          => $this->namespace_prefixed( 'debug' ),
						'title'       => __( 'Debug Log', 'woocommerce-mailchimp' ),
						'desc'        => $label,
						'type'        => 'checkbox',
						'default'     => 'no',
						'desc_tip'    => __( 'Enable logging MailChimp API calls. Only enable for troubleshooting purposes.', 'woocommerce-mailchimp' ),
					);

				$settings[]  = array(
						'id'          => 'sysinfo',
						'title'       => __( 'System Info', 'woocommerce-mailchimp' ),
						'type'        => 'sysinfo',
						'desc'        => __( 'Copy the information below and send it to us when reporting an issue with the plugin.', 'woocommerce-mailchimp' ) . '<p/>',
						'desc_tip'    => '',
					);

				$settings[] = array( 'type' => 'sectionend', 'id' => 'troubleshooting_settings' );

				$settings = apply_filters( $this->namespace_prefixed( 'settings_troubleshooting' ), $settings );

			}

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
		public function mailchimp( $api_key = null ) {

			$sswcmc = SSWCMC();

			return $sswcmc->mailchimp( $api_key );

		}

		/**
		 * get_lists function.
		 *
		 * @access public
		 * @return void
		 */
		public function get_lists() {

			if ( $this->mailchimp() ) {
				$mailchimp_lists = $this->mailchimp()->get_lists();
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
					'no_lists' => __( 'Oops! No lists in your MailChimp account...', 'woocommerce-mailchimp' ),
				);
				add_action( 'admin_notices', array( $this, 'mailchimp_no_lists_found' ) );
			} else {
				$default = array(
					'' => __( 'Select a list...', 'woocommerce-mailchimp' ),
				);
			}
			$mailchimp_lists = array_merge( $default, $mailchimp_lists );

			return $mailchimp_lists;
			
		}

		/**
		 * get_interest_groups function.
		 *
		 * @access public
		 * @return void
		 */
		public function get_interest_groups() {

			if ( $this->mailchimp() && $this->has_list() ) {
				$interest_groups = $this->mailchimp()->get_interest_categories_with_interests( $this->get_list() );
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
			echo $this->get_message( sprintf( __( 'Oops! There are no lists in your MailChimp account. %sClick here%s to create one.', 'woocommerce-mailchimp' ), '<a href="https://admin.mailchimp.com/lists/new-list/" target="_blank">', '</a>' ) );
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
				sprintf( __( 'Unable to load lists from MailChimp: (%s) %s. ', 'woocommerce-mailchimp' ), $this->mailchimp()->get_error_code(), $this->mailchimp()->get_error_message() ) .
				sprintf( __( 'Please check your Settings %ssettings%s.', 'woocommerce-mailchimp' ), '<a href="' . WOOCOMMERCE_MAILCHIMP_SETTINGS_URL .'">', '</a>' )
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

		public function sysinfo_field( $value ) {

			// $option_value = self::get_option( $value['id'], $value['default'] );
			$option_value = SS_System_Info::get_system_info();
			// Description handling
			$field_description = WC_Admin_Settings::get_field_description( $value );
			extract( $field_description );
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
					<?php echo $tooltip_html; ?>
				</th>
				<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
					<?php echo $description; ?>

					<textarea
						name="<?php echo esc_attr( $value['id'] ); ?>"
						id="<?php echo esc_attr( $value['id'] ); ?>"
						style="font-family: Menlo,Monaco,monospace;display: block; overflow: auto; white-space: pre; width: 800px; height: 400px;<?php echo esc_attr( $value['css'] ); ?>"
						class="<?php echo esc_attr( $value['class'] ); ?>"
						placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
						readonly="readonly" onclick="this.focus(); this.select()"
						<?php //echo implode( ' ', $custom_attributes ); ?>
						><?php echo esc_textarea( $option_value );  ?></textarea>
				</td>
			</tr>
			<?php
		}

	} //end class SS_WC_MailChimp

	return SS_WC_Settings_MailChimp::get_instance();

} //end if ( ! class_exists( 'SS_WC_Settings_MailChimp' ) )