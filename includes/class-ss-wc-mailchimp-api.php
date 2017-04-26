<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Minimal MailChimp API v3.0 wrapper
 *
 * @class       SS_WC_MailChimp_API
 * @version     2.0
 * @package     WooCommerce MailChimp
 * @author      Saint Systems
 */
class SS_WC_MailChimp_API {

	/**
	 * @var string
	 */
	public $api_key;

	/**
	 * @var string
	 */
	public $datacenter = 'us1';

	/**
	 * @var string
	 */
	private $api_root = 'https://<dc>.api.mailchimp.com/3.0/';

	/**
	 * @var boolean
	 */
	private $debug = false;

	/**
	 * @var array
	 */
	private $last_response;

	/**
	 * @var WP_Error
	 */
	private $last_error;

	/**
	 * @var WC_Logger
	 */
	private $log;

	/**
	 * Create a new instance
	 * @param string $api_key MailChimp API key
	 * @param boolean $debug  Whether or not to log API calls
	 */
	function __construct( $api_key, $debug = false ) {

		$this->debug = $debug;

		if ( $this->debug === true ) {
			$this->log = new WC_Logger();
		}

		$this->api_key = $api_key;
		$api_key_parts = explode( '-', $this->api_key );
		$this->datacenter = empty( $api_key_parts[1] ) ? 'us1' : $api_key_parts[1];
		$this->api_root = str_replace( '<dc>', $this->datacenter, $this->api_root );

	} //end function __construct

	/**
	 * @param string $resource
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function get( $resource, $args = array() ) {

		if ( is_array( $args) && ! array_key_exists( 'count', $args ) ) {
			$args['count'] = 10;
		}

		return $this->api_request( 'GET', $resource, $args );

	} //end function post

	/**
	 * @param string $resource
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function post( $resource, $args = array() ) {

		return $this->api_request( 'POST', $resource, $args );

	} //end function post

	/**
	 * @param string $resource
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function put( $resource, $args = array() ) {

		return $this->api_request( 'PUT', $resource, $args );

	} //end function put

	/**
	 * @param string $resource
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function patch( $resource, $args = array() ) {

		return $this->api_request( 'PATCH', $resource, $args );

	} //end function patch

	/**
	 * @param string $resource
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function delete( $resource, $args = array() ) {

		return $this->api_request( 'DELETE', $resource, $args );

	} //end function delete

	/**
	 * Performs the underlying HTTP request.
	 * @param  string $method HTTP method (GET|POST|PUT|PATCH|DELETE)
	 * @param  string $resource MailChimp API resource to be called
	 * @param  array  $args   array of parameters to be passed
	 * @return array          array of decoded result
	 */
	private function api_request( $method, $resource, $args = array() ) {

		$this->reset();

		$url = $this->api_root . $resource;

		global $wp_version;

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
				'User-Agent'     => 'woocommerce-mailchimp/' . SS_WC_MAILCHIMP_VERSION . '; WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' ),
			),
		);

		// attach arguments (in body or URL)
		if ( $method === 'GET' ) {
			$url = add_query_arg( $args, $url );
		} else {
			$request_args['body'] = json_encode( $args );
		}

		// print_r($url);
		// die();

		$raw_response = wp_remote_request( $url, $request_args );

		$this->last_response = $raw_response;

		$this->maybe_log( $url, $method, $args, $raw_response );

		if ( is_wp_error( $raw_response ) ) {

			$this->last_error = new WP_Error( 'ss-wc-mc-api-request-error', $raw_response->get_error_message(), $this->format_error( $resource, $method, $raw_response ) );

			return false;

		} elseif ( is_array( $raw_response ) 
			&& $raw_response['response']['code'] 
			&& floor( $raw_response['response']['code'] ) / 100 >= 4 ) {

			$json = wp_remote_retrieve_body( $raw_response );

			$error = json_decode( $json, true );

			$this->last_error = new WP_Error( 'ss-wc-mc-api-request-error', $error['detail'], $this->format_error( $resource, $method, $raw_response ) );

			return false;

		} else {

			$json = wp_remote_retrieve_body( $raw_response );

			$result = json_decode( $json, true );

			return $result;

		}

	} //end function api_request

	/**
	 * Empties all data from previous response
	 */
	private function reset() {
		$this->last_response = null;
		$this->last_error = null;
	}

	/**
	 * Conditionally log MailChimp API Call
	 * @param  string $resource MailChimp API Resource
	 * @param  string $method   HTTP Method
	 * @param  array $args      HTTP Request Body
	 * @param  array $response  WP HTTP Response
	 * @return void
	 */
	private function maybe_log( $resource, $method, $args, $response ) {

		if ( $this->debug === true ) {
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
	 * @return array|WP_Error
	 */
	public function get_last_response() {
		return $this->last_response;
	}

	/**
	 * Returns error code from error property
	 * @return string error code
	 */
	public function get_error_code() {

		$last_error = $this->last_error;
		if ( is_wp_error( $last_error ) ) {
			return $last_error->get_error_code();
		}
		return null;

	} //end get_error_code

	/**
	 * Returns error message from error property
	 * @return string error message
	 */
	public function get_error_message() {

		$last_error = $this->last_error;
		if ( is_wp_error( $last_error ) ) {
			return $last_error->get_error_message();
		}
		return null;

	} //end get_error_message

} //end class SS_WC_MailChimp_API
