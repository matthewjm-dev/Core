<?php // IPS-CORE Functions

class ipsCore_database
{
    private $db_host = DB_HOST;
    private $db_name = DB_NAME;
    private $db_user = DB_USER;
    private $db_pass = DB_PASS;
    public $connected = false;
    public $connection;
    public $connection_error = false;

    public function __construct()
    {
        try {
            $this->connection = new PDO('mysql:host=' . $this->db_host . ';dbname=' . $this->db_name . ';charset=utf8', $this->db_user, $this->db_pass);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            $this->connection_error = $e->getMessage();
        }
        if (!$this->connection_error) {
            $this->connected = true;
        } else {
            ipsCore::add_error('Database connection failure: ' . $e->getMessage(), true);
        }
    }

    public function is_connected()
    {
        if ($this->connected) {
            return true;
        }

        return false;
    }

    public function query($sql, $params = [], $return_data = false)
    {
        if ($this->is_connected()) {
            try {
                $query = $this->connection->prepare($sql);
                if ($params) {
                    foreach ($params as $key => $param) {
                        $name = $param[0];
                        $value = $param[1];
                        if (!is_array($value)) {
                            if (isset($param[2])) {
                                $type = $param[2];
                            } elseif (is_int($value)) {
                                $type = PDO::PARAM_INT;
                            } else {
                                $type = PDO::PARAM_STR;
                            }
                            if (!$query->bindValue($name, $value, $type)) {
                                $error = 'Failed to bind parameter: ' . $name . ' ' . $value . ' ' . $type . ' to query.';
                                ipsCore::add_error($error, true);
                            }
                        } else {
                            $error = 'Query parameter binding failed due to array received. Name: ' . $name;
                            ipsCore::add_error($error, true);
                        }
                    }
                }
                if ($query->execute()) {
                    if ($return_data) {
                        $result = $query->fetchAll(PDO::FETCH_ASSOC);
                    } else {
                        $result = true;
                    }
                } else {
                    ipsCore::add_error('Failed to execute query.');
                    $result = false;
                }
            } catch (PDOException $e) {
                ipsCore::add_error('Database query failure: ' . $e->getMessage());

                return false;
            }

            return $result;
        } else {
            ipsCore::add_error('Could not execute query, database not connected.');
        }

        return false;
    }

    public function does_table_exist($table)
    {
        $sql = 'SELECT 1 FROM ' . $this->validate($table) . ' LIMIT 1';

        if ($this->query($sql) !== false) {
            return true;
        }

        return false;
    }

    public function get_table_schema($table)
    {
        $sql = 'SHOW COLUMNS FROM ' . $this->validate($table);

        $schema = $this->query($sql, [], true);

        return $schema;
    }

    public function create_table($table, $fields)
    {
        if (!$this->does_table_exist($table)) {
            $primary_key_tag = 'PRIMARY KEY';
            $has_primary = false;
            $first = true;
            $sql = 'CREATE TABLE ' . $this->validate($table) . ' (';

            foreach ($fields as $field_key => $field) {
                if (!$first) {
                    $sql .= ', ';
                } else {
                    $first = false;
                }
                $sql .= '`' . $field_key . '` ';
                $sql .= (isset($field['type']) ? $field['type'] : 'text');
                $sql .= (isset($field['length']) ? '(' . $field['length'] . ')' : '');
                $sql .= (isset($field['extra']) ? ' ' . implode(' ', $field['extra']) : '');
            }

            $sql .= ');';

            if ($this->query($sql)) {
                return true;
            }
            ipsCore::add_error('Failed to create table: ' . $table . '.');

            return false;
        }
        ipsCore::add_error('The table ' . $table . ' allready exists.');

        return false;
    }

    public function modify_table($table, $new_name)
    {
        if (!$this->does_table_exist($new_name)) {
            $sql = 'RENAME TABLE ' . $this->validate($table) . ' TO `' . $this->validate($new_name) . '`';

            if ($this->query($sql)) {
                return true;
            }
            ipsCore::add_error('Failed to rename table: "' . $table . '" to "' . $new_name . '".');

            return false;
        }
        ipsCore::add_error('The table "' . $new_name . '" already exists.');

        return false;
    }

