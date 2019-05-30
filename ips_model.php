<?php // IPS-CORE -filename-
// A model represents a database table

class ipsCore_model
{

    protected $name;
    protected $table;
    protected $fields;
    protected $pkey;

    // Getters
    public function get_name()
    {
        return $this->name;
    }

    public function get_table()
    {
        return $this->table;
    }

    public function get_pkey()
    {
        return $this->pkey;
    }

    public function get_id()
    {
        return $this->{$this->get_pkey()};
    }

    // Setters
    public function set_name($name)
    {
        $this->name = $name;
    }

    public function set_table($table)
    {
        $this->table = $table;
    }

    public function set_pkey($pkey)
    {
        $this->pkey = $pkey;
    }

    // Construct
    public function __construct($model, $table = ' ')
    {
        $this->set_name($model);
        if ($table == ' ') {
            $table = $model;
        }
        $this->set_table($table);

        ipsCore::$database = new ipsCore_database();
        ipsCore::$session = new ipsCore_session();

        if ($this->table !== false) {
            $this->table = (substr($table, 0, strlen(DB_PREFIX)) === DB_PREFIX ? $table : DB_PREFIX . $table);
            $this->set_schema();
        }
    }

    // Methods
    public function add_prefix($text)
    {
        return (substr($text, 0, strlen(DB_PREFIX)) === DB_PREFIX ? $text : DB_PREFIX . $text);
    }

    public function prefix_join($join)
    {
        if ($join) {
            if (isset($join['table'])) {
                $join['table'] = $this->add_prefix($join['table']);
            }

            if (isset($join['on'][0]) && isset($join['on'][1])) {
                if (strpos($join['on'][0], ".") !== false) {
                    $join['on'][0] = $this->add_prefix($join['on'][0]);
                }
                if (strpos($join['on'][1], ".") !== false) {
                    $join['on'][1] = $this->add_prefix($join['on'][1]);
                }
            }
        }

        return $join;
    }

    public function prefix_where($where)
    {
        if ($where) {
            foreach($where as $where_key => $where_item) {
                if (strpos($where_key, ".") !== false) {
                    unset($where[$where_key]);
                    $where[$this->add_prefix($where_key)] = $where_item;
                }
            }
        }

        return $where;
    }

    public function set_schema()
    {
        $this->fields = [];

        if ($this->table && ipsCore::$database->does_table_exist($this->table)) {
            $fields = ipsCore::$database->get_table_schema($this->table);

            foreach ($fields as $field) {
                $name = $field['Field'];
                $type = $field['Type'];

                $this->$name = false;
                $this->fields[$name] = ['type' => $type];

                if ($field['Key'] == 'PRI') {
                    $this->set_pkey($field['Field']);
                }
            }
        }
    }

    private function get_pkey_args()
    {
        return ['type' => 'int', 'length' => 11, 'extra' => ['NOT NULL', 'AUTO_INCREMENT', 'PRIMARY KEY']];
    }

    public function create_table($table, $id = 'id')
    {
        $table = $this->add_prefix($table);
        $fields = [
            $id => $this->get_pkey_args(),
            'created' => ['type' => 'int', 'length' => 11],
            'modified' => ['type' => 'int', 'length' => 11],
            'live' => ['type' => 'tinyint', 'length' => 1],
            'removed' => ['type' => 'tinyint', 'length' => 1],
            'locked' => ['type' => 'tinyint', 'length' => 1],
            'position' => ['type' => 'int', 'length' => 11],
            'title' => ['type' => 'text', 'length' => 255],
        ];

        if (ipsCore::$database->create_table($table, $fields)) {
            return true;
        }
        return false;
    }

    public function modify_table($table, $new_name)
    {
        $table = $this->add_prefix($table);
        $new_name = $this->add_prefix($new_name);

        if (ipsCore::$database->modify_table($table, $new_name)) {
            return true;
        }
        return false;
    }

    public function remove_table($table = false)
    {
    	if ($table === false) {
			$table = $this->table;
		}
		$table = $this->add_prefix($table);

		if (ipsCore::$database->drop_table($table)) {
			return true;
		}
		return false;
    }

    public function modify_pkey($new_name)
    {
        $args = $this->get_pkey_args();

        if ($this->modify_column($this->get_pkey(), $new_name, $args['type'], $args['length'], false, $args['extra'])) {
            $this->set_pkey($new_name);
            return true;
        }
        return false;
    }

    public function create_column($name, $type = 'text', $length = false, $default = false, $extra = false)
    {
        // To Do: Check schema if column already exists

        if (ipsCore::$database->create_column($this->table, $name, $type, $length, $default, $extra)) {
            return true;
        }
        return false;
    }

