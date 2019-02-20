<?php // IPS-CORE Controller

class ipsCore_controller {

	protected $name;
	protected $view;

	public $models = [];

	private $reserved_data_keys = array( 'stylesheets', 'scripts', 'page_title', 'breadcrumbs', 'flash_message' );

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

	public function load_model( $model, $name = false ) {
		if ( !$name ) { $name = $model; }
		$name = str_replace( '/', '_', $name );
		$model_name  = str_replace( '/', '_', $model ) . '_model';

		if ( class_exists( $model_name ) ) {
			$this->{ $name } = new $model_name( $name );
		} else {
			die( 'Requested Model Class "' . $model_name . '" Does Not Exist' );
			ipsCore::add_error( 'Requested Model Class "' . $model_name . '" Does Not Exist' );
		}
	}

	public function build_view( $build = 'html', $show_in_layout = true ) {

		if ( $build == 'html' ) {
			if ( $flash = $this->model->get_flash() ) {
				ipsCore::$data[ 'flash_message' ] = $flash;
				$this->model->remove_flash();
			} else {
				ipsCore::$data[ 'flash_message' ] = false;
			}
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
		if ( in_array( $key, ipsCore::$data[ $key ] ) ) {
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
			if ( ipsCore::is_environment_dev() ) {
				$stylesheet = 'src/' . $stylesheet;
			} else {
				$stylesheet = 'dist/' . $stylesheet . '.min';
			}
			ipsCore::$data[ 'stylesheets' ][] = '/css/' . $stylesheet . '.css';
		}
	}

	public function add_script( $scripts ) {
		if ( !is_array( $scripts ) ) {
			$scripts = [ $scripts ];
		}
		foreach ( $scripts as $script ) {
			if ( ipsCore::is_environment_dev() ) {
				$script = 'src/' . $script;
			} else {
				$script = 'dist/' . $script . '.min';
			}
			ipsCore::$data[ 'scripts' ][] = '/js/' . $script . '.js';
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

	public function set_breadcrumbs( array $breadcrumbs = [] ) {
		ipsCore::$data[ 'breadcrumbs' ] = $breadcrumbs;
	}

	public function add_flash( $type, $message ) {
		if ( !$this->model->get_flash() ) {
			$this->model->add_flash( [ 'type' => $type, 'message' => $message ] );
		} else {
			ipsCore::add_error( 'A flash message is allready queued.' );
		}
	}

}
