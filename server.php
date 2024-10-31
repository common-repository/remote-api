<?php 

/**
 * This class implements the Remote_API_Server which hooks in early on parse_request to  
 * intercept the request and redirect to the called function where needed.
 * It also implements rewrite rules based on the configuration parameters server_entry_key, server_format_key
 * Please note that all valid callback a client should be allowed to call need to be whitelisted using register_server_function()
 *
 */
class Remote_API_Server {
	private $response_format = false;
	private $whitelisted_functions = array();
	
	/**
	 * Setup rewrite rules and query parameters and intercept the request via parse_request
	 * 
	 * @access public
	 * @return void
	 */
	public function __construct() {
		add_action( 'parse_request', array( &$this, 'intercept_request' ) );
		add_filter( 'query_vars', array( &$this, 'add_query_vars' ) );
		add_filter( 'pre_transient_rewrite_rules', array( &$this, 'add_rewrite_rules' ) );
		add_filter( 'transient_rewrite_rules', array( &$this, 'add_rewrite_rules' ) );
		add_filter( 'option_rewrite_rules', array( &$this, 'add_rewrite_rules' ) );
	}
	
	/**
	 * Add query parameters for server_entry_key and server_format_key configs
	 * 
	 * @access public
	 * @param mixed $query_vars
	 * @return void
	 */
	public function add_query_vars( $query_vars ) {
		if ( !in_array( Remote_API_Config::instance()->get( 'server_entry_key', 'query' ), $query_vars ) )
			$query_vars[] = Remote_API_Config::instance()->get( 'server_entry_key', 'query' );
		if ( !in_array( Remote_API_Config::instance()->get( 'server_format_key', 'query' ), $query_vars ) )
			$query_vars[] = Remote_API_Config::instance()->get( 'server_format_key', 'query' );
		return $query_vars;
	}
	
	/**
	 * Validate if we have a API request and forward to handle_request()
	 * 
	 * @access public
	 * @param mixed &$request
	 * @return void
	 */
	public function intercept_request( &$request ) {
 		if ( array_key_exists( Remote_API_Config::instance()->get( 'server_entry_key', 'query' ), $request->query_vars) && array_key_exists( Remote_API_Config::instance()->get( 'server_format_key', 'query' ), $request->query_vars ) ) {
 			$this->handle_request( $request->query_vars[ Remote_API_Config::instance()->get( 'server_entry_key', 'query' ) ], $request->query_vars[ Remote_API_Config::instance()->get( 'server_format_key', 'query' ) ] );
 		}
		return $request;
	}
	
	/**
	 * Add the rewrite rules needed to achieve a URL format such as
	 * http://<blogname>/<server_entry_key>/<request_string>/<server_format_key>/<format>
	 *
	 * @access public
	 * @param mixed $rules
	 * @return void
	 */
	public function add_rewrite_rules( $rules ) {
		if ( empty( $rules ) )
			return $rules;

		$rapi_rule = Remote_API_Config::instance()->get( 'server_entry_key', 'rewrite' ) . '/(.+)/' . Remote_API_Config::instance()->get( 'server_format_key', 'rewrite' ) . '/(' . implode( "|", Remote_API_Response_Format::get() ) . ')/?$';
		$rapi_dst = 'index.php?' . Remote_API_Config::instance()->get( 'server_entry_key', 'query' ) . '=$matches[1]&' . Remote_API_Config::instance()->get( 'server_format_key', 'query' ) . '=$matches[2]';
		
		if ( !isset( $rules[$rapi_rule] ) ) {
			$new_rule = array( $rapi_rule => $rapi_dst );
			$rules = $new_rule + $rules;
		}
		return $rules;
	}
	
	/**
	 * Validate the request, setup the request parameters and make sure the request is executed
	 * via the template_redirect action
	 * 
	 * @access public
	 * @param mixed $request_string
	 * @param mixed $format
	 * @return void
	 */
	public function handle_request( $request_string, $format ) {
		try {
			$this->response_format = $format;
			$request = new Remote_API_Request;
			if ( $request_data = $request->parse( $request_string ) ) {
				$this->request_data = $request_data;
				add_action( 'template_redirect', array( &$this, 'caller' ), 0 );
			} else {
				throw new Remote_API_Exception( 'Invalid request string' );
			}
		} catch ( Remote_API_Exception $e ) {
			$e->print_exception( $format );
		}
 	}
	
	/**
	 * Call the method callback set in the request and generate a response based on the requested format
	 * 
	 * @access public
	 * @return void
	 */
	public function caller() {
		try {
			if ( !isset( $this->request_data['method'] ) || !is_callable( $this->request_data['method'] ) )
				throw new Remote_API_Exception( 'Requested method is not callable' );
				
			if ( $this->is_allowed_function( $this->request_data['method'] ) )
				$result = call_user_func( $this->request_data['method'], $this->request_data['args'] );
			else
				throw new Remote_API_Exception( 'Requested method is not registered' );
			
			if ( isset( $this->request_data['cache'] ) )
				$http_cache = $this->request_data['cache'];
			else 
				$http_cache = false;

		 	$data = Remote_API_Response::generate( $result['result'], $result['status'], $this->response_format, false, $http_cache );
	 	} catch ( Remote_API_Exception $e ) {
			$e->print_exception( $format );
		}
		exit;
	}
	
	/**
	 * Whitelist a server function for usage by a client
	 * 
	 * @access public
	 * @param mixed $callback
	 * @return void
	 */
	public function register_server_function( $callback ) {
		$callback_key = md5( serialize( $callback ) );
		if ( !in_array( $callback_key, $this->whitelisted_functions ) )
			$this->whitelisted_functions[] = $callback_key;
	}
	
	/**
	 * Check if a callback is a registered server function
	 * 
	 * @access public
	 * @param mixed $callback
	 * @return void
	 */
	public function is_allowed_function( $callback ) {
		$callback_key = md5( serialize( $callback ) );
		if ( in_array( $callback_key, $this->whitelisted_functions ) )
			return true;
		else
			return false;
	}
}