    public function drop_table($table)
    {
        $sql = 'DROP TABLE ' . $this->validate($table);

        if ($this->query($sql)) {
            return true;
        }
        ipsCore::add_error('Failed to drop table: "' . $table . '""');

        return false;
    }

    public function create_column($table, $name, $type = 'text', $length = false, $default = false, $extra = false)
    {
        $sql = 'ALTER TABLE ' . $this->validate($table) . ' ADD `' . $this->validate($name) . '` ' . strtoupper($type);

        $sql .= ($length ? '(' . $length . ')' : '');
        $sql .= ($extra ? ' ' . $extra : '');
        $sql .= ($default ? ' DEFAULT ' . (substr($default, -2) == '()' ? $default : '"' . $default . '"') : '');

        if ($this->query($sql)) {
            return true;
        }
        ipsCore::add_error('The column "' . $name . '" could not be created in "' . $table . '".');

        return false;
    }

    public function modify_column($table, $old_name, $name, $type = 'text', $length = false, $default = false, $extra = false)
    {
        $sql = 'ALTER TABLE ' . $this->validate($table) . ' CHANGE `' . $this->validate($old_name) . '` `' . $this->validate($name) . '` ' . strtoupper($type);

        $sql .= ($length ? '(' . $length . ')' : '');
        $sql .= ($extra ? ' ' . (is_array($extra) ? implode(' ', $extra) : $extra) : '');
        $sql .= ($default ? ' DEFAULT ' . (substr($default, -2) == '()' ? $default : '"' . $default . '"') : '');

        if ($this->query($sql)) {
            return true;
        }
        ipsCore::add_error('The column "' . $name . '" could not be modified in "' . $table . '".');

        return false;
    }

    public function drop_column($table, $column)
    {
        $sql = 'ALTER TABLE ' . $this->validate($table) . ' DROP COLUMN ' . $this->validate($column);

        if ($this->query($sql)) {
            return true;
        }
        ipsCore::add_error('The column "' . $column . '" could not be dropped from "' . $table . '".');

        return false;
    }

