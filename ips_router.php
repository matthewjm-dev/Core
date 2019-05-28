<?php // IPS-ROUTER

class ipsCore_route
{

    protected $uri;
    protected $canonical;
    protected $controller;
    protected $method;
    protected $args;

    // Getters
    public function get_uri()
    {
        return $this->uri;
    }

    public function get_canonical()
    {
        return $this->canonical;
    }

    public function get_controller()
    {
        return $this->controller;
    }

    public function get_method()
    {
        return $this->method;
    }

    public function get_args()
    {
        return $this->args;
    }

    // Setters
    public function set_uri($uri)
    {
        $this->uri = $uri;
    }

    public function set_method($method)
    {
        $this->method = $method;
    }

    public function set_args($args)
    {
        $this->args = $args;
    }

    // Construct
    public function __construct($uri, $controller, $method, $args = [])
    {
        $this->uri = $uri;
        $this->controller = $controller;
        $this->method = $method;
        $this->args = $args;
    }
}

class ipsCore_router
{

    public $routes = array();
    public $uri;
    protected $route;
    public $route_canonical;

    // Getters
    public function get_routes()
    {
        return $this->routes;
    }

    public function get_route()
    {
        return $this->route;
    }

    public function init()
    {
        require_once(ipsCore::$path_app . '/routes.php'); // load App routes
        require_once(ipsCore::$path_apps . 'routes.php'); // load shared routes

        $found_route = false;
        $controller = 'pages';
        $method = 'index';
        $args = false;

        $uri_variations = [
            ipsCore::$uri,
            ipsCore::$uri . '/',
            rtrim(ipsCore::$uri, '/'),
        ];

        // check for matching routes
        foreach ($uri_variations as $uri) {
            if (isset($this->routes[$uri])) {
                $this->route = $this->routes[$uri];
                $this->route_canonical = $uri;
                $found_route = true;
                break;
            } else {
                foreach ($this->routes as $route) {
                    $route_parts = explode('/', $route->get_uri());
                    $num_route_parts = count($route_parts) - 1;
                    foreach ($route_parts as $route_part_key => $route_part) {
                        if (isset(ipsCore::$uri_parts[$route_part_key]) && (ipsCore::$uri_parts[$route_part_key] == $route_part || $route_part === '*')) {
                            if ($route_part_key == $num_route_parts) {
                                $this->route = $route;
                                $this->route_canonical = $uri;

                                if (!$route->get_args()) {
                                    $args = ipsCore::$uri_parts;
                                    unset($args[$route_part_key]);
                                    $this->route->set_args($args);
                                }

                                ipsCore::$uri_current .= ipsCore::$app->get_uri() . '/' . $route->get_controller() . '/';

                                $found_route = true;
                                break;
                            }
                        }
                    }
                    if ($found_route) {
                        break;
                    }
                }
            }
            if ($found_route) {
                break;
            }
        }

        // assemble route
        if (!$found_route) {

            if (ipsCore::$app->get_uri() != '') {
                $appless_uri = str_replace('/' . ipsCore::$app->get_uri() . '/', '/', ipsCore::$uri);
                ipsCore::$uri_current .= '/' . ipsCore::$app->get_uri();
            } else {
                $appless_uri = ipsCore::$uri;
            }
            $path_parts = explode('/', trim($appless_uri, '/'));

            if (!empty($path_parts)) {
                if (isset($path_parts[0]) && $this->check_controller_exists($path_parts[0])) {
                    $controller = array_shift($path_parts);
                    ipsCore::$uri_current .= '/' . $controller;

                    if (!empty($path_parts)) {
                        require_once(ipsCore::get_controller_route($controller));

                        if (method_exists($controller . '_controller', $path_parts[0])) {
                            $method = str_replace('-', '_', array_shift($path_parts));
                            ipsCore::$uri_current .= '/' . $method;
                        } else {
                            $method = 'index';
                        }
                    }

                    if (!empty($path_parts)) {
                        $args = $path_parts;
                    }
                } else {
                    $method = 'call_error404';
                }
            } else {
                // 404
                $method = 'call_error404';
            }

            if (substr(ipsCore::$uri_current, -1) != '/') {
                ipsCore::$uri_current .= '/';
            }

            $this->route = new ipsCore_route(ipsCore::$uri_current, $controller, $method, $args);

        }

        $this->dispatch($this->route);
    }

    public function add_route($uri, $controller, $method, $args = [])
    {
        $route = new ipsCore_route($uri, $controller, $method, $args);
        $this->routes[$uri] = $route;
    }

    public function check_controller_exists($controller)
    {
        if (file_exists(ipsCore::get_controller_route($controller))) {
            return true;
        }

        return false;
    }

    public function dispatch(ipsCore_route $route)
    {
        $controller = $route->get_controller();
        $controller_parts = explode('/', $controller);
        $controller_parts_num = count($controller_parts);
        if ($controller_parts_num > 1) {
            $controller_parts_last = array_pop($controller_parts);
            $controller = $controller_parts_last;
        }

        if ($this->check_controller_exists($controller)) {
            require_once(ipsCore::get_controller_route($controller));
            $controller_name = str_replace('/', '_', $controller) . '_controller';

            if (class_exists($controller_name)) {
                ipsCore::$controller = new $controller_name($controller);

                if (!method_exists(ipsCore::$controller, $route->get_method())) {
                    $route->set_action($route->get_method());
                    $route->set_method('index');
                }
                if (is_array($route->get_args())) {
                    ipsCore::$controller->{$route->get_method()}(...$route->get_args());
                } else {
                    ipsCore::$controller->{$route->get_method()}($route->get_args());
                }
            } else {
                ipsCore::add_error('Requested Controller Class "' . $controller_name . '" Does Not Exist');
            }
        } else {
            ipsCore::add_error('Requested Controller "' . $controller . '" Does Not Exist');
        }
    }

}
