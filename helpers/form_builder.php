<?php // IPS-CORE Form Builder

class ipsCore_form_builder
{

    protected $name;
    protected $method  = 'POST';
    protected $action;
    protected $classes = [];
    protected $fields  = [];
    protected $form_html;

    public static $field_types = [
        'number'          => ['title' => 'Number', 'type' => 'int', 'length' => '11'],
        'price'           => ['title' => 'Price', 'type' => 'int', 'length' => '11'],
        'text'            => ['title' => 'Text Input', 'type' => 'varchar', 'length' => '255'],
        'email'           => ['title' => 'Email Address Input', 'type' => 'varchar', 'length' => '255'],
        'password'        => ['title' => 'Password Input', 'type' => 'varchar', 'length' => '255'],
        'textarea'        => ['title' => 'Text Area', 'type' => 'text', 'length' => false],
        'editor'          => ['title' => 'WYSIWYG Editor', 'type' => 'text', 'length' => false],
        'select'          => ['title' => 'Select', 'type' => 'text', 'length' => false, 'link' => false],
        'selectmulti'     => ['title' => 'Select Multi', 'type' => 'text', 'length' => false, 'link' => false],
        'radio'           => ['title' => 'Radios', 'type' => 'text', 'length' => false, 'link' => false],
        'check'           => ['title' => 'Check Boxes', 'type' => 'text', 'length' => false, 'link' => false],
        'linkselect'      => ['title' => 'Link Select', 'type' => 'varchar', 'length' => '255', 'link' => true],
        'linkselectmulti' => ['title' => 'Link Select Multi', 'type' => 'text', 'length' => false, 'link' => true],
        'linkradio'       => ['title' => 'Link Radios', 'type' => 'varchar', 'length' => '255', 'link' => true],
        'linkcheck'       => ['title' => 'Link Check Boxes', 'type' => 'varchar', 'length' => '255', 'link' => true],
        'datepicker'      => ['title' => 'Date Picker', 'type' => 'varchar', 'length' => '255'],
        'colourpicker'    => ['title' => 'Color Picker', 'type' => 'varchar', 'length' => '255'],
        'file'            => ['title' => 'File Upload', 'type' => 'int', 'length' => '11', 'file' => true],
        'file_multiple'   => ['title' => 'File Upload Multiple', 'type' => 'varcar', 'length' => '255', 'file' => true],
        'image'           => ['title' => 'Image Upload', 'type' => 'int', 'length' => '11', 'file' => false],
        'hidden'          => ['title' => 'Hidden Field', 'type' => 'text', 'unselectable' => true],
        'date'            => ['title' => 'Date Field', 'type' => 'varchar', 'length' => '255'],
        'time'            => ['title' => 'Time Field', 'type' => 'varchar', 'length' => '255'],
        'timestamp'       => ['title' => 'TimeStamp Field', 'type' => 'int', 'length' => '11'],
    ];

    public static $password_complexity = '^\S*(?=\S{6,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])\S*$^';
    public static $password_message    = 'Passwords must be at least 6 characters long, contain at least one Uppercase and Lowercase characters and at least one number.';

    public static $title_options = [
        ['text' => 'Mr', 'value' => 'Mr'],
        ['text' => 'Mrs', 'value' => 'Mrs'],
        ['text' => 'Miss', 'value' => 'Miss'],
        ['text' => 'Ms', 'value' => 'Ms'],
        ['text' => 'Mx', 'value' => 'Mx'],
        ['text' => 'Sir', 'value' => 'Sir'],
        ['text' => 'Dr', 'value' => 'Dr'],
        ['text' => 'Lady', 'value' => 'Lady'],
        ['text' => 'Lord', 'value' => 'Lord'],
    ];

    public static $bool_options = [
        ['text' => 'Yes', 'value' => 1],
        ['text' => 'No', 'value' => 0],
    ];

    public static $currency_symbol = '&pound;';

    public $repeater_name = false;
    public $repeater_row_key = false;
    public $repeater_row = false;

    // Getters
    public function get_name()
    {
        return $this->name;
    }

    public function get_method()
    {
        return $this->method;
    }

    public function get_action()
    {
        return $this->action;
    }

    public function get_classes()
    {
        return $this->classes;
    }

    public function get_fields()
    {
        return $this->fields;
    }

    public function get_form_html()
    {
        return $this->form_html;
    }

    public function get_field_value($field)
    {
        //return (isset($this->fields[$field]['value']) ? $this->fields[$field]['value'] : ( $this->fields[$field]['default'] ? $this->fields[$field]['default'] : NULL ) );
        if (isset($this->fields[$field])) {
            $field_type = self::get_field_types($this->fields[$field]['type']);
            if (isset($this->fields[$field]['value']) && $this->fields[$field]['value'] != '') {
                if ($field_type['type'] == 'int') {
                    $this->fields[$field]['value'] = (int)$this->fields[$field]['value'];
                }
                return $this->fields[$field]['value'];
            } elseif (isset($this->fields[$field]['default']) && $this->fields[$field]['default'] != '') {
                return $this->fields[$field]['default'];
            } else {
                if ($field_type['type'] == 'int' && !$field_type['file']) {
                    if (isset($field_type['default'])) {
                        return $field_type['default'];
                    } else {
                        return 0;
                    }
                } elseif ($field_type['file']) {
                    return $_FILES[$field];
                }
            }
        }
        return null;
    }

    // Setters
    public function set_method($method)
    {
        $this->method = $method;
    }

    public function set_action($action)
    {
        if (substr($action, -1) != '/') {
            $action .= '/';
        }
        $this->action = $action;
    }

