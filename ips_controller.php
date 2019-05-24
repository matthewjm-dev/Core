<?php // IPS-CORE Controller

class ipsCore_controller
{

    protected $name;
    protected $view;
    protected $additional;

    public $models = [];

    // GETTERS
    public function get_name()
    {
        return $this->name;
    }

    public function get_view()
    {
        return $this->view;
    }

    public function get_additional()
    {
        return $this->additional;
    }

    // SETTERS
    public function set_name($name)
    {
        $this->name = $name;
    }

    public function set_view($view)
    {
        $this->view = $view;
    }

    public function set_additional($additional)
    {
        $this->additional = $additional;
    }

    public function __construct($controller, $additional = false)
    {
        $this->set_name($controller);
        $this->set_additional($additional);
    }

    public function set_page_title($title)
    {
        ipsCore::$data['page_title'] = $title;
    }

    public function call_error404()
    {
        header('HTTP/1.0 404 Not Found');
        $this->set_view('404');
        $this->error404();
    }

    public function load_model($model, $name = false, $table = ' ')
    {
        if (!$name) {
            $name = $model;
        }
        if ($model = $this->get_model($model, $name, $table)) {
            $this->{$name} = $model;
        }
    }

    public function get_model($model, $name = false, $table = ' ')
    {
        if (!$name) {
            $name = $model;
        }
        if ($table == ' ') {
            $table = $model;
        }
        if ($table !== false) {
            if (!strpos($table, DB_PREFIX)) {
                $table = DB_PREFIX . $table;
            }
        }
        $name = str_replace('/', '_', $name);
        $model_name = str_replace('/', '_', $model) . '_model';

        if (class_exists($model_name)) {
            return new $model_name($name, $table);
        } else {
            ipsCore::add_error('Requested Model Class "' . $model_name . '" Does Not Exist', true);
        }
    }

    public function build_view(array $args = []) //$show_in_layout = true, $build = 'twig' )
    {
        $defaults = [
            'layout' => true,
            'json' => false,
            'type' => 'twig',
        ];

        $args = array_merge($defaults, $args);

        if ($args['json']) {
            ipsCore::$output = new ips_json($this->view, $args['type']);
            ipsCore::$output_type = 'json';
        } else {
            if (!$this->get_view()) {
                $view_path = $this->get_name() . '/' . ipsCore::$router->get_route()->get_method();
                $this->set_view($view_path);
            }

            ipsCore::$output = new ips_view($this->view, $args['layout'], $args['type']);
        }
    }

    public function add_data(array $data_items)
    {
        ipsCore::add_data($data_items);
    }

    public function add_stylesheet($stylesheets)
    {

        if (!is_array($stylesheets)) {
            $stylesheets = [$stylesheets];
        }

        foreach ($stylesheets as $stylesheet) {
            if (ipsCore::is_environment_live()) {
                $stylesheet = 'dist/' . $stylesheet . '.min';
            } else {
                $stylesheet = 'src/' . $stylesheet;
            }
            ipsCore::$data['stylesheets'][] = '/css/' . $stylesheet . '.css';
        }
    }

    public function add_script($scripts)
    {
        if (!is_array($scripts)) {
            $scripts = [$scripts];
        }
        foreach ($scripts as $script) {
            if (ipsCore::is_environment_live()) {
                $script = 'dist/' . $script . '.min';
            } else {
                $script = 'src/' . $script;
            }
            ipsCore::$data['scripts'][] = '/js/' . $script . '.js';
        }
    }

    public function add_library($libs)
    {
        if (!is_array($libs)) {
            $libs = [$libs];
        }
        foreach ($libs as $lib => $extentions) {
            if (!is_array($extentions)) {
                $extentions = [$extentions];
            }
            foreach ($extentions as $extention) {
                if (in_array($extention, ['min.js', 'js'])) {
                    ipsCore::$data['libraries']['scripts'][] = '/lib/' . $lib . '/' . $lib . '.' . $extention;
                } else {
                    ipsCore::$data['libraries']['styles'][] = '/lib/' . $lib . '/' . $lib . '.' . $extention;
                }
            }
        }
    }

