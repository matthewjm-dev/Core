<?php /**
 * IPS-CORE Controller
 */

class ipsCore_controller
{

    protected $name;
    protected $view;
    protected $view_app = false;
    protected $view_class = '';
    protected $canonical = '';
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

    public function get_view_app()
    {
        return $this->view_app;
    }

    public function get_view_class()
    {
        return $this->view_class;
    }

    public function get_additional()
    {
        return $this->additional;
    }

    public function get_canonical()
    {
        return $this->canonical;
    }

    // SETTERS
    public function set_name($name)
    {
        $this->name = $name;
    }

    public function set_view($view, $app = false)
    {
        $this->view = $view;
        $this->view_app = $app;
    }

    public function set_view_class($class)
    {
        $this->view_class = $class;
    }

    public function add_view_class($class)
    {
        if ($this->view_class != '') {
            $this->view_class .= ' ';
        }
        $this->view_class .= $class;
    }

    public function set_additional($additional)
    {
        $this->additional = $additional;
    }

    public function set_canonical($canonical)
    {
        ipsCore::$data['canonical_url'] = rtrim(ipsCore::$site_base, '/') . $canonical;
    }

    public function set_page_title($title)
    {
        ipsCore::$data['page_title'] = $title;
    }

    public function set_error404($func = 'call_error404') {
        ipsCore::$router->get_route()->set_method($func);
    }

    /**
     * ipsCore_controller constructor.
     * @param      $controller
     * @param bool $additional
     */
    public function __construct($controller, $additional = false)
    {
        $this->set_name($controller);
        $this->set_additional($additional);
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
            if (!strpos($table, ipsCore::$app->database['prefix'])) {
                $table = ipsCore::$app->database['prefix'] . $table;
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
            'layout' => 'main',
            'json' => false,
            'type' => 'twig',
            'class' => ''
        ];

        $args = array_merge($defaults, $args);

        if ($args['json']) {
            ipsCore::$output = new ips_json($this->view, $this->view_app, $args['type']);
            ipsCore::$output_type = 'json';
        } else {
            if (!$this->get_view()) {
                $view_path = $this->get_name() . '/' . ipsCore::$router->get_route()->get_method();
                $this->set_view($view_path);
            }

            $view_class = $args['class'] . ($args['class'] != '' ? ' ' : '') . $this->get_view_class();

            ipsCore::$output = new ips_view($this->view, $this->view_app, $args['layout'], $args['type'], $view_class);
        }
    }

    public function build_json(array $args = []) //$show_in_layout = true, $build = 'twig' )
    {
        $defaults = [
            'json' => true,
        ];

        $args = array_merge($defaults, $args);

        /*if (!ipsCore::has_data('json')) {
            $this->add_json(['success' => true]);
        }*/

        $this->build_view($args);
    }

    public function add_js_vars(array $vars)
    {
        ipsCore::add_js_vars($vars);
    }

    public function add_data(array $data_items)
    {
        ipsCore::add_data($data_items);
    }

    public function add_json(array $data_items)
    {
        if (ipsCore::has_data('json')) {
            $data_items = array_merge(ipsCore::get_data('json'), $data_items);
        }

        ipsCore::add_data(['json' => $data_items]);
    }

    public function add_json_redirect($url)
    {
        $this->add_json([
            'redirect' => $url,
        ]);
    }

    public function add_json_success($data, $mute = false)
    {
        $args = ['success' => true];

        if (!is_array($data)) {
            $args['success'] = $data;
        } else {
            $args = array_merge($args, $data);
        }

        if ($mute) {
            unset($args['success']);
        }

        $this->add_json($args);
    }

    public function add_json_failure($errors, $mute = false)
    {
        $args = [
            'success' => false,
            'errors' => [],
        ];

        if (!is_array($errors)) {
            $errors = [$errors];
        }

        $args['errors'] = $errors;

        if ($mute) {
            unset($args['success']);
        }

        $this->add_json($args);
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
            if ($extentions === false) {
                ipsCore::$data['libraries']['styles'][] = $lib;
            } else {
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
    }

    public function add_external_script($scripts)
    {
        if (is_array($scripts)) {
            foreach ($scripts as $script_key => $script) {
                if (!isset(ipsCore::$data['scripts'][$script_key])) {
                    ipsCore::$data['scripts'][$script_key] = $script;
                }
            }
        } else {
            if (!isset(ipsCore::$data['scripts'][$scripts])) {
                ipsCore::$data['scripts'][$scripts] = $scripts;
            }
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

        $total = $args['model']->count();
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
        ];

        $args = array_merge($defaults, $args);

        if ($args['current_page'] === false || !is_numeric($args['current_page'])) {
            $args['current_page'] = 1;
        }

        if ($args['model']) {
            $items = $this->get_paginated($args);
        }

        return $items;
    }

    protected function get_paginated($args)
    {
        $items = [];

        if (isset($args['model']) && $args['model']) {

            $defaults = [
                'current_page' => 1,
                'per_page' => 10,
                'orderby' => $args['model']->get_pkey(),
                'order' => 'DESC',
                'include_unlive' => false,
                'include_removed' => false,
            ];

            $args = array_merge($defaults, $args);

            if ($args['current_page'] === false || !is_numeric($args['current_page'])) {
                $args['current_page'] = 1;
            }

            $offset = ($args['current_page'] - 1) * $args['per_page'];

            $args['model']
                ->order($args['orderby'], $args['order'])
                ->limit($args['per_page'], $offset);

            if ($args['model']->has_field('live') && !$args['include_unlive']) {
                $args['model']->where(['live' => 1]);
            }

            if ($args['model']->has_field('removed') && !$args['include_removed']) {
                $args['model']->where(['removed' => 'IS NULL']);
            }

            $this->set_pagination($args);

            $items = $args['model']->get_all();
        }

        return $items;
    }

    public function where_live()
    {
        return ['live' => 1, 'removed' => 0];
    }

}
