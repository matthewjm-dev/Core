<?php // IPS-CORE Functions

class ipsCore_functions {

	public function generate_slug( $name ) {
		return strtolower( str_replace( ' ', '-', $name ) );
	}

	public function redirect( $url ) {
		$path = ipsCore::$site_base . $url;
		header( "Location: " . $path );
	}

	public function is_page( $page ) {
		$cur = ipsCore::$router->get_route()->get_controller();
		if ( $cur == $page ) {
			return true;
		} return false;
	}
}