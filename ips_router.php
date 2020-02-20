<?php // IPS CORE ROUTER

class ipsCore_router
{
    public $routes = array();
    public $uri;
    protected $route;
    public $route_canonical;
    protected $group_uri = false;
    protected $group_controller = false;

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
            //ipsCore::$uri,
            trim(ipsCore::$uri, '/'),
        ];

        // check for matching routes
        foreach ($uri_variations as $uri) {
            if (isset($this->routes[$uri])) {
                $this->route = $this->routes[$uri];
                $this->route_canonical = $uri;
                $found_route = true;
                break;
            } else {
                $matching_routes = $this->routes;

                // Loop though the URI route parts
                foreach (ipsCore::$uri_parts as $uri_route_part_key => $uri_route_part) {
                    // Loop through remaining matching routes
                    foreach ($matching_routes as $route_key => $route) {
                        // If the route has a part for this URI part, and the route part does not equal *
                        if (isset($route->get_uri_parts()[$uri_route_part_key]) && $route->get_uri_parts()[$uri_route_part_key] != '*') {
                            // If the route part for this URI part does not match
                            if ($route->get_uri_parts()[$uri_route_part_key] != $uri_route_part) {
                                unset($matching_routes[$route_key]); // Remove route from matching routes
                            }
                        }
                    }
                }

                // If there are matching routes remaining
                if (!empty($matching_routes)) {
                    // If there are more than 1 matching routes
                    if (count($matching_routes) > 1) {
                        // Loop through remaining matching routes
                        foreach ($matching_routes as $route_key => $route) {
                            $route_parts = $route->get_uri_parts();
                            if ($route->get_uri_parts_num() > count(ipsCore::$uri_parts) && end($route_parts) != '.') {
                                unset($matching_routes[$route_key]);
                            }
                        }

                        // If we still have matching routes
                        if (!empty($matching_routes)) {
                            // If there are still more than 1 matching routes, choose the 1st longest route
                            if (count($matching_routes) > 1) {
                                $longest_route = reset($matching_routes);
                                foreach ($matching_routes as $route_key => $route) {
                                    if ($route->get_uri_parts_num() > $longest_route->get_uri_parts_num()) {
                                        $longest_route = $route;
                                    }
                                }
                                $matching_routes = [$longest_route];
                            }

                            // We have a matching route!
                            $route = reset($matching_routes);
                            $this->set_matched_route($route);
                            $found_route = true;
                            break;
                        }
                    } else { // We have a matching route!
                        $route = reset($matching_routes);
                        $this->set_matched_route($route);
                        $found_route = true;
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

                        if (method_exists($controller . '_controller', str_replace('-', '_', $path_parts[0]))) {
                            $method = array_shift($path_parts);
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

    private function set_matched_route($route) {
        $this->route = $route;
        $this->route_canonical = ipsCore::$uri;
        ipsCore::$uri_current .= ipsCore::$app->get_uri() . '/' . $this->route->get_controller() . '/';

        $args = ipsCore::$uri_parts;

        $i = 0;
        $to_deduct = 0;
        $parts = $route->get_uri_parts();
        if (end($parts) == '*') {
            $to_deduct = 1;
        }
        while ($i <= (($route->get_uri_parts_num() - 1) - $to_deduct)) {
            unset($args[ $i ]);
            $i++;
        }

        $this->route->set_args($args);
    }

    private function set_group_uri($uri) {
        $this->group_uri = ($this->group_uri ? $this->group_uri . '/' . $uri : $uri);
    }

    private function get_group_uri() {
        return ($this->group_uri ? $this->group_uri : '');
    }

    private function clear_group_uri() {
        $this->group_uri = false;
    }

    private function set_group_controller($controller) {
        $this->group_controller = $controller;
    }

    private function get_group_controller() {
        return $this->group_controller;
    }

    private function clear_group_controller() {
        $this->group_controller = false;
    }

    public function add_route_group($uri, $controller, $routes)
    {
        $this->set_group_uri($uri);

        if ($controller) {
            $this->set_group_controller($controller);
        }

        $routes();

        $this->clear_group_uri();
        $this->clear_group_controller();
    }

    /**
     * Can be called as:
     * add_route('uri', 'controller', 'method', $args)
     * add_route('uri', 'controller', 'method')
     * add_route('uri', 'method')
     * add_route('uri')
     */
    public function add_route()
    {
        $func_args = func_get_args();
        $num_args = count($func_args);
        if (!$num_args || $num_args < 1) {
            ipsCore::add_error('Not enough arguments passed to add_route');
        }

        $uri = $func_args[0];
        $args = [];

        if ($num_args == 4) {
            $controller = $func_args[1];
            $method = $func_args[2];
            $args = $func_args[3];
        }

        if ($num_args == 3) {
            $controller = $func_args[1];
            $method = $func_args[2];
        }

        if ($num_args == 2) {
            $controller = $this->get_group_controller();
            $method = $func_args[1];
        }

        if ($num_args == 1) {
            $controller = $this->get_group_controller();
            $method = str_replace('/', '_', $uri);
        }

        if ($uri != '' && $this->get_group_uri() != '') {
            $full_uri = $this->get_group_uri() . '/' . $uri;
        } elseif ($this->get_group_uri() != '') {
            $full_uri = $this->get_group_uri();
        } else {
            $full_uri = $uri;
        }

        $route = new ipsCore_route($full_uri, $controller, $method, $args);
        $this->routes[$full_uri] = $route;
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
                    //$route->set_action($route->get_method());
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
