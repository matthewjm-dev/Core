<?php // IPS-CORE Form Builder

class ipsCore_form_builder
{

    protected $name;
    protected $method = 'POST';
    protected $action;
    protected $classes = [];
    protected $fields = [];

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

    public function get_field_value($field)
    {
        //return (isset($this->fields[$field]['value']) ? $this->fields[$field]['value'] : ( $this->fields[$field]['default'] ? $this->fields[$field]['default'] : NULL ) );
        if (isset($this->fields[$field]['value'])) {
            return $this->fields[$field]['value'];
        } elseif (isset($this->fields[$field]['default'])) {
            return $this->fields[$field]['default'];
        } else {
            return NULL;
        }
    }

    // Setters
    public function set_method($method)
    {
        $this->method = $method;
    }

    public function set_action($action)
    {
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

    // Construct
    public function __construct($name)
    {
        $this->name = $name;
    }

    // Field Types
    public static function get_field_types($type = false, $nolinks = false)
    {
        $fields = [
            'int' => ['title' => 'Number', 'type' => 'int', 'length' => '11'],
            'price' => ['title' => 'Price', 'type' => 'decimal', 'length' => '4,2'],
            'text' => ['title' => 'Text Input', 'type' => 'varchar', 'length' => '255'],
            'password' => ['title' => 'Password Input', 'type' => 'varchar', 'length' => '255'],
            'textarea' => ['title' => 'Text Area', 'type' => 'text', 'length' => false],
            'editor' => ['title' => 'WYSIWYG Editor', 'type' => 'text', 'length' => false],
            'select' => ['title' => 'Dropdown', 'type' => 'text', 'length' => false, 'link' => false],
            'radio' => ['title' => 'Radios', 'type' => 'text', 'length' => false, 'link' => false],
            'check' => ['title' => 'Check Boxes', 'type' => 'text', 'length' => false, 'link' => false],
            'linkselect' => ['title' => 'Link Dropdown', 'type' => 'varchar', 'length' => '255', 'link' => true],
            'linkradio' => ['title' => 'Link Radios', 'type' => 'varchar', 'length' => '255', 'link' => true],
            'linkcheck' => ['title' => 'Link Check Boxes', 'type' => 'varchar', 'length' => '255', 'link' => true],
            'datepicker' => ['title' => 'Date Picker', 'type' => 'varchar', 'length' => '255'],
            'colourpicker' => ['title' => 'Color Picker', 'type' => 'varchar', 'length' => '255'],
        ];

        if ($type) {
            if (isset($fields[$type])) {
                $field = $fields[$type];
                $field['key'] = $type;
                return $field;
            }
            return false;
        }
        if ( $nolinks ) {
            foreach( $fields as $field_key => $field ) {
                if ( isset( $field[ 'link' ] ) && $field[ 'link' ] ) {
                    unset( $fields[ $field_key ] );
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
                'label' => ($label ?: $name),
                'type' => $type,
                'value' => (isset($options['value']) ? $options['value'] : NULL),
                'default' => (isset($options['default']) ? $options['default'] : NULL),
                'options' => (isset($options['options']) ? $options['options'] : []),
                'required' => (isset($options['required']) ? $options['required'] : false),
                'placeholder' => (isset($options['placeholder']) ? $options['placeholder'] : NULL),
                'comment' => (isset($options['comment']) ? $options['comment'] : NULL),
                'classes' => (isset($options['classes']) ? $options['classes'] : NULL),
                'fieldset_classes' => (isset($options['fieldset_classes']) ? $options['fieldset_classes'] : NULL),
            ];
        } else {
            foreach ($errors as $error) {
                ipsCore::error($error);
            }
        }
    }

    public function start_section_repeater($name)
    {
        $this->fields['section_start_' . $name] = [
            'name' => $name,
            'type' => 'section_start_repeater',
        ];
    }

    public function start_section($name)
    {
        $this->fields['section_start_' . $name] = [
            'name' => $name,
            'type' => 'section_start',
        ];
    }

    public function end_section($name)
    {
        $this->fields['section_end_' . $name] = [
            'name' => $name,
            'type' => 'section_end',
        ];
    }

    public function add_html($name, $content)
    {
        $this->fields['html_' . $name] = [
            'name' => $name,
            'placeholder' => $content,
            'type' => 'html',
        ];
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

    /* Int */
    public function add_int($name, $label, array $options = [])
    {
        $this->add_field($name, $label, 'number', $options);
    }

    public function validate_int($field)
    {
        if (!is_int($this->fields[$field]['value'])) {
            return 'Field is not an integer';
        }
        return false;
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

    /* Password */
    public function add_password($name, $label, array $options = [])
    {
        $this->add_field($name, $label, 'password', $options);
    }

    public function validate_password()
    {
        return false;
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

    /* WYSIWYG Editor */
    public function add_editor($name, $label, array $options = [])
    {
        $options['classes'] = $options['classes'] . ' editor';
        $this->add_field($name, $label, 'textarea', $options);
    }

    public function validate_editor()
    {
        return false;
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

    /* Check Boxes */
    public function add_check($name, $label, array $options = [])
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

    public function validate_check($field)
    {
        $this->validate_field_options($field);
        return false;
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

    /* Date Picker */
    public function add_datepicker($name, $label, array $options = [])
    {

    }

    public function validate_datepicker()
    {
        return false;
    }

    /* Colour Picker */
    public function add_colourpicker($name, $label, array $options = [])
    {

    }

    public function validate_colourpicker()
    {
        return false;
    }

    /* Hidden */
    public function add_hidden($name, $label, array $options = [])
    {
        $this->add_field($name, $label, 'hidden', $options);
    }

    public function validate_hidden()
    {
        return false;
    }

    /* Submit */
    public function add_submit($name, $label, array $options = [])
    {
        $this->add_field($name, $label, 'submit', $options);
    }

    public function render_fields()
    {
        return $this->render(true);
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

    public function render($fields_only = false)
    {
        if (!$fields_only) {
            $html = '<form id="' . $this->get_name() . '"
					class="' . implode(' ', $this->get_classes()) . '"
					action="' . $this->get_action() . '"
					method="' . $this->get_method() . '">';
        }

        foreach ($this->get_fields() as $field) {
            $value = '';
            $placeholder = '';
            $label = '';
            $first = true;
            $required = false;

            $field_id = 'id="' . $field['name'] . '"';
            $field_name = ' name="' . $field['name'] . '"';

            if (isset($field['required']) && $field['required']) {
                $required = true;
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
            if (isset($field['label'])) {
                $field_label = '<label for="' . $field['name'] . '">' . $field['label'] . ($required ? '<span class="req">*</span>' : '') . '</label>';
            }

            $field_default = false;
            if (isset($field['default'])) {
                $field_default = $field['default'];
            }

            $field_value = '';
            if (isset($field['value'])) {
                $field_value = ' value="' . $field['value'] . '"';
            } elseif ($field_default !== false) {
                $field['value'] = $field['default'];
                $field_value = ' value="' . $field_default . '"';
            }

            $field_comment = '';
            if (isset($field['comment'])) {
                $field_comment = '<p>' . $field['comment'] . '</p>';
            }

            switch ($field['type']) {
                case 'section_start':
                    $html .= '<div ' . $field_id . ' class="form-section">';
                    break;
                case 'section_start_repeater':
                    $html .= '<div ' . $field_id . ' class="form-section repeater">';
                    $html .= '	<div class="repeat-section"><span>Add</span></div>';
                    break;
                case 'section_end':
                    $html .= '</div>';
                    break;
                case 'html':
                    $html .= $field['placeholder'];
                    break;
                case 'password':
                    $html .= '<fieldset id="field-' . $field['name'] . '" class="' . $fieldset_classes . '">' . $field_label . $field_comment;
                    $html .= '<input type="password" ' . $field_id . $field_name . $field_value . ' placeholder="' . $field['placeholder'] . '" /></fieldset>';
                    break;
                case 'select':
                case 'linkselect':
                    $html .= '<fieldset id="field-' . $field['name'] . '" class="select ' . $fieldset_classes . '">' . $field_label . $field_comment;
                    if ($field['options'] || $field['placeholder']) {
                        $html .= '<select ' . $field_id . $field_classes . $field_name . '>';
                        if ($field['placeholder']) {
                            $html .= '<option selected disabled="disabled">' . $field['placeholder'] . '</option>';
                        }
                        if ($field['options']) {
                            foreach ($field['options'] as $option) {
                                $option_selected = ((isset($option['selected']) && $option['selected'] === true) || ($option['value'] == $field['value'])) ? 'selected' : '';
                                $option_disabled = (isset($option['disabled']) && $option['disabled'] === true) ? ' disabled' : '';
                                $html .= '<option value="' . $option['value'] . '" ' . $option_selected . $option_disabled . '>' . $option['text'] . '</option>';
                            }
                        }
                        $html .= '</select>';
                    } else {
                        $html .= '<p>No Options</p>';
                    }
                    $html .= '</fieldset>';
                    break;
                case 'radio':
                case 'linkradio':
                    $html .= '<fieldset id="field-' . $field['name'] . '" class="radio ' . $fieldset_classes . '">' . $field_label . $field_comment;
                    if ($field['options']) {
                        foreach ($field['options'] as $option) {
                            $option_id = ($first) ? '' . $field_id . '' : '';
                            $option_selected = ($option['value'] == $field['value']) ? 'checked' : '';
                            $html .= '<label class="radiofield"><input' . $field_classes . ' type="radio" ' . $option_id . ' name="' . $field['name'] . '[]" value="' . $option['value'] . '" ' . $option_selected . ' />' . $option['text'] . '</label>';
                            $first = false;
                        }
                    }
                    $html .= '</fieldset>';
                    break;
                case 'check':
                case 'linkcheck':
                    $html .= '<fieldset id="field-' . $field['name'] . '" class="check ' . $fieldset_classes . '">' . $field_label . $field_comment;
                    if ($field['options']) {
                        foreach ($field['options'] as $option) {
                            $option_id = ($first) ? '' . $field_id . '' : '';
                            $option_selected = ((isset($option['selected']) && $option['selected'] == true) || ($option['value'] == $field['value'])) ? 'checked' : '';
                            $html .= '<label class="checkfield"><input' . $field_classes . ' type="checkbox" ' . $option_id . ' name="' . $field['name'] . '[]" value="' . $option['value'] . '" ' . $option_selected . ' />' . $option['text'] . '</label>';
                            $first = false;
                        }
                    }
                    $html .= '</fieldset>';
                    break;
                case 'textarea':
                    $html .= '<fieldset id="field-' . $field['name'] . '" class="' . $fieldset_classes . '">' . $field_label . $field_comment;
                    $html .= '<textarea ' . $field_id . $field_classes . $field_name . '>' . $field['value'] . '</textarea></fieldset>';
                    break;
                case 'editor':
                    $html .= '<fieldset id="field-' . $field['name'] . '" class="' . $fieldset_classes . '">' . $field_label . $field_comment;
                    $html .= '<textarea ' . $field_id . $field_classes . $field_name . '>' . $field['value'] . '</textarea></fieldset>';
                    break;
                case 'datepicker':

                    break;
                case 'colourpicker':

                    break;
                case 'hidden':
                    $html .= '<input type="hidden" ' . $field_id . $field_name . $field_value . ' />';
                    break;
                case 'submit':
                    //$html .= '<fieldset id="field-' . $field['name'] . '"' . $fieldset_classes . '><input type="submit" ' . $field_id . $field_classes . $field_name . $field_value . ' /></fieldset>';
                    $html .= '<fieldset id="field-' . $field['name'] . '">' . $field_comment . '<button ' . $field_id . $field_classes . $field_name . '>' . $field['label'] . '</button></fieldset>';
                    break;
                case 'int':
                    $html .= '<fieldset id="field-' . $field['name'] . '"' . $fieldset_classes . '>' . $field_label . $field_comment;
                    $html .= '<input type="number" ' . $field_id . $field_classes . $field_name . $field_value . ' placeholder="' . $field['placeholder'] . '" /></fieldset>';
                    break;
                case 'price':
                case 'text':
                default:
                    $html .= '<fieldset id="field-' . $field['name'] . '"' . $fieldset_classes . '>' . $field_label . $field_comment;
                    $html .= '<input type="text" ' . $field_id . $field_classes . $field_name . $field_value . ' placeholder="' . $field['placeholder'] . '" /></fieldset>';
                    break;
            }
        }
        if (!$fields_only) {
            $html .= '</form>';
        }

        return $html;
    }

    public function populate_form($fields = false)
    {
        if (!$fields) {
            $fields = ( isset( $_REQUEST ) ? $_REQUEST : [] );
        }
        foreach ($this->get_fields() as $field_key => $field) {
            if ($field_type = ipsCore_form_builder::get_field_types($field['type'])) {
                $value = false;
                if (is_object($fields)) {
                    if (isset($fields->$field_key)) {
                        $value = $fields->$field_key;
                    } else if (isset($field->default) && !empty($field->default)) {
                        $value = $field->default;
                    }
                } else {
                    if (isset($fields[$field_key])) {
                        $value = $fields[$field_key];
                    } else if (isset($field['default']) && !empty($field['default'])) {
                        $value = $field['default'];
                    }
                }

                if ( isset($field_type['link'])) {
                    if (is_array($value)) {
                        $value = implode(',', $value);
                    }

                    $values = explode(',', $value);
                    foreach( $values as $value_item ) {
                        $option_key = array_search($value_item, array_column($this->fields[$field_key]['options'], 'value'));
                        if ( $option_key !== false ) {
                            $this->fields[$field_key]['options'][$option_key]['selected'] = true;
                        }
                    }
                }

                $this->fields[$field_key]['value'] = $value;
            }
        }
    }

    public function validate_form(&$errors)
    {
        foreach ($this->get_fields() as $field) {
            if (ipsCore_form_builder::get_field_types($field['type'])) {
                $errored = false;

                // Required validation
                if ($field['required'] == true) {
                    if (!isset($field['value']) || empty($field['value']) || $field['value'] == '' || $field['value'] == ' ') {
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

}