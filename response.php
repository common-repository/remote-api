<?php

/**
 * Remote_API_Response class.
 * Creates API responses in various formats
 * 
 */
class Remote_API_Response {

	private $format = NULL;
	
	/**
	 * __construct function.
	 * 
	 * @access public
	 * @param bool $status. Basic result status. Either true or false (default: true)
	 * @param array $response_data. An array with the result data. (default: array())
	 * @param string $format. Response format as defined in Remote_API_Response_Format::get() (default: 'raw')
	 * @param bool $return. Return the response or print it out right away (default: false)
	 * @param bool $http_cache. Currently not used but implemented for response formats to allow setting caching headers via print_header() (default: false)
	 * @return void
	 */
	public function __construct( $status = true, $response_data = array(), $format = 'raw', $return = false, $http_cache = false ) {
		$this->format = $this->format( $format );
		if ( false === $this->format ) {
			throw new Remote_API_Exception( 'No such response format' );
		} 

		if ( $return )
			ob_start();
			
		$this->format->http_cache = $http_cache;
		$this->format->print_header();
		$this->format->set_status( $status );
		$this->format->print_response( $response_data );
		$this->format->print_footer();
		
		if ( $return ) 
			return ob_get_clean();
		
		exit;
	}
	
	/**
	 * This function generates an API Response based on the provided response_data
	 * The proposed return structure should be something like array( 'response' => array( 'status' => <true/false>, 'result' => <response string/array> ) );
	 *
	 * @access public
	 * @static
	 * @param array $response_data. An array with the result data. (default: array())
	 * @param bool $status. Basic result status. Either true or false (default: true)
	 * @param string $format. Response format as defined in Remote_API_Response_Format::get() (default: 'raw')
	 * @param bool $return. Return the response or print it out right away (default: false)
	 * @param bool $http_cache. Currently not used but implemented for response formats to allow setting caching headers via print_header() (default: false)
	 * @return void
	 */
	public static function generate( $response_data = array(), $status = true, $format = 'raw', $return = false, $http_cache = false ) {
		$response_data = (array) $response_data;
		$response = new Remote_API_Response( $status, $response_data, $format, $return, $http_cache );
		if ( $return )
			return $response;
	}
	
	/**
	 * Returns the Remote_API_Format instance for a particular format
	 * 
	 * @access public
	 * @param mixed $format. Response format as defined in Remote_API_Response_Format::get() (default: 'raw')
	 * @return void 
	 */
	public function format( $format ) {
		if ( ! Remote_API_Response_Format::exists( $format ) )
			return false;
		
		$class_name = 'Remote_API_Format_' . ucfirst( $format );
		return new $class_name;
	}
}

/**
 * Remote_API_Response_Format class.
 * 
 */
class Remote_API_Response_Format {
	private static $formats = array();
	
	/**
	 * get all registered formats.
	 * 
	 * @access public
	 * @static
	 * @return void
	 */
	public static function get() {
		return self::$formats;
	}
	
	/**
	 * check if a format exists
	 * 
	 * @access public
	 * @static
	 * @param mixed $format. Response format as defined in Remote_API_Response_Format::get()
	 * @return void
	 */
	public static function exists( $format ) {
		if ( in_array( $format, self::$formats ) )
			return true;
		return false;
	}
	
	/**
	 * Register the existing response formats
	 * 
	 * @access public
	 * @static
	 * @return void
	 */
	public static function register() {
		self::scan_formats();
	}
	
	private static function scan_formats() {
		$all_classes = get_declared_classes();
		$formats = Remote_API_Response_Format::get();
		foreach ( $all_classes as $class_name ) {
			if ( preg_match( '/Remote_API_Format_(.+)$/', $class_name, $match ) ) {
				if ( empty( $formats ) || !in_array( strtolower( $match[1] ), $formats ) ) {
					$formats[] = strtolower( $match[1] );
				}
			}
		}
		self::$formats = $formats;
	}

}

/**
 * Abstract Remote_API_Format class.
 * 
 * @abstract
 */
abstract class Remote_API_Format {

	public static $format = false;
	private $status = null;
	public $http_cache = false;
	
	public function __construct() {

	}
	
	/**
	 * Convert Input data to desired output format and return it
	 * 
	 * @access public
	 * @abstract
	 * @param mixed $input. Input array / string to be converted
	 * @return void
	 */
	abstract function convert( $input );
	
	/**
	 * Output a response based on the selected input.
	 * 
	 * @access public
	 * @abstract
	 * @param mixed $input
	 * @return void
	 */
	abstract function print_response( $input );
	
	/**
	 * Print the header for your response. Useful when adding http headers or building a html response
	 * 
	 * @access public
	 * @abstract
	 * @return void
	 */
	abstract function print_header();
	
	/**
	 * Print a footer under your response. 
	 * 
	 * @access public
	 * @abstract
	 * @return void
	 */
	abstract function print_footer();
	
	/**
	 * Set the response status. This should be the indicator if the result is successful or unsuccessful
	 * 
	 * @access public
	 * @abstract
	 * @param mixed $status
	 * @return void
	 */
	abstract function set_status( $status );

}

/**
 * Remote_API_Format_Xml Format implementation.
 * This will be the default format and will return a xml based representation of the data
 * 
 * @extends Remote_API_Format
 */
class Remote_API_Format_Xml extends Remote_API_Format {

	protected $xml = null;

	public function convert( $input ) {
		$input = (array) $input;
		$data = array( 'status' => $this->status, 'result' => $input );
		$this->add_response( $this->xml, $data );
		return $this->xml->asXml();
	}

	public function print_response( $input ) {
		$output = $this->convert( $input );
		echo $output;
	}

	public function print_header() {
		header( 'Content-Type: text/xml' );
		$this->xml = simplexml_load_string( '<?xml version="1.0" encoding="utf-8"?><response></response>' );
	}

	public function print_footer() {

	}
	
	public function set_status( $status ) {
		$this->status = $status;
	}
	
	private function add_response( $xml, array $response ) {
		foreach ( $response as $key => $val ) {
			if ( is_array( $val ) || is_object( $val ) ) {
				$val = (array) $val;
				$child = $xml->addChild( $key );
				$this->add_response( $child, $val );
		} else
			$xml->addChild( $key, $val );
		}
		return $xml;
	}
}

/**
 * Remote_API_Format_Json Format implementation
 * This class generates a JSON response that can be used in ajax or other javascript implementations
 * 
 * @extends Remote_API_Format
 */
class Remote_API_Format_Json extends Remote_API_Format {

	public function convert( $input ) {
		$data = array( 'response' => array( 'status' => $this->status, 'result' => $input ) );
		$output = json_encode( $data );
		return $output;
	}

	public function print_response( $input ) {
		$output = $this->convert( $input );
		echo $output;
	}

	public function print_header() {
		header( 'Content-Type: application/json' );
	}
	
	public function print_footer() {}
	
	public function set_status( $status ) {
		$this->status = $status;
	}

}