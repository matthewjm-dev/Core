<?php // IPS-CORE -filename-
// A model represents a database table

class ipsCore_model {

	protected $name;
	protected $table;

	// Getters
	public function get_name() { return $this->name; }

	// Setters
	public function set_name( $name ) {    $this->name = $name; }

	// Construct
	public function __construct( $model ) {
		$this->set_name( $model );
		$this->table = DB_PREFIX . $model;

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

	public function create_table() {

	}

	public function remove_table() {

	}

	public function get_all( $where ) {



		$sql = 'SELECT *
        FROM ' . $this->table . '
        WHERE ' . $field . ' = :value
        LIMIT 1';
		$params = [
			[ ':value', $value, $data_type ]
		];

		$result = ipsCore::$database->query( $sql, $params, true )[0];

		if ( $result ) {
			return $result;
		} return false;
	}

	public function get_by( $field, $value ) {

	}

	public function add( $to_add ) {

	}

	public function modify() {

	}

	public function remove() {

	}

	public function save() {

	}

}


