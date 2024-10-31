<?php

// client part

class Remote_API_Lazy_Widget extends WP_Widget {

	public function Remote_API_Lazy_Widget() {
		parent::WP_Widget( false, $name = 'Remote_API_Lazy_Widget' );
		add_action( 'init', array( &$this, 'register_dummy_sidebar' ), 1000 );
		add_filter( 'widget_update_callback', array( &$this, 'widget_update' ), 0, 4 );
	}

	public function register_dummy_sidebar() {
		global $wp_registered_sidebars;
		
		$all_instances = $this->get_settings();
		$sidebars_widgets = wp_get_sidebars_widgets();
		
		if ( -1 == $this->number ) {
			$instance = array();
			return false;
		} else {
			foreach( $all_instances as $instance_key => $instance ) {
				$housing_sidebar = false;
				foreach( $sidebars_widgets as $sidebar => $widgets ) {
					if ( in_array( $this->id_base . '-' . $instance_key, $widgets ) )
						$housing_sidebar = $sidebar;
				}
				if ( false === $housing_sidebar || !isset( $wp_registered_sidebars[$housing_sidebar] ) || 'wp_inactive_widgets' == $housing_sidebar ) 
					continue;
					
				$housing_sidebar_settings = $wp_registered_sidebars[$housing_sidebar];
					
				$instance = apply_filters( 'widget_form_callback', $instance, $this );
				$title = apply_filters( 'widget_title', $instance['title'] );
				
				register_sidebar(
					array(
						'id' => 'sidebar-' . $this->id_base . '-' . $instance_key ,
						'name' => $this->name . ': ' . $title,
						'description' => __( 'Drop Widgets ' . $this->name . ': ' . $title  . ' should load in this area. Never drop a ' . $this->name . ' widget in here' ),
						'before_widget' => $housing_sidebar_settings['before_widget'],
						'after_widget' => $housing_sidebar_settings['after_widget'],
						'before_title' => $housing_sidebar_settings['before_title'],
						'after_title' => $housing_sidebar_settings['after_title']
					)
				);
			}
		}

	}

	private function call( $instance ) {
		try {
			$client = new Remote_API_Client;
			$client->set_call_param( 'ajax_result_id',  $this->id_base . '-' . $this->number );
			$client->set_call_param( 'ajax_loading_html',  '<img src="/wp-admin/images/loading.gif" />' );

			$result = $client->call( 'get_remote_api_lazy_loading_widget', array( 'sidebar_index' => 'sidebar-' . $this->id_base . '-' . $this->number ), 'json', 'ajax' );
			return $result;
		} catch ( Remote_API_Exception $e ) {
			return false;
		}
	}

	public function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );
		echo $before_widget . $this->call( $instance ) . $after_widget;
	}

	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		return $instance;
	}

	public function form( $instance ) {
		$title = esc_attr( $instance['title'] );
?>
		<p class="description">This is a placeholder widget for other lazy loading widgets. Reload the page when adding or deleting this widget to make related sidebars appear.</p>

		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
		<?php
	}
	
	public function widget_update( $instance, $new_instance, $old_instance, $class ) {
		// emergency break in case someone drops a lazy loading widget in a lazy loading sidebar. prevent recursion
		if ( isset( $_POST['sidebar'] ) && strpos( $_POST['sidebar'], $this->id_base ) && $class->id_base == $this->id_base )
			return false;
		return $instance;
	}
}

function register_remote_api_lazy_widget() {
	register_widget( "Remote_API_Lazy_Widget" );
}
add_action( 'widgets_init', 'register_remote_api_lazy_widget' );

function load_remote_api_lazy_widget_scripts() {
	wp_enqueue_script( 'jquery' );
}
add_action( 'init', 'load_remote_api_lazy_widget_scripts' );

// server part

function get_remote_api_lazy_loading_widget( $args ) {
	global $wp_registered_sidebars;
	$sidebar_index = $args['sidebar_index'];
	$sidebars_widgets = wp_get_sidebars_widgets();
	if ( is_active_sidebar( $sidebar_index ) ) {
		ob_start();
		dynamic_sidebar( $sidebar_index );
		$output = ob_get_clean();
	}
	return array( 'status' => true, 'result' => array( 'output' => $output ) );
}
$server->register_server_function( 'get_remote_api_lazy_loading_widget' );

