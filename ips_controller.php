<?php // IPS-CORE Controller

class ipsCore_controller {

	protected $name;
	protected $view;

	public $models = [];

	private $reserved_data_keys = array( 'stylesheets', 'scripts', 'page_title', /*'breadcrumbs', 'flash_message'*/ );

	// GETTERS
	public function get_name() { return $this->name; }
	public function get_view() { return $this->view; }

	// SETTERS
	public function set_name( $name ) { $this->name = $name; }
	public function set_view( $view ) { $this->view = $view; }

	public function __construct( $controller ) {
		$this->set_name( $controller );
	}

	public function set_page_title( $title ) {
		ipsCore::$data[ 'page_title' ] = $title;
	}

	public function call_error404() {
		header( 'HTTP/1.0 404 Not Found' );
		$this->set_view( '404' );
		$this->error404();
	}

	public function load_model( $model, $name = false, $table = ' ' ) {
        if ( !$name ) { $name = $model; }
		if ( $model = $this->get_model( $model, $name, $table ) ) {
            $this->{$name} = $model;
        }
	}

    public function get_model( $model, $name = false, $table = ' ' ) {
        if ( !$name ) { $name = $model; }
        if ( $table == ' ' ) { $table = $model; }
        if ( $table !== false ) {
            if (!strpos($table, DB_PREFIX)) {
                $table = DB_PREFIX . $table;
            }
        }
        $name = str_replace( '/', '_', $name );
        $model_name  = str_replace( '/', '_', $model ) . '_model';

        if ( class_exists( $model_name ) ) {
            return new $model_name( $name, $table );
        } else {
            ipsCore::add_error( 'Requested Model Class "' . $model_name . '" Does Not Exist', true );
        }
    }

	public function build_view( $build = 'html', $show_in_layout = true ) {

		if ( $build == 'html' ) {
			if ( !$this->get_view() ) {
				$view_path = $this->get_name() . '/' . ipsCore::$router->get_route()->get_method();
				$this->set_view( $view_path );
			}
			ipsCore::$output = new ips_view( $this->view, $show_in_layout );
		} else {
			ipsCore::$output = new ips_json( $this->view );
			ipsCore::$output_type = 'json';
		}
	}

	public function add_data( array $data_items ) {
		foreach ( $data_items as $data_key => $data_value ) {
			if ( !in_array( $data_key, $this->reserved_data_keys ) ) {
				ipsCore::$data[ $data_key ] = $data_value;
			} else {
				ipsCore::add_error( 'Data key "' . $data_key . '" ( "' . print_r( $data_value, true ) . '" ) is reserved.' );
			}
		}
	}

	public function get_data( $key ) {
		if ( isset( ipsCore::$data[ $key ] ) ) {
			return ipsCore::$data[ $key ];
		} else {
			ipsCore::add_error( 'Data key "' . $key . '" does not exist.' );
		}
	}

	public function add_stylesheet( $stylesheets ) {

		if ( !is_array( $stylesheets ) ) {
			$stylesheets = [ $stylesheets ];
		}

		foreach ( $stylesheets as $stylesheet ) {
			if ( ipsCore::is_environment_live() ) {
                $stylesheet = 'dist/' . $stylesheet . '.min';
			} else {
                $stylesheet = 'src/' . $stylesheet;
			}
			ipsCore::$data[ 'stylesheets' ][] = '/css/' . $stylesheet . '.css';
		}
	}

	public function add_script( $scripts ) {
		if ( !is_array( $scripts ) ) {
			$scripts = [ $scripts ];
		}
		foreach ( $scripts as $script ) {
			if ( ipsCore::is_environment_live() ) {
                $script = 'dist/' . $script . '.min';
			} else {
                $script = 'src/' . $script;
			}
			ipsCore::$data[ 'scripts' ][] = '/js/' . $script . '.js';
		}
	}

	public function add_library( $libs ) {
		if ( !is_array( $libs ) ) {
			$libs = [ $libs ];
		}
		foreach ( $libs as $lib ) {
			ipsCore::$data[ 'librarys' ][] = '/lib/' . $lib;
		}
	}

	public function add_external_script( $scripts ) {
		if ( is_array( $scripts ) ) {
			foreach ( $scripts as $script ) {
				ipsCore::$data[ 'scripts' ][] = $script;
			}
		} else {
			ipsCore::$data[ 'scripts' ][] = $scripts;
		}
	}

	public function get_part( $name, $data = false ) {
        $view = new ips_view( $name, false );
        $this->add_data( $data );
        $view->build();

        return $view->display( true );
    }

	public function set_pagination($model, $current = 1, $options = [])
	{
		$per_page = (isset( $options[ 'per_page'] ) ? $options[ 'per_page'] : 10 );
		$show_around = (isset( $options[ 'show_around'] ) ? $options[ 'show_around'] : 2 );
		$slug = (isset( $options[ 'slug'] ) ? $options[ 'slug'] . '/' : '' );

		$total = $model->count();
		$num_pages = ceil(($total / $per_page));
		$show_pages = $show_around * 2;

		$start_page = $current - $show_around;
		$end_page = $current + $show_around;

		if ($start_page <= 1) {
			$start_page = 1;
			$end_page = $show_pages + 1;
			$start_show = false;
		} else {
			$start_show = true;
		}

		if ($end_page >= $num_pages) {
			$start_page = $num_pages - $show_pages;
			if ( $start_page <= 1 ) {
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
				'current' => false
			];
		}

		$i = $start_page;
		while ($i <= $end_page) {
			$items[] = [
				'href' => ipsCore::$uri_current . $slug . $i,
				'text' => $i,
				'current' => ($i == $current ? true : false)
			];
			$i++;
		}

		if ($end_show) {
			$items[] = [
				'href' => ipsCore::$uri_current . $slug . $num_pages,
				'text' => '...',
				'current' => false
			];
		}

		$this->add_data(['pagination' => $this->get_part('parts/pagination', [
			'pagination_items' => $items,
			'pagination_total' => $total,
			'pagination_previous' => $previous,
			'pagination_next' => $next
		])]);
	}

	public function get_paginated($model, $current_page = 1, $options = [])
	{
		$per_page = (isset( $options[ 'per_page'] ) ? $options[ 'per_page'] : 10 );
		$options[ 'per_page' ] = $per_page;
		$where_extra = (isset( $options[ 'where'] ) ? $options[ 'where'] : [] );

		if ( $where_extra === false ) {
			$where = false;
		} else {
			$where = array_merge( $this->where_live(), $where_extra );
		}

		if (!$current_page) {
			$current_page = 1;
		}
		$offset = ($current_page - 1) * $per_page;

		$this->set_pagination($model, $current_page, $options);

		$order = [$model->get_pkey(), 'DESC'];

		return $model->get_all($where, $order, [$per_page, $offset]);
	}

}
