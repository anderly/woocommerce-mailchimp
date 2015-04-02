<?php
/**
 * WooCommerce MailChimp Settings
 *
 * @author 		Saint Systems
 * @package     WooCommerce MailChimp
 * @version		1.4
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'SS_WC_Settings_MailChimp' ) ) {

	/**
	 * @class   SS_WC_Settings_MailChimp
	 * @extends WC_Settings_Page
	 */
	class SS_WC_Settings_MailChimp extends WC_Settings_Page  {

		/**
		 * Constructor
		 *
		 * @access public
		 * @return void
		 */
		public function __construct() {

			$this->id   = 'mailchimp';
			$this->label = __( 'MailChimp', 'ss_wc_mailchimp' );
			
			$this->register_hooks();

		} //end function __construct

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

			if ( is_admin() ) {
				
			}

		} //end function ensure_tab

		/**
		 * Get sections
		 *
		 * @return array
		 */
		public function get_sections() {

			$sections = array(
				''			=> __( 'General', 'ss_wc_mailchimp' ),
				'checkout'	=> __( 'Checkout', 'ss_wc_mailchimp' ),
				'widget' 	=> __( 'Widget', 'ss_wc_mailchimp' ),
				'shortcode'	=> __( 'ShortCode', 'ss_wc_mailchimp' ),
				'labels' 	=> __( 'Labels', 'ss_wc_mailchimp' ),
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

			$this->wc_enqueue_js("
	 			(function($){
					$(document).ready(function() {
						var apiKey = $('#woocommerce_mailchimp_api_key').val();
						if (apiKey === '') {
							SS_WC_MailChimp.loadLists(apiKey);
						}
					});
	 			})(jQuery);
			");
		}

		/**
		 * Get settings array
		 *
		 * @return array
		 */
		public function get_settings( $current_section = '' ) {

			if ( 'checkout' == $current_section ) {

				$settings = apply_filters( 'ss_wc_mailchimp_settings_general', array(

					array(
						'title' => __( 'Subscribe on Checkout', 'ss_wc_mailchimp' ),
						'type' 	=> 'title',
						'desc' 	=> '',
						'id' 	=> 'checkout_options'
					),

					array(
						'title'    => __( 'Enable', 'ss_wc_mailchimp' ),
						'desc'     => __( '<p>Choose <strong>Subscribe automatically</strong>, if you wish to subscribe all customers to one of your lists without asking for their consent.</p> <p>Choose <strong>Ask for permission</strong>, if you wish to add a checkbox to your Checkout page so users can opt-in to receive your newsletters.</p>', 'ss_wc_mailchimp' ),
						'id'       => 'woocommerce_mailchimp_subscribe_on_checkout',
						'type'     => 'select',
						'class'    => 'wc-enhanced-select',
						'desc_tip' => true,
						'options'  => array(
							'0'      => __( 'Disabled', 'ss_wc_mailchimp' ),
							'1' => __( 'Subscribe automatically', 'ss_wc_mailchimp' ),
							'2'    => __( 'Ask for permission', 'ss_wc_mailchimp' )
						)
					),

					array( 'type' => 'sectionend', 'id' => 'checkout_options' ),

				));

			} elseif ( 'widget' == $current_section ) {

			} elseif ( 'shortcode' == $current_section ) {

			} elseif ( 'label' == $current_section ) {	

			} else {

				global $SS_WC_MailChimp;

				if ( $SS_WC_MailChimp->load_mailchimp() ) {
					$user_lists = $SS_WC_MailChimp->mailchimp->get_lists();
					$lists = array();
					if ( is_array( $user_lists ) && ! empty( $user_lists ) ) {
						$lists = $user_lists;
					}
					if ( count( $lists ) > 0 ) {
						$mailchimp_lists = array_merge( array( '' => __( 'Select a list...', 'ss_wc_mailchimp' ) ), $lists );
					} else {
						$mailchimp_lists = array( '' => __( 'Please create a list in your MailChimp account', 'ss_wc_mailchimp' ) );
					}

					$groupings = $SS_WC_MailChimp->mailchimp->get_groups( get_option( 'woocommerce_mailchimp_main_list' ) );
					$mailchimp_groups = array();
					if ( is_array( $groupings ) ) {
						foreach ( $groupings as $grouping ) {
							foreach ( $grouping->groups as $group ) {
								$mailchimp_groups[ $grouping->name.':'.$group->name ] = $grouping->name.':'.$group->name;
							}
						}
					}
				} else {
					$mailchimp_lists = array( '' => __( 'Enter your api key above to see your lists', 'ss_wc_mailchimp' ) );
					$mailchimp_groups = array();
				}

				$settings = apply_filters( 'ss_wc_mailchimp_settings_general', array(

					array(
						'title' => __( 'General', 'ss_wc_mailchimp' ),
						'type' 	=> 'title',
						'desc' 	=> 'Enter your MailChimp settings below to control how WooCommerce integrates with your MailChimp lists.',
						'id' 	=> 'general_options'
					),

					array(
						'title'    => __( 'Enable Integration', 'ss_wc_mailchimp' ),
						'desc'     => 'Enable MailChimp Integration',
						'id'       => 'woocommerce_mailchimp_enabled',
						'type'     => 'checkbox',
						'default'  => '0',
						'desc_tip' => __( 'Enable or disable MailChimp integration.', 'ss_wc_mailchimp' ),
					),

					array(
						'title'    => __( 'API Key', 'ss_wc_mailchimp' ),
						'desc'     => __( '<br/><a href="https://us2.admin.mailchimp.com/account/api/" target="_blank">Login to MailChimp</a> to look up your api key.', 'ss_wc_mailchimp' ),
						'id'       => 'woocommerce_mailchimp_api_key',
						'type'     => 'text',
						'default'  => '',
						'css'      => 'min-width:300px;',
						'desc_tip' => 'Your API Key is how the plugin communicates with your MailChimp account.',
					),

					array(
						'title'    => __( 'Main List', 'ss_wc_mailchimp' ),
						'desc'     => __( 'All customers will be added to this list.', 'ss_wc_mailchimp' ),
						'id'       => 'woocommerce_mailchimp_main_list',
						'class'    => '',
						'css'      => 'min-width:300px;',
						'default'  => '',
						'type'     => 'select',
						'options'  => $mailchimp_lists,
						'desc_tip' =>  true,
					),

					array(
						'title'    => __( 'Groups', 'ss_wc_mailchimp' ),
						'desc'     => __( 'Optional: MailChimp Groups to add customers to upon checkout.', 'ss_wc_mailchimp' ),
						'id'       => 'woocommerce_mailchimp_groups',
						'class'    => '',
						'css'      => 'min-width:300px;',
						'default'  => '',
						'type'     => 'multiselect',
						'options'  => $mailchimp_groups,
						'desc_tip' =>  true,
					),

					array( 'type' => 'sectionend', 'id' => 'general_options' ),

				));
			}

			return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings, $current_section );

		} //end function get_settings

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

	} //end class SS_WC_MailChimp

	return new SS_WC_Settings_MailChimp();

} //end if ( ! class_exists( 'SS_WC_Settings_MailChimp' ) )