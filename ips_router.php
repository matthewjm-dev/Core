<?php // IPS-ROUTER

class ipsCore_route {

	protected $uri;
	protected $canonical;
	protected $controller;
	protected $method;
	protected $action;

	// Getters
	public function get_uri() { return ipsCore::$uri; }
	public function get_canonical() { return $this->canonical; }
	public function get_controller() { return $this->controller; }
	public function get_method() { return $this->method; }
	public function get_action() { return $this->action; }

	// Setters
	public function set_method( $method ) { $this->method = $method; }
	public function set_action( $action ) { $this->action = $action; }

	// Construct
	public function __construct( $controller, $method, $action = [] ) {
		$this->controller = $controller;
		$this->method     = $method;
		$this->action     = $action;
	}
}

class ipsCore_router {

	public $routes = array();
	public $uri;
	protected $route;
	public $route_canonical;

	// Getters
	public function get_routes() { return $this->routes; }
	public function get_route() { return $this->route; }

	public function init() {
		require_once( ipsCore::$path_app . '/routes.php' ); // load App routes
		require_once( ipsCore::$path_apps . 'routes.php' ); // load shared routes

		$found_route = FALSE;
		$controller = 'pages';
		$method = 'index';
		$action = false;

		// create URI match variations
        /*if ( ipsCore::$app->get_uri() != '' ) {
            $appless_uri = str_replace( '/' . ipsCore::$app->get_uri(), '', ipsCore::$uri );
        } else {
            $appless_uri = ipsCore::$uri;
        }*/
		$uri_variations = [
            ipsCore::$uri,
            ipsCore::$uri . '/',
			rtrim( ipsCore::$uri, '/' )
		];

		//die( var_dump( $this->routes ) );
		
		// check for matching routes
		foreach ( $uri_variations as $uri ) {
			if ( isset( $this->routes[ $uri ] ) ) {
				$this->route = $this->routes[ $uri ][0];
				$this->route_canonical = $uri;
				$found_route = TRUE;
				continue;
			}
		}

		// assemble route
		if ( !$found_route ) {

			/*if ( count( $path_parts ) > 3 ) {
				$path_parts_controller = $path_parts[0] . '/' . $path_parts[1];
			} else {
				$path_parts_controller = $path_parts[0];
			}*/

            if ( ipsCore::$app->get_uri() != '' ) {
                $appless_uri = str_replace( '/' . ipsCore::$app->get_uri(), '', ipsCore::$uri );
            } else {
                $appless_uri = ipsCore::$uri;
            }
            $path_parts = explode( '/', trim( $appless_uri, '/' ) );

			if ( !empty( $path_parts ) ) {
				if ( isset( $path_parts[0] ) && $this->check_controller_exists( $path_parts[0] ) ) {
					$controller = array_shift( $path_parts );

					if ( !empty( $path_parts ) ) {
                        if ( method_exists( $controller, $path_parts[0] ) ) {
                            $method = str_replace( '-', '_', array_shift( $path_parts ) );
                        } else {
                            $method = 'index';
                        }
					}

					if ( !empty( $path_parts ) ) {
						$action = $path_parts;
					}
				} else {
					$method = 'call_error404';
				}
			} else {
				// 404
				$method = 'call_error404';
			}

			$this->route = new ipsCore_route( $controller, $method, $action );

		}

		$this->dispatch( $this->route );
	}

	public function add_route( $uri, ipsCore_route $route ) {
		$this->routes[ $uri ] = [ $route ];
	}

	public function check_controller_exists( $controller ) {
		if ( file_exists( ipsCore::get_controller_route( $controller ) ) ) {
			return true;
		}
		return false;
	}

	public function dispatch( ipsCore_route $route ) {
		$controller = $route->get_controller();
		$controller_parts = explode( '/', $controller );
		$controller_parts_num = count( $controller_parts );
		if ( $controller_parts_num > 1 ) {
			$controller_parts_last = array_pop( $controller_parts );
			$controller = $controller_parts_last;
		}

		if ( $this->check_controller_exists( $controller ) ) {
			require_once( ipsCore::get_controller_route( $controller ) );
			$controller_name = str_replace( '/', '_', $controller ) . '_controller';

			if ( class_exists( $controller_name ) ) {
				ipsCore::$controller = new $controller_name( $controller );

				if ( !method_exists( ipsCore::$controller, $route->get_method() ) ) {
				    $route->set_action( $route->get_method() );
					$route->set_method( 'index' );
				}
				if ( is_array( $route->get_action() ) ) {
					ipsCore::$controller->{ $route->get_method() }( ...$route->get_action() );
				} else {
					ipsCore::$controller->{ $route->get_method() }( $route->get_action() );
				}
				//} else { // rather than erroring and dieing now just selecting index method
				//	ipsCore::add_error( 'Requested Method "' . $method . '" Does Not Exist' );
				//}
			} else {
				ipsCore::add_error( 'Requested Controller Class "' . $controller_name . '" Does Not Exist' );
			}
		} else {
			ipsCore::add_error( 'Requested Controller "' . $controller . '" Does Not Exist' );
		}
	}

}
