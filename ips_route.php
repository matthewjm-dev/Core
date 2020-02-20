<?php // IPS CORE ROUTE

class ipsCore_route
{
    protected $uri;
    protected $uri_parts;
    protected $uri_parts_num;
    protected $canonical;
    protected $controller;
    protected $method;
    protected $args;

    // Getters
    public function get_uri()
    {
        return $this->uri;
    }

    public function get_uri_parts()
    {
        return $this->uri_parts;
    }

    public function get_uri_parts_num()
    {
        return $this->uri_parts_num;
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
        $this->uri_parts = explode('/', $uri);
        $this->uri_parts_num = count($this->uri_parts);
    }

    public function set_controller($controller)
    {
        $this->controller = $controller;
    }

    public function set_method($method)
    {
        $this->method = str_replace('-', '_', $method );
    }

    public function set_args($args)
    {
        $this->args = $args;
    }

    // Construct
    public function __construct($uri, $controller, $method, $args = [])
    {
        $this->set_uri($uri);
        $this->set_controller($controller);
        $this->set_method($method);
        $this->set_args($args);
    }
}