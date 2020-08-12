<?php // IPS-CORE App

class ipsCore_app
{
    private $title;
    private $name;
    private $directory;
    private $uri;
    private $uri_slashed;
    private $version;
    private $core_version;
    private $supports_modules = false;

    public $database = [
        'host' => false, 'name' => false, 'user' => false, 'pass' => false, 'prefix' => false,
    ];
    public $mailer = [
        'type' => false, 'from' => false, 'settings' => [],
    ];

    public function __construct($app)
    {
        if (isset($app['app']['name'])) {
            $this->name = $app['app']['name'];
        } else {
            ipsCore::add_error('App.ini missing: App > Name', true);
        }

        if (isset($app['app']['title'])) {
            $this->title = $app['app']['title'];
        } else {
            $this->title = $this->name;
        }

        if (isset($app['app']['dir'])) {
            $this->directory = $app['app']['dir'];
        } else {
            ipsCore::add_error('App.ini missing: directory', true);
        }

        if (isset($app['app']['uri'])) {
            $this->uri = $app['app']['uri'];
            $this->uri_slashed = '/' . $app['app']['uri'] . ($app['app']['uri'] ? '/' : '');
        } else {
            ipsCore::add_error('App.ini missing: App > Uri', true);
        }

        if (isset($app['app']['version'])) {
            $this->version = $app['app']['version'];
        } else {
            ipsCore::add_error('App.ini missing: version', true);
        }

        if (isset($app['core']['version'])) {
            $this->core_version = $app['core']['version'];
        } else {
            ipsCore::add_error('App.ini missing: Core > Version', true);
        }

        if (isset($app['app']['support_modules'])) {
            $this->support_modules = $app['app']['support_modules'];
        }

        $this->load_config();
    }

    // Getters
    public function get_title()
    {
        return $this->title;
    }

    public function get_name()
    {
        return $this->name;
    }

    public function get_lower_name()
    {
        return strtolower($this->name);
    }

    public function get_directory()
    {
        return $this->directory;
    }

    public function get_uri()
    {
        return $this->uri;
    }

    public function get_uri_slashed()
    {
        return $this->uri_slashed;
    }

    public function get_version()
    {
        return $this->version;
    }

    public function get_core_version()
    {
        return $this->core_version;
    }

    public function does_support_modules()
    {
        return $this->support_modules;
    }

    // Methods

    public function load_config() {
        $config_file = ipsCore::$path_apps . 'config-' . $this->get_lower_name() . '.ini';

        if (file_exists($config_file)) {
            $config = parse_ini_file($config_file, true);

            if (isset($config['db-' . ipsCore::$environment])) {
                $this->database['host'] = $config['db-' . ipsCore::$environment]['host'];
                $this->database['name'] =  $config['db-' . ipsCore::$environment]['name'];
                $this->database['user'] =  $config['db-' . ipsCore::$environment]['user'];
                $this->database['pass'] =  $config['db-' . ipsCore::$environment]['password'];
                $this->database['prefix'] =  $config['db-' . ipsCore::$environment]['prefix'];
            }

            if (isset($config['mail-' . ipsCore::$environment])) {
                $configs = $config['mail-' . ipsCore::$environment];

                if (isset($configs['mailer'])) {
                    $this->mailer['type'] = $configs['mailer'];
                    unset($configs['mailer']);
                }

                if (isset($configs['mailer_from'])) {
                    $this->mailer['from'] = $configs['mailer_from'];
                    unset($configs['mailer_from']);
                }

                if (isset($configs['suspend_mail'])) {
                    if ($configs['suspend_mail']) {
                        $this->mailer['suspend_mail'] = true;
                    } else {
                        $this->mailer['suspend_mail'] = false;
                    }
                    unset($configs['suspend_mail']);
                }

                foreach($configs as $mail_config => $mail_config_value) {
                    $this->mailer[$mail_config] = $mail_config_value;
                }
            }

            if (!$this->mailer['type']) {
                $this->mailer['type'] = 'mailer';
            }
            if (!$this->mailer['from']) {
                $this->mailer['from'] = 'Tester <test@example.com>';
            }
            if (!isset($this->mailer['suspend_mail'])) {
                $this->mailer['suspend_mail'] = true;
            }
        } else {
            ipsCore::add_error('App Config (apps/config-' . $this->get_lower_name() . '.ini) missing', true);
        }
    }
}
