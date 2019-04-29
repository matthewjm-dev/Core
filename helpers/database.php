<?php // IPS-CORE Functions

class ipsCore_database {
	private $db_host = DB_HOST;
	private $db_name = DB_NAME;
	private $db_user = DB_USER;
	private $db_pass = DB_PASS;
	public $connected = false;
	public $connection;
	public $connection_error = false;

	public function __construct() {
		try {
			$this->connection = new PDO( 'mysql:host=' . $this->db_host . ';dbname=' . $this->db_name . ';charset=utf8', $this->db_user, $this->db_pass );
			$this->connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		}
		catch( PDOException $e ) {
			$this->connection_error = $e->getMessage();
		}
		if ( !$this->connection_error ) {
			$this->connected = true;
		} else {
			ipsCore::add_error( 'Database connection failure: ' . $e->getMessage(), true );
		}
	}

	public function is_connected() {
		if ( $this->connected ) {
			return true;
		}
		return false;
	}

	public function query( $sql, $params = [], $return_data = false ) {
		if ( $this->is_connected() ) {
			try {
				$query = $this->connection->prepare( $sql );
				if ( $params ) {
					foreach( $params as $key => $param ) {
						$name   = $param[0];
						$value  = $param[1];
						if ( !is_array( $value ) ) {
							$type   = ( isset( $param[2] ) ) ? $param[2] : PDO::PARAM_STR;
							if ( !$query->bindValue( $name, $value, $type ) ) {
								$error = 'Failed to bind parameter: ' . $name . ' ' . $value . ' ' . $type . ' to query.';
								ipsCore::add_error( $error, true );
							}
						} else {
							$error = 'Query parameter binding failed due to array received. Name: ' . $name;
							ipsCore::add_error( $error, true );
						}
					}
				}
				if ( $query->execute() ) {
					if ( $return_data ) {
						$result = $query->fetchAll( PDO::FETCH_ASSOC );
					} else {
						$result = true;
					}
				} else {
					ipsCore::add_error( 'Failed to execute query.' );
					$result = false;
				}
			}
			catch ( PDOException $e ) {
				ipsCore::add_error( 'Database query failure: ' . $e->getMessage() );
				return false;
			}
			return $result;
		} else {
			ipsCore::add_error( 'Could not execute query, database not connected.' );
		}
		return false;
	}

	public function does_table_exist( $table ) {
		$sql = 'SELECT 1 FROM ' . $this->validate( $table ) . ' LIMIT 1';

		if ( $this->query( $sql ) !== false ) {
			return true;
		} return false;
	}

	public function get_table_schema( $table ) {
		$sql = 'SHOW COLUMNS FROM ' . $this->validate( $table );

		$schema = $this->query( $sql, [], true );

		return $schema;
	}

	public function create_table( $table, $fields ) {
		if ( !$this->does_table_exist( $table ) ) {
			$primary_key_tag = 'PRIMARY KEY';
			$has_primary = false;
			$first = true;
			$sql = 'CREATE TABLE ' . $this->validate( $table ) . ' (';

			foreach ( $fields as $field_key => $field ) {
			    if ( !$first ) { $sql .= ', '; } else { $first = false; }
                $sql .= '`' . $field_key . '` ';
                $sql .= ( isset( $field[ 'type' ] ) ? $field[ 'type' ] : 'text' );
                $sql .= ( isset( $field[ 'length' ] ) ? '(' . $field[ 'length' ] . ')' : '' );
                $sql .= ( isset( $field[ 'extra' ] ) ? ' ' . implode( ' ', $field[ 'extra' ] ) : '' );
			}

			$sql .= ');';

			if ( $this->query( $sql ) ) {
                return true;
            }
            ipsCore::add_error( 'Failed to create table: ' . $table . '.' );
            return false;
		}
        ipsCore::add_error( 'The table ' . $table . ' allready exists.' );
        return false;
	}

	public function modify_table( $table, $new_name ) {
        if ( !$this->does_table_exist( $new_name ) ) {
            $sql = 'RENAME TABLE ' . $this->validate( $table ) . ' TO `' . $this->validate( $new_name ) . '`';

            if ( $this->query( $sql ) ) {
                return true;
            }
            ipsCore::add_error( 'Failed to rename table: "' . $table . '" to "' . $new_name . '".' );
            return false;
        }
        ipsCore::add_error( 'The table "' . $new_name . '" already exists.' );
        return false;
    }

	public function remove_table( $table ) {

	}

	public function create_column( $table, $name, $type = 'text', $length = false, $default = false, $extra = false ) {
	    $sql = 'ALTER TABLE ' . $this->validate( $table ) . ' ADD `' . $this->validate( $name ) . '` ' . strtoupper( $type );

        $sql .= ( $length ? '(' . $length . ')' : '' );
        $sql .= ( $extra ? ' ' . $extra : '' );
        $sql .= ( $default ? ' DEFAULT ' . ( substr( $default, -2 ) == '()' ? $default : '"' . $default . '"' ) : '' );

	    if ( $this->query( $sql ) ) {
	        return true;
        }
        ipsCore::add_error( 'The column "' . $name . '" could not be created in "' . $table . '".' );
        return false;
    }

