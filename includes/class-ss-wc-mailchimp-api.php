<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Minimal MailChimp API v3.0 wrapper
 *
 * @class       SS_WC_MailChimp_API
 * @extends     WC_Integration
 * @version     1.4.0
 * @package     WooCommerce MailChimp
 * @author      Saint Systems
 */
class SS_WC_MailChimp_API {

	public $api_key;

	public $datacenter = 'us1';

	public $error;

	private $api_root = 'https://<dc>.api.mailchimp.com/3.0/';

	private $debug = false;

	/**
	 * Create a new instance
	 * @param string $api_key MailChimp API key
	 */
	function __construct( $api_key, $debug = false ) {

		$this->debug = $debug;

		if ( $this->debug ) {
			$this->log = new WC_Logger();
		}

		$this->api_key = $api_key;
		$api_key_parts = explode( '-', $this->api_key );
		$this->datacenter = empty( $api_key_parts[1] ) ? 'us1' : $api_key_parts[1];
		$this->api_root = str_replace( '<dc>', $this->datacenter, $this->api_root );

	} //end function __construct

	/**
	 * Returns error code from error property
	 * @return string error code
	 */
	public function get_error_code() {

		return $this->error->get_error_code();

	} //end get_error_code

	/**
	 * Returns error message from error property
	 * @return string error message
	 */
	public function get_error_message() {

		return $this->error->get_error_message();

	} //end get_error_message

	/**
	 * Call an API method. Every request needs the API key, so that is added automatically -- you don't need to pass it in.
	 * @param  string $resource MailChimp API resource to be called
	 * @param  array  $args   Assoc array of parameters to be passed
	 * @param  string $method HTTP method (GET|POST|PUT|PATCH|DELETE)
	 * @return array          Associative array of json decoded API response.
	 */
	public function call( $resource, $args = array(), $method = 'GET' ) {

		return $this->api_request( $resource, $args, $method );

	} //end function call

	public function get( $resource, $count = 10 ) {

		if ( $count ) {
			$resource .= '?count=' . $count;
		}

		return $this->api_request( $resource, array(), 'GET' );

	} //end function post

	public function post( $resource, $args = array() ) {

		return $this->api_request( $resource, $args, 'POST' );

	} //end function post

	public function put( $resource, $args = array() ) {

		return $this->api_request( $resource, $args, 'PUT' );

	} //end function put

	public function patch( $resource, $args = array() ) {

		return $this->api_request( $resource, $args, 'PATCH' );

	} //end function patch

	public function delete( $resource, $args = array() ) {

		return $this->api_request( $resource, $args, 'DELETE' );

	} //end function delete

	/**
	 * Performs the underlying HTTP request.
	 * @param  string $resource MailChimp API resource to be called
	 * @param  array  $args   array of parameters to be passed
	 * @param  string $method HTTP method (GET|POST|PUT|PATCH|DELETE)
	 * @return array          array of decoded result
	 */
	private function api_request( $resource, $args = array(), $method = 'GET' ) {      

		$url = $this->api_root . $resource;

		$request_args = array(
			'method'        => $method,
			'sslverify'     => false,
			'timeout'       => 60,
			'redirection'   => 5,
			'httpversion'   => '1.1',
			'headers'       => array(
				'Content-Type'   => 'application/json',
				'Accept'         => 'application/json',
				'Authorization'  => 'apikey ' . $this->api_key,
			),
			'user-agent'    => 'SaintSystems-WooCommerce-MailChimp-WordPress-Plugin/' . get_bloginfo( 'url' ),
		);

		if ( $method !== 'GET' && is_array( $args ) && !empty( $args ) ) {
			$request_args['body'] = json_encode( $args );
		}

		$raw_response = wp_remote_request( $url, $request_args );

		$this->maybe_log( $url, $method, $args, $raw_response );

		if ( is_wp_error( $raw_response ) ) {

			$this->error = new WP_Error( 'ss-wc-mc-api-request-error', $raw_response->get_error_message(), $this->format_error( $resource, $method, $raw_response ) );

			return $this->error;

		} elseif ( is_array( $raw_response ) 
			&& $raw_response['response']['code'] 
			&& floor( $raw_response['response']['code'] ) / 100 >= 4 ) {

			$json = wp_remote_retrieve_body( $raw_response );

			$error = json_decode( $json, true );

			$this->error = new WP_Error( 'ss-wc-mc-api-request-error', $error['detail'], $this->format_error( $resource, $method, $raw_response ) );

			return $this->error;

		} else {

			// Always clear just in case
			$this->error = null;

			$json = wp_remote_retrieve_body( $raw_response );

			$result = json_decode( $json, true );

			return $result;

		}

	} //end function api_request