    public function add_external_script($scripts)
    {
        if (is_array($scripts)) {
            foreach ($scripts as $script) {
                ipsCore::$data['scripts'][] = $script;
            }
        } else {
            ipsCore::$data['scripts'][] = $scripts;
        }
    }

    /*public function get_part($name, $data = false)
    {
        $view = new ips_view($name, false);
        $this->add_data($data);
        $view->build();

        return $view->display(true);
    }*/

    protected function set_pagination($args) // ($model, $current = 1, $options = [])
    {
        $current = $args['current_page'];
        $per_page = (isset($args['per_page']) ? $args['per_page'] : 10);
        $show_around = (isset($args['show_around']) ? $args['show_around'] : 2);
        $slug = (isset($args['slug']) ? $args['slug'] . '/' : '');

        $total = $args['model']->count($args['where']);
        $num_pages = ceil(($total / $per_page));
        $show_pages = $show_around * 2;

        $start_page = $current - $show_around;
        $end_page = $current + $show_around;

        $start_item = ($current == 1 ? 1 : ($current * $per_page) - ($per_page - 1));
        $end_item = ($current == 1 ? ($per_page > $total ? $total : $per_page) : (($current * $per_page) > $total ? $total : ($current * $per_page)));

        if ($start_page <= 1) {
            $start_page = 1;
            $end_page = $show_pages + 1;
            $start_show = false;
        } else {
            $start_show = true;
        }

        if ($end_page >= $num_pages) {
            $start_page = $num_pages - $show_pages;
            if ($start_page <= 1) {
                $start_page = 1;
            }
            $end_page = $num_pages;
            $end_show = false;
        } else {
            $end_show = true;
        }

        $previous = ($current != 1 ? ipsCore::$uri_current . $slug . ($current - 1) : false);
        $next = ($current != $num_pages ? ipsCore::$uri_current . $slug . ($current + 1) : false);

        $items = [];

        if ($start_show) {
            $items[] = [
                'href' => ipsCore::$uri_current . $slug . 1,
                'text' => '...',
                'current' => false,
            ];
        }

        $i = $start_page;
        while ($i <= $end_page) {
            $items[] = [
                'href' => ipsCore::$uri_current . $slug . $i,
                'text' => $i,
                'current' => ($i == $current ? true : false),
            ];
            $i++;
        }

        if ($end_show) {
            $items[] = [
                'href' => ipsCore::$uri_current . $slug . $num_pages,
                'text' => '...',
                'current' => false,
            ];
        }

        $this->add_data([
            'pagination' => ipsCore::get_part('parts/pagination', [
                'pagination_items' => $items,
                'pagination_total' => 'Showing ' . $start_item . ' - ' . $end_item . ' of ' . $total,
                'pagination_previous' => $previous,
                'pagination_next' => $next,
            ]),
        ]);
    }

    public function get_filtered_list($args)
    {//$model, $current_page = 1, $options = []) {
        $items = [];

        $defaults = [
            'model' => false,
            'current_page' => 1,
            'per_page' => 10,
            'where' => [],
        ];

        $args = array_merge($defaults, $args);
        if ($args['current_page'] === false) {
            $args['current_page'] = 1;
        }

        if ($args['model']) {
            $items = $this->get_paginated($args);
        }

        return $items;
    }

    protected function get_paginated($args)
    {
        $defaults = [
            'model' => false,
            'current_page' => 1,
            'per_page' => 10,
            'where' => [],
        ];

        $args = array_merge($defaults, $args);

        $items = [];

        if ($args['model']) {

            if ($args['where'] === false) {
                $where = false;
            } else {
                $where = array_merge($this->where_live(), $args['where']);
                $args['where'] = $where;
            }

            $offset = ($args['current_page'] - 1) * $args['per_page'];

            $this->set_pagination($args);

            if (isset($args['order'])) {
                $order = $args['order'];
            } else {
                $order = [$args['model']->get_pkey(), 'DESC'];
            }

            $items = $args['model']->get_all($where, $order, [$args['per_page'], $offset]);
        }

        return $items;
    }

    public function where_live()
    {
        return ['live' => 1, 'removed' => 0];
    }

}
