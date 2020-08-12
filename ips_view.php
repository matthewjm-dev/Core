<?php // IPS-CORE View

class ips_view
{

    protected $template;
    protected $path_app      = false;
    protected $show_in_layout;
    protected $content       = false;
    protected $type          = 'twig';
    protected $allowed_types = ['twig', 'php', 'html', 'js', 'css'];
    protected $is_twig       = false;
    protected $twig_helper;
    protected $view_class    = '';

    // Construct
    public function __construct($template, $path_app = false, $show_in_layout = 'main', $type = 'twig', $view_class = false)
    {
        $this->template = $template;

        if ($path_app) {
            $path_app = ipsCore::get_app_dir_by_name($path_app);
        } else {
            $path_app = ipsCore::$app->get_directory();
        }

        $this->path_app = $path_app;

        $this->show_in_layout = $show_in_layout;
        if (is_string($type) && in_array($type, $this->allowed_types)) {
            $this->type = $type;
        }

        if ($this->type == 'twig') {
            ipsCore::requires_core_helper(['twig']);
            $this->is_twig = true;
            $this->twig_helper = new twig_helper();
        }

        if ($view_class) {
            $this->view_class = $view_class;
        }

        if (!$this->view_exists(ipsCore::get_view_route($template, $path_app, $this->type))) {
            ipsCore::add_error('View "' . $template . '" could not be found');
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

    public function set_body_class($classes)
    {
        if (is_array($classes)) {
            foreach ($classes as $class) {
                if ($this->view_class != '') {
                    $this->view_class .= ' ';
                }
                $this->view_class .= $class;
            }
        } else {
            if ($this->view_class != '') {
                $this->view_class .= ' ';
            }
            $this->view_class .= $classes;
        }
    }

    public function get_body_class()
    {
        return $this->view_class;
    }


    public function build($part_data = false)
    {
        ob_start();

        if ($this->show_in_layout !== true && $this->show_in_layout !== false) {
            $this->include_layout($this->show_in_layout, $part_data);
        } else {
            if ($this->show_in_layout) {
                ?><!DOCTYPE html>
                <html dir="ltr" lang="en" class="no-js"><?php
                $this->include_template('layout/head');
                ?><body>
                <div id="header"><?php
                    $this->include_template('layout/header');
                    $this->include_template('layout/nav');
                    ?></div><?php
            }

            $this->include_template($this->template, $part_data, $this->path_app);

            if ($this->show_in_layout) {
                $this->include_template('layout/footer');

                ?></body>
                </html><?php
            }
        }

        $this->content = ob_get_clean();
    }

    public function include_template($path, $data = false, $path_app = false)
    {
        if (!$data) {
            $data = ipsCore::$data;
        }

        $path_extension = ipsCore::get_view_route($path, $path_app, $this->type);
        if ($this->view_exists($path_extension)) {
            if ($this->is_twig) {
                $view_path = ($path_app ? $path_app : ipsCore::$app->get_directory()) . '/views/';
                $path = $view_path . $path;
                ipsCore::add_data(['view_path' => $view_path]);

                $this->twig_helper->render($path, $data);
            } else {
                extract($data);
                include($path_extension);
            }
        }
    }

    public function include_layout($path, $data = false)
    {
        if (!$data) {
            $data = ipsCore::$data;
        }

        $path_extension = ipsCore::get_layout_route($path);
        if ($this->view_exists($path_extension)) {
            extract($data);
            include($path_extension);
        }
    }

    public function display($return = false)
    {
        if ($this->content !== false) {
            if ($return) {
                return $this->content;
            }
            echo $this->content;
        } else {
            ipsCore::add_error('Nothing to Display.');
        }
    }

}