    public function set_classes(array $classes)
    {
        $this->classes = $classes;
    }

    public function add_classes(array $classes)
    {
        $this->classes = array_merge($this->classes, $classes);
    }

    public function set_field_value($field, $value)
    {
        $this->fields[$field]['value'] = $value;
    }

    public function form_html($html)
    {
        $this->form_html .= $html;
    }

    public static function add_field_type($key, array $args)
    {
        self::$field_types[$key] = $args;
    }

    // Construct
    public function __construct($name)
    {
        $this->name = $name;
    }

    // Field Types
    public static function get_field_types($type = false, $args = [])
    {
        if (method_exists(static::class, 'add_fields')) {
            static::add_fields();
        //    self::add_fields();
        }

        $fields = self::$field_types;

        if ($type) {
            if (isset($fields[$type])) {
                $field = $fields[$type];
                $field['key'] = $type;

                return $field;
            }

            return false;
        }

        if (!isset($args['showunselectables'])) {
            foreach ($fields as $field_key => $field) {
                if (isset($field['unselectable']) && $field['unselectable']) {
                    unset($fields[$field_key]);
                }
            }
        }

        if (isset($args['dontshows']) && !empty($args['dontshows'])) {
            foreach ($fields as $field_key => $field) {
                foreach ($args['dontshows'] as $dontshow => $dontshow_value) {
                    if (isset($field[$dontshow]) && $field[$dontshow] == $dontshow_value) {
                        unset($fields[$field_key]);
                    }
                }
            }
        }

        return $fields;
    }

    // Methods
    public function add_field($name = false, $label = false, $type = false, array $options)
    {
        $errors = [];

        if (!$name) {
            $errors[] = 'Name is required to create the field';
        }

        if (!$type) {
            $errors[] = 'Field type is required to create the field';
        }

        if (empty($errors)) {
            $id = $this->get_name() . '-' . $name;
            $class = $name . ' ' . $type;
            $value = (isset($options['value']) ? $options['value'] : ($this->repeater_name && isset($this->repeater_row[$name]) ? $this->repeater_row[$name] : null));
            $key = $name . ($this->repeater_name ? '[' . $this->repeater_row_key . ']' : ''); // For fields in a repeater group
            $name = $name . ($this->repeater_name ? '[]' : '');

            $this->fields[$key] = [
                'id'                     => $id,
                'name'                   => $name,
                'label'                  => $label,
                'type'                   => $type,
                'value'                  => $value,
                'default'                => (isset($options['default']) ? $options['default'] : null),
                'options'                => (isset($options['options']) ? $options['options'] : []),
                'required'               => (isset($options['required']) ? $options['required'] : false),
                'placeholder'            => (isset($options['placeholder']) ? $options['placeholder'] : ''),
                'placeholder_selectable' => (isset($options['placeholder_selectable']) ? $options['placeholder_selectable'] : null),
                'comment'                => (isset($options['comment']) ? $options['comment'] : false),
                'classes'                => (isset($options['classes']) ? $options['classes'] . ' ' . $class : $class),
                'fieldset_classes'       => (isset($options['fieldset_classes']) ? $options['fieldset_classes'] . ' ' . $class : $class),
                'disabled'               => (isset($options['disabled']) && $options['disabled'] ? true : false),
                'extra_data'             => (isset($options['extra_data']) && !empty($options['extra_data']) ? $options['extra_data'] : false),
                'repeater_group'         => ($this->repeater_name ? $this->repeater_name : false),
            ];
        } else {
            foreach ($errors as $error) {
                ipsCore::error($error);
            }
        }
    }

    public function remove_field($name)
    {
        if (isset($this->fields[$name])) {
            unset($this->fields[$name]);
            return true;
        }
        return false;
    }

    /* Field Group */
    public function field_group($name, $field_funcs, $options = []) {
        $wrapper = (isset($options['wrapper']) ? $options['wrapper'] : false);
        $classes = (isset($options['classes']) ? $options['classes'] : []);
        $title = (isset($options['title']) ? $options['title'] : false);

        $this->start_section($name, $wrapper, $classes);
        if ($title) {
            $field_title = 'field-group-title-' . $name;
            $this->add_html($field_title, '<p class="form-section-title">' . $title . '</p>');
        }

        $this->add_html('field-group-inner-start-' . $name, '<div class="form-section-inner">');

        $field_funcs();

        $this->add_html('field-group-inner-end-' . $name, '</div>');

        $this->end_section($name);
    }

