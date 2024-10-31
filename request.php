<?php

/**
 * Remote_API_Request class.
 * This class will build / verify and decode request strings
 * It includes some basic security to make sure that the requests need to be encrypted with a known key/salt
 */
class Remote_API_Request {

	private $request = array();
	private $args = array();

	public function __construct() {

	}
	
	/**
	 * Sets an argument for the request
	 * 
	 * @access public
	 * @param mixed $key
	 * @param string $value. (default: '')
	 * @return void
	 */
	public function set_argument( $key, $value='' ) {
		$this->args[$key] = $value;
	}
	
	/**
	 * Removes an argument from the request
	 * 
	 * @access public
	 * @param mixed $key
	 * @return void
	 */
	public function remove_argument( $key ) {
		if ( isset( $this->args[$key] ) )
			unset( $this->args[$key] );
		return true;
	}
	
	/**
	 * Returns all arguments
	 * 
	 * @access public
	 * @return void
	 */
	public function get_arguments() {
		return $this->args;
	}
	
	/**
	 * Gets the value of a particular argument
	 * 
	 * @access public
	 * @param mixed $key
	 * @return void
	 */
	public function get_argument( $key ) {
		if ( isset( $this->args[$key] ) )
			return $this->args[$key];
		return false;
	}
	
	/**
	 * Builds a request string based on an array of input arguments.
	 * Make sure to set api_key and secret configurations
	 * 
	 * @access public
	 * @param array $args. (default: array())
	 * @return void
	 */
	public function build( $args=array() ) {
		foreach( $args as $key => $value ) {
			$this->set_argument( $key, $value );
		}
		
		$this->set_argument( 'chk', hash_hmac( 'md5', serialize( $this->get_arguments() ) . Remote_API_Config::instance()->get( 'api_key' ), Remote_API_Config::instance()->get( 'secret' ) ) );

		$this->request = base64_encode( serialize( $this->args ) ) . md5( Remote_API_Config::instance()->get( 'api_key' ) );
		return $this->request;
	}
	
	/**
	 * Parses a request string and returns it's content as array or false in case of an invalid request string
	 * 
	 * @access public
	 * @param mixed $request_string
	 * @return void
	 */
	public function parse( $request_string ) {
		if ( $this->verify( $request_string ) )
			return $this->request;
		else
			return false;
	}
	
	/**
	 * Verifies a Request and returns true/false for valid/invalid request strings
	 * 
	 * @access public
	 * @param mixed $request_string
	 * @return void
	 */
	public function verify( $request_string ) {
		$public_key = substr( $request_string, strlen( md5( Remote_API_Config::instance()->get( 'api_key' )  ) ) * -1 );
		$data_raw = base64_decode( substr( $request_string, 0, strlen( $request_string ) - strlen( md5( Remote_API_Config::instance()->get( 'api_key' ) ) - 1 ) ) );
		$data = unserialize( $data_raw );
		$data_chk = $data['chk'];
		unset( $data['chk'] );

		if ( $public_key <> md5( Remote_API_Config::instance()->get( 'api_key' ) ) || hash_hmac( 'md5', serialize( $data ) . Remote_API_Config::instance()->get( 'api_key' ), Remote_API_Config::instance()->get( 'secret' ) ) <> $data_chk )
			return false;
			
		
		$this->request = $data;
		return true;
	}

}