<?php

final class SS_WC_MailChimp_Migration_From_2_3_0_To_2_3_1 extends SS_WC_MailChimp_Migration {

	public function __construct( $current_version, $target_version ) {
		parent::__construct( $current_version, $target_version );
	}

	/**
	 * [up description]
	 * @return [type] [description]
	 */
	public function up() {

		try {

			$existing_tags = $this->settings['tags'];

			if ( is_array( $existing_tags ) && ! empty( $existing_tags ) ) {

				$list_id = $this->settings['list'];
				if ( ! empty( $list_id ) ) {

					$current_tags = $this->mailchimp->get_tags( $list_id );

					if ( ! empty( $current_tags ) ) {

						// Let's correct the tags
						$new_tags = array_filter( $current_tags, function ( $tag_name ) use ( $existing_tags ) {
							return in_array( $tag_name, $existing_tags );
						});

						$this->settings['tags'] = array_map('strval', array_keys( $new_tags ) );

						// Save the settings
						$this->save_settings();

					}

				}

			}

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

		throw new Exception('Cannot rollback to versions prior to 2.3.1');

	}

}
