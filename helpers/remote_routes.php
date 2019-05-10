<?php // IPS-CORE Remote Routes

ipsCore::requires_core_helper( 'database' );

class ipsCore_remote_routes {

	private $temp_db;

	public function __construct() {
		$this->temp_db = new ipsCore_database();
		//$this->load_routes();
	}

	public function get_routes() {
		$sql = 'SELECT uri, route
				FROM ' . DB_PREFIX . 'routes';

		$results = $this->temp_db->query($sql, [], true);

		if ($results) {
			$routes = [];
			foreach ( $results as $result ) {
				$route = [];
				$path = explode( '/', $result['route'] );

				$route['uri'] = $result['uri'];
				$route['controller'] = (isset($path[0])) ? $path[0] : '';
				$route['method'] = (isset($path[1])) ? $path[1] : '';
				$route['action'] = (isset($path[2])) ? $path[2] : '';

				$routes[] = $route;
			}
			return $routes;
		} return false;
	}

	/*public function load_routes() {
		$routes = $this->get_routes();

		foreach ( $routes as $route ) {
			$uri = $route[0];
			$path = explode( '/', $route[1] );
			$controller = (isset($path[0])) ? $path[0] : '';
			$method = (isset($path[1])) ? $path[1] : '';
			$action = (isset($path[2])) ? $path[2] : '';

			ipsCore::$router->add_route( $uri, $controller, $method, $action );
		}
	}*/

}
