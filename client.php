<?php
/**
 * Remote_API_Client class.
 * This class can build an API request using Remote_API_Request and execute it via curl 
 * or provide the necesarry JavaScript for Ajax based execution via jquery
 */
class Remote_API_Client {
	
	private $server_uri = '';
	private $server_entry_key = '';
	private $server_format_key = '';
	private $api_key = '';
	private $secret = '';
	
	private $call_parameters = array();
	
	/**
	 * __construct function.
	 * called from static method Remote_API_Client::call( $method, $arguments = array(), $format = 'xml', $call_method = 'curl', $http_cache = false )
	 * 
	 * @access public
	 * @param string $server_uri. (default: '')
	 * @param string $api_key. (default: '')
	 * @param string $secret. (default: '')
	 * @param string $server_entry_key. (default: 'remote.api')
	 * @param string $server_format_key. (default: 'format')
	 * @return void
	 */
	public function __construct( $server_uri = '', $api_key = '', $secret = '', $server_entry_key = '', $server_format_key = '' ) {
		$params = array( 'server_uri', 'api_key', 'secret', 'server_entry_key', 'server_format_key' );
		foreach( $params as $param )
			$this->{$param} = $this->get_config_param( $param, ${$param} );
		
	} 
	
	private function get_config_param( $key, $value ) {
		if ( empty( $value ) )
			$value = Remote_API_Config::instance()->get( $key );
		
		if ( empty( $value ) )
			throw new Remote_API_Exception( "Parameter $key is required" );
		return $value;
	}
	
	private function build_request_uri( $request_string, $format ) {
		$parts = array( $this->server_uri, $this->server_entry_key, $request_string, $this->server_format_key, $format );
		return implode( "/", $parts );
	}
	
	/**
	 * This static function Remote_API_Client::call() is used to build a request string, it's uri and execute the request or provide the 
	 * javascript needed to update a CSS ID via jQuery.
	 *
	 * @access public
	 * @param mixed $method. Callback for the function that should be executed by the server.
	 * @param array $arguments. An array of arguments that will be passed on to the callback function. (default: array())
	 * @param string $format. Response format as defined in Remote_API_Response_Format::get() (default: 'xml')
	 * @param string $call_method. How should the call be executed. Current Options curl or ajax (default: 'curl')
	 * @param bool $http_cache. (default: false)
	 * @return void
	 */
	public function call( $method, $arguments = array(), $format = 'xml', $call_method = 'curl', $http_cache = false ) {
		$data = array( 'method' => $method, 'http_cache' => $http_cache, 'args' => $arguments );
		$request = new Remote_API_Request;
		$request_string = $request->build( $data );
		$request_url = $this->build_request_uri( $request_string, $format );
		
		switch ( $call_method ) {
			case 'curl': 
				$result = file_get_contents( $request_url );
				break;
			case 'ajax':
				$result = $this->print_ajax( $request_url );
				break;
		}
		return $result;
	}
	
	private function print_ajax( $request_url ) {
		if ( !$loading_html = $this->get_call_param( 'ajax_loading_html' ) ) {
			$loading_html = '';
		}
		
		$result = '
<script type="text/javascript">
jQuery(function($) {
	$(window).load(function(){
		$.getJSON( \'' . $request_url . '\', function( data ) {
			if ( data.response.status ) {
				html_result = data.response.result.output;
			} else {
				html_result = "There was an error";
			}
			$(\'#' . $this->get_call_param( 'ajax_result_id' ) . '\').replaceWith( html_result );
		});
	});
});
</script>
		' . $loading_html . '
		';
		
		return $result;
	}
	
	/**
	 * set_call_param function. sets a parameter which later can be used in the caller function to set curl timeouts, CSS Ids or the like
	 * 
	 * @access public
	 * @param mixed $key
	 * @param mixed $value
	 * @return void
	 */
	public function set_call_param( $key, $value ) {
		$this->call_parameters[$key] = $value;
	}
	
	/**
	 * get_call_param function. gets a parameter set via set_call_param()
	 * 
	 * @access public
	 * @param mixed $key
	 * @return void
	 */
	public function get_call_param( $key ) {
		if ( isset( $this->call_parameters[$key] ) )
			return $this->call_parameters[$key];
		return NULL;
	}
}
