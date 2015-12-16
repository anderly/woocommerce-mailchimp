<?php
/**
 * Minimal MailChimp API v3 wrapper
 */
class SS_MailChimp_API {

	public $api_key;
	private $api_endpoint = 'https://<dc>.api.mailchimp.com/3.0/';
	private $verify_ssl   = false;

	/**
	 * Create a new instance
	 * @param string $api_key Your MailChimp API key
	 */
	function __construct( $api_key ) {
		$this->api_key = $api_key;
		list( , $dc ) = explode( '-', $this->api_key );
		if ( ! $dc ) {
			$dc = 'us2';
		}
		$this->api_endpoint = str_replace( '<dc>', $dc, $this->api_endpoint );

	} //end function __construct

	/**
	 * Call an API method. Every request needs the API key, so that is added automatically -- you don't need to pass it in.
	 * @param  string $method The API method to call, e.g. 'lists'
	 * @param  array  $args   An array of arguments to pass to the method. Will be json-encoded for you.
	 * @return array          Associative array of json decoded API response.
	 */
	public function call( $resource, $args = array(), $method = 'GET' ) {

		return $this->api_request( $resource, $args, $method );

	} //end function call

	public function get( $resource, $args = array() ) {

		return $this->api_request( $resource, $args, 'GET' );

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
	 * Performs the underlying HTTP request. Not very exciting
	 * @param  string $method The API method to be called
	 * @param  array  $args   Assoc array of parameters to be passed
	 * @return array          Assoc array of decoded result
	 */
	private function api_request( $resource, $args = array(), $method ) {      

		$url = $this->api_endpoint . $resource;

		$request_args = array(
			'method'		=> $method,
			'body' 			=> json_encode( $args ),
			'sslverify' 	=> false,
			'timeout' 		=> 60,
			'httpversion'   => '1.1',
			'headers'       => array(
				'Content-Type'   => 'application/json',
				'Accept'   		 => 'application/json',
				//'Authorization'	 => 'Basic ' . base64_encode( 'username:' . $this->api_key ),
				'Authorization'	 => 'apikey ' . $this->api_key,
			),
			'user-agent'	=> 'WooCommerce-MailChimp-PHP-MCAPI/3.0'
		);

		$raw_response = wp_remote_request( $url, $request_args );

		if ( is_wp_error( $raw_response ) ) {

			throw new Exception( 'Error calling MailChimp API \'' . $method . '\' for resource(\'' . $resource . '\') error: ' . json_encode( $raw_response ) );

		} else if ( floor( $raw_response['response']['code'] ) / 100 >= 4 ) {

			throw new Exception( 'Error returned from MailChimp API \'' . $method . '\' for resource(\'' . $resource . '\') error: ' . wp_remote_retrieve_body( $raw_response ) );

		} else {

			$json = wp_remote_retrieve_body( $raw_response );

			$result = json_decode( $json, true );

			return $result;

		}

	} //end function api_request

	/**
	 * has_api_key function.
	 *
	 * @access public
	 * @return void
	 */
	public function has_api_key() {

		if ( $this->api_key ) {

			return true;

		}

	} //end function has_api_key

	/**
     * Get list
     * 
     * @access public
     * @return mixed
     */
    public function get_lists() {

        $response = $this->get( 'lists' );

        $lists = $response['lists'];

        $results = array();

        foreach ( $lists as $list ) {

            $results[ (string)$list['id'] ] = $list['name'];

        }

        return $results;

    } //end functino get_lists

    /**
     * Get merge vars
     * 
     * @access public
     * @param string $list_id
     * @return mixed
     */
    public function get_merge_vars( $list_id ) {
        $params = array( 'id' => $list_id );
        return $this->api_request( 'lists/merge-vars', $params );
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
	public function get_interest_category_with_interests( $list_id ) {

        $categories = $this->get_interest_categories( $list_id );

        $results = array();

        foreach ( $categories as $category_id => $category ) {

        	$interests = $this->get_interest_category_interests( $list_id, $category_id );

        	foreach ( $interests as $interest_id => $interest ) {

            	$results[ $interest_id ] = $category . ': ' . $interest;

            }

        }

        return $results;

	} //end function get_interest_category_with_interests

	/**
     * "Ping" the MailChimp API
     * https://apidocs.mailchimp.com/api/2.0/helper/ping.php
     * 
     * @access public
     * @return mixed
     */
    public function ping() {
        $params = array();
        return $this->api_request( 'helper/ping', $params );
    }

    /**
     * Get MailChimp account details
     * https://apidocs.mailchimp.com/api/2.0/helper/account-details.php
     * 
     * @access public
     * @param array $exclude
     * @return mixed
     */
    public function get_account_details( ) {
        $params = array( 'exclude' => array() );
        return $this->api_request( 'helper/account-details', $params );
    }

} //end class SS_MailChimp_API