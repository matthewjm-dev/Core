<?php // IPS-CORE Functions

class ipsCore_database
{
    public $connected = false;
    public $connection;
    public $connection_error = false;

    public function __construct()
    {
        if (ipsCore::$app->database['host']) {
            try {
                $this->connection = new PDO(
                    'mysql:host=' . ipsCore::$app->database['host'] . ';dbname=' . ipsCore::$app->database['name'] . ';charset=utf8',
                    ipsCore::$app->database['user'],
                    ipsCore::$app->database['pass']
                );
                $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                $this->connection_error = $e->getMessage();
            }
            if (!$this->connection_error) {
                $this->connected = true;
            } else {
                ipsCore::add_error('Database connection failure: ' . $e->getMessage(), true);
            }
        } else {
            ipsCore::add_error('Database did not attempt to connect, app database config missing', true);
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
                $this->build_where_query($args['where'], $table, $sql, $params, $first);
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

    public function build_where_query($wheres, $table, &$sql, &$params, &$first)
    {
        foreach ($wheres as $where_key => $where_value) {
            if ($where_key == 'group') {

            } else {
                $where_args = [
                    'value' => '',
                    'operator' => '=',
                    'like' => false,
                    'binding' => 'AND',
                    'sub' => false,
                ];

                if (!is_array($where_value)) {
                    $where_args = array_merge($where_args, ['value' => $where_value]);
                } else {
                    $where_args = array_merge($where_args, $where_value);
                }

                $sql .= (!$first ? ' ' . $where_args['binding'] . ' ' : ' ');
                if (strpos($where_key, ".") === false) {
                    $sql .= '`' . $table . '`.';
                }
                if ($where_args['like']) {
                    $sql .= '`' . $this->format_key($where_key) . '` LIKE :' . $this->format_param($where_key);
                    $params[] = [':' . $this->format_param($where_key), '%' . $where_args['value'] . '%'];
                } else {
                    $sql .= '`' . $this->format_key($where_key) . '` = :' . $this->format_param($where_key);
                    $params[] = [':' . $this->format_param($where_key), $where_args['value']];
                }
            }

            $first = false;
        }
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

class ipsCore_query
{
    public $query_table = '';
    public $query_sql = '';
    public $query_params = [];

    public function __construct($table)
    {
        if (ipsCore::$database->is_connected()) {
            if (ipsCore::$database->does_table_exist($table)) {
                $this->query_table = $table;
            } else {
                ipsCore::add_error('Database table does not exist');
            }
        } else {
            ipsCore::add_error('Database is not currently connected');
        }

        return $this;
    }

    public function process($return = false) {
        if ($return) {
            if ($data = ipsCore::$database->query($this->query_sql, $this->query_params, true)) {
                return $data;
            }
        } else {
            if (ipsCore::$database->query($this->query_sql, $this->query_params)) {
                return true;
            }
        }
        return false;
    }

    public function select($args)
    {
        $defaults = [
            'fields' => $this->query_table . '.*',
            'join' => false,
            'where' => false,
            'orderby' => false,
            'order' => false,
            'group' => false,
            'limit' => false,
            'offset' => false,
        ];

        $args = array_merge($defaults, $args);

        $this->query_sql = 'SELECT ' . (is_array($args['fields']) ? implode(',', $args['fields']) : $args['fields']) . ' FROM ' . $this->validate($this->query_table);

        if (isset($args['join']) && !empty($args['join']) && $args['join'] !== false) {
            if (is_array($args['join'])) {
                foreach($args['join'] as $join) {
                    $join_sql = '';
                    if (isset($join['type'])) {
                        switch ($join['type']) {
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

                    if (isset($join['table'])) {
                        $join_sql .= $join['table'] . ' ';
                    } else {
                        ipsCore::add_error('Failed to Join in select, "table" statement missing.');
                        $join_sql = false;
                    }

                    if ($join_sql) {
                        if (isset($join['on'])) {
                            $join_sql .= 'ON ';
                            $join_sql .= (strpos($join['on'][0], ".") !== false ? '' : $this->query_table . '.') . $join['on'][0] . ' = ';
                            $join_sql .= (strpos($join['on'][1], ".") !== false ? '' : $join['table'] . '.') . $join['on'][1] . ' ';
                        } else {
                            ipsCore::add_error('Failed to Join in select, "on" statement missing.');
                            $join_sql = false;
                        }
                    }

                    if ($join_sql) {
                        $this->query_sql .= $join_sql;
                    }
                }
            } else {
                $this->query_sql .= $args['join'];
            }
        }

        if ($args['where'] !== false) {
            if (is_array($args['where'])) {
                if (!empty($args['where'])) {
                    $this->query_sql .= ' WHERE';
                    $first = true;
                    $this->build_where_query($args['where'], $first);
                }
            } else {
                $this->query_sql .= $args['where'];
            }
        }

        if ($args['group'] !== false) {

        }

        if (isset($args['orderby']) && !empty($args['orderby']) && $args['orderby'] !== false) {
            $this->query_sql .= ' ORDER BY ' . $args['orderby'];

            if (isset($args['order']) && !empty($args['order']) && $args['order'] !== false) {
                $this->query_sql .= ' ' . $args['order'];
            }
        }

        if (isset($args['limit']) && !empty($args['limit']) && $args['limit'] !== false) {
            /*if (is_array($args['limit'])) {
                $this->query_sql .= ' LIMIT ' . $this->add_param('limitcount', (int)$args['limit'][0]);
                $this->query_sql .= ' OFFSET ' . $this->add_param('limitoffset', (int)$args['limit'][1]);
            } else {
                $this->query_sql .= ' LIMIT ' . $args['limit'];
            }*/
            $this->query_sql .= ' LIMIT ' . $this->add_param('limitcount', (int)$args['limit']);

            if (isset($args['offset']) && !empty($args['offset']) && $args['offset'] !== false) {
                $this->query_sql .= ' OFFSET ' . $this->add_param('limitoffset', (int)$args['offset']);
            }
        }

        return $this;
    }

    public function build_where_query($wheres, &$first)
    {
        foreach ($wheres as /*$where_key => */ $where_value) {
            //$where_key = key($where_value);
            //$where_value = $where_value[$where_key];

            if (isset($where_value['binding'])) {
                $this->query_sql .= ' ' . $where_value['binding'] . ' ';
            }

            if (isset($where_value['fields']) && !empty($where_value['fields'])) {
                if (count($where_value['fields']) > 1) {
                    $first = true;
                    $this->query_sql .= '(';
                }

                foreach($where_value['fields'] as $field_value) {
                    $field_key = key($field_value);
                    $field_value = $field_value[$field_key];

                    $field_args = [
                        'value' => '',
                        'operator' => '=',
                        'like' => false,
                        'binding' => 'AND',
                    ];

                    if (!is_array($field_value)) {
                        $field_args = array_merge($field_args, ['value' => $field_value]);
                    } else {
                        $field_args = array_merge($field_args, $field_value);
                    }

                    $this->query_sql .= (!$first && count($where_value['fields']) > 1 ? ' ' . $field_args['binding'] . ' ' : ' ');
                    if (strpos($field_key, ".") === false) {
                        $this->query_sql .= '`' . $this->query_table . '`.';
                    }
                    if ($field_args['like']) {
                        $this->query_sql .= '`' . $this->format_key($field_key) . '` LIKE ' . $this->add_param($field_key, '%' . $field_args['value'] . '%');
                    } else {
                        $this->query_sql .= '`' . $this->format_key($field_key) . '` ' . $field_args['operator'] . ' ' . $this->add_param($field_key, $field_args['value']);
                    }

                    $first = false;
                }

                if (count($where_value['fields']) > 1) {
                    $this->query_sql .= ')';
                }
            }

            /*if ($where_key === 'where_and_group') {
                $this->query_sql .= ' AND (';
                $first = true;
                $this->build_where_query($where_value, $first);
                $this->query_sql .= ') ';
            } elseif ($where_key === 'where_or_group') {
                $this->query_sql .= ' OR (';
                $first = true;
                $this->build_where_query($where_value, $first);
                $this->query_sql .= ') ';
            } else {
                $where_args = [
                    'value' => '',
                    'operator' => '=',
                    'like' => false,
                    'binding' => 'AND',
                    'sub' => false,
                ];

                if (!is_array($where_value)) {
                    $where_args = array_merge($where_args, ['value' => $where_value]);
                } else {
                    $where_args = array_merge($where_args, $where_value);
                }

                $this->query_sql .= (!$first ? ' ' . $where_args['binding'] . ' ' : ' ');
                if (strpos($where_key, ".") === false) {
                    $this->query_sql .= '`' . $this->query_table . '`.';
                }
                if ($where_args['like']) {
                    $this->query_sql .= '`' . $this->format_key($where_key) . '` LIKE ' . $this->add_param($where_key, '%' . $where_args['value'] . '%');
                } else {
                    $this->query_sql .= '`' . $this->format_key($where_key) . '` = ' . $this->add_param($where_key, $where_args['value']);
                }
            }*/

            $first = false;
        }
    }

    public function add_param($field, $value)
    {
        $i = 1;
        $field = ':' . $this->format_param($field);
        $exists = true;
        while ($exists) {
            if ($this->has_param($field, $this->query_params)) {
                $field = $field . $i++;
            } else {
                $exists = false;
                break;
            }
        }
        $this->query_params[] = [$field, $value];

        return $field;
    }

    public function has_param($field)
    {
        if (!empty($this->query_params)) {
            foreach ($this->query_params as $param) {
                if ($field == $param[0]) {
                    return true;
                }
            }
        }

        return false;
    }

    public function format_param($key)
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '_', $key);
    }

    public function format_key($key)
    {
        return str_replace('.', '`.`', $key);
    }

    public function validate($string)
    {
        if (preg_match('/^[a-z0-9_]*$/', $string)) {
            return $string;
        }

        ipsCore::add_error('Database query parameter failed to validate.');

        return ' ';
    }

}
