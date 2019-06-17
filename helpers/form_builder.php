<?php // IPS-CORE Form Builder

class ipsCore_form_builder
{

    protected $name;
    protected $method = 'POST';
    protected $action;
    protected $classes = [];
    protected $fields = [];
    protected $form_html;

    public static $field_types = [
        'int' => ['title' => 'Number', 'type' => 'int', 'length' => '11'],
        'price' => ['title' => 'Price', 'type' => 'decimal', 'length' => '4,2'],
        'text' => ['title' => 'Text Input', 'type' => 'varchar', 'length' => '255'],
        'email' => ['title' => 'Email Address Input', 'type' => 'varchar', 'length' => '255'],
        'password' => ['title' => 'Password Input', 'type' => 'varchar', 'length' => '255'],
        'textarea' => ['title' => 'Text Area', 'type' => 'text', 'length' => false],
        'editor' => ['title' => 'WYSIWYG Editor', 'type' => 'text', 'length' => false],
        'select' => ['title' => 'Select', 'type' => 'text', 'length' => false, 'link' => false],
        'radio' => ['title' => 'Radios', 'type' => 'text', 'length' => false, 'link' => false],
        'check' => ['title' => 'Check Boxes', 'type' => 'text', 'length' => false, 'link' => false],
        'linkselect' => ['title' => 'Link Select', 'type' => 'varchar', 'length' => '255', 'link' => true],
        'linkradio' => ['title' => 'Link Radios', 'type' => 'varchar', 'length' => '255', 'link' => true],
        'linkcheck' => ['title' => 'Link Check Boxes', 'type' => 'varchar', 'length' => '255', 'link' => true],
        'datepicker' => ['title' => 'Date Picker', 'type' => 'varchar', 'length' => '255'],
        'colourpicker' => ['title' => 'Color Picker', 'type' => 'varchar', 'length' => '255'],
        'file' => ['title' => 'File Upload', 'type' => 'int', 'length' => '11', 'file' => true],
        'image' => ['title' => 'Image Upload', 'type' => 'int', 'length' => '11', 'file' => false],
        'hidden' => ['title' => 'Hidden Field', 'type' => 'text', 'length' => '255', 'unselectable' => true],
    ];

