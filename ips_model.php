<?php // IPS-CORE -filename-
// A model represents a database table

class ipsCore_model
{

    protected $model_name;
    protected $model_table;
    protected $model_fields;
    protected $model_pkey;

    protected $query_join;
    protected $query_join_default = [];
    protected $query_where;
    protected $query_where_default = [];
    protected $query_order;
    protected $query_order_default = false;
    protected $query_orderby;
    protected $query_orderby_default = false;
    protected $query_limit;
    protected $query_limit_default = false;
    protected $query_offset;
    protected $query_offset_default = false;

    protected $current_page = 0;

    // Getters
    public function get_model_name()
    {
        return $this->model_name;
    }

    public function get_model_table()
    {
        return $this->model_table;
    }

    public function get_pkey()
    {
        return $this->model_pkey;
    }

    public function get_id()
    {
        return $this->{$this->get_pkey()};
    }

    public function get_prop($property) {
        if (isset($this->{$property})) {
            return $this->{$property};
        }
        return '';
    }

    // Setters
    public function set_name($name)
    {
        $this->model_name = $name;
    }

    public function set_table($table)
    {
        $this->model_table = $table;
    }

    public function set_pkey($pkey)
    {
        $this->model_pkey = $pkey;
    }

    // Construct
    public function __construct($name, $table = ' ')
    {
        $this->set_name($name);
        if ($table == ' ') {
            $table = $name;
        }
        $this->set_table($table);

        /*ipsCore::$database = new ipsCore_database();
        ipsCore::$session = new ipsCore_session();*/

        $this->reset();

        if ($this->model_table !== false) {
            $this->model_table = (substr($table, 0, strlen(ipsCore::$app->database['prefix'])) === ipsCore::$app->database['prefix'] ? $table : ipsCore::$app->database['prefix'] . $table);
            $this->set_schema();
        }
    }

    // Methods

