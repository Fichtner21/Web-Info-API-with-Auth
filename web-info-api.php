<?php

/**

* Plugin Name: Web Info API with Auth
* Plugin Uri: https://github.com/fichtner21
* Description: Customowy endpoint z auth
* Version: 0.1.1
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


	// Register our REST Server
	public function init(){
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
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
			    echo 'Wersja WordPress: '.$wp_version . '<br>';
			}

			function get_plugins_all(){
				if ( ! function_exists( 'get_plugins' ) ) {
				    require_once ABSPATH . 'wp-admin/includes/plugin.php';
				}
				 
				$all_plugins = get_plugins();


				echo '<h3>Wtyczki:</h3> ' . '<br>';
				foreach($all_plugins as $plugin){
					echo 'Nazwa wtyczki: ' . $plugin['Name'] . ' ';
					echo ', wersja wtyczki: ' . $plugin['Version'] . ' ' . '<br>';					
				}
				 
				// Save the data to the error log so you can see what the array format is like.
				//error_log( print_r( $all_plugins, true ) );
			}

			function list_the_plugins() {
			    $plugins = get_option ( 'active_plugins', array () );
			    echo '<h4 style="padding-left:20px">Wtyczki aktywne</h4>';
			    echo '<ol>';
			    $apl = get_option('active_plugins');
				$plugins = get_plugins();
				$activated_plugins = array();
				foreach ($apl as $p){           
				    if(isset($plugins[$p])){
				         array_push($activated_plugins, $plugins[$p]);
				    } 
				}

				foreach($activated_plugins as $plug){					
					echo '<li>'. $plug['Name'] . ', ' . $plug['Version'] . '</li>';
				}			    
			}

			function get_theme_info(){				
				$my_theme = wp_get_theme();
				echo '<h3>Motyw:</h3> '. '<br>';						
				echo 'Nazwa motywu: ' . $my_theme['Name'] . '<br>';
				echo 'Wersja: ' . $my_theme['Version'] . '<br>';
				echo 'Autor: ' . $my_theme['Author'] . '<br>';				
			}

			function get_option_info(){ ?>
				<h3>Opcje:</h3>
				<ol>
					<li><?= 'admin_email: ' . get_option('admin_email'); ?></li>
					<li><?= 'blogname: ' . get_option('blogname'); ?></li>
					<li><?= 'blogdescription: ' . get_option('blogdescription'); ?></li>
					<li><?= 'blog_charset: ' . get_option('blog_charset'); ?></li>
					<li><?= 'date_format: ' . get_option('date_format'); ?></li>
					<li><?= 'default_category: ' . get_option('default_category'); ?></li>
					<li><?= 'home: ' . get_option('home'); ?></li>
					<li><?= 'siteurl: ' . get_option('siteurl'); ?></li>
					<li><?= 'template: ' . get_option('template'); ?></li>
					<li><?= 'users_can_register: ' . get_option('users_can_register'); ?></li>
					<li><?= 'posts_per_page: ' . get_option('posts_per_page'); ?></li>
					<li><?= 'posts_per_rss: ' . get_option('posts_per_rss'); ?></li>					
				</ol><?php
			}

			function get_total_all(){
				$all_options = wp_load_alloptions();
				$my_options  = array();
				 
				foreach ( $all_options as $name => $value ) {
				    if ( stristr( $name, '_transient' ) ) {
				        $my_options[ $name ] = $value;
				    }
				}
				 
				print_r( $my_options );
			}				
			
			return wp_version() . get_plugins_all() . list_the_plugins() . get_theme_info() . get_option_info() . get_total_all();			
		}
		else {
			return new WP_Error( 'invalid-method', 'You must specify a valid username and password.', array( 'status' => 400 /* Bad Request */ ) );
		}
	}
}
 
$lps_rest_server = new Website_Info();