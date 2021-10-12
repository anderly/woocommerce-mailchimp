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
			$last_response = $api->get_last_response();
			if (floor( $last_response['response']['code'] ) / 100 >= 4) {
				$body = $last_response['body'];
				$errorMessage = $last_response['response']['code'] . ' ' . $last_response['response']['message'];
				if (preg_match("'<h1>(.*)</h1>'si", $body, $matches)) {
					$errorMessage .= '<br/>' . $matches[1] . ': ';
				}
				if (preg_match("'<p>(.*)</p>'si", $body, $matches)) {
					$errorMessage .= $matches[1];
				}
				return array(
					'error' => $errorMessage
				);
			}
			return false;
		}

		do_action( 'sswcmc_log', __METHOD__ . ' Mailchimp Account Retrieved: ' . print_r( $account, true ) );

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
				$args['count'] = 500;
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

			set_transient( 'sswcmc_lists', $results, MINUTE_IN_SECONDS * 5 );

		}

		return $results;

	} //end function get_lists

	/**
	 * Get list
	 *
	 * @access public
	 * @return mixed
	 */
	public function get_list_web_ids( $args = array() ) {

		if ( ! $results = get_transient( 'sswcmc_list_web_ids' ) ) {

			$resource = 'lists/';

			if ( ! array_key_exists( 'count', $args ) ) {
				$args['count'] = 500;
			}

			$response = $this->api->get( $resource, $args );

			if ( ! $response ) {
				return false;
			}

			$lists = $response['lists'];

			$results = array();

			foreach ( $lists as $list ) {

				$results[ (string)$list['id'] ] = $list['web_id'];

			}

			set_transient( 'sswcmc_list_web_ids', $results, MINUTE_IN_SECONDS * 5 );

		}

		return $results;

	} //end function get_list_web_ids

	/**
	 * Get Subscriber
	 * @param  string $list_id         The MailChimp list ID
	 * @param  string $email_address   The user's email address
	 * @return string                  The status of the subscriber
	 */
	public function get_subscriber_status( $list_id, $email_address ) {

		$subscriber_hash = $this->get_subscriber_hash( $email_address );

		$resource = "lists/$list_id/members/$subscriber_hash";

		$response = $this->api->get( $resource, array() );

		if ( ! $response ) {
			return false;
		}

		return $response['status'];

	} //end function get_subscriber_status

	/**
	 * Subscribe the user to the list
	 * @param  string $list_id         The MailChimp list ID
	 * @param  string $email_address   The user's email address
	 * @param  string $email_type      html|text
	 * @param  array $merge_fields     Array of MailChimp Merge Tags
	 * @param  array $interests        Array of MailChimp Interest Groups
	 * @param  boolean $double_opt_in  Whether to send a double opt-in email to confirm subscription
	 * @return mixed $response         The MailChimp API response
	 */
	public function subscribe( $list_id, $email_address, $email_type, $merge_fields, $interests, $double_opt_in, $tags = array() ) {

		$args = array(
			'email_address' => $email_address,
			'status'        => $double_opt_in ? 'pending' : 'subscribed',
			'email_type'    => $email_type,
		);

		if ( is_array( $merge_fields ) && !empty( $merge_fields ) ) {
			$args['merge_fields'] = $merge_fields;
		}

		if ( is_array( $interests ) && !empty( $interests ) ) {
			$args['interests'] = $interests;
		}

		$subscriber_status = $this->get_subscriber_status( $list_id, $email_address );

		// If user is already subscribed or unsubscribed, set their status to 'subscribed' (and bypass the double opt-in email)
		if ( 'subscribed' === $subscriber_status || 'unsubscribed' === $subscriber_status ) {
			$args['status'] = 'subscribed';
		}

		$subscriber_hash = $this->get_subscriber_hash( $email_address );

		$base_resource = "lists/$list_id/members/$subscriber_hash";

		$resource = "$base_resource?skip_merge_validation=true";

		$response = $this->api->put( $resource, $args );

		if ( ! $response ) {
			return false;
		}

		if ( is_array( $tags ) && !empty( $tags ) ) {

			$args = array(
				'tags' => $tags
			);

			do_action( 'sswcmc_log', __METHOD__ . ' Attempting to add tags to subscriber ('.$email_address.'): ' . print_r( $args, true ) );

			$response_tags = $this->api->post( $base_resource . '/tags', $args );

			do_action( 'sswcmc_log', __METHOD__ . ' Subscriber tags response: ' . print_r( $response_tags, true ) );
		}

		return $response;

	} //end function subscribe

	/**
	 * Unsubscribe the user from the list
	 * @param  string $list_id         The MailChimp list ID
	 * @param  string $email_address   The user's email address
	 * @return mixed $response         The MailChimp API response
	 */
	public function unsubscribe( $list_id, $email_address ) {

		$args = array(
			'status' => 'unsubscribed',
		);

		if ( ! $subscriber_status = $this->get_subscriber_status( $list_id, $email_address ) ) {
			return false;
		}

		$subscriber_hash = $this->get_subscriber_hash( $email_address );

		$resource = "lists/$list_id/members/$subscriber_hash";

		$response = $this->api->put( $resource, $args );

		if ( ! $response ) {
			return false;
		}

		return $response;

	} //end function subscribe

	/**
	 * Returns the MD5 hash of the email
	 * @param  string $email_address The email address to hash
	 * @return string                MD5 hash of the lower-cased email address
	 */
	public function get_subscriber_hash( $email_address ) {

		return md5( strtolower( trim( $email_address ) ) );

	} //end function get_subscriber_hash

	/**
	 * Get merge fields
	 *
	 * @access public
	 * @param string $list_id
	 * @return mixed
	 */
	public function get_merge_fields( $list_id ) {

		if ( ! $results = get_transient( "sswcmc_{$list_id}_merge_fields" ) ) {

			$resource = "lists/$list_id/merge-fields";

			$response = $this->api->get( $resource );

			if ( ! $response ) {
				return false;
			}

			$merge_fields = $response['merge_fields'];

			$results = array();

			foreach ( $merge_fields as $merge_field ) {

				$results[ $merge_field['tag'] ] = array(
					'name' => sprintf( '%s (%s)', $merge_field['name'], $merge_field['tag'] ),
					'required' => $merge_field['required'],
				);

			}

			// Cache list merge tags for 15 minutes
			set_transient( "sswcmc_{$list_id}_merge_fields", $results, MINUTE_IN_SECONDS * 5 );

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

			$response = $this->api->get( $resource, array( 'count' => 500 ) );

			if ( ! $response ) {
				return false;
			}

			$categories = $response['categories'];

			$results = array();

			foreach ( $categories as $category ) {

				$results[ $category['id'] ] = $category['title'];

			}

			set_transient( "sswcmc_{$list_id}_interest_categories", $results, MINUTE_IN_SECONDS * 5 );

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

			$response = $this->api->get( $resource, array( 'count' => 500 ) );

			if ( ! $response ) {
				return false;
			}

			$interests = $response['interests'];

			$results = array();

			foreach ( $interests as $interest ) {

				$results[ $interest['id'] ] = $interest['name'];

			}

			set_transient( "sswcmc_{$list_id}_{$interest_category_id }_interests", $results, MINUTE_IN_SECONDS * 5 );

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
				continue;
			}

			foreach ( $interests as $interest_id => $interest ) {

				$results[ $interest_id ] = $category . ': ' . $interest;

			}

		}

		return $results;

	} //end function get_interest_categories_with_interests

	/**
	 * Get interest categories with interests
	 *
	 * @access public
	 * @param string $list_id
	 * @return mixed
	 */
	public function get_tags( $list_id ) {

		if ( ! $results = get_transient( "sswcmc_{$list_id}_tags" ) ) {

			$resource = "lists/$list_id/segments?type=static";

			$response = $this->api->get( $resource, array( 'count' => 500 ) );

			if ( ! $response ) {
				return false;
			}

			$tags = $response['segments'];

			$results = array();

			foreach ( $tags as $tag ) {

				$results[ $tag['id'] ] = $tag['name'];

			}

			set_transient( "sswcmc_{$list_id}_tags", $results, MINUTE_IN_SECONDS * 5 );

		}

		return $results;

	} //end function get_tags

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
