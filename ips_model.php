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
    public function add_prefix( $text ) {
        return (substr($text, 0, strlen(DB_PREFIX)) === DB_PREFIX ? $text : DB_PREFIX . $text);
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
        $table = $this->add_prefix( $table );
        $fields = [
            $id => $this->get_pkey_args(),
            'created' => ['type' => 'varchar', 'length' => 255],
            'modified' => ['type' => 'varchar', 'length' => 255],
            'live' => ['type' => 'int', 'length' => 11],
            'removed' => ['type' => 'int', 'length' => 11],
        ];

        if (ipsCore::$database->create_table($table, $fields)) {
            return true;
        }
        return false;
    }

    public function modify_table($table, $new_name)
    {
        $table = $this->add_prefix( $table );
        $new_name = $this->add_prefix( $new_name );

        if (ipsCore::$database->modify_table($table, $new_name)) {
            return true;
        }
        return false;
    }

    public function remove_table()
    {

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

    public function get_all_data($where = false)
    {
        $items = ipsCore::$database->select($this->table, '*', $where);

        if (!empty($items)) {
            return $items;
        }
        return false;
    }

    public function get_all($where = false)
    {
        $items = $this->get_all_data($where);
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

    public function get($where)
    {
        $item = ipsCore::$database->select($this->table, '*', $where, 1);

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

    public function retrieve($where)
    {
        if (!is_array($where)) {
            $where = [$this->get_pkey() => $where];
        }

        $item = ipsCore::$database->select($this->table, '*', $where)[0];

        if ($item) {
            foreach ($item as $item_data_key => $item_data) {
                $this->{$item_data_key} = $item_data;
            }
            return true;
        }
        return false;
    }

    public function modify()
    {

    }

    public function remove()
    {

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

