<?php

final class SS_WC_MailChimp_Migration_From_2_0_To_2_0_15 extends SS_WC_MailChimp_Migration {

	public function __construct( $current_version, $target_version ) {
		parent::__construct( $current_version, $target_version );
	}

	/**
	 * [up description]
	 * @return [type] [description]
	 */
	public function up() {

		try {

			$wrong_double_optin_setting_name = $this->namespace_prefixed('double_optin' );

			if ( !$wrong_double_optin_setting = get_option( $wrong_double_optin_setting_name ) ) {
				// we didn't find the wrong double optin setting (ss_wc_mailchimp_double_optin) saved in the database, so nothing to do
				return true;
			}

			// Let's set the correct double opt in setting
			$this->settings['double_opt_in'] = $wrong_double_optin_setting;

			// Save the settings
			$this->save_settings();

			// Finally, let's delete the wrong setting so we don't cause any confusion
			delete_option( $wrong_double_optin_setting_name );

		} catch (Exception $e) {
			return false;
		}

		return true;

	}

	/**
	 * [down description]
	 * @return [type] [description]
	 */
	public function down() {

		throw new Exception('Cannot rollback to versions prior to 2.0.15');

	}

}