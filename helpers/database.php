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
								$error = 'Failed to bind parameter: ' . $name . ' ' . $value . ' ' . $type . ' ' . $length . ' to query.';
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
		$sql = 'SELECT 1 FROM ' . $table . ' LIMIT 1';

		if ( $this->query( $sql ) !== false ) {
			return true;
		} return false;
	}

	public function get_table_schema( $table ) {
		$sql = 'SHOW COLUMNS FROM ' . $table;

		$schema = $this->query( $sql, [], true );

		return $schema;
	}

	public function create_table( $table, $fields ) {
		if ( !$this->does_table_exist( $table ) ) {
			$primary_key_tag = 'PRIMARY KEY';
			$has_primary = false;
			$sql = 'CREATE TABLE ' . $table . ' (';

			foreach ( $fields as $field ) {
				if ( isset( $field[ 'name' ] ) ) {
					$sql .= $field[ 'name' ] . ' ';
					$sql .= ( isset( $field[ 'type' ] ) ? $field[ 'type' ] : 'text' );
					$sql .= ( isset( $field[ 'length' ] ) ? '(' . $field[ 'length' ] . ')' : '' );

					if ( $has_primary === false ) {
						if ( $key_item = array_search( $primary_key_tag, $field[ 'extras' ] ) ) {
							unshift( $field[ 'extras' ][ $key_item ] );
							$has_primary = $field[ 'name' ];
						}
					}

					$sql .= ( isset( $field[ 'extras' ] ) ? ' ' . implode( ' ', $field[ 'extras' ] ) : '' ) . ', ';
				}
			}

			if ( $has_primary !== false ) {
				$sql .= $primary_key_tag . '(' . $has_primary . ')';
			}

			$sql .= ');';

			$this->query( $sql );
			return true;
		}
        ipsCore::add_error( 'The table ' . $table . ' allready exists.' );
        return false;
	}

	public function remove_table( $table ) {

	}

	public function create_column( $table, $name, $type = 'text', $length = false, $default = false, $extra = false ) {
	    $sql = 'ALTER TABLE ' . $table . ' ADD ' . $name . ' ' . strtoupper( $type );

        $sql .= ( $length ? '(' . $length . ')' : '' );
        $sql .= ( $extra ? ' ' . $extra : '' );
        $sql .= ( $default ? ' DEFAULT ' . ( substr( $default, -2 ) == '()' ? $default : '"' . $default . '"' ) : '' );

	    if ( $this->query( $sql ) ) {
	        return true;
        }
        ipsCore::add_error( 'The column ' . $name . ' could not be created in ' . $table . '.' );
        return false;
    }

	public function select( $table, $fields = '*', $where = false, $limit = false, $join = false, $group = false ) {
		$sql = 'SELECT ' . ( is_array( $fields ) ? implode( ',', $fields ) : $fields ) . ' FROM ' . $table;
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
					$sql .= $where_key . ' = :' . $where_key;
					$params[]  = [ ':' . $where_key, $where_value ];
                    $first = false;
				}
			} else {
				$sql .= '';
			}
		}

		if ( $limit !== false ) {
			$sql .= ' LIMIT ' . $limit;
		}

		if ( $data = $this->query( $sql, $params, true ) ) {
			return $data;
		}
		//ipsCore::add_error( 'Failed to retrieve requested data from ' . $table . '.'  ); // no need to throw error as query might be empty
		return false;
	}

	public function insert( $sql, $params ) {
		if ( $this->query( $sql, $params ) ) {
			return $this->connection->lastInsertId();
		} return false;
	}

	public function update( $sql ) {

	}

	public function delete( $sql ) {

	}

	/*public function get_field($field) {
		if (ips_core_is_valid_field_name($field)) {
			$sql = 'SELECT * FROM ' . DB_PREFIX . 'fields WHERE tag = :tag';
			$params = array(array(':tag', $field, PDO::PARAM_STR, 255));
			$result = $this->query($sql, $params, true);
			return $result;
		}
		return false;
	}

	public function add_field($field) {
		$cur_time = time();
		$sql = 'INSERT INTO ' . DB_PREFIX . 'fields (tag, type, content, parent, date_created, date_modified)
				VALUES (:tag, :type, :content, :parent, :date_created, :date_modified)';
		$params = array(
			array(':tag', $field, PDO::PARAM_STR),
			array(':type', 0, PDO::PARAM_INT),
			array(':content', '', PDO::PARAM_STR),
			array(':parent', '', PDO::PARAM_STR),
			array(':date_created', $cur_time, PDO::PARAM_STR),
			array(':date_modified', $cur_time, PDO::PARAM_STR)
		);
		$result = $this->query($sql, $params);

		if ($result) { return $result; }
		return false;
	}

	public function field_exists($field) {
		$sql = 'SELECT id FROM ' . DB_PREFIX . 'fields WHERE tag = :tag';
		$params = array(array(':tag', $field, PDO::PARAM_STR, 255));
		$result = $this->query($sql, $params, true);

		if ($result) { return true; }
		return false;
	}

	public function get_session($session_id) {
		$sql = 'SELECT * FROM ' . DB_PREFIX . 'sessions WHERE id = :id';
		$params = array(array(':id', $session_id, PDO::PARAM_INT, 11));
		$result = $this->query($sql, $params, true);

		if ($result) { return $result; }
		return false;
	}

	public function create_session($auth, $user_id, $time) {
		$sql = 'INSERT INTO ' . DB_PREFIX . 'sessions (auth, user_id, date_created)
				VALUES (:auth, :user_id, :date_created)';
		$params = array(
			array(':auth', $auth, PDO::PARAM_STR),
			array(':user_id', $user_id, PDO::PARAM_INT),
			array(':date_created', $time, PDO::PARAM_STR),
		);
		$result = $this->query($sql, $params);

		if ($result) { return $result; }
		return false;
	}

	public function get_route_path($route) {
		$or = ""; $i = 0;
		$params = array();
		foreach ($route as $part) {
			if ($i != 0) { $or .= " OR "; }
			$or .= "slug = :route" . $i;
			$params[] = array(':route' . $i, $part, PDO::PARAM_STR, 255);
			$i++;
		}
		$sql = 'SELECT slug, target FROM ' . DB_PREFIX . 'routes WHERE ' . $or;
		$result = $this->query($sql, $params, true);

		if ($result) { return $result; }
		return false;
	}*/

}