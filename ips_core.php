<?php // IPS-CORE

class ipsCore
{

    public static $app;
    public static $uri;
    public static $uri_parts;
    public static $uri_get;
    public static $uri_current;
    public static $environment;

    public static $path_base;
    public static $path_core;
    public static $path_core_includes;
    public static $path_core_helpers;
    public static $path_libraries;
    public static $path_apps;
    public static $path_app;
    public static $path_app_helpers;
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

    protected static $reserved_data_keys = ['stylesheets', 'scripts', 'page_title', /*'breadcrumbs', 'flash_message'*/];

    public static function init()
    {
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'PUT':
            case 'POST':
            case 'GET':
            case 'HEAD':
            case 'DELETE':
            case 'OPTIONS':
                self::$request_type = $_SERVER['REQUEST_METHOD'];
                break;
            default:
                self::add_error('Invalid request type: ' . $_SERVER['REQUEST_METHOD']);
                break;
        }

        self::set_environment();
        self::set_timezone();
    }

    public static function setup()
    {
        require_once(self::$path_core . 'ips_controller.php');
        require_once(self::$path_core . 'ips_model.php');
        require_once(self::$path_core . 'ips_view.php');
        require_once(self::$path_core . 'ips_router.php');

        self::get_includes();
        self::find_helpers(self::$path_core_helpers);
        self::find_helpers(self::$path_app_helpers);
        self::$session = new ipsCore_session();
        self::$functions = new ipsCore_functions();
        self::$router = new ipsCore_router();
        self::$router->init();
    }

    // METHODS
    public static function set_environment()
    {
        $environment_settings = parse_ini_file(ipsCore::$path_base . '/environment.ini', true);

        self::$environment = $environment_settings['environment_settings']['environment'];
    }

    public static function is_environment_dev()
    {
        if (self::$environment == 'development') {
            return true;
        }

        return false;
    }

    public static function is_environment_live()
    {
        if (self::$environment == 'live') {
            return true;
        }

        return false;
    }

    public static function add_error($error, $fatal = false)
    {
        if ($fatal) {
            die('Fatal Error: ' . $error);
        } else {
            self::$errors[] = $error;
        }
    }

    public static function has_errors()
    {
        if (!empty (self::$errors)) {
            return true;
        }

        return false;
    }

    public static function display_errors()
    {
        if (self::$output_type == 'html') {
            foreach (self::$errors as $error) {
                echo '<p style="border:1px solid #000;padding:1px 4px;margin:2px 0;">' . $error . '</p>';
            }
        }
    }

    public static function get_includes()
    {
        $includes = glob(self::$path_core_includes . '*.php');
        if ($includes) {
            foreach ($includes as $include) {
                if (is_file($include)) {
                    require_once($include);
                    self::$includes[] = $include;
                }
            }
        }
    }

    public static function requires_controller($controllers, $app = false)
    {
        if (!is_array($controllers)) {
            $controllers = [$controllers];
        }
        foreach ($controllers as $controller) {
            $controller_path = self::get_controller_route($controller, $app);

            if (file_exists($controller_path)) {
                require_once($controller_path);

                return true;
            } else {
                self::add_error('Required Controller "' . $controller . '" does not exist.');

                return false;
            }
        }
    }

    public static function requires_model($models, $app = false)
    {
        if (!$app) {
            $app = ipsCore::$app->get_directory();
        }
        if (!is_array($models)) {
            $models = [$models];
        }
        foreach ($models as $model) {
            $model_path = self::get_model_route($model, $app);
            if (file_exists($model_path)) {
                require_once($model_path);
            } else {
                self::add_error('Required Model "' . $model . '" does not exist.');
            }
        }
    }

    public static function get_controller_route($controller, $app = false)
    {
        return self::get_file_route($controller, 'controllers', $app);
    }

    public static function get_model_route($model, $app = false)
    {
        return self::get_file_route($model, 'models', $app);
    }

    public static function get_object_route($object, $app = false)
    {
        return self::get_file_route($object, 'objects', $app);
    }

    public static function get_view_route($view)
    {
        return self::get_file_route($view, 'views');
    }

    public static function get_layout_route($layout)
    {
        return self::get_file_route($layout, 'layouts');
    }

    public static function get_part_route($part)
    {
        return self::get_file_route($part, 'parts');
    }

    private static function get_file_route($file, $dir, $app = false)
    {
        $path = ipsCore::$path_app;

        if ($app) {
            $path = ipsCore::$path_apps . $app;
        }

        $file = $path . '/' . $dir . '/' . $file . '.php';

        return $file;
    }

    public static function get_additional_controller($controller)
    {
        if (ipsCore::requires_controller($controller)) {

            $controller_name = str_replace('/', '_', $controller) . '_controller';

            if (class_exists($controller_name)) {
                $controller = new $controller_name($controller, true);

                return $controller;
            }
        }

        return false;
    }

    public static function get_part($name, $data)
    {
        $view = new ips_view($name, false);
        ipsCore::add_data($data);
        $view->build();

        return $view->display(true);
    }

    public static function add_data(array $data_items)
    {
        foreach ($data_items as $data_key => $data_value) {
            if (!in_array($data_key, ipsCore::$reserved_data_keys)) {
                ipsCore::$data[$data_key] = $data_value;
            } else {
                ipsCore::add_error('Data key "' . $data_key . '" ( "' . print_r($data_value, true) . '" ) is reserved.');
            }
        }
    }

    public static function get_data($key)
    {
        if (isset(ipsCore::$data[$key])) {
            return ipsCore::$data[$key];
        } else {
            ipsCore::add_error('Data key "' . $key . '" does not exist.');
        }
    }

    public static function find_helpers($pattern, $current_dir = '')
    {
        $helpers = glob($pattern . '*.php');
        foreach ($helpers as $helper) {
            if (is_file($helper)) {
                self::$helpers[] = $current_dir . basename($helper, '.php');
            }
        }

        $directories = glob($pattern . '*', GLOB_ONLYDIR | GLOB_NOSORT);
        if (!empty($directories)) {
            foreach ($directories as $directory) {
                $current_dir = basename($directory) . '/';
                self::find_helpers($directory . '/', $current_dir);
            }
        }
    }

    public static function requires_core_helper($helpers)
    {

        if (!is_array($helpers)) {
            $helpers = [$helpers];
        }

        foreach ($helpers as $helper) {
            if (in_array($helper, self::$helpers) && !in_array($helper, self::$helpers_active)) {
                self::$helpers_active[] = $helper;
                require_once(self::$path_core_helpers . $helper . '.php');
            } else {
                self::add_error('Helper "' . $helper . '" is already active.');
            }
        }
    }

    public static function requires_helper($helpers)
    {

        if (!is_array($helpers)) {
            $helpers = [$helpers];
        }

        foreach ($helpers as $helper) {
            if (in_array($helper, self::$helpers) && !in_array($helper, self::$helpers_active)) {
                self::$helpers_active[] = $helper;
                require_once(self::$path_app_helpers . $helper . '.php');
            } else {
                self::add_error('Helper "' . $helper . '" is already active (ensure name does not conflict with a core helper).');
            }
        }
    }

    public static function build()
    {
        if (self::$controller) {
            if (self::$output !== false) {
                if (!empty(self::$output)) {
                    self::$output->build();
                } else {
                    self::add_error('Output is empty.');
                }
            }
        } else {
            self::add_error('No controller to build with.');
        }
    }

    public static function render()
    {
        if (self::$output) {
            if (self::$output_type == 'html') {

            } elseif (self::$output_type == 'json') {
                header('Content-Type: application/json');

            }
            self::$output->display();
        } else {
            self::add_error('No output type set.');
        }
    }

    public static function set_timezone()
    {
        if (!ini_get('date.timezone')) {
            date_default_timezone_set('GMT');
        }
    }

}

