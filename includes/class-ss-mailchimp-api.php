<?php
/**
 * Minimal MailChimp API v2 wrapper
 */
class SS_MailChimp_API {

	private $api_key;
	private $api_endpoint = 'https://<dc>.api.mailchimp.com/2.0/';
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
	 * @param  string $method The API method to call, e.g. 'lists/list'
	 * @param  array  $args   An array of arguments to pass to the method. Will be json-encoded for you.
	 * @return array          Associative array of json decoded API response.
	 */
	public function call( $method, $args = array() ) {

		return $this->api_request( $method, $args );

	} //end function call

	/**
	 * Performs the underlying HTTP request. Not very exciting
	 * @param  string $method The API method to be called
	 * @param  array  $args   Assoc array of parameters to be passed
	 * @return array          Assoc array of decoded result
	 */
	private function api_request( $method, $args = array() ) {      
		$args['apikey'] = $this->api_key;

		$url = $this->api_endpoint.'/'.$method.'.json';

		$request_args = array(
			'body' 			=> json_encode( $args ),
			'sslverify' 	=> false,
			'timeout' 		=> 60,
			'httpversion'   => '1.1',
			'headers'       => array(
				'Content-Type'   => 'application/json'
			),
			'user-agent'	=> 'PHP-MCAPI/2.0'
		);

		$request = wp_remote_post( $url, $request_args );

		return is_wp_error( $request ) ? false : json_decode( wp_remote_retrieve_body( $request ) );

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
	}

	/**
	 * get_lists function.
	 *
	 * @access public
	 * @return void
	 */
	public function get_lists() {
		if ( ! $mailchimp_lists = get_transient( 'sswcmclist_' . md5( $this->api_key ) ) ) {

			$lists = $this->api_request( 'lists/list' );

			if ( $lists ) {

				if ( isset( $lists->status ) && $lists->status === "error" ) {

					echo '<div class="error"><p>' . sprintf( __( 'Unable to load lists from MailChimp: (%s) %s', 'ss_wc_mailchimp' ), $lists->code, $lists->error ) . '</p></div>';

					return false;

				} else {
					foreach ( $lists->data as $list ) {
						$mailchimp_lists[ $list->id ] = $list->name;
					}

					if ( sizeof( $mailchimp_lists ) > 0 ) {
						set_transient( 'sswcmclist_' . md5( $this->api_key ), $mailchimp_lists, 60*60*1 );
					}
				}

			} else {
				$mailchimp_lists = array();
			}
		}

		return $mailchimp_lists;

	} //end function get_lists

	/**
	 * get_groups function.
	 *
	 * @access public
	 * @return void
	 */
	public function get_groups( $list_id, $counts = false ) {
		$params = array('id' => $list_id, 'counts' => $counts);
        return $this->api_request('lists/interest-groupings', $params);

	} //end function get_groups

} //end class SS_MailChimp_API