<?php // IPS-CORE Json View

class ips_json
{
    protected $template;
    protected $path_app      = false;
    protected $content;
    protected $type          = 'twig';
    protected $allowed_types = ['twig', 'php', 'html', 'js', 'css'];
    protected $is_twig       = false;
    protected $twig_helper;

    // Construct
    public function __construct($template = false, $path_app = false, $type = 'twig')
    {
        $this->template = $template;

        if ($path_app) {
            $path_app = ipsCore::get_app_dir_by_name($path_app);
            $this->path_app = $path_app;
        }

        if (in_array($type, $this->allowed_types)) {
            $this->type = $type;
        }

        if ($this->type == 'twig') {
            ipsCore::requires_core_helper(['twig']);
            $this->is_twig = true;
            $this->twig_helper = new twig_helper();
        }
    }

    // Methods
    private function view_exists($view_path)
    {
        if (file_exists($view_path)) {
            return true;
        }
        return false;
    }

    public function build()
    {
        if ($this->template) {
            ob_start();

            $this->include_template($this->template, $this->path_app);

            if (isset(ipsCore::$data['json'])) {
                $json = ipsCore::$data['json'];
                unset(ipsCore::$data['json']);
            } else {
                $json = [];
            }

            //$data = [ 'html' => ob_get_clean(), 'json' => $json ];
            $data = array_merge(['html' => ob_get_clean()], $json);
        } else {
            $data = ipsCore::$data['json'];

            if (isset($data['errors'])) {
                foreach (ipsCore::$errors as $error) {
                    $data['errors'][] = $error;
                }
            }
        }

        $this->content = json_encode($data);
    }

    public function include_template($path, $path_app)
    {
        $path_extension = ipsCore::get_view_route($path, $path_app, $this->type);
        if ($this->view_exists($path_extension)) {
            if ($this->is_twig) {
                $path = 'views/' . $path;
                $path = ($path_app ? $path_app . '/' . $path : '');
                $this->twig_helper->render($path);
            } else {
                extract(ipsCore::$data);
                include($path_extension);
            }
        }
    }

    public function display()
    {
        if ($this->content) {
            echo $this->content;
        } else {
            ipsCore::add_error('Nothing to Display.');
        }
    }
}