    public function modify_column( $table, $old_name, $name, $type = 'text', $length = false, $default = false, $extra = false ) {
        $sql = 'ALTER TABLE ' . $this->validate( $table ) . ' CHANGE `' . $this->validate( $old_name ) . '` `' . $this->validate( $name ) . '` ' . strtoupper( $type );

        $sql .= ( $length ? '(' . $length . ')' : '' );
        $sql .= ( $extra ? ' ' . (is_array( $extra ) ? explode(' ', $extra) : $extra ) : '' );
        $sql .= ( $default ? ' DEFAULT ' . ( substr( $default, -2 ) == '()' ? $default : '"' . $default . '"' ) : '' );

        if ( $this->query( $sql ) ) {
            return true;
        }
        ipsCore::add_error( 'The column "' . $name . '" could not be modified in "' . $table . '".' );
        return false;
    }

    public function remove_column( $table, $column ) {
	    $sql = 'ALTER TABLE ' . $this->validate( $table ) . ' DROP COLUMN ' . $this->validate( $column );

        if ( $this->query( $sql ) ) {
            return true;
        }
        ipsCore::add_error( 'The column "' . $column . '" could not be dropped from "' . $table . '".' );
        return false;
    }

	public function select( $table, $fields = '*', $where = false, $limit = false, $join = false, $group = false ) {
		$sql = 'SELECT ' . ( is_array( $fields ) ? implode( ',', $fields ) : $fields ) . ' FROM ' . $this->validate( $table );
		$params = [];

		if ( $join !== false ) {

		}

		if ( $group !== false ) {

		}

		if ( $where !== false ) {
			if ( is_array( $where ) ) {
				$sql .= ' WHERE ';
				$first = true;
				foreach ( $where as $where_key => $where_value ) {
				    $sql .= ( !$first ? ' AND ' : ' ' );
					$sql .= '`' . $where_key . '` = :' . $where_key;
					$params[]  = [ ':' . $where_key, $where_value ];
                    $first = false;
				}
			} else {
				$sql .= '';
			}
		}

		if ( $limit !== false ) {
		    if ( is_array( $limit ) ) {
                //$sql .= ' LIMIT :limitoffset, :limitcount';
                //$sql .= ' LIMIT :limitcount OFFSET :limitoffset';
                $sql .= ' LIMIT ' . $limit[0] . ' OFFSET ' . $limit[1];
                $params[]  = [ ':limitcount', $limit[0] ];
                $params[]  = [ ':limitoffset', $limit[1] ];
            } else {
                $sql .= ' LIMIT ' . $limit;
            }
		}

		if ( $data = $this->query( $sql, $params, true ) ) {
			return $data;
		}
		//ipsCore::add_error( 'Failed to retrieve requested data from ' . $table . '.'  ); // no need to throw error as query might be empty
		return false;
	}

	public function insert_custom( $sql, $params ) {
		if ( $this->query( $sql, $params ) ) {
			return $this->connection->lastInsertId();
		} return false;
	}

	public function insert( $table, $fields ) {
		$sql = 'INSERT INTO ' . $this->validate( $table ) . ' SET ';
		$params = [];
		$first = true;

		foreach ($fields as $field_key => $field) {
			if (!$first) {
				$sql .= ', ';
			} else {
				$first = false;
			}

			$sql .= '`' . $field_key . '` = :' . $field_key;
			$params[] = [':' . $field_key, $field];
		}

		if ( $this->query( $sql, $params ) ) {
			return $this->connection->lastInsertId();
		} return false;
	}

	public function update( $table, $fields, $where = false ) {
	    if ( $where && is_array( $where ) ) {
            $sql = 'UPDATE ' . $this->validate( $table ) . ' SET ';
            $params = [];
            $first = true;

            foreach ($fields as $field_key => $field) {
                if (!$first) {
                    $sql .= ', ';
                } else {
                    $first = false;
                }

                $sql .= '`' . $field_key . '` = :' . $field_key;
                $params[] = [':' . $field_key, $field];
            }

			$sql .= ' WHERE';
			$first = true;

			foreach ( $where as $where_key => $where_value ) {
				$sql .= ( !$first ? ' AND ' : ' ' );
				$sql .= '`' . $where_key . '` = :' . $where_key;
				$params[]  = [ ':' . $where_key, $where_value ];
				$first = false;
			}

            if ($this->query($sql, $params)) {
                return true;
            }
            ipsCore::add_error( 'Failed to update "' . $table . '".'  );
            return false;
        }
        ipsCore::add_error( 'Refused to update "' . $table . '" due to no WHERE clause.'  );
	    return false;
	}

	public function delete( $table, $where ) {
        $sql = 'DELETE FROM ' . $this->validate( $table ) . ' WHERE ';
        $params = [];
        $first = true;

        foreach ( $where as $where_key => $where_value ) {
            $sql .= ( !$first ? ' AND ' : ' ' );
            $sql .= '`' . $where_key . '` = :' . $where_key;
            $params[]  = [ ':' . $where_key, $where_value ];
            $first = false;
        }

        if ($this->query($sql, $params)) {
            return true;
        }
        ipsCore::add_error( 'Failed to delete from "' . $table . '".'  );
        return false;
	}

	public function validate( $string ) {
        if ( preg_match( '/^[a-z0-9_]*$/', $string ) ) {
            return $string;
        }

        ipsCore::add_error( 'Database query parameter failed to validate.' );
        return ' ';
    }

}
