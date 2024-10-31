<?php

/**
 * Remote_API_Config class.
 * Storage container for configuration options
 * 
 */
class Remote_API_Config {

	private static $__instance = NULL;
	
	private $config = array( 'formats' => array() );
	
	/**
	 * Returns a singleton instance for this class to ensure a unique configuation storage
	 * 
	 * @access public
	 * @static
	 * @return void
	 */
	public static function instance() {
		if ( self::$__instance == NULL ) 
			self::$__instance = new Remote_API_Config;
		return self::$__instance;
	}
	
	/**
	 * set function. sets a $key to $value
	 * 
	 * @access public
	 * @param mixed $key
	 * @param mixed $value
	 * @return void
	 */
	public function set( $key, $value ) {
		return $this->config[$key] = $value;
	}
	
	/**
	 * get function. receives the value for $key
	 * 
	 * @access public
	 * @param mixed $key
	 * @return void
	 */
	public function get( $key, $reformat = false ) {
		if ( isset( $this->config[$key] ) ) {
			switch( $reformat ) {
				case false:
					return $this->config[$key];
					break;
				case 'query':
					return preg_replace( "#[^a-z0-9-_]#siU", "-", $this->config[$key] );
					break;
				case 'rewrite':
					return preg_quote( $this->config[$key] );
					break;
			}
		} else {
			return false;
		}
	}
	
}