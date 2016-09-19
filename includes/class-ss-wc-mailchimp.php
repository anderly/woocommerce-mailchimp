<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Minimal MailChimp helper
 *
 * @class       SS_WC_MailChimp
 * @version     2.0
 * @package     WooCommerce MailChimp
 * @author      Saint Systems
 */
class SS_WC_MailChimp {

	/**
	 * @var SS_WC_MailChimp_API
	 */
	public $api;

	public $api_key;

	public $debug;

	/**
	 * Create a new instance
	 * @param string $api_key MailChimp API key
	 */
	function __construct( $api_key, $debug = false ) {

		$this->api_key = $api_key;

		$this->debug = $debug;

		require_once( SS_WC_MAILCHIMP_DIR . 'includes/class-ss-wc-mailchimp-api.php' );
		$this->api = new SS_WC_MailChimp_API( $api_key, $debug );

	} //end function __construct

	/**
	 * Get account
	 * 
	 * @access public
	 * @return mixed
	 */
	public function get_account( $api_key = null ) {

		$resource = '';

		$api = $this->api;

		if ( ! empty( $api_key ) ) {
			$api = new SS_WC_MailChimp_API( $api_key, $this->debug );
		}

		$account = $api->get( $resource );

		if ( ! $account ) {
			return false;
		}

		return $account;

	} //end function get_account

	/**
	 * Get list
	 * 
	 * @access public
	 * @return mixed
	 */
	public function get_lists( $args = array() ) {

		if ( ! $results = get_transient( 'sswcmc_lists' ) ) {

			$resource = 'lists';

			if ( ! array_key_exists( 'count', $args ) ) {
				$args['count'] = 100;
			}

			$response = $this->api->get( $resource, $args );

			if ( ! $response ) {
				return false;
			}

			$lists = $response['lists'];

			$results = array();

			foreach ( $lists as $list ) {

				$results[ (string)$list['id'] ] = $list['name'];

			}

			set_transient( 'sswcmc_lists', $results, 60*15*1 );

		}

		return $results;

	} //end function get_lists

	public function subscribe( $list_id, $email_address,  $email_type, $merge_fields, $interests, $double_optin ) {

		$args = array(
			'email_address' => $email_address,
			'status'        => $double_optin ? 'pending' : 'subscribed',
			'email_type'    => $email_type,
			'merge_fields'  => $merge_fields,
		);

		if ( is_array( $interests ) && !empty( $interests ) ) {
			$args['interests'] = $interests;
		}

		$subscriber_hash = md5( strtolower( $email_address ) );

		$resource = "lists/$list_id/members/$subscriber_hash";

		$response = $this->api->put( $resource, $args );

		if ( ! $response ) {
			return false;
		}

		return $response;

	} //end function subscribe

	/**
	 * Get merge fields
	 * 
	 * @access public
	 * @param string $list_id
	 * @return mixed
	 */
	public function get_merge_fields( $list_id ) {

		$resource = "lists/$list_id/merge-fields";

		$response = $this->api->get( $resource );

		if ( ! $response ) {
			return false;
		}

		$merge_fields = $response['merge_fields'];

		$results = array();

		foreach ( $merge_fields as $merge_field ) {

			$results[ $merge_field['id'] ] = $merge_field['tag'];

		}

		return $results;

	} //end function get_merge_fields

	/**
	 * Get interest categories
	 *
	 * @access public
	 * @param string $list_id
	 * @return mixed
	 */
	public function get_interest_categories( $list_id ) {

		if ( ! $results = get_transient( "sswcmc_{$list_id}_interest_categories" ) ) {

			$resource = "lists/$list_id/interest-categories";

			$response = $this->api->get( $resource );

			if ( ! $response ) {
				return false;
			}

			$categories = $response['categories'];

			$results = array();

			foreach ( $categories as $category ) {

				$results[ $category['id'] ] = $category['title'];

			}

			set_transient( "sswcmc_{$list_id}_interest_categories", $results, 60*15*1 );

		}

		return $results;

	} //end function get_interest_categories

	/**
	 * Get interest category interests
	 *
	 * @access public
	 * @param string $list_id
	 * * @param string $interest_category_id
	 * @return mixed
	 */
	public function get_interest_category_interests( $list_id, $interest_category_id ) {

		if ( ! $results = get_transient( "sswcmc_{$list_id}_{$interest_category_id }_interests" ) ) {

			$resource = "lists/$list_id/interest-categories/$interest_category_id/interests";

			$response = $this->api->get( $resource );

			if ( ! $response ) {
				return false;
			}

			$interests = $response['interests'];

			$results = array();

			foreach ( $interests as $interest ) {

				$results[ $interest['id'] ] = $interest['name'];

			}

			set_transient( "sswcmc_{$list_id}_{$interest_category_id }_interests", $results, 60*15*1 );

		}

		return $results;

	} //end function get_interest_category_interests

	/**
	 * Get interest categories with interests
	 *
	 * @access public
	 * @param string $list_id
	 * @return mixed
	 */
	public function get_interest_categories_with_interests( $list_id ) {

		$categories = $this->get_interest_categories( $list_id );

		if ( ! $categories ) {
			return false;
		}

		$results = array();

		foreach ( $categories as $category_id => $category ) {

			$interests = $this->get_interest_category_interests( $list_id, $category_id );

			if ( ! $interests ) {
				return false;
			}

			foreach ( $interests as $interest_id => $interest ) {

				$results[ $interest_id ] = $category . ': ' . $interest;

			}

		}

		return $results;

	} //end function get_interest_categories_with_interests

	/**
	 * Returns error code from error property
	 * @return string error code
	 */
	public function get_error_code() {

		return $this->api->get_error_code();

	} //end get_error_code

	/**
	 * Returns error message from error property
	 * @return string error message
	 */
	public function get_error_message() {

		return $this->api->get_error_message();

	} //end get_error_message

} //end class SS_WC_MailChimp
