<?php // IPS-CORE -filename-
// A model represents a database table

class ipsCore_model {

	protected $name;
	protected $table;
	protected $fields;

	// Getters
	public function get_name() { return $this->name; }

	// Setters
	public function set_name( $name ) { $this->name = $name; }

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
	    $this->fields = [];

		if ( $this->table && ipsCore::$database->does_table_exist( $this->table ) ) {
			$fields = ipsCore::$database->get_table_schema( $this->table );

			foreach ( $fields as $field ) {
				$this->$field;
				$this->fields[] = $field;
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

	public function create_table( $id = 'id' ) {
	    $fields = [
            $id => [ 'type' => 'int', 'length' => 11, 'extra' => [ 'NOT NULL', 'AUTO INCREMENT', 'PRIMARY KEY' ] ],
            'live' => [ 'int' => 'varchar', 'length' => 11 ],
            'removed' => [ 'int' => 'varchar', 'length' => 11 ],
            'created' => [ 'type' => 'varchar', 'length' => 255 ],
            'modified' => [ 'type' => 'varchar', 'length' => 255 ],
        ];

		if ( ipsCore::$database->create_table( $this->table, $fields ) ) {
			return true;
		} return false;
	}

	public function remove_table() {

	}

	public function add_field( $name, $type = 'text', $length = false, $default = false, $extra = false ) {
	    // To Do: Check schema if column already exists

	    if ( ipsCore::$database->create_column( $this->table, $name, $type, $length, $default, $extra ) ) {
	        return true;
        } return false;
    }

	public function get_all( $where = false ) {
		$items = ipsCore::$database->select( $this->table, '*', $where );

		if ( $items ) {
			return $items;
		} return false;
	}

	public function retrieve( $field, $value ) {
        $where = [];
	    $where[ $field ] = $value;

        $item = ipsCore::$database->select( $this->table, '*', $where );

        if ( $item ) {


            return true;
        } return false;
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


