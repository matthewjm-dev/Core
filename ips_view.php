<?php // IPS-CORE Template

class ips_view {

	protected $template;
	protected $content;
	protected $show_in_layout;

	// Construct
	public function __construct( $template, $show_in_layout ) {
	    if ( !$this->view_exists( ipsCore::get_view_route( $template ) ) ) {
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

	public function template_part( $name, $data = [] ) {
		$template_path = ipsCore::get_part_route( $name );
		if ( $this->view_exists( $template_path ) ) {
			if ( is_array( $data ) ) {
				extract( $data );
			}
			include( $template_path );
		} else {
			ipsCore::add_error( 'Template part could not be found' );
		}
	}

	public function build() {
		extract( ipsCore::$data );

		ob_start();

		?><!DOCTYPE html>
		<html dir="ltr" lang="en" class="no-js"><?php
			$head_path = ipsCore::get_view_route( 'layout/head' );
			include( $head_path );
			?><body><?php

			if ( $this->show_in_layout ) {
				?><div id="header"><?php
					$header_path = ipsCore::get_view_route( 'layout/header' );
					if ( $this->view_exists( $header_path ) ) {
						include( $header_path );
					}
					$nav_path    = ipsCore::get_view_route( 'layout/nav' );
					if ( $this->view_exists( $nav_path ) ) {
						include( $nav_path );
					}
				?></div><?php
			}

			$view_path = ipsCore::get_view_route( $this->template );
			if ( $this->view_exists( $view_path ) ) {
				include( $view_path );
			}

			if ( $this->show_in_layout ) {
				$footer_path = ipsCore::get_view_route( 'layout/footer' );
				if ( $this->view_exists( $footer_path ) ) {
					include( $footer_path );
				}
			}

			?></body>
		</html><?php

		$this->content = ob_get_clean();
	}

	public function display() {
		if ( $this->content ) {
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
			$json = ipsCore::$data[ 'json' ];
			unset( ipsCore::$data[ 'json' ] );

			$data = [ 'html' => ob_get_clean(), 'json' => $json ];
		} else {
			$data = ipsCore::$data['json'];
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
