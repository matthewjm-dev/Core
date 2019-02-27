<?php // IPS-CORE -filename-
// A model represents a database table

class ipsCore_model {

	protected $name;
	protected $table;
	protected $fields;

	// Getters
	public function get_name() { return $this->name; }
	public function get_table() { return $this->table; }

	// Setters
	public function set_name( $name ) { $this->name = $name; }
	public function set_table( $table ) { $this->table = $table; }

	// Construct
	public function __construct( $model, $table = ' ' ) {
		$this->set_name( $model );
		if ( $table == ' ' ) {
			$table = $model;
		}
		$this->set_table( $table );

		ipsCore::$database = new ipsCore_database();
		ipsCore::$session = new ipsCore_session();

		if ( $this->table !== false ) {
			$this->table = ( substr( $table, 0, strlen( DB_PREFIX ) ) === DB_PREFIX ? $table : DB_PREFIX . $table );
			$this->set_schema();
		}
	}

	// Methods
	public function set_schema() {
	    $this->fields = [];

		if ( $this->table && ipsCore::$database->does_table_exist( $this->table ) ) {
			$fields = ipsCore::$database->get_table_schema( $this->table );

			foreach ( $fields as $field ) {
			    $name = $field[ 'Field' ];
			    $type = $field[ 'Type' ];

				$this->$name = false;
				$this->fields[$name] = [ 'type' => $type ];
			}
		}
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

	public function get_all_data( $where = false ) {
		$items = ipsCore::$database->select( $this->table, '*', $where );

		if ( !empty( $items ) ) {
			return $items;
		} return false;
	}

	public function get_all( $where = false ) {
		$items = $this->get_all_data( $where );
		$model = get_class( $this );
		$objects = [];

		if ( !empty( $items ) ) {
			foreach ( $items as $item ) {
				$object = new $model( $this->name, $this->table );
				foreach ( $item as $item_data_key => $item_data ) {
					$object->{ $item_data_key } = $item_data;
				}
				$objects[] = $object;
			}
		}

		if ( !empty( $objects ) ) {
			return $objects;
		} return false;
	}

	public function get( $where ) {
        $item = ipsCore::$database->select( $this->table, '*', $where, 1 );

        if ( !empty( $item ) ) {
            return $item[0];
        } return false;
    }

	public function retrieve( $field, $value = false ) {
	    if ( $value === false ) {
	        $value = $field;
	        if ( isset( $this->fields ) ) {
                $field = key( $this->fields );
            } else {
                $field = 'id';
            }
        }

	    $where = [ $field => $value ];

        $item = ipsCore::$database->select( $this->table, '*', $where )[0];

        if ( $item ) {
            foreach ( $item as $item_data_key => $item_data ) {
                $this->{ $item_data_key } = $item_data;
            }
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

		$fields = [];
		$where = [];
		$first = true;
		$insert = false;

		foreach ( $this->fields as $field_key => $field ) {
			if ( $first ) {
				$first = false;
				if ( $this->{ $field_key } !== false ) {
					$where[ $field_key ] = $this->{ $field_key };
				} else {
					$insert = true;
				}
			} else {
				$fields[ $field_key ] = $this->{ $field_key };
			}
		}

		if ( !empty( $fields ) && ( $insert || !empty( $where ) ) ) {
			if ( $insert ) {
				if ( ipsCore::$database->insert( $this->table, $fields ) ) {
					return true;
				}
			} else {
				if ( ipsCore::$database->update( $this->table, $fields, $where ) ) {
					return true;
				}
			}
		} return false;
	}

}


