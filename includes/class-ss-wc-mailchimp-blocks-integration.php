<?php
/**
 * WooCommerce MailChimp Blocks Integration
 *
 * @author  Saint Systems
 * @package WooCommerce MailChimp
 * @version 2.5.2
 */

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

if ( ! class_exists( 'SS_WC_MailChimp_Blocks_Integration' ) ) {

	final class SS_WC_MailChimp_Blocks_Integration implements IntegrationInterface {

		private $sswcmc = null;

		public function __construct( $sswcmc ) {
			$this->sswcmc = $sswcmc;
		}

		public function get_name() {
			return 'woocommerce-mailchimp';
		}

		public function initialize() {
			$this->register_frontend_scripts();
			$this->register_editor_scripts();
		}

		public function get_script_handles() {
			return array( 'wc-mailchimp-blocks-integration' );
		}

		public function get_editor_script_handles() {
			return array( 'wc-mailchimp-blocks-integration' );
		}

		public function get_script_data() {
			return array(
				'displayOptIn' => $this->sswcmc->display_opt_in(),
				'optInLabel' => $this->sswcmc->opt_in_label(),
				'optInDefaultStatus' => $this->sswcmc->opt_in_checkbox_default_status(),
			);
		}

		private function register_frontend_scripts() {
			$script_path = '/assets/js/blocks/checkout.js';
			$script_url = plugins_url( $script_path, SS_WC_MAILCHIMP_FILE );
			$script_asset_path = dirname( SS_WC_MAILCHIMP_FILE ) . '/assets/js/blocks/checkout.asset.php';
			$script_asset = file_exists( $script_asset_path )
				? require $script_asset_path
				: array(
					'dependencies' => array(),
					'version' => $this->get_file_version( $script_path ),
				);

			wp_register_script(
				'wc-mailchimp-blocks-integration',
				$script_url,
				$script_asset['dependencies'],
				$script_asset['version'],
				true
			);
		}

		private function register_editor_scripts() {
			$script_path = '/assets/js/blocks/checkout.js';
			$script_url = plugins_url( $script_path, SS_WC_MAILCHIMP_FILE );
			$script_asset_path = dirname( SS_WC_MAILCHIMP_FILE ) . '/assets/js/blocks/checkout.asset.php';
			$script_asset = file_exists( $script_asset_path )
				? require $script_asset_path
				: array(
					'dependencies' => array(),
					'version' => $this->get_file_version( $script_path ),
				);

			wp_register_script(
				'wc-mailchimp-blocks-integration',
				$script_url,
				$script_asset['dependencies'],
				$script_asset['version'],
				true
			);
		}

		protected function get_file_version( $file ) {
			if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( dirname( SS_WC_MAILCHIMP_FILE ) . $file ) ) {
				return filemtime( dirname( SS_WC_MAILCHIMP_FILE ) . $file );
			}
			return defined( 'SS_WC_MAILCHIMP_VERSION' ) ? SS_WC_MAILCHIMP_VERSION : '2.5.2';
		}

	}

}
