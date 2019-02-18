<?php // IPS-CORE

class ipsCore {

	public static $app;
	public static $uri;
	public static $uri_parts;

	public static $path_base;
	public static $path_apps;
	public static $path_app;
	public static $path_core;
	public static $path_core_includes;
	public static $path_core_helpers;
	public static $path_libraries;
	public static $path_public;
	public static $path_public_css;
	public static $path_public_js;
	public static $path_public_img;

	public static $site_protocol;
	public static $site_url;
	public static $site_base;

	public static $includes = array();
	public static $helpers = array();
	public static $helpers_active = array();

	public static $functions;
	public static $database;
	public static $session;
	public static $controller;

	public static $router;
	public static $errors = array();

	public static $request;
	public static $request_type;

	public static $data = array(); // Front end data
	public static $output; // Front end page output
	public static $output_type = 'html'; // html / json

	public static function setup() {

		switch ( $_SERVER['REQUEST_METHOD'] ) {
			case 'PUT':
			case 'POST':
			case 'GET':
			case 'HEAD':
			case 'DELETE':
			case 'OPTIONS':
				self::$request_type = $_SERVER['REQUEST_METHOD'];
			break;
			default:
				self::add_error( 'Invalid request type: ' . $_SERVER['REQUEST_METHOD'] );
			break;
		}

		//die( self::$path_core );
		
		require_once( self::$path_core . '/ips_controller.php' );
		require_once( self::$path_core . '/ips_model.php' );
		require_once( self::$path_core . '/ips_view.php' );
		require_once( self::$path_core . '/ips_router.php' );

		self::get_includes();
		self::find_helpers();
		self::$session = new ipsCore_session();
		self::$functions = new ipsCore_functions();
		self::$router = new ipsCore_router();
		self::$router->init();
	}

	// METHODS
	public static function add_error( $error, $fatal = false ) {
		if ( $fatal ) {
			die( 'Fatal Error: ' . $error );
		} else {
			self::$errors[] = $error;
		}
	}

	public static function has_errors() {
		if ( !empty ( self::$errors ) ) {
			return true;
		} return false;
	}

	public static function display_errors() {
		if ( self::$output_type == 'html' ) {
			foreach ( self::$errors as $error ) {
				echo '<p style="border:1px solid #000;padding:1px 4px;margin:2px 0;">' . $error . '</p>';
			}
		} else {
			return self::$errors;
		}
	}

	public static function get_includes() {
		$includes = glob( self::$path_core_includes . '\*.php' );
		if ( $includes ) {
			foreach( $includes as $include ) {
				if ( is_file( $include ) ) {
					require_once( $include );
					self::$includes[] = $include;
				}
			}
		}
	}

	public static function requires_controller( $controllers, $app = false ) {
		if ( !is_array( $controllers ) ) {
			$controllers = array( $controllers );
		}
		foreach ( $controllers as $controller ) {
			$controller_path = self::get_controller_route( $controller, $app );

			if ( file_exists( $controller_path ) ) {
				require_once( $controller_path );
			} else {
				self::add_error( 'Required Controller "' . $controller . '" does not exist.' );
			}
		}
	}

	public static function requires_model( $models, $app = false ) {
		if ( !is_array( $models ) ) {
			$models = array( $models );
		}
		foreach ( $models as $model ) {
			$model_path = self::get_model_route( $model, $app );
			if ( file_exists( $model_path ) ) {
				require_once( $model_path );
			} else {
				self::add_error( 'Required Model "' . $model . '" does not exist.' );
			}
		}
	}

	public static function get_controller_route( $controller, $app = false ) {		
		return self::get_file_route( $controller, 'controllers', $app );
	}

	public static function get_model_route( $model, $app = false ) {
		return self::get_file_route( $model, 'models', $app );
	}

	public static function get_view_route( $view ) {
		return self::get_file_route( $view, 'views' );
	}

	public static function get_part_route( $part ) {
		return self::get_file_route( $part, 'parts' );
	}

	private static function get_file_route( $file, $dir, $app = false ) {
		$path = ipsCore::$path_app;

		if ( $app ) { $path = ipsCore::$path_apps . $app; }

		$file = $path . '/' . $dir . '/' . $file . '.php';

		return $file;
	}

	public static function find_helpers() {
		$helpers = glob( self::$path_core_helpers . '/*.php' );
		if ( $helpers ) {
			foreach( $helpers as $helper ) {
				if ( is_file( $helper ) ) {
					self::$helpers[] = basename( $helper, '.php' );
				}
			}
		}
	}

	public static function requires_helper( $helper ) {

		if ( in_array( $helper, self::$helpers ) && !in_array( $helper, self::$helpers_active ) ) {
			self::$helpers_active[] = $helper;
			require_once( self::$path_core_helpers . '/' . $helper . '.php' );
		} else {
			self::add_error( 'Helper "' . $helper . '" is already active.' );
		}
	}

	public static function build() {
		if ( self::$controller ) {
			if ( self::$output !== false ) {
				if ( !empty( self::$output ) ) {
					self::$output->build();
				} else {
					self::add_error( 'Output is empty.' );
				}
			}
		} else {
			self::add_error( 'No controller to build with.' );
		}
	}

	public static function render() {
		if ( self::$output ) {
			if ( self::$output_type == 'html' ) {

			} elseif ( self::$output_type == 'json' ) {
				header('Content-Type: application/json');

			}
			self::$output->display();
		} else {
			self::add_error( 'No output type set.' );
		}
	}

}

class ipsCore_app {
	protected $name;
	protected $directory;
	protected $uri;
	protected $core_version;

	public function __construct( $current_app_key, $current_app ) {		
		if ( isset( $current_app[ 'name' ] ) ) {
			$this->set_name( $current_app[ 'name' ] );
		} else {
			ipsCore::add_error( 'App Config missing: name', true );
		}
		if ( !empty( $current_app_key ) ) {
			$this->set_directory( $current_app_key );
		} else {
			ipsCore::add_error( 'App Config missing: directory', true );
		}
		if ( isset( $current_app[ 'uri' ] ) ) {
			$this->set_uri( $current_app[ 'uri' ] );
		} else {
			ipsCore::add_error( 'App Config missing: uri', true );
		}
		if ( isset( $current_app[ 'core' ] ) ) {
			$this->set_core_version( $current_app[ 'core' ] );
		} else {
			ipsCore::add_error( 'App Config missing: core', true );
		}
	}

	// Getters
	public function get_name() { return $this->name; }
	public function get_directory() { return $this->directory; }
	public function get_uri() { return $this->uri; }
	public function get_core_version() { return $this->core_version; }

	// Setters
	private function set_name( $name ) { $this->name = $name; }
	private function set_directory( $directory ) { $this->directory = $directory; }
	private function set_uri( $uri ) { $this->uri = $uri; }
	private function set_core_version( $core_version ) { $this->core_version = $core_version; }
}
