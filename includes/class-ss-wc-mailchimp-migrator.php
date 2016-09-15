<?php 

/**
 * WooCommerce MailChimp plugin migrator class
 */
final class SS_WC_MailChimp_Migrator {

	const VERSION_KEY = 'ss_wc_mailchimp_version';

	const VERSIONS = array(
		'1.3.X',
		'2.0',
	);

	public static function migrate( $target_version ) {

		$current_version = get_option( self::VERSION_KEY );
		if ( ! $current_version ) {
			$current_version = '1.3.X';
		}
	
		if ( $current_version !== $target_version ) {

			// error_log( 'Need to migrate from ' . $current_version . ' to ' . $target_version );

			require_once( 'migrations/class-ss-wc-migration.php' );

			$start = array_search( $current_version, self::VERSIONS );

			// error_log( 'Starting at migration ' . $start );

			for ($start; $start < count(self::VERSIONS) - 1; $start++) {
			    $next = $start + 1;
			    $current_version = self::VERSIONS[$start];
				$target_version = self::VERSIONS[$next];

				// error_log( 'Migrating from ' . $current_version . ' to ' . $target_version );
				// 
			    if ( file_exists( SS_WC_MAILCHIMP_DIR . "includes/migrations/class-ss-wc-migration-from-$current_version-to-$target_version.php" ) ) {

					require_once( SS_WC_MAILCHIMP_DIR . "includes/migrations/class-ss-wc-migration-from-$current_version-to-$target_version.php" );

					$migration_name = 'SS_WC_MailChimp_Migration_From_'. self::clean_version( $current_version ) .'_To_'. self::clean_version( $target_version );

					$migration = new $migration_name( $current_version, $target_version );
					if ( $migration->up() ) {
						// Update the current plugin version
						update_option( self::VERSION_KEY, $target_version );
					}

				}
			}

		}

	}

	private static function clean_version( $version ) {
		return str_replace( '.', '', $version );
	}

}