    public function add_prefix($text)
    {
        return (substr($text, 0, strlen(ipsCore::$app->database['prefix'])) === ipsCore::$app->database['prefix'] ? $text : ipsCore::$app->database['prefix'] . $text);
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

    public function get_query_join()
    {
        if ($this->query_join && $this->query_join !== false) {
            if (!is_array($this->query_join)) {
                $this->query_join = [$this->query_join];
            }

            if (!empty($this->query_join)) {
                foreach($this->query_join as $join_key => $join) {
                    if (isset($join['table'])) {
                        $this->query_join[$join_key]['table'] = $this->add_prefix($join['table']);
                    }

                    if (isset($join['on'][0]) && isset($join['on'][1])) {
                        if (strpos($join['on'][0], ".") !== false) {
                            $this->query_join[$join_key]['on'][0] = $this->add_prefix($join['on'][0]);
                        }
                        if (strpos($join['on'][1], ".") !== false) {
                            $this->query_join[$join_key]['on'][1] = $this->add_prefix($join['on'][1]);
                        }
                    }
                }
            }
        }

        return $this->query_join;
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

    public function get_query_where()
    {
        if ($this->query_where && $this->query_where !== false) {
            if (!is_array($this->query_where)) {
                $this->query_where = [$this->query_where];
            }

            if (!empty($this->query_where)) {
                foreach($this->query_where as $where_key => $where_item) {
                    foreach($where_item['fields'] as $field_key => $field) {
                        $where_field = key($field);
                        $where_value = $field[$where_field];
                        if (strpos($where_field, ".") !== false) {
                            unset($this->query_where[$where_key]['fields'][$field_key]);
                            $this->query_where[$where_key]['fields'][$field_key][$this->add_prefix($where_field)] = $where_value;
                        }
                    }
                }
            }
        }

        return $this->query_where;
    }

    public function set_schema()
    {
        $this->fields = [];

        if ($this->model_table && ipsCore::$database->does_table_exist($this->model_table)) {
            $fields = ipsCore::$database->get_table_schema($this->get_model_table());

            foreach ($fields as $field) {
                $name = $field['Field'];
                $type = $field['Type'];
                $default = $field['Default'];
                $extra = $field['Extra'];

                $this->$name = $default;
                $this->fields[$name] = [
                    'type' => $type,
                    'default' => $default,
                    'extra' => $extra
                ];

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
            'live' => ['type' => 'tinyint', 'length' => 1, 'default' => '0'],
            'removed' => ['type' => 'tinyint', 'length' => 1, 'default' => '0'],
            'locked' => ['type' => 'tinyint', 'length' => 1, 'default' => '0'],
            'position' => ['type' => 'int', 'length' => 11, 'default' => '0'],
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
			$table = $this->get_model_table();
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

        if ($this->modify_column($this->get_pkey(), $new_name, $args['type'], $args['length'], false/*, $args['extra']*/)) {
            $this->set_pkey($new_name);
            return true;
        }
        return false;
    }

    public function create_column($name, $type = 'text', $length = false, $default = false, $extra = false)
    {
        // TODO: Check schema if column already exists

        if (ipsCore::$database->create_column($this->get_model_table(), $name, $type, $length, $default, $extra)) {
            return true;
        }
        return false;
    }

    public function modify_column($name, $new_name, $type = 'text', $length = false, $default = false, $extra = false)
    {
        // TODO: Check schema if column already exists

        if ($this->get_model_table() && $name && $new_name) {
            if (ipsCore::$database->modify_column($this->get_model_table(), $name, $new_name, $type, $length, $default, $extra)) {
                return true;
            }
        }
        return false;
    }

    public function remove_column($name)
    {
        if ($this->get_model_table() && $name) {
            if (ipsCore::$database->drop_column($this->get_model_table(), $name)) {
                return true;
            }
        }
        return false;
    }

    /*public function get_all_data($where = false, $order = false, $limit = false, $join = false) // TODO: remove old method
    {
        $items = ipsCore::$database->select($this->get_model_table(), ['where' => $this->prefix_where($where), 'order' => $order, 'limit' => $limit, 'join' => $this->prefix_join($join)]);

        if (!empty($items)) {
            return $items;
        }
        return false;
    }*/

    /*public function get_all($where = false, $order = false, $limit = false, $join = false) // TODO: remove old method
    {
        $items = $this->get_all_data($where, $order, $limit, $join);
        $model = get_class($this);
        $objects = [];

        if (!empty($items)) {
            foreach ($items as $item) {
                $object = new $model($this->get_model_name(), $this->get_model_table());
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
    }*/

    /*public function get_all_array($where = false, $order = false, $limit = false, $join = false) // TODO: remove old method
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
    }*/

    /*public function get($where) // TODO: remove old method
    {
        if (!is_array($where)) {
            $where = [$this->get_pkey() => $where];
        }

        $item = ipsCore::$database->select($this->get_model_table(), ['where' => $this->prefix_where($where), 'limit' => 1]);

        if (!empty($item)) {
            $item = $item[0];
            $model = get_class($this);

            $object = new $model($this->get_model_name(), $this->get_model_table());
            foreach ($item as $item_data_key => $item_data) {
                $object->{$item_data_key} = $item_data;
            }

            return $object;
        }
        return false;
    }*/

    /*public function retrieve($where, $order = false, $join = false) // TODO: remove old method
    {
        if (!is_array($where)) {
            $where = [$this->get_pkey() => $where];
        }

        if (isset($join['table'])) {
            $join['table'] = $this->add_prefix($join['table']);
        }

        $item = ipsCore::$database->select($this->get_model_table(), ['where' => $this->prefix_where($where), 'order' => $order, 'join' => $join])[0];

        if ($item) {
            foreach ($item as $item_data_key => $item_data) {
                $this->{$item_data_key} = $item_data;
            }
            return true;
        }
        return false;
    }*/

    /* Query Functions */

    public function reset() {
        $this->query_join = $this->query_join_default;
        $this->query_where = $this->query_where_default;
        $this->query_order = $this->query_order_default;
        $this->query_orderby = $this->query_orderby_default;
        $this->query_limit = $this->query_limit_default;
        $this->query_offset = $this->query_offset_default;

        return $this;
    }

    public function addwheretoquery($args) {

        foreach($args['fields'] as $field_key => $field) {
            if (!is_array($field)) {
                unset($args['fields'][$field_key]);
                $args['fields'][] = [$this->get_pkey() => $field];
            }
        }

        $this->query_where[] = $args;
    }

    public function where() {
        $wheres = ['fields' => func_get_args()];
        if (!empty($this->query_where)) {
            $wheres = array_merge($wheres, ['binding' => 'AND']);
        }
        $this->addwheretoquery($wheres);
        return $this;
    }

    public function or_where() {
        $this->addwheretoquery(['binding' => 'OR', 'fields' => func_get_args()]);
        return $this;
    }

    public function and_where() {
        $this->addwheretoquery(['binding' => 'AND', 'fields' => func_get_args()]);
        return $this;
    }

    public function where_in() {
        $this->addwheretoquery(['operator' => 'IN', 'fields' => func_get_args()]);
        return $this;
    }

    public function or_where_in() {
        $this->addwheretoquery(['binding' => 'OR', 'operator' => 'IN', 'fields' => func_get_args()]);
        return $this;
    }

    public function and_where_in() {
        $this->addwheretoquery(['binding' => 'AND', 'operator' => 'IN', 'fields' => func_get_args()]);
        return $this;
    }

    public function where_live() {
        $this->where(['live' => 1], ['removed' => 0]);

        return $this;
    }

    public function order($orderby, $order = 'DESC') {
        $this->query_orderby = $orderby;
        $this->query_order = $order;

        return $this;
    }

    public function limit($limit, $offset = false) {
        $this->query_limit = $limit;
        $this->query_offset = $offset;

        return $this;
    }

    public function offset($offset) {
        $this->query_offset = $offset;

        return $this;
    }

    public function join($args) {
        // TODO: do add prefix to join?
        /*if (isset($join['table'])) {
            $join['table'] = $this->add_prefix($join['table']);
        }*/

        $this->query_join[] = $args;

        return $this;
    }

    public function get_all_data()
    {
        $items = (new ipsCore_query($this->get_model_table()))->select([
            'join' => $this->get_query_join(),
            'where' => $this->get_query_where(),
            'orderby' => $this->query_orderby,
            'order' => $this->query_order,
            'limit' => $this->query_limit,
            'offset' => $this->query_offset,
        ])->process(true);

        if (!empty($items)) {
            return $items;
        }
        return false;
    }

    public function get_all($array_keys = false)
    {
        $items = $this->get_all_data();
        $model = get_class($this);
        $objects = [];

        if (!empty($items)) {
            foreach ($items as $item) {
                $object = new $model($this->get_model_name(), $this->get_model_table());
                foreach ($item as $item_data_key => $item_data) {
                    $object->{$item_data_key} = $item_data;
                }
                if ($array_keys) {
                    $objects[$object->get_id()] = $object;
                } else {
                    $objects[] = $object;
                }
            }
        }

        if (!empty($objects)) {
            return $objects;
        }
        return false;
    }

    public function get_all_array()
    {
        $items = $this->get_all_data();
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

    public function get()
    {
        $args = func_get_args();
        if ($args) {
            if (!is_array($args[0])) {
                $args = [$this->get_pkey() => $args[0]];
                $this->where($args);
            } else {
                $this->where(...$args);
            }
        }

        $item = (new ipsCore_query($this->get_model_table()))->select([
            'where' => $this->get_query_where(),
            'limit' => 1,
        ])->process(true);

        if (!empty($item)) {
            $item = $item[0];
            $model = get_class($this);

            $object = new $model($this->get_model_name(), $this->get_model_table());
            foreach ($item as $item_data_key => $item_data) {
                $object->{$item_data_key} = $item_data;
            }

            return $object;
        }
        return false;
    }

    public function retrieve()
    {
        $args = func_get_args();
        if ($args) {
            if (!is_array($args[0])) {
                $args = [$this->get_pkey() => $args[0]];
                $this->where($args);
            } else {
                $this->where(...$args);
            }
        }

        $item = (new ipsCore_query($this->get_model_table()))->select([
            'join' => $this->get_query_join(),
            'where' => $this->get_query_where($this->query_where),
            'order' => $this->query_order,
            'limit' => 1,
        ])->process(true)[0];

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
        $count = ipsCore::$database->select($this->get_model_table(), ['fields' => $count_str, 'where' => $this->prefix_where($where), 'join' => $this->prefix_join($join)]);

        if (!empty( $count ) ) {
            return $count[0][$count_str];
        }
        return false;
    }

    public function count_new()
    {
        $count_str = 'COUNT(*)';
        $count = (new ipsCore_query($this->get_model_table()))->select([
            'fields' => $count_str,
            'join' => $this->get_query_join(),
            'where' => $this->get_query_where($this->query_where),
        ])->process(true)[0];

        if (!empty( $count ) ) {
            return $count[$count_str];
        }
        return false;
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
            if ( ipsCore::$database->delete( $this->get_model_table(), $this->prefix_where($where) ) ) {
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
                if ($this->{$field_key} !== false && $this->{$field_key} !== null) {
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
                if ($id = ipsCore::$database->insert($this->get_model_table(), $fields)) {
                    $this->{$insert} = $id;
                    return true;
                }
            } else {
                if (ipsCore::$database->update($this->get_model_table(), $fields, $where)) {
                    return true;
                }
            }
        }
        return false;
    }

}