    /* Repeater Group */
    public function repeater_group($name, $field_funcs, $options)
    {
        $classes = (isset($options['classes']) ? implode(' ', $options['classes']) : '');
        $title = (isset($options['title']) ? $options['title'] : false);
        $data = (isset($options['data']) ? unserialize($options['data']) : []);

        $this->repeater_name = $name;

        $this->add_html('repeater-group-start-' . $name, '
            <div id="repeater-group-' . $name . '" class="form-section repeater-group ' . $classes . '">
                <p class="form-section-title">' . $title . '</p>
                <div class="repeater-group-fields">');

        $row_start = '<div class="repeater-group-item">';
        $row_end = '<div class="repeater-group-item-remove"><i class="fa fa-trash"></i></div></div>';

        if (empty($data)) {
            $this->add_html('repeater-group-row-start-' . $name, $row_start);
            $field_funcs();
            $this->add_html('repeater-group-row-end-' . $name, $row_end);
        } else {
            foreach ($data as $row_key => $row) {
                $this->repeater_row_key = $row_key;
                $this->repeater_row = $row;
                $this->add_html('repeater-group-row-start-' . $row_key . '-' . $name, $row_start);
                $field_funcs();
                $this->add_html('repeater-group-row-end-' . $row_key . '-' . $name, $row_end);
            }
        }

        $this->add_html('repeater-group-end' . $name, '
                </div>
                <button class="repeater-group-add">Add New</button>
            </div>');

        $this->repeater_name = false;
        $this->repeater_row_key = false;
        $this->repeater_row = false;
    }

    /* Start Section */
    public function start_section($name, $wrapper = false, $classes = [])
    {
        $classes[] = $name;

        if ($wrapper) {
            $this->fields['section_start_wrapper_' . $name] = [
                'name'        => $name . '_wrapper',
                'placeholder' => '<div id="' . $this->get_name() . '-' . $name . '-wrapper" class="form-section-wrapper ' . implode(' ', $classes) . '"><div>',
                'type'        => 'html',
                'classes'     => $classes,
            ];
        }

        $this->fields['section_start_' . $name] = [
            'name'    => $name,
            'type'    => 'section_start',
            'classes' => $classes,
        ];
    }

    public function render_section_start($field)
    {
        $this->form_html('<div id="' . $this->get_name() . '-' . $field['name'] . '" class="form-section ' . implode(' ', $field['classes']) . '">');
    }

    /* End Section */
    public function end_section($name)
    {
        $this->fields['section_end_' . $name] = [
            'name' => $name,
            'type' => 'section_end',
        ];

        if (isset($this->fields['section_start_wrapper_' . $name])) {
            $this->fields['section_end_wrapper_' . $name] = [
                'name'        => $name . '_wrapper',
                'placeholder' => '</div></div><div id="' . $this->get_name() . '-' . $name . '-shadow" class="form-section-shadow ' . implode(' ', $this->fields['section_start_wrapper_' . $name]['classes']) . '"></div>',
                'type'        => 'html',
            ];
        }
    }

    public function render_section_end($field, $args)
    {
        $this->form_html('</div>');
    }

    public function add_html($name, $content)
    {
        $this->fields['html_' . $name] = [
            'name'        => $name,
            'placeholder' => $content,
            'type'        => 'html',
        ];
    }

    public function render_html($field, $args)
    {
        $this->form_html($field['placeholder']);
    }

    /* Text Box */
    public function add_text($name, $label, array $options = [])
    {
        $this->add_field($name, $label, 'text', $options);
    }

    public function validate_text($field)
    {
        return false;
    }

    public function render_text($field, $args)
    {
        $this->form_html('<fieldset id="field-' . $field['id'] . '" class="' . $args['fieldset_classes'] . '">' . $args['field_label'] . $args['field_comment']);
        $this->form_html('<input type="text" id="' . $field['id'] . '" name="' . $field['name'] . '" class="' . $args['field_classes'] . '" value="' . $args['field_value'] . '" placeholder="' . (isset($field['placeholder']) ? $field['placeholder'] : '') . '" /></fieldset>');
    }

    /* Int */
    public function add_number($name, $label, array $options = [])
    {
        $this->add_field($name, $label, 'number', $options);
    }

    public function validate_number($field)
    {
        if ($this->fields[$field]['repeater_group'] && is_array($this->fields[$field]['value'])) {
            foreach ($this->fields[$field]['value'] as $value_key => $value) {
                $this->fields[$field]['value'][$value_key] = (int)$this->fields[$field]['value'][$value_key];

                if (!is_int($this->fields[$field]['value'][$value_key])) {
                    return 'Field is not an integer';
                }
            }
        } else {
            $this->fields[$field]['value'] = (int)$this->fields[$field]['value'];

            if (!is_int($this->fields[$field]['value'])) {
                return 'Field is not an integer';
            }
        }

        return false;
    }

    public function render_number($field, $args)
    {
        $this->form_html('<fieldset id="field-' . $field['id'] . '" class="int ' . $args['fieldset_classes'] . '">' . $args['field_label'] . $args['field_comment']);
        $this->form_html('<input type="number" id="' . $field['id'] . '" name="' . $field['name'] . '" class="' . $args['field_classes'] . '" value="' . $args['field_value'] . '" placeholder="' . $field['placeholder'] . '" /></fieldset>');
    }

    /* Price */
    public function add_price($name, $label, array $options = [])
    {
        $options['classes'] = (isset($options['classes']) ? $options['classes'] : '') . ' text';
        $this->add_field($name, $label, 'price', $options);
    }

    public function validate_price($field)
    {
        if ($this->fields[$field]['repeater_group'] && is_array($this->fields[$field]['value'])) {
            foreach ($this->fields[$field]['value'] as $value_key => $value) {
                $this->fields[$field]['value'][$value_key] = (int)($this->fields[$field]['value'][$value_key] * 100);

                if (!is_int($this->fields[$field]['value'][$value_key])) {
                    return 'Field is not a Price integer';
                }
            }
        } else {
            $this->fields[$field]['value'] = (int)($this->fields[$field]['value'] * 100);

            if (!is_int($this->fields[$field]['value'])) {
                return 'Field is not a Price integer';
            }
        }

        return false;
    }

    public function render_price($field, $args)
    {
        $display_price = ($args['field_value'] > 0 ? number_format($args['field_value'] / 100, 2) : 0);

        $this->form_html('<fieldset id="field-' . $field['id'] . '" class="price ' . $args['fieldset_classes'] . '">' . $args['field_label'] . $args['field_comment'] .
            '<span class="price-wrap">
                <span class="price-symbol">' . self::$currency_symbol . '</span>
                <input type="text" id="' . $field['id'] . '" name="' . $field['name'] . '" class="' . $args['field_classes'] . '" value="' . $display_price . '" placeholder="' . $field['placeholder'] . '" />
            </span>
        </fieldset>');
    }

    /* Email Address */
    public function add_email($name, $label, array $options = [])
    {
        $this->add_field($name, $label, 'email', $options);
    }

    public function validate_email($field)
    {
        if ($this->fields[$field]['value'] == '' || filter_var($this->fields[$field]['value'], FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        return 'That is not a valid email address';
    }

    public function render_email($field, $args)
    {
        $this->form_html('<fieldset id="field-' . $field['id'] . '" class="email ' . $args['fieldset_classes'] . '">' . $args['field_label'] . $args['field_comment']);
        $this->form_html('<input type="email" id="' . $field['id'] . '" name="' . $field['name'] . '" value="' . $args['field_value'] . '" placeholder="' . $field['placeholder'] . '" /></fieldset>');
    }

    /* Password */
    public function add_password($name, $label, array $options = [])
    {
        $this->add_field($name, $label, 'password', $options);
    }

    public function validate_password($field)
    {
        if (!preg_match(self::$password_complexity, $this->fields[$field]['value'])) {
            return self::$password_message;
        }

        return false;
    }

    public function render_password($field, $args)
    {
        $this->form_html('<fieldset id="field-' . $field['id'] . '" class="password ' . $args['fieldset_classes'] . '">' . $args['field_label'] . $args['field_comment']);
        $this->form_html('<input type="password" id="' . $field['id'] . '" name="' . $field['name'] . '" value="' . $args['field_value'] . '" placeholder="' . $field['placeholder'] . '" /></fieldset>');
    }

    /* Textarea */
    public function add_textarea($name, $label, array $options = [])
    {
        $this->add_field($name, $label, 'textarea', $options);
    }

    public function validate_textarea()
    {
        return false;
    }

    public function render_textarea($field, $args)
    {
        $this->form_html('<fieldset id="field-' . $field['id'] . '" class="textarea ' . $args['fieldset_classes'] . '">' . $args['field_label'] . $args['field_comment']);
        $this->form_html('<textarea id="' . $field['id'] . '" name="' . $field['name'] . '" class="' . $args['field_classes'] . '">' . htmlentities($field['value']) . '</textarea></fieldset>');
    }

    /* Timestamp Input */
    public function add_timestamp($name, $label, array $options = [])
    {
        $this->add_field($name, $label, 'timestamp', $options);
    }

    public function validate_timestamp($field)
    {
        $value = $this->fields[$field]['value'];

        if (!((string) (int) $value === $value)
            && ($value <= PHP_INT_MAX)
            && ($value >= ~PHP_INT_MAX)) {
            return 'Invalid Unix Timestamp';
        }

        return false;
    }

    public function render_timestamp($field, $args)
    {
        $this->form_html('<fieldset id="field-' . $field['id'] . '" class="timestamp ' . $args['fieldset_classes'] . '">' . $args['field_label'] . $args['field_comment']);
        $this->form_html('<input type="number" id="' . $field['id'] . '" name="' . $field['name'] . '" value="' . $args['field_value'] . '" placeholder="' . $field['placeholder'] . '" /></fieldset>');
    }

    /* Date Input */
    public function add_date($name, $label, array $options = [])
    {
        $this->add_field($name, $label, 'date', $options);
    }

    public function validate_date()
    {
        return false;
    }

    public function render_date($field, $args)
    {
        $this->form_html('<fieldset id="field-' . $field['id'] . '" class="date ' . $args['fieldset_classes'] . '">' . $args['field_label'] . $args['field_comment']);
        $this->form_html('<input type="date" id="' . $field['id'] . '" name="' . $field['name'] . '" value="' . $args['field_value'] . '" placeholder="' . $field['placeholder'] . '" /></fieldset>');
    }

    /* Time Input */
    public function add_time($name, $label, array $options = [])
    {
        $this->add_field($name, $label, 'time', $options);
    }

    public function validate_time()
    {
        return false;
    }

    public function render_time($field, $args)
    {
        $this->form_html('<fieldset id="field-' . $field['id'] . '" class="time ' . $args['fieldset_classes'] . '">' . $args['field_label'] . $args['field_comment']);
        $this->form_html('<input type="time" id="' . $field['id'] . '" name="' . $field['name'] . '" value="' . $args['field_value'] . '" placeholder="' . $field['placeholder'] . '" /></fieldset>');
    }

    /* WYSIWYG Editor */
    public function add_editor($name, $label, array $options = [])
    {
        if (isset($options['classes'])) {
            $options['classes'] = $options['classes'] . ' bob';
        }
        $this->add_field($name, $label, 'editor', $options);
    }

    public function validate_editor()
    {
        return false;
    }

    public function render_editor($field, $args)
    {
        $this->form_html('<fieldset id="field-' . $field['id'] . '" class="' . $args['fieldset_classes'] . '">' . $args['field_label'] . $args['field_comment']);
        $this->form_html('<textarea id="' . $field['id'] . '" name="' . $field['name'] . '" class="' . $args['field_classes'] . '">' . $field['value'] . '</textarea></fieldset>');
    }

    /* Select Dropdown */
    public function add_select($name, $label, array $options = [])
    {
        if (isset($options['options'])) {
            if (!is_array($options['options'])) {
                $options['options'] = [$options['options']];
            }
            $this->add_field($name, $label, 'select', $options);
        } else {
            ipsCore::add_error('Options are required for a select dropdown');
        }
    }

    public function validate_select($field)
    {
        return $this->validate_field_options($field);
    }

    public function render_select($field, $args)
    {
        $placeholder_selectable = false;
        if (isset($field['placeholder_selectable']) && $field['placeholder_selectable']) {
            $placeholder_selectable = true;
        }

        $select_multiple = false;
        if (isset($args['select_multiple']) && $args['select_multiple']) {
            $select_multiple = true;
        }

        $this->form_html('<fieldset id="field-' . $field['id'] . '" class="select ' . $args['fieldset_classes'] . '">' . $args['field_label'] . $args['field_comment']);
        if ($field['options'] || $field['placeholder']) {
            $this->form_html('<select ' . ($select_multiple ? 'multiple ' : '') . 'id="' . $field['id'] . '" name="' . $field['name'] . ($select_multiple ? '[]' : '') . '" class="' . $args['field_classes'] . '">');
            if ($field['placeholder']) {
                $this->form_html('<option selected ' . ($placeholder_selectable ? 'value="0"' : 'disabled="disabled"') . ' >' . $field['placeholder'] . '</option>');
            }
            if ($field['options'] && !empty($field['options'])) {
                foreach ($field['options'] as $option) {
                    $option_selected = ((isset($option['selected']) && $option['selected'] === true) || ($option['value'] == $field['value'])) ? 'selected' : '';
                    $option_disabled = (isset($option['disabled']) && $option['disabled'] === true) ? ' disabled' : '';
                    $this->form_html('<option value="' . $option['value'] . '" ' . $option_selected . $option_disabled . '>' . $option['text'] . '</option>');
                }
            }
            $this->form_html('</select>');
        } else {
            $this->form_html('<p>No Options</p>');
        }
        $this->form_html('</fieldset>');
    }

    /* Select Multi */
    public function add_selectmulti($name, $label, array $options = [])
    {
        if (isset($options['options'])) {
            if (!is_array($options['options'])) {
                $options['options'] = [$options['options']];
            }
            $this->add_field($name, $label, 'selectmulti', $options);
        } else {
            ipsCore::add_error('Options are required for a select multi dropdown');
        }
    }

    public function validate_selectmulti($field)
    {
        $this->validate_field_options($field);

        return false;
    }

    public function render_selectmulti($field, $args)
    {
        if (!isset($args['fieldset_classes'])) {
            $args['fieldset_classes'] = '';
        }
        $args['fieldset_classes'] .= ' multiple';

        $args['select_multiple'] = true;

        return $this->render_select($field, $args);
    }

    /* Radio Buttons */
    public function add_radio($name, $label, array $options = [])
    {
        if (isset($options['options'])) {
            if (!is_array($options['options'])) {
                $options['options'] = [$options['options']];
            }
            $this->add_field($name, $label, 'radio', $options);
        } else {
            ipsCore::add_error('Options are required for radio buttons');
        }
    }

    public function validate_radio($field)
    {
        $this->validate_field_options($field);

        return false;
    }

    public function render_radio($field, $args)
    {
        $first = true;
        $this->form_html('<fieldset id="field-' . $field['id'] . '" class="radio ' . $args['fieldset_classes'] . '">' . $args['field_label'] . $args['field_comment']);
        if ($field['placeholder']) {
            $this->form_html('<label class="radiofield"><input class="' . $args['field_classes'] . '" type="radio" name="' . $field['name'] . '" value="0" />' . $field['placeholder'] . '</label>');
        }
        if ($field['options']) {
            foreach ($field['options'] as $option) {
                $this->render_radio_item($field, $args, $option);
            }
        }
        $this->form_html('</fieldset>');
    }

    public function render_radio_item($field, $args, $option)
    {
        $option_selected = ((isset($option['selected']) && $option['selected'] == true) || ($option['value'] == $field['value'])) ? 'checked' : '';
        $this->form_html('<label class="radiofield"><input class="' . $args['field_classes'] . '" type="radio" name="' . $field['name'] . '" value="' . $option['value'] . '" ' . $option_selected . ' />' . $option['text'] . '</label>');
    }

    /* Check Boxes */
    public function add_check($name, $label, array $options = [])
    {
        if (isset($options['options'])) {
            if (!is_array($options['options'])) {
                $options['options'] = [$options['options']];
            }
            $this->add_field($name, $label, 'check', $options);
        } else {
            ipsCore::add_error('Options are required for radio buttons');
        }
    }

    public function validate_check($field)
    {
        $this->validate_field_options($field);

        return false;
    }

    public function render_check($field, $args)
    {
        $first = true;
        $this->form_html('<fieldset id="field-' . $field['id'] . '" class="check ' . $args['fieldset_classes'] . '">' . $args['field_label'] . $args['field_comment']);
        if ($field['options']) {
            foreach ($field['options'] as $option) {
                $this->render_check_item($field, $args, $option, $first);
                $first = false;
            }
        }
        $this->form_html('</fieldset>');
    }

    public function render_check_item($field, $args, $option, $first = false)
    {
        $option_id = ($first) ? 'id="' . $field['id'] . '"' . '' : '';
        $option_selected = ((isset($option['selected']) && $option['selected'] == true) || ($option['value'] == $field['value'])) ? 'checked' : '';
        $this->form_html('<label class="checkfield"><input class="' . $args['field_classes'] . '" type="checkbox" ' . $option_id . ' name="' . $field['name'] . '[]" value="' . $option['value'] . '" ' . $option_selected . ' />' . $option['text'] . '</label>');
    }

    /* Select Dropdown ( LINK field ) */
    public function add_linkselect($name, $label, array $options = [])
    {
        if (isset($options['options'])) {
            if (!is_array($options['options'])) {
                $options['options'] = [$options['options']];
            }
            $this->add_field($name, $label, 'select', $options);
        } else {
            ipsCore::add_error('Options are required for radio buttons');
        }
    }

    public function validate_linkselect($field)
    {
        $this->validate_field_options($field);

        return false;
    }

    public function render_linkselect($field, $args)
    {
        return $this->render_select($field, $args);
    }

    /* Select Multi ( LINK field ) */
    public function add_linkselectmulti($name, $label, array $options = [])
    {
        if (isset($options['options'])) {
            if (!is_array($options['options'])) {
                $options['options'] = [$options['options']];
            }
            $this->add_field($name, $label, 'select', $options);
        } else {
            ipsCore::add_error('Options are required for a link select multi dropdown');
        }
    }

    public function validate_linkselectmulti($field)
    {
        $this->validate_field_options($field);

        return false;
    }

    public function render_linkselectmulti($field, $args)
    {
        return $this->render_selectmulti($field, $args);
    }

    /* Radio Buttons ( LINK field ) */
    public function add_linkradio($name, $label, array $options = [])
    {
        if (isset($options['options'])) {
            if (!is_array($options['options'])) {
                $options['options'] = [$options['options']];
            }
            $this->add_field($name, $label, 'radio', $options);
        } else {
            ipsCore::add_error('Options are required for radio buttons');
        }
    }

    public function validate_linkradio($field)
    {
        $this->validate_field_options($field);

        return false;
    }

    public function render_linkradio($field, $args)
    {
        return $this->render_radio($field, $args);
    }

    /* Check Boxes ( LINK field ) */
    public function add_linkcheck($name, $label, array $options = [])
    {
        if (isset($options['options'])) {
            if (!is_array($options['options'])) {
                $options['options'] = [$options['options']];
            }
            $this->add_field($name, $label, 'checkbox', $options);
        } else {
            ipsCore::add_error('Options are required for radio buttons');
        }
    }

    public function validate_linkcheck($field)
    {
        $this->validate_field_options($field);

        return false;
    }

    public function render_linkcheck($field, $args)
    {
        return $this->render_check($field, $args);
    }

    /* Date Picker */
    public function add_datepicker($name, $label, array $options = [])
    {

    }

    public function validate_datepicker()
    {
        return false;
    }

    public function render_datepicker($field, $args)
    {

    }

    /* Colour Picker */
    public function add_colourpicker($name, $label, array $options = [])
    {

    }

    public function validate_colourpicker()
    {
        return false;
    }

    public function render_colourpicker($field, $args)
    {

    }

    /* File Upload */
    public function add_file($name, $label, array $options = [])
    {
        $this->add_field($name, $label, 'file', $options);
    }

    public function validate_file()
    {
        return false;
    }

    public function render_file($field, $args)
    {
        $this->form_html('<fieldset id="field-' . $field['id'] . '" class="file ' . $args['fieldset_classes'] . '">' . $args['field_label'] . $args['field_comment']);
        $this->form_html('<input type="file" id="' . $field['id'] . '" name="' . $field['name'] . '" class="' . $args['field_classes'] . '" value="' . $args['field_value'] . '" placeholder="' . $field['placeholder'] . '" />');
        if (isset($field['preview']) && !empty($field['preview'])) {
            $this->form_html('<p class="preview"><a class="preview" href="' . $field['preview']['url'] . '" ></a><a class="preview-remove" href="' . $field['preview']['remove'] . '" ></a></p>');
        }
        $this->form_html('</fieldset>');
    }

    /* File Upload */
    public function add_file_multiple($name, $label, array $options = [])
    {
        $this->add_field($name, $label, 'file_multiple', $options);
    }

    public function validate_file_multiple($field)
    {
        $values = $this->fields[$field]['value'];

        if (is_array($values) && count($values) == 1) {
            if (reset($values) == 'undefined') {
                return 'No files chosen';
            }
        } elseif ($values == 'undefined') {
            return 'No files chosen';
        }

        return false;
    }

    public function render_file_multiple($field, $args)
    {
        $this->form_html('<fieldset id="field-' . $field['id'] . '" class="file ' . $args['fieldset_classes'] . '">' . $args['field_label'] . $args['field_comment']);
        $this->form_html('<input type="file" id="' . $field['id'] . '" name="' . $field['name'] . '[]" class="' . $args['field_classes'] . '" value="' . $args['field_value'] . '" placeholder="' . $field['placeholder'] . '" multiple="multiple" />');
        if (isset($field['preview']) && !empty($field['preview'])) {
            $this->form_html('<p class="preview"><a class="preview" href="' . $field['preview']['url'] . '" ></a><a class="preview-remove" href="' . $field['preview']['remove'] . '" ></a></p>');
        }
        $this->form_html('</fieldset>');
    }

    /* Image Upload */
    public function add_image($name, $label, array $options = [])
    {
        $this->add_field($name, $label, 'image', $options);
    }

    public function validate_image()
    {
        return false;
    }

    public function render_image($field, $args)
    {
        $this->form_html('<fieldset id="field-' . $field['name'] . '" class="image ' . $args['fieldset_classes'] . '">' . $args['field_label'] . $args['field_comment']);
        $this->form_html('<input type="file" id="' . $field['name'] . '" name="' . $field['name'] . '" class="' . $args['field_classes'] . '" value="' . $args['field_value'] . '" placeholder="' . $field['placeholder'] . '" />');
        if (isset($field['preview']) && !empty($field['preview'])) {
            $this->form_html('<div class="preview" src="' . $field['preview']['url'] . '" ><a class="preview-url" href="' . $field['preview']['url'] . '"><a class="preview-remove" href="' . $field['preview']['remove'] . '"></a></a></div>');
        }
        $this->form_html('</fieldset>');
    }

    /* Hidden */
    public function add_hidden($name, array $options = [])
    {
        $this->add_field($name, false, 'hidden', $options);
    }

    public function validate_hidden()
    {
        return false;
    }

    public function render_hidden($field, $args)
    {
        $this->form_html('<input type="hidden" id="' . $field['id'] . '" name="' . $field['name'] . '" value="' . $args['field_value'] . '" />');
    }

    /* Toggle Button */
    public function add_toggle($name, $label, array $options = []) {
        $this->add_field($name, $label, 'toggle', $options);
    }

    public function validate_toggle($field)
    {
        $this->validate_field_options($field);

        return false;
    }

    public function render_toggle($field, $args)
    {
        if (count($field['options']) == 2) {
            $this->form_html('<fieldset id="field-' . $field['id'] . '" class="' . $args['fieldset_classes'] . '">' . $args['field_comment']);

            $true = $field['options'][0];
            $false = $field['options'][1];

            if ($field['value'] == '') {
                $option_selected = ($field['default'] ? 'checked' : '');
            } else {
                $option_selected = ((isset($true['selected']) && $true['selected'] == 1) || ($true['value'] == $field['value'])) ? 'checked' : '';
            }

            $this->form_html('<label for="' . $field['name'] . '">
                    <span class="toggle-title">' . $field['label'] . '</span>
                    <span class="toggle-text">' . $false['text'] . '</span>
                    <input class="' . $args['field_classes'] . '" type="checkbox" id="' . $field['name'] . '" name="' . $field['name'] . '" value="' . $true['value'] . '" ' . $option_selected . ' />
                    <span class="slider"></span>
                    <span class="toggle-text">' . $true['text'] . '</span>
                </label>
            </fieldset>');
        } else {
            ipsCore::add_error('2 Options are required for Toggle fields');
        }
    }

    /* Submit */
    public function add_submit($name, $label, array $options = [])
    {
        $this->add_field($name, $label, 'submit', $options);
    }

    public function render_submit($field, $args)
    {
        $this->form_html('<fieldset id="field-' . $field['id'] . '" class="submit ' . $args['fieldset_classes'] . '">' . $args['field_comment'] . '<button id="' . $field['id'] . '" name="' . $field['name'] . '" class="' . $args['field_classes'] . '" ' . $args['field_attributes'] . '>' . $field['label'] . '</button></fieldset>');
    }

    public function render_fields()
    {
        return $this->render(true);
    }

    public function get_field_args($field)
    {
        if (isset($field['required']) && $field['required']) {
            $field['fieldset_classes'] .= ' required';
        }

        $field_classes = '';
        if (isset($field['classes'])) {
            $field_classes = $field['classes'];
        }

        $fieldset_classes = '';
        if (isset($field['fieldset_classes'])) {
            if (is_array($field['fieldset_classes'])) {
                $fieldset_classes = implode(' ', $field['fieldset_classes']);
            } else {
                $fieldset_classes = $field['fieldset_classes'];
            }
        }

        $field_label = '';
        if (isset($field['label']) && $field['label']) {
            $field_label = '<label for="' . $field['name'] . '">' . $field['label'] . (isset($field['required']) && $field['required'] ? '<span class="req">*</span>' : '') . '</label>';
        }

        $field_default = false;
        if (isset($field['default'])) {
            $field_default = $field['default'];
        }

        $field_value = '';
        if (isset($field['value'])) {
            $field_value = htmlentities($field['value']);
        } elseif ($field_default !== false) {
            $field['value'] = $field_default;
            $field_value = htmlentities($field_default);
        }

        $field_attributes = '';
        if (isset($field['disabled']) && $field['disabled']) {
            $field_attributes .= ' disabled';
        }

        $field_comment = '';
        if (isset($field['comment']) && $field['comment']) {
            $field_comment = '<p>' . $field['comment'] . '</p>';
        }

        return [
            'field_classes'    => $field_classes,
            'fieldset_classes' => $fieldset_classes,
            'field_label'      => $field_label,
            'field_default'    => $field_default,
            'field_value'      => $field_value,
            'field_attributes' => $field_attributes,
            'field_comment'    => $field_comment,
        ];
    }

    public function render($fields_only = false)
    {
        if (!$fields_only) {
            $this->form_html('<form id="' . $this->get_name() . '"
					class="' . implode(' ', $this->get_classes()) . '"
					action="' . $this->get_action() . '"
					method="' . $this->get_method() . '">');
        }

        foreach ($this->get_fields() as $field) {
            $render_function = 'render_' . $field['type'];
            if (!method_exists($this, $render_function)) {
                $render_function = 'render_text';
            }

            $this->{$render_function}($field, $this->get_field_args($field));
        }
        if (!$fields_only) {
            $this->form_html('</form>');
        }

        return $this->get_form_html();
    }

    public function populate_form($fields = false)
    {
        if (!$fields) {
            $fields = (isset($_REQUEST) ? $_REQUEST : []);
        }

        foreach ($this->get_fields() as $field_key => $field) {
            if ($field_type = self::get_field_types($field['type'])) {
                $value = false;
                $set_value = false;

                if (is_object($fields)) {
                    if (isset($fields->$field_key)) {
                        $value = $fields->$field_key;
                        $set_value = true;
                    } elseif ($array_field_key = $this->is_field_key_array($field_key)) {
                        if (isset($fields->$array_field_key)) {
                            $value = $fields->$array_field_key;
                            $set_value = true;
                        }
                    }

                    if (!$set_value) {
                        if (isset($field->default) && !empty($field->default)) {
                            $value = $field->default;
                        }
                    }
                } else {
                    if (isset($fields[$field_key])) {
                        $value = $fields[$field_key];
                        $set_value = true;
                    } elseif ($array_field_key = $this->is_field_key_array($field_key)) {
                        if (isset($fields[$array_field_key])) {
                            $value = $fields[$array_field_key];
                            $set_value = true;
                        }
                    }

                    if (!$set_value) {
                        if (isset($field['default']) && !empty($field['default'])) {
                            $value = $field['default'];
                        }
                    }
                }

                /*if (isset($field_type['file'])) {
                    if (isset($_FILES['files']['name'][$field_key])) {
                        $value = $_FILES['files']['name'][$field_key];
                    }
                }*/

                if (isset($field_type['file'])) {
                    if (isset($_FILES[$field_key]['name']) && !empty($_FILES[$field_key]['name']) && $_FILES[$field_key]['name'][0] != 'undefined') {
                        $first = true;
                        foreach ($_FILES[$field_key]['name'] as $file_key => $file_name) {
                            if (!$first) {
                                $value .= ',';
                            }
                            if (is_array($file_name)) {
                                $value .= $file_name[$file_key];
                            } else {
                                $value .= $file_name;
                            }
                            $first = false;
                        }
                    }
                }

                if (isset($field_type['link'])) {
                    if (is_array($this->fields[$field_key]['options'])) {
                        if (is_array($value)) {
                            $value = implode(',', $value);
                        }

                        $values = explode(',', $value);
                        foreach ($values as $value_item) {
                            $option_key = array_search($value_item, array_column($this->fields[$field_key]['options'], 'value'));
                            if ($option_key !== false) {
                                $this->fields[$field_key]['options'][$option_key]['selected'] = true;
                            }
                        }
                    }
                }

                $this->set_field_value($field_key, $value);
            }
        }
    }

    /* Re-useable validation */
    private function validate_field_options($field)
    {
        if ($this->fields[$field]['value'] && !empty($this->fields[$field]['value'])) {
            $values = explode(',', $this->fields[$field]['value']);
            if ($values && !empty($values)) {
                foreach ($values as $value) {
                    $option_key = array_search($value, array_column($this->fields[$field]['options'], 'value'));
                    if ($option_key === false) {
                        return 'Submitted value ' . $value . ' is not a valid option.';
                    }
                }
            }
        }

        return false;
    }

    public function validate_form(&$errors)
    {
        foreach ($this->get_fields() as $field) {
            if (self::get_field_types($field['type'])) {
                $errored = false;

                // Required validation
                if ($field['required'] == true) {
                    if (!isset($field['value']) || $field['value'] == '' || $field['value'] == ' ') {
                        $errors[$field['name']] = 'This is a required field';
                        $errored = true;
                    }
                }

                if (!$errored) {
                    // Basic field validation
                    $validate_func = 'validate_' . $field['type'];
                    $error = $this->{$validate_func}($field['name']);
                    if ($error) {
                        $errors[$field['name']] = $error;
                        $errored = true;
                    }
                }
            }
        }
    }

    public function get_field_type_options($current_type = false)
    {
        $select_options = [];
        foreach (self::get_field_types() as $field_type_key => $field_type) {
            $field_type_option = ['text' => $field_type['title'], 'value' => $field_type_key];
            if ($field_type_key == $current_type) {
                $field_type_option['selected'] = true;
            }
            $select_options[] = $field_type_option;
        }

        return $select_options;
    }

    public function show_field_options_output($options)
    {
        $return = '';

        if ((@unserialize($options) !== false)) {
            $options = unserialize($options);

            if (!empty($options)) {
                foreach ($options as $option) {
                    if (isset($option['value']) && isset($option['text'])) {
                        $return .= $option['value'] . ' : ' . $option['text'] . "\r\n";
                    } else {
                        ipsCore::add_error('There was a problem reading option data');
                    }
                }
            }
        }

        return $return;
    }

    public function show_field_options($options)
    {
        $return = '';

        if ((@unserialize($options) !== false)) {
            $return = unserialize($options);
        }

        return $return;
    }

    public function process_field_options($options)
    {
        $options = explode("\r\n", $options);
        $return = [];

        if (!empty($options)) {
            foreach ($options as $option) {
                if ($option && $option != '' && $option != ' ') {
                    $option = explode(':', $option);
                    $return[] = [
                        'value' => trim($option[0], ' '),
                        'text'  => trim($option[1], ' '),
                    ];
                }
            }
        }

        return serialize($return);
    }

    public function option_exists($value, $options) {
        foreach ($options as $option) {
            if ($option['value'] == $value) {
                return true;
            }
        }

        return false;
    }

    public function is_field_key_array($field_key) {
        $test = '[]';
        $test_len = 2;
        $field_key_length = strlen($field_key);

        if ($test_len > $field_key_length) {
            return false;
        }

        if (substr_compare($field_key, $test, $field_key_length - $test_len, $test_len) === 0) {
            return str_replace($test, '', $field_key);
        }

        return false;
    }

    public function get_repeater_group($group_key) {
        $items = [];

        foreach ($this->fields as $field_key => $field) {
            if (isset($field['repeater_group']) && $field['repeater_group'] == $group_key) {
                foreach ($field['value'] as $value_key => $value) {
                    $items[$value_key][$this->is_field_key_array($field_key)] = $value;
                }
            }
        }

        return $items;
    }

}
