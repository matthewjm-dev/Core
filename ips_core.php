<?php // IPS-CORE

class ipsCore
{

    public static $apps;
    public static $app;
    public static $uri;
    public static $uri_parts;
    public static $uri_get;
    public static $uri_current;
    public static $environment;

    public static $var_get;
    public static $var_post;
    public static $var_request;

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

    public static $includes = [];
    public static $helpers = [];
    //public static $helpers_active = [];

    public static $functions;
    public static $database;
    public static $mailer;
    public static $session;
    public static $controller;

    public static $router;
    public static $errors = [];

    public static $request;
    public static $request_type;

    public static $cache = []; // Simple, single request non persistent cache
    public static $cache_key_schema = 'schema_table_columns';

    public static $data = []; // Front end data
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
        self::get_core_app();
    }

    public static function get_core_app() {
        require_once(self::$path_core . 'ips_app.php');
    }

    public static function setup()
    {
        require_once(self::$path_core . 'ips_controller.php');
        require_once(self::$path_core . 'ips_model.php');
        require_once(self::$path_core . 'ips_view.php');
        require_once(self::$path_core . 'ips_router.php');
        require_once(self::$path_core . 'ips_helper.php');

        self::$var_get = (isset($_GET) && !empty($_GET) ? $_GET : []);
        self::$var_post = (isset($_POST) && !empty($_POST) ? $_POST : []);
        self::$var_request = (isset($_REQUEST) && !empty($_REQUEST) ? $_REQUEST : []);

        self::get_includes();
        /*self::find_helpers(self::$path_core_helpers);
        self::find_helpers(self::$path_app_helpers);*/
        self::setup_mailer();
        self::$database = (ipsCore::$app->database['host'] ? new ipsCore_database() : false);
        self::$session = new ipsCore_session();
        self::$functions = new ipsCore_functions();
        self::$router = new ipsCore_router();
        self::$router->init();
    }

    // METHODS
    public static function get_var($name, $type = false) {
        if (!$type) {
            if (isset(self::$var_post[$name])) {
                return self::$var_post[$name];
            } elseif (isset(self::$var_get[$name])) {
                return self::$var_get[$name];
            } elseif (isset(self::$var_request[$name])) {
                return self::$var_request[$name];
            }
        } else {
            if ($type == 'post' && isset(self::$var_post[$name])) {
                return self::$var_post[$name];
            } elseif ($type == 'get' && isset(self::$var_get[$name])) {
                return self::$var_get[$name];
            } elseif ($type == 'request' && isset(self::$var_request[$name])) {
                return self::$var_request[$name];
            }
        }

        return false;
    }

    public static function set_environment()
    {
    	$env_file = ipsCore::$path_base . '/environment.ini';
        if (file_exists($env_file) && $environment_settings = parse_ini_file(ipsCore::$path_base . '/environment.ini', true)) {
			self::$environment = $environment_settings[ 'environment_settings' ][ 'environment' ];
		} else {
        	die('Environment file missing');
		}
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
        if (!$app) {
            $app = ipsCore::$app->get_directory();
        } else {
            $app = ipsCore::get_app_dir_by_name($app);
        }

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
        } else {
            $app = ipsCore::get_app_dir_by_name($app);
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

    public static function requires_core_helper($helpers)
    {
        if (!is_array($helpers)) {
            $helpers = [$helpers];
        }

        foreach ($helpers as $helper) {
            $helper_path = self::$path_core_helpers . $helper . '.php';
            if (file_exists($helper_path)) {
                require_once($helper_path);
            }
        }
    }

    public static function requires_helper($helpers, $app = false)
    {
        if (!$app) {
            $app = ipsCore::$app->get_directory();
        } else {
            $app = ipsCore::get_app_dir_by_name($app);
        }

        if (!is_array($helpers)) {
            $helpers = [$helpers];
        }

        foreach ($helpers as $helper) {
            $helper_path = self::get_helper_route($helper, $app);
            if (file_exists($helper_path)) {
                require_once($helper_path);
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

    public static function get_helper_route($helper, $app = false)
    {
        return self::get_file_route($helper, 'helpers', $app);
    }

    public static function get_object_route($object, $app = false)
    {
        return self::get_file_route($object, 'objects', $app);
    }

    public static function get_view_route($view, $type = 'twig')
    {
        return self::get_file_route($view, 'views', false, $type);
    }

    public static function get_layout_route($layout, $type = 'php')
    {
        return self::get_file_route($layout, 'layouts', false, $type);
    }

    public static function get_part_route($part)
    {
        return self::get_file_route($part, 'parts');
    }

    private static function get_file_route($file, $dir, $app = false, $extension = false)
    {
        $path = ipsCore::$path_app;

        if (!$extension) {
            $extension = 'php';
        }

        if ($app) {
            $path = ipsCore::$path_apps . $app;
        }

        $file = $path . '/' . $dir . '/' . $file . '.' . $extension;

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

    public static function get_part($name, $data = [], $type = 'twig')
    {
        $view = new ips_view($name, false, $type);
        $view->build($data);

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
        if (ipsCore::has_data($key)) {
            return ipsCore::$data[$key];
        } else {
            ipsCore::add_error('Data key "' . $key . '" does not exist.');
        }
    }

    public static function has_data($key)
    {
        if (isset(ipsCore::$data[$key])) {
            return true;
        } else {
            return false;
        }
    }

    /*public static function find_helpers($pattern, $current_dir = '')
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
            }
        }
    }

    public static function requires_helper($helpers, $app = false)
    {
        if (!$app) {
            $app = ipsCore::$app->get_directory();
        }

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
    }*/

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

    public static function setup_mailer() {
        $mailer_file = 'mailer';

        if (ipsCore::$app->mailer['type'] != 'mailer') {
            $mailer_file .= '_' . ipsCore::$app->mailer['type'];
        }

        $mailer = 'ipsCore_' . $mailer_file;

        self::requires_core_helper([$mailer_file]);
        self::$mailer = new $mailer();
    }

    public static function get_app_by_name($name) {
        $name = strtolower($name);
        foreach (ipsCore::$apps as $app) {
            if (strtolower($app->get_name()) == $name) {
                return $app;
            }
        }
        return false;
    }

    public static function get_app_dir_by_name($name) {
        if ($app = self::get_app_by_name($name)) {
            return $app->get_directory();
        }
        return false;
    }

    public static function get_app_uri_by_name($name) {
        if ($app = self::get_app_by_name($name)) {
            return $app->get_uri();
        }
        return false;
    }

    public static function get_app_uri_slashed_by_name($name) {
        if ($app = self::get_app_by_name($name)) {
            return $app->get_uri_slashed();
        }
        return false;
    }

    public static function cache_exists($name) {
        if (isset(ipsCore::$cache[$name])) {
            return true;
        }
        return false;
    }

    public static function set_cache($name, $data) {
        if (!ipsCore::cache_exists($name)) {
            ipsCore::$cache[$name] = $data;
            return true;
        }
        return false;
    }

    public static function update_cache($name, $data) {
        if (ipsCore::cache_exists($name)) {
            ipsCore::set_or_update_cache($name, $data);
            return true;
        }
        return false;
    }

    public static function set_or_update_cache($name, $data) {
        ipsCore::$cache[$name] = $data;
    }

    public static function get_cache($name) {
        if (ipsCore::cache_exists($name)) {
            return ipsCore::$cache[$name];
        }
        return false;
    }

    public static function add_cache($name, $data, $key = false) {
        if (!ipsCore::cache_exists($name)) {
            ipsCore::set_cache($name, []);
        }

        if ($key) {
            if (!isset(ipsCore::get_cache($name)[$key])) {
                ipsCore::$cache[$name][$key] = $data;
            }
        } else {
            ipsCore::$cache[$name][] = $data;
        }
    }

    public static function remove_cache($name) {
        if (ipsCore::cache_exists($name)) {
            unset(ipsCore::$cache[$name]);
        }
        return true;
    }
}
