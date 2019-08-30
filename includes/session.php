<?php // IPS-CORE Session

class ipsCore_session {

	function __construct() {
		$this->start();
		session_write_close();
	}

	public function init() {
		register_shutdown_function( 'session_write_close' );
	}

	public function start() {
		if ( $this->started() ) { return TRUE; }

		$this->init();

		session_write_close();

		if ( headers_sent() ) {
			if ( empty( $_SESSION ) ) {
				$_SESSION = array();
			}
		} else {
			// For IE <= 8
			session_cache_limiter( 'must-revalidate' );
			session_start();
		}

		return $this->started();
	}

	public function started() {
		return ( session_status() === PHP_SESSION_ACTIVE );
	}

	public function read( $name ) {
		if ( is_null( $name ) ) { return $_SESSION; }
		if ( empty( $name ) ) { return FALSE; }
		if ( isset( $_SESSION[ $name ] ) ) { return $_SESSION[ $name ]; }
		return NULL;
	}

	public function write( $name, $value ) {
		if ( ! $this->start() ) { return FALSE; }
		if ( empty( $name ) ) { return FALSE; }

		$sessionWrite = $name;
		if ( ! is_array( $sessionWrite ) ) {
			$sessionWrite = array( $name => $value );
		}

		foreach ( $sessionWrite as $key => $value ) {
			$_SESSION[ $key ] = $value;
		}

		session_write_close();

		return true;
	}

	public function check( $name ) {
		if ( empty( $name ) ) { return FALSE; }
		if ( isset( $_SESSION[ $name ] ) && ! is_null( $_SESSION[ $name ] ) ) { return TRUE; }
		return FALSE;
	}

	public function delete( $name ) {
		if ( ! $this->start() ) { return FALSE; }

		if ( $this->check( $name ) ) {
			unset( $_SESSION[ $name ] );
			session_write_close();
			return !$this->check( $name );
		}

		session_write_close();
		return FALSE;
	}

	public function destroy() {
		session_destroy();
		$_SESSION = null;
	}

}
