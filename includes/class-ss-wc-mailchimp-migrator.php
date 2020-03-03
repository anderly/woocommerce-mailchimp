<?php

/**
 * WooCommerce MailChimp plugin migrator class
 */
final class SS_WC_MailChimp_Migrator {

	const VERSION_KEY = 'ss_wc_mailchimp_version';
	const API_KEY = 'ss_wc_mailchimp_api_key';
	const LIST_KEY = 'ss_wc_mailchimp_list';
	const OLD_SETTINGS_KEY = 'woocommerce_mailchimp_settings';

	protected static $versions = array(
		'1.3.X',
		'2.0',
		'2.0.15',
		'2.3.0',
		'2.3.1',
		'2.3.2',
		'2.3.3',
	);

	public static function migrate( $target_version ) {

		$current_version = get_option( self::VERSION_KEY );
		$api_key = get_option( self::API_KEY );
		$list = get_option( self::LIST_KEY );

		if ( ! $current_version && ! $api_key && ! $list ) {
			// This is a new install, so no need to migrate
			update_option( self::VERSION_KEY, $target_version );
			return;
		}

		if ( ! $current_version ) {
			$current_version = '2.3.0';
		}

		if ( $current_version !== $target_version ) {

			// error_log( 'Need to migrate from ' . $current_version . ' to ' . $target_version );

			require_once( SS_WC_MAILCHIMP_DIR . 'includes/migrations/class-ss-wc-migration.php' );

			$start = array_search( $current_version, self::$versions );

			// error_log( 'Starting at migration ' . $start );
			if ( ! $start ) {
				update_option( self::VERSION_KEY, $target_version );
				return;
			}

			for ($start; $start < count(self::$versions) - 1; $start++) {
				$next = $start + 1;
				$current_version = self::$versions[$start];
				$next_version = self::$versions[$next];

				// error_log( 'Migrating from ' . $current_version . ' to ' . $target_version );
				//
				if ( file_exists( SS_WC_MAILCHIMP_DIR . "includes/migrations/class-ss-wc-migration-from-$current_version-to-$next_version.php" ) ) {

					do_action( 'sswcmc_log', 'Migrating from ' . $current_version . ' to ' . $target_version );

					require_once( SS_WC_MAILCHIMP_DIR . "includes/migrations/class-ss-wc-migration-from-$current_version-to-$next_version.php" );

					$migration_name = 'SS_WC_MailChimp_Migration_From_'. self::clean_version( $current_version ) .'_To_'. self::clean_version( $next_version );

					$migration = new $migration_name( $current_version, $next_version );
					if ( $migration->up() ) {
						// Update the current plugin version
						update_option( self::VERSION_KEY, $next_version );
						do_action( 'sswcmc_log', 'Finished Migrating from ' . $current_version . ' to ' . $target_version );
					}

				} else {
					// Update the current plugin version
					do_action( 'sswcmc_log', 'No migration found from ' . $current_version . ' to ' . $next_version . '. Setting current version to: ' . $next_version );

					update_option( self::VERSION_KEY, $next_version );
				}
			}
			//update_option( self::VERSION_KEY, $target_version );

		}

	}

	private static function clean_version( $version ) {
		return str_replace( '.', '_', $version );
	}

}
