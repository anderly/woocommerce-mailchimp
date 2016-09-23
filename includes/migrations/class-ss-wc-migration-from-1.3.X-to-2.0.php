<?php

final class SS_WC_MailChimp_Migration_From_1_3_X_To_2_0 extends SS_WC_MailChimp_Migration {

	protected $interest_groupings;

	public function __construct( $current_version, $target_version ) {
		parent::__construct( $current_version, $target_version );
	}

	/**
	 * [up description]
	 * @return [type] [description]
	 */
	public function up() {

		$interest_groupings_key = 'interest_groupings';
		$groups_key = 'groups';

		try {

			if ( !array_key_exists( $interest_groupings_key, $this->settings ) || !array_key_exists( $groups_key, $this->settings ) ) return;

			$list = $this->settings['list'];
			$this->interest_groupings = $this->settings[$interest_groupings_key];
			$groups = $this->settings[$groups_key];

			if ( empty( $this->interest_groupings ) || empty( $groups ) || empty( $list ) ) return;

			$interest_categories = $this->mailchimp->get_interest_categories( $list );

			$selected_interest_category = array_filter( $interest_categories, array( $this, 'filter_interest_groups' ) );

			$selected_interest_category_id = key( $selected_interest_category );

			$interest_category_interests = $this->mailchimp->get_interest_category_interests( $list, $selected_interest_category_id );

			$groups = explode( ',', $groups );

			$selected_interest_category_interests = array_intersect( $interest_category_interests, $groups );

			$interest_groups = array_keys($selected_interest_category_interests);

			$this->settings['interest_groups'] = $interest_groups;

			unset($this->settings[$interest_groupings_key]);
			unset($this->settings[$groups_key]);

			$this->save_settings();
		} catch (Exception $e) {
			// If all else fails, finish the upgrade and the user will have to reselect the interest groups.
			unset($this->settings[$interest_groupings_key]);
			unset($this->settings[$groups_key]);

			$this->save_settings();
		}

		return true;

	}

	public function filter_interest_groups( $interest_group ) {
		return $interest_group == $this->interest_groupings;
	}

	/**
	 * [down description]
	 * @return [type] [description]
	 */
	public function down() {

		throw new Exception('Cannot rollback to versions prior to 2.0');

	}

}
