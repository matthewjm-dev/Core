<?php // IPS-CORE Template

class ips_view {

	protected $template;
	protected $is_twig = false;
	protected $twig_helper;
	protected $content = false;
	protected $show_in_layout;

	// Construct
	public function __construct( $template, $show_in_layout, $twig = false ) {
	    if ( $twig ) {
	        ipsCore::requires_core_helper(['twig']);
	        $this->is_twig = true;
	        $this->twig_helper = new twig_helper();
        }
	    if ( !$this->view_exists( ipsCore::get_view_route( $template, $this->is_twig ) ) ) {
            ipsCore::add_error( 'View "' . $template . '" could not be found' );
        }

		$this->template = $template;
		$this->show_in_layout = $show_in_layout;
	}

	// Methods
	private function view_exists( $view_path ) {
		if ( file_exists( $view_path ) ) {
			return true;
		} return false;
	}

	public function build() {
        extract( ipsCore::$data );

		ob_start();

		if ( $this->show_in_layout !== true && $this->show_in_layout !== false ) {
            $this->include_template($this->show_in_layout, 'get_layout_route');
        } else {
            if ( $this->show_in_layout ) {
                ?><!DOCTYPE html>
                <html dir="ltr" lang="en" class="no-js"><?php
                    $this->include_template('layout/head');
                    ?><body>
                    <div id="header"><?php
                        $this->include_template('layout/header');
                        $this->include_template('layout/nav');
                    ?></div><?php
            }

            $this->include_template($this->template);

            if ( $this->show_in_layout ) {
                $this->include_template('layout/footer');

                    ?></body>
                </html><?php
            }
        }

		$this->content = ob_get_clean();
	}

	public function include_template($path, $route = 'get_view_route') {
        $path_extension = ipsCore::{$route}( $path, $this->is_twig );
        if ( $this->view_exists( $path_extension ) ) {
            if ( $this->is_twig ) {
                $this->twig_helper->render($path, ipsCore::$data);
            } else {
                extract(ipsCore::$data);
                include($path_extension);
            }
        }
    }

	public function display( $return = false) {
		if ( $this->content !== false ) {
		    if ( $return ) {
                return $this->content;
            }
            echo $this->content;
		} else {
			ipsCore::add_error( 'Nothing to Display.' );
		}
	}

}

class ips_json {

	protected $template;
	protected $content;

	// Construct
	public function __construct( $template = false ) {
		$this->template = $template;
	}

	// Methods
	private function view_exists( $view_path ) {
		if ( file_exists( $view_path ) ) {
			return true;
		} return false;
	}

	public function build() {

		if ( $this->template ) {
			extract( ipsCore::$data );
			ob_start();
			$view_path   = ipsCore::get_view_route( $this->template );
			if ( $this->view_exists( $view_path ) ) {
				include( $view_path );
			}

			if (isset(ipsCore::$data[ 'json' ])) {
			    $json = ipsCore::$data[ 'json' ];
                unset( ipsCore::$data[ 'json' ] );
            } else {
                $json = true;
            }

			$data = [ 'html' => ob_get_clean(), 'json' => $json ];
		} else {
			$data = ipsCore::$data['json'];

			if ( isset( $data[ 'errors' ] ) ) {
			    foreach ( ipsCore::$errors as $error ){
                    $data[ 'errors' ][] = $error;
                }
            }
		}

		$this->content = json_encode( $data );
	}

	public function display() {
		if ( $this->content ) {
			echo $this->content;
		} else {
			ipsCore::add_error( 'Nothing to Display.' );
		}
	}
}