    public function select($table, $args)
    { // $fields = '*', $where = false, $order = false, $limit = false, $join = false, $group = false
        $defaults = [
            'fields' => $table . '.*',
            'where' => false,
            'order' => false,
            'limit' => false,
            'join' => false,
            'group' => false,
        ];

        $args = array_merge($defaults, $args);

        $sql = 'SELECT ' . (is_array($args['fields']) ? implode(',', $args['fields']) : $args['fields']) . ' FROM ' . $this->validate($table);
        $params = [];

        if ($args['join'] !== false) {
            if (is_array($args['join'])) {
                $join_sql = '';
                if (isset($args['join']['type'])) {
                    switch ($args['join']['type']) {
                        case 'full':
                            $join_sql .= ' FULL JOIN ';
                            break;
                        case 'inner':
                            $join_sql .= ' INNER JOIN ';
                            break;
                        case 'right':
                            $join_sql .= ' RIGHT JOIN ';
                            break;
                        case 'left':
                        default:
                            $join_sql .= ' LEFT JOIN ';
                            break;
                    }
                } else {
                    $join_sql .= ' LEFT JOIN ';
                }

                if (isset($args['join']['table'])) {
                    $join_sql .= $args['join']['table'] . ' ';
                } else {
                    ipsCore::add_error('Failed to Join in select, "table" statement missing.');
                    $join_sql = false;
                }

                if ($join_sql) {
                    if (isset($args['join']['on'])) {
                        $join_sql .= 'ON ';
                        $join_sql .= (strpos($args['join']['on'][0], ".") !== false ? '' : $table . '.') . $args['join']['on'][0] . ' = ';
                        $join_sql .= (strpos($args['join']['on'][1], ".") !== false ? '' : $args['join']['table'] . '.') . $args['join']['on'][1] . ' ';
                    } else {
                        ipsCore::add_error('Failed to Join in select, "on" statement missing.');
                        $join_sql = false;
                    }
                }

                if ($join_sql) {
                    $sql .= $join_sql;
                }
            } else {
                $sql .= $args['join'];
            }
        }

        if ($args['where'] !== false) {
            if (is_array($args['where'])) {
                $sql .= ' WHERE';
                $first = true;
                foreach ($args['where'] as $where_key => $where_value) {
                    $where_like = false;
                    if ( is_array( $where_value ) && $where_value[1] = 'like') {
                        $where_value = $where_value[0];
                        $where_like = true;
                    }
                    $sql .= (!$first ? ' AND ' : ' ');
                    if (strpos($where_key, ".") === false) {
                        $sql .= '`' . $table . '`.';
                    }
                    if ( $where_like ) {
                        $sql .= '`' . $this->format_key($where_key) . '` LIKE :' . $this->format_param($where_key);
                        $params[] = [':' . $this->format_param($where_key), '%' . $where_value . '%'];
                    } else {
                        $sql .= '`' . $this->format_key($where_key) . '` = :' . $this->format_param($where_key);
                        $params[] = [':' . $this->format_param($where_key), $where_value];
                    }

                    $first = false;
                }
            } else {
                $sql .= $args['where'];
            }
        }

        if ($args['group'] !== false) {

        }

        if ($args['order'] !== false) {
            $sql .= ' ORDER BY ' . $args['order'][0] . ' ' . $args['order'][1];
        }

        if ($args['limit'] !== false) {
            if (is_array($args['limit'])) {
                $sql .= ' LIMIT :limitcount OFFSET :limitoffset';
                $params[] = [':limitcount', (int)$args['limit'][0]];
                $params[] = [':limitoffset', (int)$args['limit'][1]];
            } else {
                $sql .= ' LIMIT ' . $args['limit'];
            }
        }

        if ($data = $this->query($sql, $params, true)) {
            return $data;
        }

        return false;
    }

    public function insert_custom($sql, $params)
    {
        if ($this->query($sql, $params)) {
            return $this->connection->lastInsertId();
        }

        return false;
    }

    public function insert($table, $fields)
    {
        $sql = 'INSERT INTO ' . $this->validate($table) . ' SET ';
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

        if ($this->query($sql, $params)) {
            return $this->connection->lastInsertId();
        }

        return false;
    }

    public function update($table, $fields, $where = false)
    {
        if ($where && is_array($where)) {
            $sql = 'UPDATE ' . $this->validate($table) . ' SET ';
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

            foreach ($where as $where_key => $where_value) {
                $sql .= (!$first ? ' AND ' : ' ');
                $sql .= '`' . $where_key . '` = :' . $where_key;
                $params[] = [':' . $where_key, $where_value];
                $first = false;
            }

            if ($this->query($sql, $params)) {
                return true;
            }
            ipsCore::add_error('Failed to update "' . $table . '".');

            return false;
        }
        ipsCore::add_error('Refused to update "' . $table . '" due to no WHERE clause.');

        return false;
    }

    public function delete($table, $where)
    {
        $sql = 'DELETE FROM ' . $this->validate($table) . ' WHERE ';
        $params = [];
        $first = true;

        foreach ($where as $where_key => $where_value) {
            $sql .= (!$first ? ' AND ' : ' ');
            $sql .= '`' . $where_key . '` = :' . $where_key;
            $params[] = [':' . $where_key, $where_value];
            $first = false;
        }

        if ($this->query($sql, $params)) {
            return true;
        }
        ipsCore::add_error('Failed to delete from "' . $table . '".');

        return false;
    }

    public function validate($string)
    {
        if (preg_match('/^[a-z0-9_]*$/', $string)) {
            return $string;
        }

        ipsCore::add_error('Database query parameter failed to validate.');

        return ' ';
    }

    public function format_param($key)
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '_', $key);
    }

    public function format_key($key)
    {
        return str_replace('.', '`.`', $key);
    }

}
