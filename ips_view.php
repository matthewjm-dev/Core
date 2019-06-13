<?php // IPS-CORE Template

class ips_view {

	protected $template;
    protected $show_in_layout;
    protected $content = false;
	protected $type = 'twig';
    protected $allowed_types = ['twig', 'php', 'html', 'js', 'css'];
	protected $is_twig = false;
	protected $twig_helper;

	// Construct
	public function __construct( $template, $show_in_layout = 'main', $type = 'twig' ) {
        $this->template = $template;
        $this->show_in_layout = $show_in_layout;
        if (is_string($type) && in_array($type, $this->allowed_types)) {
            $this->type = $type;
        }

	    if ( $this->type == 'twig' ) {
	        ipsCore::requires_core_helper(['twig']);
	        $this->is_twig = true;
	        $this->twig_helper = new twig_helper();
        }

	    if ( !$this->view_exists( ipsCore::get_view_route( $template, $this->type ) ) ) {
            ipsCore::add_error( 'View "' . $template . '" could not be found' );
        }
	}

	// Methods
	private function view_exists( $view_path ) {
		if ( file_exists( $view_path ) ) {
			return true;
		} return false;
	}

	public function build() {
		ob_start();

		if ( $this->show_in_layout !== true && $this->show_in_layout !== false ) {
            $this->include_layout($this->show_in_layout);
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

	public function include_template($path) {
        $path_extension = ipsCore::get_view_route( $path, $this->type );
        if ( $this->view_exists( $path_extension ) ) {
            if ( $this->is_twig ) {
                $this->twig_helper->render($path, ipsCore::$data);
            } else {
                extract(ipsCore::$data);
                include($path_extension);
            }
        }
    }

    public function include_layout($path) {
        $path_extension = ipsCore::get_layout_route( $path );
        if ( $this->view_exists( $path_extension ) ) {
            extract(ipsCore::$data);
            include($path_extension);
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
    protected $type = 'twig';
    protected $allowed_types = ['twig', 'php', 'html', 'js', 'css'];
    protected $is_twig = false;
    protected $twig_helper;

	// Construct
	public function __construct( $template = false, $type = 'twig' ) {
        $this->template = $template;
        if (in_array($type, $this->allowed_types)) {
            $this->type = $type;
        }

        if ( $this->type == 'twig' ) {
            ipsCore::requires_core_helper(['twig']);
            $this->is_twig = true;
            $this->twig_helper = new twig_helper();
        }
	}

	// Methods
	private function view_exists( $view_path ) {
		if ( file_exists( $view_path ) ) {
			return true;
		} return false;
	}

	public function build() {
		if ( $this->template ) {
			ob_start();

			$this->include_template($this->template);

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

    public function include_template($path) {
        $path_extension = ipsCore::get_view_route( $path, $this->type );
        if ( $this->view_exists( $path_extension ) ) {
            if ( $this->is_twig ) {
                $this->twig_helper->render($path, ipsCore::$data);
            } else {
                extract(ipsCore::$data);
                include($path_extension);
            }
        }
    }

	public function display() {
		if ( $this->content ) {
			echo $this->content;
		} else {
			ipsCore::add_error( 'Nothing to Display.' );
		}
	}
}
