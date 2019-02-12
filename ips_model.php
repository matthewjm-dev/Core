<?php // IPS-CORE -filename-

class ipsCore_model {

	protected $name;

	// Getters
	public function get_name() { return $this->name; }

	// Setters
	public function set_name( $name ) {	$this->name = $name; }

	// Construct
	public function __construct( $model ) {
		$this->set_name( $model );

		ipsCore::$database = new ipsCore_database();
		ipsCore::$session = new ipsCore_session();
	}

	// Methods
	public function get_flash() {
		if ( ipsCore::$session->read( 'flash_message' ) ) {
			return ipsCore::$session->read( 'flash_message' );
		} return false;
	}

	public function add_flash( $content ) {
		ipsCore::$session->write( 'flash_message', $content );
	}

	public function remove_flash() {
		ipsCore::$session->write( 'flash_message', false );
	}

}

