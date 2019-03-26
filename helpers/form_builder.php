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

    private function get_fields()
    {
        return $this->fields;
    }

    public function get_field_value($field)
    {
        return $this->fields[$field]['value'];
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
    public static function get_field_types($type = false)
    {
        $fields = [
            'tinyint' => ['title' => 'Small Number', 'type' => 'tinyint', 'length' => '4'],
            'int' => ['title' => 'Number', 'type' => 'int', 'length' => '11'],
            'bigint' => ['title' => 'Big Number', 'type' => 'bigint', 'length' => '20'],
            'price' => ['title' => 'Price', 'type' => 'decimal', 'length' => '4,2'],
            'text' => ['title' => 'Text Input', 'type' => 'varchar', 'length' => '255'],
            'password' => ['title' => 'Password Input', 'type' => 'varchar', 'length' => '255'],
            'textarea' => ['title' => 'Text Area', 'type' => 'text', 'length' => false],
            'editor' => ['title' => 'WYSIWYG Editor', 'type' => 'text', 'length' => false],
            'select' => ['title' => 'Dropdown', 'type' => 'text', 'length' => false, 'link' => false],
            'radio' => ['title' => 'Radios', 'type' => 'text', 'length' => false, 'link' => false],
            'check' => ['title' => 'Check Boxes', 'type' => 'text', 'length' => false, 'link' => false],
            'linkselect' => ['title' => 'Link Dropdown', 'type' => 'int', 'length' => '11', 'link' => true],
            'linkradio' => ['title' => 'Link Radios', 'type' => 'int', 'length' => '11', 'link' => true],
            'linkcheck' => ['title' => 'Link Check Boxes', 'type' => 'int', 'length' => '11', 'link' => true],
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
        return $fields;
    }

    // Methods
    public function add_field_a(array $options)
    {
        $errors = [];

        if (!isset($options['name']) || $options['name'] == '') {
            $errors[] = 'Name is required to create the field';
        }

        if (!isset($options['type']) || $options['type'] == '') {
            $errors[] = 'Field type is required to create the field';
        }

        if (empty($errors)) {
            $this->fields[$options['name']] = [
                'name' => $options['name'],
                'label' => (isset($options['label']) ? $options['label'] : $options['name']),
                'type' => $options['type'],
                'value' => (isset($options['value']) ? $options['value'] : NULL),
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

    public function add_field($name, $label = NULL, $type, $value = NULL, array $options = [], $required = false, $placeholder = NULL, $comment = NULL, $classes = NULL, $fieldset_classes = NULL)
    {
        $this->fields[$name] = [
            'name' => $name,
            'label' => $label,
            'type' => $type,
            'value' => $value,
            'options' => $options,
            'required' => $required,
            'placeholder' => $placeholder,
            'comment' => $comment,
            'classes' => $classes,
            'fieldset_classes' => $fieldset_classes,
        ];
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

    /* Text Box */
    public function add_text_a(array $options)
    {
        $options['type'] = 'text';
        $this->add_field_a($options);
    }

    public function add_text($name, $label = NULL, $value = NULL, $required = false, $placeholder = NULL, $comment = NULL, $classes = NULL, $fieldset_classes = NULL)
    {
        $this->add_field($name, $label, 'text', $value, [], $required, $placeholder, $comment, $classes, $fieldset_classes);
    }

    public function validate_text($value)
    {
        return false;
    }

    /* Tiny Int */
    public function add_tinyint_a(array $options)
    {
        $options['type'] = 'number';
        $this->add_field_a($options);
    }

    public function add_tinyint($name, $label = NULL, $value = NULL, $required = false, $placeholder = NULL, $comment = NULL, $classes = NULL, $fieldset_classes = NULL)
    {
        $this->add_field($name, $label, 'number', $value, [], $required, $placeholder, $comment, $classes, $fieldset_classes);
    }

    public function validate_tinyint($value)
    {
        if (!is_int($value)) {
            return 'Field is not an integer';
        }
        return false;
    }

    /* Int */
    public function add_int_a(array $options)
    {
        $options['type'] = 'number';
        $this->add_field_a($options);
    }

    public function add_int($name, $label = NULL, $value = NULL, $required = false, $placeholder = NULL, $comment = NULL, $classes = NULL, $fieldset_classes = NULL)
    {
        $this->add_field($name, $label, 'number', $value, [], $required, $placeholder, $comment, $classes, $fieldset_classes);
    }

    public function validate_int($value)
    {
        if (!is_int($value)) {
            return 'Field is not an integer';
        }
        return false;
    }

    /* Big Int */
    public function add_bigint_a(array $options)
    {
        $options['type'] = 'number';
        $this->add_field_a($options);
    }

    public function add_bigint($name, $label = NULL, $value = NULL, $required = false, $placeholder = NULL, $comment = NULL, $classes = NULL, $fieldset_classes = NULL)
    {
        $this->add_field($name, $label, 'number', $value, [], $required, $placeholder, $comment, $classes, $fieldset_classes);
    }

    public function validate_bigint($value)
    {
        if (!is_int($value)) {
            return 'Field is not an integer';
        }
        return false;
    }

    /* Price */
    public function add_price_a(array $options)
    {
        $options['type'] = 'text';
        $options['classes'] = $options['classes'] . ' text';
        $this->add_field_a($options);
    }

    public function add_price($name, $label = NULL, $value = NULL, $required = false, $placeholder = NULL, $comment = NULL, $classes = NULL, $fieldset_classes = NULL)
    {
        $this->add_field($name, $label, 'text', $value, [], $required, $placeholder, $comment, $classes . ' price', $fieldset_classes);
    }

    public function validate_price()
    {

    }

    /* Password */
    public function add_password_a(array $options)
    {
        $options['type'] = 'password';
        $this->add_field_a($options);
    }

    public function add_password($name, $label = NULL, $value = NULL, $required = false, $placeholder = NULL, $comment = NULL, $classes = NULL, $fieldset_classes = NULL)
    {
        $this->add_field($name, $label, 'password', $value, [], $required, $placeholder, $comment, $classes, $fieldset_classes);
    }

    public function validate_password()
    {
        return false;
    }

    /* Textarea */
    public function add_textarea_a(array $options)
    {
        $options['type'] = 'textarea';
        $this->add_field_a($options);
    }

    public function add_textarea($name, $label = NULL, $value = NULL, $required = false, $placeholder = NULL, $comment = NULL, $classes = NULL, $fieldset_classes = NULL)
    {
        $this->add_field($name, $label, 'textarea', $value, [], $required, $placeholder, $comment, $classes, $fieldset_classes);
    }

    public function validate_textarea()
    {
        return false;
    }

    /* WYSIWYG Editor */
    public function add_editor_a(array $options)
    {
        $options['type'] = 'textarea';
        $options['classes'] = $options['classes'] . ' editor';
        $this->add_field_a($options);
    }

    public function add_editor($name, $label = NULL, $value = NULL, $required = false, $placeholder = NULL, $classes = NULL, $comment = NULL, $fieldset_classes = NULL)
    {
        $this->add_field($name, $label, 'textarea', $value, [], $required, $placeholder, $comment, $classes . ' editor', $fieldset_classes);
    }

    public function validate_editor()
    {
        return false;
    }

    /* Select Dropdown */
    public function add_select_a(array $options)
    {
        $options['type'] = 'select';
        if (!is_array($options['options'])) {
            $options['options'] = [$options['options']];
        }
        $this->add_field_a($options);
    }

    public function add_select($name, $label = NULL, $options = [], $required = false, $placeholder = NULL, $comment = NULL, $classes = NULL, $fieldset_classes = NULL)
    {
        if (!is_array($options)) {
            $options = [$options];
        }
        $this->add_field($name, $label, 'select', NULL, $options, $required, $placeholder, $comment, $classes, $fieldset_classes);
    }

    public function validate_select()
    {
        return false;
    }

    /* Radio Buttons */
    public function add_radio_a(array $options)
    {
        $options['type'] = 'radio';
        if (!is_array($options['options'])) {
            $options['options'] = [$options['options']];
        }
        $this->add_field_a($options);
    }

    public function add_radio($name, $label = NULL, $options = [], $required = false, $placeholder = NULL, $comment = NULL, $classes = NULL, $fieldset_classes = NULL)
    {
        if (!is_array($options)) {
            $options = [$options];
        }
        $this->add_field($name, $label, 'radio', NULL, $options, $required, $placeholder, $comment, $classes, $fieldset_classes);
    }

    public function validate_radio()
    {
        return false;
    }

    /* Check Boxes */
    public function add_check_a(array $options)
    {
        $options['type'] = 'checkbox';
        if (!is_array($options['options'])) {
            $options['options'] = [$options['options']];
        }
        $this->add_field_a($options);
    }

    public function add_check($name, $label = NULL, $options = [], $required = false, $placeholder = NULL, $comment = NULL, $classes = NULL, $fieldset_classes = NULL)
    {
        if (!is_array($options)) {
            $options = [$options];
        }
        $this->add_field($name, $label, 'checkbox', NULL, $options, $required, $placeholder, $comment, $classes, $fieldset_classes);
    }

    public function validate_check()
    {
        return false;
    }

    /* Select Dropdown ( LINK field ) */
    public function add_linkselect_a(array $options)
    {
        $options['type'] = 'select';
        if (!is_array($options['options'])) {
            $options['options'] = [$options['options']];
        }
        $this->add_field_a($options);
    }

    public function add_linkselect($name, $label = NULL, $options = [], $required = false, $placeholder = NULL, $comment = NULL, $classes = NULL, $fieldset_classes = NULL)
    {
        if (!is_array($options)) {
            $options = [$options];
        }
        $this->add_field($name, $label, 'select', NULL, $options, $required, $placeholder, $comment, $classes, $fieldset_classes);
    }

    public function validate_linkselect()
    {
        return false;
    }

    /* Radio Buttons ( LINK field ) */
    public function add_linkradio_a(array $options)
    {
        $options['type'] = 'radio';
        if (!is_array($options['options'])) {
            $options['options'] = [$options['options']];
        }
        $this->add_field_a($options);
    }

    public function add_linkradio($name, $label = NULL, $options = [], $required = false, $placeholder = NULL, $comment = NULL, $classes = NULL, $fieldset_classes = NULL)
    {
        if (!is_array($options)) {
            $options = [$options];
        }
        $this->add_field($name, $label, 'radio', NULL, $options, $required, $placeholder, $comment, $classes, $fieldset_classes);
    }

    public function validate_linkradio()
    {
        return false;
    }

    /* Check Boxes ( LINK field ) */
    public function add_linkcheck_a(array $options)
    {
        $options['type'] = 'checkbox';
        if (!is_array($options['options'])) {
            $options['options'] = [$options['options']];
        }
        $this->add_field_a($options);
    }

    public function add_linkcheck($name, $label = NULL, $options = [], $required = false, $placeholder = NULL, $comment = NULL, $classes = NULL, $fieldset_classes = NULL)
    {
        if (!is_array($options)) {
            $options = [$options];
        }
        $this->add_field($name, $label, 'checkbox', NULL, $options, $required, $placeholder, $comment, $classes, $fieldset_classes);
    }

    public function validate_linkcheck()
    {
        return false;
    }

    /* Date Picker */
    public function add_datepicker_a(array $options)
    {

    }

    public function add_datepicker()
    {

    }

    public function validate_datepicker()
    {
        return false;
    }

    /* Colour Picker */
    public function add_colourpicker_a(array $options)
    {

    }

    public function add_colourpicker()
    {

    }

    public function validate_colourpicker()
    {
        return false;
    }

    /* Hidden */
    public function add_hidden_a(array $options)
    {
        $options['type'] = 'hidden';
        $this->add_field_a($options);
    }

    public function add_hidden($name, $value)
    {
        $this->add_field($name, NULL, 'hidden', $value, [], NULL);
    }

    public function validate_hidden()
    {
        return false;
    }

    /* Submit */
    public function add_submit_a($options)
    {
        $options['type'] = 'submit';
        $this->add_field_a($options);
    }

    public function add_submit($name, $value, $comment = NULL)
    {
        $this->add_field($name, NULL, 'submit', $value, [], NULL, $comment);
    }

    public function render_fields()
    {
        return $this->render(true);
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
                $fieldset_classes = ' class="' . $field['fieldset_classes'] . '"';
            }

            $field_label = '';
            if (isset($field['label'])) {
                $field_label = '<label for="' . $field['name'] . '">' . $field['label'] . ($required ? '<span class="req">*</span>' : '') . '</label>';
            }

            $field_value = '';
            if (isset($field['value'])) {
                $field_value = ' value="' . $field['value'] . '"';
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
                case 'password':
                    $html .= '<fieldset id="field-' . $field['name'] . '"' . $fieldset_classes . '>' . $field_label . $field_comment;
                    $html .= '<input type="password" ' . $field_id . $field_name . $field_value . ' placeholder="' . $field['placeholder'] . '" /></fieldset>';
                    break;
                case 'select':
                case 'linkselect':
                    $html .= '<fieldset id="field-' . $field['name'] . '"' . $fieldset_classes . '>' . $field_label . $field_comment;
                    if ($field['options'] || $field['placeholder']) {
                        $html .= '<select ' . $field_id . $field_classes . $field_name . '>';
                        if ($field['placeholder']) {
                            $html .= '<option selected disabled="disabled">' . $field['placeholder'] . '</option>';
                        }
                        if ($field['options']) {
                            foreach ($field['options'] as $option) {
                                $option_selected = (isset($option['selected']) && $option['selected'] === true) ? 'selected' : '';
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
                    $html .= '<fieldset id="field-' . $field['name'] . '"' . $fieldset_classes . '>' . $field_label . $field_comment;
                    if ($field['options']) {
                        foreach ($field['options'] as $option) {
                            $option_id = ($first) ? '' . $field_id . '' : '';
                            $option_selected = ($option['value']) ? 'checked' : '';
                            $html .= '<label class="radiofield"><input' . $field_classes . ' type="radio" ' . $option_id . ' name="' . $field['name'] . '[]" value="' . $option['value'] . '" ' . $option_selected . ' />' . $option['text'] . '</label>';
                            $first = false;
                        }
                    }
                    $html .= '</fieldset>';
                    break;
                case 'check':
                case 'linkcheck':
                    $html .= '<fieldset id="field-' . $field['name'] . '"' . $fieldset_classes . '>' . $field_label . $field_comment;
                    if ($field['options']) {
                        foreach ($field['options'] as $option) {
                            $option_id = ($first) ? '' . $field_id . '' : '';
                            $option_selected = (isset($option['selected']) && $option['selected'] == true) ? 'checked' : '';
                            $html .= '<label class="checkfield"><input' . $field_classes . ' type="checkbox" ' . $option_id . ' name="' . $field['name'] . '[]" value="' . $option['value'] . '" ' . $option_selected . ' />' . $option['text'] . '</label>';
                            $first = false;
                        }
                    }
                    $html .= '</fieldset>';
                    break;
                case 'textarea':
                    $html .= '<fieldset id="field-' . $field['name'] . '"' . $fieldset_classes . '>' . $field_label . $field_comment;
                    $html .= '<textarea ' . $field_id . $field_classes . $field_name . '>' . $field['value'] . '</textarea></fieldset>';
                    break;
                case 'editor':
                    $html .= '<fieldset id="field-' . $field['name'] . '"' . $fieldset_classes . '>' . $field_label . $field_comment;
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
                    $html .= '<fieldset id="field-' . $field['name'] . '">' . $field_comment . '<button ' . $field_id . $field_classes . $field_name . '>' . $field['value'] . '</button></fieldset>';
                    break;
                case 'tinyint':
                case 'int':
                case 'bigint':
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

    public function populate_form($fields)
    {
        foreach ($this->get_fields() as $field_key => $field) {
            if (ipsCore_form_builder::get_field_types($field['type'])) {
                if (is_object($fields)) {
                    if (isset($fields->$field_key)) {
                        $this->fields[$field_key]['value'] = $fields->$field_key;
                    }
                } else {
                    if (isset($fields[$field_key])) {
                        $this->fields[$field_key]['value'] = $fields[$field_key];
                    }
                }
            }
        }
    }

    public function validate_form(&$errors)
    {
        foreach ($this->get_fields() as $field) {
            if (ipsCore_form_builder::get_field_types($field['type'])) {
                $errored = false;

                // Required validation
                if ( $field['required'] == true ) {
                    if ( !isset( $field['value']) || empty( $field['value'] ) || $field['value'] == '' || $field['value'] == ' ' ) {
                        $errors[$field['name']] = 'This is a required field';
                        $errored = true;
                    }
                }

                if ( !$errored ) {
                    // Basic field validation
                    $validate_func = 'validate_' . $field['type'];
                    $error = $this->{$validate_func}($field['value']);
                    if ($error) {
                        $errors[$field['name']] = $error;
                        $errored = true;
                    }
                }
            }
        }
    }

}