	/**
	 * Conditionally log MailChimp API Call
	 * @param  string $resource MailChimp API Resource
	 * @param  string $method   HTTP Method
	 * @param  array $args      HTTP Request Body
	 * @param  array $response  WP HTTP Response
	 * @return void
	 */
	private function maybe_log( $resource, $method, $args, $response ) {

		if ( $this->debug ) {
			$this->log->add( 'woocommerce-mailchimp', "MailChimp API Call RESOURCE: $resource \n METHOD: $method \n BODY: " . print_r( $args, true ) . " \n RESPONSE: " . print_r( $response, true ) );
		}

	}

	/**
	 * Formats api_request info for inclusion in WP_Error $data
	 * @param  [type] $resource [description]
	 * @param  [type] $method   [description]
	 * @param  [type] $response [description]
	 * @return [type]           [description]
	 */
	private function format_error( $resource, $method, $response ) {
		return array(
			'resource' => $resource,
			'method'   => $method,
			'response' => json_encode($response),
		);
	}

	/**
	 * has_api_key function.
	 *
	 * @access public
	 * @return void
	 */
	public function has_api_key() {

		return !empty( $this->api_key );

	} //end function has_api_key

	/**
	 * Get list
	 * 
	 * @access public
	 * @return mixed
	 */
	public function get_lists( $count = 100 ) {

		$response = $this->get( 'lists', $count );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$lists = $response['lists'];

		$results = array();

		foreach ( $lists as $list ) {

			$results[ (string)$list['id'] ] = $list['name'];

		}

		return $results;

	} //end functino get_lists

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

		$response = $this->put( "lists/$list_id/members/$subscriber_hash", $args );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		return $response;

	}

	/**
	 * Get merge fields
	 * 
	 * @access public
	 * @param string $list_id
	 * @return mixed
	 */
	public function get_merge_fields( $list_id ) {

		$response = $this->get( "lists/$list_id/merge-fields" );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$merge_fields = $response['merge_fields'];

		$results = array();

		foreach ( $merge_fields as $merge_field ) {

			$results[ $merge_field['id'] ] = $merge_field['tag'];

		}

		return $results;

	}

	/**
	 * Get interest categories
	 *
	 * @access public
	 * @param string $list_id
	 * @return mixed
	 */
	public function get_interest_categories( $list_id ) {

		$response = $this->get( "lists/$list_id/interest-categories" );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$categories = $response['categories'];

		$results = array();

		foreach ( $categories as $category ) {

			$results[ $category['id'] ] = $category['title'];

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

		$response = $this->get( "lists/$list_id/interest-categories/$interest_category_id/interests" );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$interests = $response['interests'];

		$results = array();

		foreach ( $interests as $interest ) {

			$results[ $interest['id'] ] = $interest['name'];

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

		if ( is_wp_error( $categories ) ) {
			return false;
		}

		$results = array();

		foreach ( $categories as $category_id => $category ) {

			$interests = $this->get_interest_category_interests( $list_id, $category_id );

			if ( is_wp_error( $interests ) ) {
				return false;
			}

			foreach ( $interests as $interest_id => $interest ) {

				$results[ $interest_id ] = $category . ': ' . $interest;

			}

		}

		return $results;

	} //end function get_interest_categories_with_interests

} //end class SS_WC_MailChimp_API
