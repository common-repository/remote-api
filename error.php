<?php

/**
 * Remote_API_Exception class.
 * Used as exception within the Remote_API to ensure that errors are
 * received in the selected response format whenever possible
 * 
 * @extends Exception
 */
class Remote_API_Exception extends Exception
{
	public function __construct( $message, $code = 0 ) {
		parent::__construct( $message, $code );
	}

	public function __toString() {
		return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
	}
	
	private function build_result() {
		return array( 'response' => array( 'message' => $this->getMessage() ), 'status' => false );
	}
	
	/**
	 * print_exception function. Print the exception and format it in the desired format
	 * 
	 * @access public
	 * @param string $format. (default: 'xml')
	 * @return void
	 */
	public function print_exception( $format = 'xml' ) {
		$result = $this->build_result();
		
		if ( Remote_API_Response_Format::exists( $format ) ) {
			Remote_API_Response::generate( $result['response'], $result['status'], $format );
		} else {
			echo 'no format found';
		}
		
		exit;
	}
}