class ipsCore_app
{
    private $name;
    private $directory;
    private $uri;
    private $core_version;

    public function __construct($current_app_dir, $current_app)
    {
        if (isset($current_app['app']['name'])) {
            $this->name = $current_app['app']['name'];
        } else {
            ipsCore::add_error('App Config missing: App > Name', true);
        }
        if (!empty($current_app_dir)) {
            $this->directory = $current_app_dir;
        } else {
            ipsCore::add_error('App Config missing: directory', true);
        }
        if (isset($current_app['app']['uri'])) {
            $this->uri = $current_app['app']['uri'];
        } else {
            ipsCore::add_error('App Config missing: App > Uri', true);
        }
        if (isset($current_app['core']['version'])) {
            $this->core_version = $current_app['core']['version'];
        } else {
            ipsCore::add_error('App Config missing: Core > Version', true);
        }

        if (isset($current_app['db-' . ipsCore::$environment])) {
            define('DB_HOST', $current_app['db-' . ipsCore::$environment]['host']);
            define('DB_NAME', $current_app['db-' . ipsCore::$environment]['name']);
            define('DB_USER', $current_app['db-' . ipsCore::$environment]['user']);
            define('DB_PASS', $current_app['db-' . ipsCore::$environment]['password']);
            define('DB_PREFIX', $current_app['db-' . ipsCore::$environment]['prefix']);
        }
    }

    // Getters
    public function get_name()
    {
        return $this->name;
    }

    public function get_directory()
    {
        return $this->directory;
    }

    public function get_uri()
    {
        return $this->uri;
    }

    public function get_core_version()
    {
        return $this->core_version;
    }
}
