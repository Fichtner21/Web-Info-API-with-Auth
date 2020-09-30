<?php

/**

* Plugin Name: Web Info API with Auth
* Plugin Uri: https://github.com/fichtner21
* Description: Customowy endpoint z auth
* Version: 0.1.2
* Author: Ernest Fichtner
* Author URI: https://github.com/fichtner21
*/

class Website_Info extends WP_REST_Controller {
	private $api_namespace;
	private $base;
	private $api_version;
	private $required_capability;
	
	public function __construct() {
		$this->api_namespace = 'website_info/v';
		$this->base = 'website-details';
		$this->api_version = '1';
		$this->required_capability = 'read';  // Minimum capability to use the endpoint
		
		$this->init();
	}
	
	
	public function register_routes() {
		$namespace = $this->api_namespace . $this->api_version;
		
		register_rest_route( $namespace, '/' . $this->base, array(
			array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( $this, 'update_website_info' ), ),
		)  );
	}

	public function my_customize_rest_cors() {
		remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
		add_filter( 'rest_pre_serve_request', function( $value ) {
			header( 'Access-Control-Allow-Origin: http://localhost:8080' );
			header( 'Access-Control-Allow-Methods: GET' );			
			header( 'Access-Control-Allow-Headers: Access-Control-Allow-Headers, Origin, Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Access-Control-Request-Headers, username, password, cache-control' );
			header( 'Access-Control-Allow-Credentials: true' );

			return $value;
		} );
	}

	

	// Register our REST Server
	public function init(){
		add_action( 'rest_api_init', array( $this, 'register_routes')); 
		add_action( 'rest_api_init', array($this, 'my_customize_rest_cors') );
	}
	
	
	public function update_website_info( WP_REST_Request $request ){
		$creds = array();
		$headers = getallheaders();

		// Get username and password from the submitted headers.
		if ( array_key_exists( 'username', $headers ) && array_key_exists( 'password', $headers ) ) {
			$creds['user_login'] = $headers["username"];
			$creds['user_password'] =  $headers["password"];
			$creds['remember'] = false;
			$user = wp_signon( $creds, false );  // Verify the user.
			
			// TODO: Consider sending custom message because the default error
			// message reveals if the username or password are correct.
			if ( is_wp_error($user) ) {
				echo $user->get_error_message();
				return $user;
			}
			
			wp_set_current_user( $user->ID, $user->user_login );
			
			// A subscriber has 'read' access so a very basic user account can be used.
			if ( ! current_user_can( $this->required_capability ) ) {
				return new WP_Error( 'rest_forbidden', 'You do not have permissions to view this data.', array( 'status' => 401 ) );
			}

			function wp_version(){
				global $wp_version;
				$wp_version_obj = new stdClass();
				$wp_version_obj->version = $wp_version;
				
			    return json_encode($wp_version_obj);			    
			}

			function get_plugins_all(){
				if ( ! function_exists( 'get_plugins' ) ) {
				    require_once ABSPATH . 'wp-admin/includes/plugin.php';
				}
				 
				$all_plugins = get_plugins();
				$get_plugins_all_name = array();				
				$get_plugins_all_ver = array();				
				
				foreach($all_plugins as $plugin){					
					$plug_name = $plugin['Name'];
					$plug_ver = $plugin['Version'];					
					array_push($get_plugins_all_name, $plug_name);
					array_push($get_plugins_all_ver, $plug_ver);											
				}

				$get_plugins_all = array_combine($get_plugins_all_name, $get_plugins_all_ver);

				$get_plugins_all_arr_returned = array('plugins' => $get_plugins_all);
				
				// Save the data to the error log so you can see what the array format is like.
				error_log( print_r( $all_plugins, true ) );

				return json_encode($get_plugins_all_arr_returned);
			}

			function list_the_plugins() {
			    $plugins = get_option ( 'active_plugins', array () );
			    
			    $apl = get_option('active_plugins');
				$plugins = get_plugins();
				$activated_plugins = array();
				foreach ($apl as $p){           
				    if(isset($plugins[$p])){
				         array_push($activated_plugins, $plugins[$p]);
				    } 
				}

				$plugs_names_active = array();
				$plugs_ver_active = array();

				foreach($activated_plugins as $plug){					
					$plug_name_active = $plug['Name'];
					$plug_ver_active = $plug['Version'];
					array_push($plugs_names_active, $plug_name_active);
					array_push($plugs_ver_active, $plug_ver_active);
				}	

				$plugs_active = array_combine($plugs_names_active, $plugs_ver_active);
				$plugs_active_returned = array('plugins_active' => $plugs_active);

				return json_encode($plugs_active_returned);		    
			}

			function get_theme_info(){				
				$my_theme = wp_get_theme();				

				$theme_name = $my_theme['Name'];
				$theme_ver = $my_theme['Version'];
				$theme_author = strip_tags($my_theme['Author']);				

				$theme_arr_returned = array('theme' => 
					array('theme_name' => $theme_name, 'theme_ver' => $theme_ver, 'theme_author' => $theme_author)					
				);

				return json_encode($theme_arr_returned);			
			}

			

			// function get_total_all(){
			// 	$all_options = wp_load_alloptions();
			// 	$my_options  = array();
				 
			// 	foreach ( $all_options as $name => $value ) {
			// 	    if ( stristr( $name, '_transient' ) ) {
			// 	        $my_options[ $name ] = $value;
			// 	    }				    
			// 	}
				 
			// 	print_r( $my_options );
			// }


			$data = json_encode(array_merge(json_decode(wp_version(), true), json_decode(get_plugins_all(), true), json_decode(list_the_plugins(), true), json_decode(get_theme_info(), true)));

			$all_info = json_decode($data, true, JSON_UNESCAPED_SLASHES);									
			
			// return wp_version() . get_plugins_all() . list_the_plugins() . get_theme_info() . get_option_info() . get_total_all();
			return $all_info;			
		}
		else {
			return new WP_Error( 'invalid-method', 'You must specify a valid username and password.', array( 'status' => 400 /* Bad Request */ ) );
		}
	}
}
 
$lps_rest_server = new Website_Info();