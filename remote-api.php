<?php
/*
Plugin Name: Remote API
Plugin Script: remote-api.php
Plugin URI: http://wordpress.org/extend/plugins/remote-api/
Description: A set of extendable classes that allow the creation of a remote API. A basic use case for this would be lazy loading content segments or performing cross-blog actions.
Version: 0.2
Author: Thorsten Ott
Author URI: http://automattic.com
*/

/*
TODO 
- better documentation
- add simple example with jquery ( load comments via ajax )
- lazy loading widget ui improvements
- let server run as different user
- add server registry and observer patterns similar to the way memcache works to allow calling a single request across multiple servers
- add delegation in server to async jobs / cron
*/

// load exception handler
require_once( 'error.php' );

// initialize server
try {
	// load configuration container
	require_once( 'config.php' );
	// set required configuration options
	Remote_API_Config::instance()->set( 'api_key', SECRET_KEY );														// api key which is appended to request strings. YOU SHOULD CHANGE THIS
	Remote_API_Config::instance()->set( 'secret', SECRET_SALT );														// secret server key used as salt in the encryption process. YOU SHOULD CHANGE THIS
	Remote_API_Config::instance()->set( 'server_entry_key', 'remote.api' );												// url entry point for the remote api ( http://blogname/remote.api/' ). 
	Remote_API_Config::instance()->set( 'server_format_key', 'format' );												// url identifier for the response format ( http://blogname/remote.api/<request_string>/format/<format>/ )
	Remote_API_Config::instance()->set( 'server_uri', preg_replace( "/\/$/", "", get_bloginfo( 'url' ) ) );				// url for the server. used by client and server unless overwritten. no closing slash!
	
	// load response handler
	require_once( 'response.php' );
	// custom response formats need to be included 
	
	// here ^^^^
	// initialize existing response formats
	Remote_API_Response_Format::register();
	
	// initialize request handler which encodes and decodes the request strings
	require_once( 'request.php' );
	
	// initialize server which intercepts incoming requests and executes callbacks using the init action hook
	require_once( 'server.php' );
	$server = new Remote_API_Server;
	
} catch ( Remote_API_Exception $e ) {
	// throw an error in case any of the above fails
	$e->print_exception( $format );
}

// setup some client functionality

// load basic client class
require_once( 'client.php' );

// load example widget that allows lazy loading existing widgets using this classes
//*
require_once( 'examples/lazy-loading-widget.php' );
//*/


// custom client classes need to be included 


// here ^^^^