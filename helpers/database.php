<?php // IPS-CORE Functions

class ipsCore_database {
	private $db_host = DB_HOST;
	private $db_name = DB_NAME;
	private $db_user = DB_USER;
	private $db_pass = DB_PASS;
	private $query;
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
			ipsCore::add_error( 'Database connection failure: ' . $e->getMessage() );
		}
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
							if ( !$query->bindValue( $name, $value, $type) ) {
								$error = 'Failed to bind parameter: ' . $name . ' ' . $value . ' ' . $type . ' ' . $length . ' to query.';
								ipsCore::add_error( $error );
								die( $error );
							}
						} else {
							$error = 'Query parameter binding failed due to array received. Name: ' . $name;
							ipsCore::add_error( $error );
							die( $error );
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
					die ( 'Failed to execute query.' );
					$result = false;
				}
			}
			catch ( PDOException $e ) {
				ipsCore::add_error( 'Database query failure: ' . $e->getMessage() );
				die ( 'Database query failure: ' . $e->getMessage() );
				return false;
			}
			return $result;
		} else {
			ipsCore::add_error( 'Could not execute query, database not connected.' );
		}
		return false;
	}

	public function insert( $sql, $params ) {
		if ( $this->query( $sql, $params ) ) {
			return $this->connection->lastInsertId();
		} return false;
	}

	public function is_connected() {
		if ( $this->connected ) {
			return true;
		}
		return false;
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