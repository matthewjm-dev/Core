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

		ipsCore::$database = new ipsCore_database();
		ipsCore::$session = new ipsCore_session();

		if ( $this->table !== false ) {
			$this->table = DB_PREFIX . $model;
			$this->set_schema();
		}
	}

	// Methods
	public function set_schema() {
		if ( $this->table && ipsCore::$database->does_table_exist( $this->table ) ) {
			$fields = ipsCore::$database->get_table_schema( $this->table );

			foreach ( $fields as $field ) {
				$this->$field;
			}
		}
	}

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
		if ( ipsCore::$database->create_table( $this->table ) ) {
			return true;
		} return false;
	}

	public function remove_table() {

	}

	public function get_all( $where ) {
		$items = ipsCore::$database->select( $this->table, '*', $where );

		if ( $items ) {
			return $items;
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