    public static $password_complexity = '^\S*(?=\S{6,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])\S*$^';
    public static $password_message = 'Passwords must be at least 6 characters long, contain at least one Uppercase and Lowercase characters and at least one number.';

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
            if (isset($this->fields[$field]['value']) && $this->fields[$field]['value'] != '') {
                return $this->fields[$field]['value'];
            } elseif (isset($this->fields[$field]['default']) && $this->fields[$field]['default'] != '') {
                return $this->fields[$field]['default'];
            } else {
                $field_type = self::get_field_types($this->fields[$field]['type']);
                if ( $field_type['type'] == 'int') {
                    if ( isset( $field_type['default'])) {
                        return $field_type['default'];
                    } else {
                        return 0;
                    }
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
        if ( substr($action, -1) != '/' ) {
            $action .= '/';
        }
        $this->action = $action;
    }

    public function set_classes($classes)
    {
        $this->classes = $classes;
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
            $this->fields[$name] = [
                'name' => $name,
                'label' => $label,
                'type' => $type,
                'value' => (isset($options['value']) ? $options['value'] : null),
                'default' => (isset($options['default']) ? $options['default'] : null),
                'options' => (isset($options['options']) ? $options['options'] : []),
                'required' => (isset($options['required']) ? $options['required'] : false),
                'placeholder' => (isset($options['placeholder']) ? $options['placeholder'] : ''),
                'placeholder_selectable' => (isset($options['placeholder_selectable']) ? $options['placeholder_selectable'] : null),
                'comment' => (isset($options['comment']) ? $options['comment'] : false),
                'classes' => (isset($options['classes']) ? $options['classes'] : false),
                'fieldset_classes' => (isset($options['fieldset_classes']) ? $options['fieldset_classes'] : ''),
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

    public function start_section($name)
    {
        $this->fields['section_start_' . $name] = [
            'name' => $name,
            'type' => 'section_start',
        ];
    }

    public function render_section_start($field)
    {
        $this->form_html('<div id="' . $field['name'] . '" class="form-section">');
    }

    public function end_section($name)
    {
        $this->fields['section_end_' . $name] = [
            'name' => $name,
            'type' => 'section_end',
        ];
    }

    public function render_section_end($field, $args)
    {
        $this->form_html('</div>');
    }

    public function add_html($name, $content)
    {
        $this->fields['html_' . $name] = [
            'name' => $name,
            'placeholder' => $content,
            'type' => 'html',
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
        $this->form_html('<fieldset id="field-' . $field['name'] . '" class="text ' . $args['fieldset_classes'] . '">' . $args['field_label'] . $args['field_comment']);
        $this->form_html('<input type="text" id="' . $field['name'] . '" name="' . $field['name'] . '"' . $args['field_classes'] . $args['field_value'] . ' placeholder="' . (isset($field['placeholder']) ? $field['placeholder'] : '' ) . '" /></fieldset>');
    }

    /* Int */
    public function add_int($name, $label, array $options = [])
    {
        $this->add_field($name, $label, 'number', $options);
    }

    public function validate_int($field)
    {
        $this->fields[$field]['value'] = (int)$this->fields[$field]['value'];

        if (!is_int($this->fields[$field]['value'])) {
            return 'Field is not an integer';
        }

        return false;
    }

    public function render_int($field, $args)
    {
        $this->form_html('<fieldset id="field-' . $field['name'] . '" class="int ' . $args['fieldset_classes'] . '">' . $args['field_label'] . $args['field_comment']);
        $this->form_html('<input type="number" id="' . $field['name'] . '" name="' . $field['name'] . '"' . $args['field_classes'] . $args['field_value'] . ' placeholder="' . $field['placeholder'] . '" /></fieldset>');
    }

    /* Price */
    public function add_price($name, $label, array $options = [])
    {
        $options['classes'] = $options['classes'] . ' text';
        $this->add_field($name, $label, 'text', $options);
    }

    public function validate_price()
    {

    }

    public function render_price($field, $args)
    {
        $this->form_html('<fieldset id="field-' . $field['name'] . '" class="price ' . $args['fieldset_classes'] . '">' . $args['field_label'] . $args['field_comment']);
        $this->form_html('<input type="text" id="' . $field['name'] . '" name="' . $field['name'] . '"' . $args['field_classes'] .  $args['field_value'] . ' placeholder="' . $field['placeholder'] . '" /></fieldset>');
    }

    /* Email Address */
    public function add_email($name, $label, array $options = [])
    {
        $this->add_field($name, $label, 'email', $options);
    }

    public function validate_email($field)
    {
        if (filter_var($this->fields[$field]['value'], FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        return true;
    }

    public function render_email($field, $args)
    {
        $this->form_html('<fieldset id="field-' . $field['name'] . '" class="email ' . $args['fieldset_classes'] . '">' . $args['field_label'] . $args['field_comment']);
        $this->form_html('<input type="email" id="' . $field['name'] . '" name="' . $field['name'] . '"' . $args['field_value'] . ' placeholder="' . $field['placeholder'] . '" /></fieldset>');
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
        $this->form_html('<fieldset id="field-' . $field['name'] . '" class="password ' . $args['fieldset_classes'] . '">' . $args['field_label'] . $args['field_comment']);
        $this->form_html('<input type="password" id="' . $field['name'] . '" name="' . $field['name'] . '"' . $args['field_value'] . ' placeholder="' . $field['placeholder'] . '" /></fieldset>');
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
        $this->form_html('<fieldset id="field-' . $field['name'] . '" class="textarea ' . $args['fieldset_classes'] . '">' . $args['field_label'] . $args['field_comment']);
        $this->form_html('<textarea id="' . $field['name'] . '" name="' . $field['name'] . '"' . $args['field_classes'] . '>' . htmlentities($field['value']) . '</textarea></fieldset>');
    }

    /* WYSIWYG Editor */
    public function add_editor($name, $label, array $options = [])
    {
        $options['classes'] = $options['classes'] . ' editor';
        $this->add_field($name, $label, 'editor', $options);
    }

    public function validate_editor()
    {
        return false;
    }

    public function render_editor($field, $args)
    {
        $this->form_html('<fieldset id="field-' . $field['name'] . '" class="editor ' . $args['fieldset_classes'] . '">' . $args['field_label'] . $args['field_comment']);
        $this->form_html('<textarea id="' . $field['name'] . '" name="' . $field['name'] . '"' . $args['field_classes'] . '>' . $field['value'] . '</textarea></fieldset>');
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
        $this->validate_field_options($field);

        return false;
    }

    public function render_select($field, $args)
    {
        $placeholder_selectable = false;
        if (isset($field['placeholder_selectable']) && $field['placeholder_selectable']) {
            $placeholder_selectable = true;
        }

        $this->form_html('<fieldset id="field-' . $field['name'] . '" class="select ' . $args['fieldset_classes'] . '">' . $args['field_label'] . $args['field_comment']);
        if ($field['options'] || $field['placeholder']) {
            $this->form_html('<select id="' . $field['name'] . '" name="' . $field['name'] . '"' . $args['field_classes'] . '>');
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
        $this->form_html('<fieldset id="field-' . $field['name'] . '" class="radio ' . $args['fieldset_classes'] . '">' . $args['field_label'] . $args['field_comment']);
        if ($field['placeholder']) {
            $this->form_html('<label class="radiofield"><input' . $args['field_classes'] . ' type="radio" name="' . $field['name'] . '[]" value="0" />' . $field['placeholder'] . '</label>');
        }
        if ($field['options']) {
            foreach ($field['options'] as $option) {
                $option_id = ($first) ? 'id="' . $field['name'] . '"' . '' : '';
                $option_selected = ((isset($option['selected']) && $option['selected'] == true) || ($option['value'] == $field['value'])) ? 'checked' : '';
                $this->form_html('<label class="radiofield"><input' . $args['field_classes'] . ' type="radio" ' . $option_id . ' name="' . $field['name'] . '[]" value="' . $option['value'] . '" ' . $option_selected . ' />' . $option['text'] . '</label>');
                $first = false;
            }
        }
        $this->form_html('</fieldset>');
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
        $this->form_html('<fieldset id="field-' . $field['name'] . '" class="check ' . $args['fieldset_classes'] . '">' . $args['field_label'] . $args['field_comment']);
        if ($field['options']) {
            foreach ($field['options'] as $option) {
                $option_id = ($first) ? 'id="' . $field['name'] . '"' . '' : '';
                $option_selected = ((isset($option['selected']) && $option['selected'] == true) || ($option['value'] == $field['value'])) ? 'checked' : '';
                $this->form_html('<label class="checkfield"><input' . $args['field_classes'] . ' type="checkbox" ' . $option_id . ' name="' . $field['name'] . '[]" value="' . $option['value'] . '" ' . $option_selected . ' />' . $option['text'] . '</label>');
                $first = false;
            }
        }
        $this->form_html('</fieldset>');
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
        $this->form_html('<fieldset id="field-' . $field['name'] . '" class="file ' . $args['fieldset_classes'] . '">' . $args['field_label'] . $args['field_comment']);
        $this->form_html('<input type="file" id="' . $field['name'] . '" name="' . $field['name'] . '"' . $args['field_classes'] . $args['field_value'] . ' placeholder="' . $field['placeholder'] . '" />');
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
        $this->form_html('<input type="file" id="' . $field['name'] . '" name="' . $field['name'] . '"' . $args['field_classes'] . $args['field_value'] . ' placeholder="' . $field['placeholder'] . '" />');
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
        $this->form_html('<input type="hidden" id="' . $field['name'] . '" name="' . $field['name'] . '"' . $args['field_value'] . ' />');
    }

    /* Submit */
    public function add_submit($name, $label, array $options = [])
    {
        $this->add_field($name, $label, 'submit', $options);
    }

    public function render_submit($field, $args)
    {
        $this->form_html('<fieldset id="field-' . $field['name'] . '" class="submit">' . $args['field_comment'] . '<button id="' . $field['name'] . '" name="' . $field['name'] . '"' . $args['field_classes'] . '>' . $field['label'] . '</button></fieldset>');
    }

    public function render_fields()
    {
        return $this->render(true);
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

            if (isset($field['required']) && $field['required']) {
                $field['fieldset_classes'] .= ' required';
            }

            $field_classes = '';
            if (isset($field['classes'])) {
                $field_classes = ' class="' . $field['classes'] . '"';
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
                $field_value = ' value="' . htmlentities($field['value']) . '"';
            } elseif ($field_default !== false) {
                $field['value'] = $field_default;
                $field_value = ' value="' . htmlentities($field_default) . '"';
            }

            $field_comment = '';
            if (isset($field['comment']) && $field['comment']) {
                $field_comment = '<p>' . $field['comment'] . '</p>';
            }

            $render_function = 'render_' . $field['type'];
            if (!method_exists($this, $render_function)) {
                $render_function = 'render_text';
            }
            $args = [
				'field_classes' => $field_classes,
				'fieldset_classes' => $fieldset_classes,
				'field_label' => $field_label,
				'field_default' => $field_default,
				'field_value' => $field_value,
				'field_comment' => $field_comment,
			];
            $this->{$render_function}($field, $args );
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

                if (is_object($fields)) {
                    if (isset($fields->$field_key)) {
                        $value = $fields->$field_key;
                    } else {
                        if (isset($field->default) && !empty($field->default)) {
                            $value = $field->default;
                        }
                    }
                } else {
                    if (isset($fields[$field_key])) {
                        $value = $fields[$field_key];
                    } else {
                        if (isset($field['default']) && !empty($field['default'])) {
                            $value = $field['default'];
                        }
                    }
                }

                if (isset($field_type['file'])) {
                    if (isset($_FILES['files']['name'][$field_key])) {
                        $value = $_FILES['files']['name'][$field_key];
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

                $this->fields[$field_key]['value'] = $value;
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
                        return 'Given value ' . $value . ' is not a valid option.';
                    }
                }
            }
        }
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
                    $option = explode(' : ', $option);
                    $return[] = [
                        'value' => $option[0],
                        'text' => $option[1],
                    ];
                }
            }
        }

        return serialize($return);
    }

}