    public function modify_column($name, $new_name, $type = 'text', $length = false, $default = false, $extra = false)
    {
        // To Do: Check schema if column already exists

        if ($this->table && $name) {
            if (ipsCore::$database->modify_column($this->table, $name, $new_name, $type, $length, $default, $extra)) {
                return true;
            }
        }
        return false;
    }

    public function remove_column($name)
    {
        if ($this->table && $name) {
            if (ipsCore::$database->drop_column($this->table, $name)) {
                return true;
            }
        }
        return false;
    }

    public function get_all_data($where = false, $order = false, $limit = false, $join = false)
    {
        $items = ipsCore::$database->select($this->table, ['where' => $this->prefix_where($where), 'order' => $order, 'limit' => $limit, 'join' => $this->prefix_join($join)]);

        if (!empty($items)) {
            return $items;
        }
        return false;
    }

    public function get_all($where = false, $order = false, $limit = false, $join = false)
    {
        $items = $this->get_all_data($where, $order, $limit, $join);
        $model = get_class($this);
        $objects = [];

        if (!empty($items)) {
            foreach ($items as $item) {
                $object = new $model($this->name, $this->table);
                foreach ($item as $item_data_key => $item_data) {
                    $object->{$item_data_key} = $item_data;
                }
                $objects[] = $object;
            }
        }

        if (!empty($objects)) {
            return $objects;
        }
        return false;
    }

    public function get_all_array($where = false, $order = false, $limit = false, $join = false)
    {
        $items = $this->get_all_data($where, $order, $limit, $join);
        $arrays = [];

        if (!empty($items)) {
            foreach ($items as $item) {
                $array = [];
                foreach ($item as $item_data_key => $item_data) {
                    $array[$item_data_key] = $item_data;
                }
                $arrays[] = $array;
            }
        }

        if (!empty($arrays)) {
            return $arrays;
        }
        return [];
    }

    public function get($where)
    {
        if (!is_array($where)) {
            $where = [$this->get_pkey() => $where];
        }

        $item = ipsCore::$database->select($this->table, ['where' => $this->prefix_where($where), 'limit' => 1]);

        if (!empty($item)) {
            $item = $item[0];
            $model = get_class($this);

            $object = new $model($this->name, $this->table);
            foreach ($item as $item_data_key => $item_data) {
                $object->{$item_data_key} = $item_data;
            }

            return $object;
        }
        return false;
    }

    public function retrieve($where, $order = false, $limit = false, $join = false)
    {
        if (!is_array($where)) {
            $where = [$this->get_pkey() => $where];
        }

        if (isset($join['table'])) {
            $join['table'] = $this->add_prefix($join['table']);
        }

        $item = ipsCore::$database->select($this->table, ['where' => $this->prefix_where($where), 'order' => $order, 'limit' => $limit, 'join' => $join])[0];

        if ($item) {
            foreach ($item as $item_data_key => $item_data) {
                $this->{$item_data_key} = $item_data;
            }
            return true;
        }
        return false;
    }

    public function count($where = false, $join = false)
    {
        $count_str = 'COUNT(*)';
        $count = ipsCore::$database->select($this->table, ['fields' => $count_str, 'where' => $this->prefix_where($where), 'join' => $this->prefix_join($join)]);

        if (!empty( $count ) ) {
            return $count[0][$count_str];
        }
        return false;
    }

    public function modify()
    {

    }

    public function remove( $where = false )
    {
        if ( !$where ) {
            if ( $this->{$this->get_pkey()} ) {
                $where = [
                    $this->get_pkey() => $this->{$this->get_pkey()},
                ];
            }
        }

        if ( $where ) {
            if ( ipsCore::$database->delete( $this->table, $this->prefix_where($where) ) ) {
                return true;
            }
        }
        return false;
    }

    public function save()
    {
        $fields = [];
        $where = [];
        $first = true;
        $insert = false;

        foreach ($this->fields as $field_key => $field) {
            if ($first) {
                $first = false;
                if ($this->{$field_key} !== false) {
                    $where[$field_key] = $this->{$field_key};
                } else {
                    $insert = $field_key;
                }
            } else {
                $fields[$field_key] = $this->{$field_key};
            }
        }

        if (!empty($fields) && ($insert || !empty($where))) {
            if ($insert !== false) {
                if ($id = ipsCore::$database->insert($this->table, $fields)) {
                    $this->{$insert} = $id;
                    return true;
                }
            } else {
                if (ipsCore::$database->update($this->table, $fields, $where)) {
                    return true;
                }
            }
        }
        return false;
    }

}


