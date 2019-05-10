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
    ];

    public static $password_complexity = '^\S*(?=\S{6,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])\S*$';
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

    }

    public function get_field_value($field)
    {
        //return (isset($this->fields[$field]['value']) ? $this->fields[$field]['value'] : ( $this->fields[$field]['default'] ? $this->fields[$field]['default'] : NULL ) );
        if (isset($this->fields[$field]['value'])) {
            return $this->fields[$field]['value'];
        } elseif (isset($this->fields[$field]['default'])) {
            return $this->fields[$field]['default'];
        } else {
            return null;
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

    public function add_form_html($html)
    {
        $this->html .= $html;
    }

    public static function add_field_type($key, array $args)
    {
        ipsCore_form_builder::$field_types[$key] = $args;
    }

    // Construct
    public function __construct($name)
    {
        $this->name = $name;
    }

    // Field Types
    public static function get_field_types($type = false, $args = [])
    {
        $fields = ipsCore_form_builder::$field_types;

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
                'label' => ($label ?: $name),
                'type' => $type,
                'value' => (isset($options['value']) ? $options['value'] : null),
                'default' => (isset($options['default']) ? $options['default'] : null),
                'options' => (isset($options['options']) ? $options['options'] : []),
                'required' => (isset($options['required']) ? $options['required'] : false),
                'placeholder' => (isset($options['placeholder']) ? $options['placeholder'] : null),
                'placeholder_selectable' => (isset($options['placeholder_selectable']) ? $options['placeholder_selectable'] : null),
                'comment' => (isset($options['comment']) ? $options['comment'] : null),
                'classes' => (isset($options['classes']) ? $options['classes'] : null),
                'fieldset_classes' => (isset($options['fieldset_classes']) ? $options['fieldset_classes'] : null),
            ];
        } else {
            foreach ($errors as $error) {
                ipsCore::error($error);
            }
        }
    }

    public function start_section($name)
    {
        $this->fields['section_start_' . $name] = [
            'name' => $name,
            'type' => 'section_start',
        ];
    }

    public function render_start_section($field)
    {

    }

    public function end_section($name)
    {
        $this->fields['section_end_' . $name] = [
            'name' => $name,
            'type' => 'section_end',
        ];
    }

    public function render_end_section($field)
    {

    }

    public function add_html($name, $content)
    {
        $this->fields['html_' . $name] = [
            'name' => $name,
            'placeholder' => $content,
            'type' => 'html',
        ];
    }

    public function render_html($field)
    {

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

    public function render_text($field)
    {

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

    public function render_int($field)
    {

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

    public function render_price($field)
    {

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

    public function render_email($field)
    {

    }

    /* Password */
    public function add_password($name, $label, array $options = [])
    {
        $this->add_field($name, $label, 'password', $options);
    }

    public function validate_password($field)
    {
        if (!preg_match(ipsCore_form_builder::$password_complexity, $this->fields[$field]['value'])) {
            return ipsCore_form_builder::$password_message;
        }

        return false;
    }

    public function render_password($field)
    {

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

    public function render_textarea($field)
    {

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

    public function render_editor($field)
    {

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

    public function render_select($field)
    {

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

    public function render_radio($field)
    {

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

    public function render_check($field)
    {

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

    public function render_datepicker($field)
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

    public function render_colourpicker($field)
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

    public function render_file($field)
    {

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

    public function render_image($field)
    {

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

    public function render_hidden($field)
    {

    }

    /* Submit */
    public function add_submit($name, $label, array $options = [])
    {
        $this->add_field($name, $label, 'submit', $options);
    }

    public function render_submit($field)
    {

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
            $this->add_form_html('<form id="' . $this->get_name() . '"
					class="' . implode(' ', $this->get_classes()) . '"
					action="' . $this->get_action() . '"
					method="' . $this->get_method() . '">');
        }

        foreach ($this->get_fields() as $field) {
            $value = '';
            $placeholder = '';
            $label = '';
            $first = true;
            $required = false;

            $field_name = ' name="' . $field['name'] . '"';
            if (isset($field['multiple']) && $field['multiple']) {
                $field_name = ' name="' . $field['name'] . '[]"';
            }

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

            $placeholder_selectable = false;
            if (isset($field['placeholder_selectable']) && $field['placeholder_selectable']) {
                $placeholder_selectable = true;
            }

            switch ($field['type']) {
                case 'section_start':
                    $this->add_form_html('<div id="' . $field['name'] . '" class="form-section">');
                    break;
                case 'section_end':
                    $this->add_form_html('</div>');
                    break;
                case 'html':
                    $this->add_form_html($field['placeholder']);
                    break;
                case 'password':
                    $this->add_form_html('<fieldset id="field-' . $field['name'] . '" class="password ' . $fieldset_classes . '">' . $field_label . $field_comment);
                    $this->add_form_html('<input type="password" id="' . $field['name'] . '"' . $field_name . $field_value . ' placeholder="' . $field['placeholder'] . '" /></fieldset>');
                    break;
                case 'select':
                case 'linkselect':
                    $this->add_form_html('<fieldset id="field-' . $field['name'] . '" class="select ' . $fieldset_classes . '">' . $field_label . $field_comment);
                    if ($field['options'] || $field['placeholder']) {
                        $this->add_form_html('<select id="' . $field['name'] . '"' . $field_classes . $field_name . '>');
                        if ($field['placeholder']) {
                            $this->add_form_html('<option selected ' . ($placeholder_selectable ? 'value="0"' : 'disabled="disabled"') . ' >' . $field['placeholder'] . '</option>');
                        }
                        if ($field['options'] && !empty($field['options'])) {
                            foreach ($field['options'] as $option) {
                                $option_selected = ((isset($option['selected']) && $option['selected'] === true) || ($option['value'] == $field['value'])) ? 'selected' : '';
                                $option_disabled = (isset($option['disabled']) && $option['disabled'] === true) ? ' disabled' : '';
                                $this->add_form_html('<option value="' . $option['value'] . '" ' . $option_selected . $option_disabled . '>' . $option['text'] . '</option>');
                            }
                        }
                        $this->add_form_html('</select>');
                    } else {
                        $this->add_form_html('<p>No Options</p>');
                    }
                    $this->add_form_html('</fieldset>');
                    break;
                case 'radio':
                case 'linkradio':
                    $this->add_form_html('<fieldset id="field-' . $field['name'] . '" class="radio ' . $fieldset_classes . '">' . $field_label . $field_comment);
                    if ($field['placeholder']) {
                        $this->add_form_html('<label class="radiofield"><input' . $field_classes . ' type="radio" name="' . $field['name'] . '[]" value="0" />' . $field['placeholder'] . '</label>');
                    }
                    if ($field['options']) {
                        foreach ($field['options'] as $option) {
                            $option_id = ($first) ? 'id="' . $field['name'] . '"' . '' : '';
                            $option_selected = ($option['value'] == $field['value']) ? 'checked' : '';
                            $this->add_form_html('<label class="radiofield"><input' . $field_classes . ' type="radio" ' . $option_id . ' name="' . $field['name'] . '[]" value="' . $option['value'] . '" ' . $option_selected . ' />' . $option['text'] . '</label>');
                            $first = false;
                        }
                    }
                    $this->add_form_html('</fieldset>');
                    break;
                case 'check':
                case 'linkcheck':
                    $this->add_form_html('<fieldset id="field-' . $field['name'] . '" class="check ' . $fieldset_classes . '">' . $field_label . $field_comment);
                    if ($field['options']) {
                        foreach ($field['options'] as $option) {
                            $option_id = ($first) ? 'id="' . $field['name'] . '"' . '' : '';
                            $option_selected = ((isset($option['selected']) && $option['selected'] == true) || ($option['value'] == $field['value'])) ? 'checked' : '';
                            $this->add_form_html('<label class="checkfield"><input' . $field_classes . ' type="checkbox" ' . $option_id . ' name="' . $field['name'] . '[]" value="' . $option['value'] . '" ' . $option_selected . ' />' . $option['text'] . '</label>');
                            $first = false;
                        }
                    }
                    $this->add_form_html('</fieldset>');
                    break;
                case 'textarea':
                    $this->add_form_html('<fieldset id="field-' . $field['name'] . '" class="textarea ' . $fieldset_classes . '">' . $field_label . $field_comment);
                    $this->add_form_html('<textarea id="' . $field['name'] . '"' . $field_classes . $field_name . '>' . $field['value'] . '</textarea></fieldset>');
                    break;
                case 'editor':
                    $this->add_form_html('<fieldset id="field-' . $field['name'] . '" class="editor ' . $fieldset_classes . '">' . $field_label . $field_comment);
                    $this->add_form_html('<textarea id="' . $field['name'] . '"' . $field_classes . $field_name . '>' . $field['value'] . '</textarea></fieldset>');
                    break;
                case 'datepicker':

                    break;
                case 'colourpicker':

                    break;
                case 'hidden':
                    $this->add_form_html('<input type="hidden" id="' . $field['name'] . '"' . $field_name . $field_value . ' />');
                    break;
                case 'submit':
                    //$this->add_form_html('<fieldset id="field-' . $field['name'] . '"' . $fieldset_classes . '><input type="submit" id="' . $field['name'] . '"' . $field_classes . $field_name . $field_value . ' /></fieldset>';
                    $this->add_form_html('<fieldset id="field-' . $field['name'] . '" class="submit">' . $field_comment . '<button id="' . $field['name'] . '"' . $field_classes . $field_name . '>' . $field['label'] . '</button></fieldset>');
                    break;
                case 'int':
                    $this->add_form_html('<fieldset id="field-' . $field['name'] . '" class="int ' . $fieldset_classes . '">' . $field_label . $field_comment);
                    $this->add_form_html('<input type="number" id="' . $field['name'] . '"' . $field_classes . $field_name . $field_value . ' placeholder="' . $field['placeholder'] . '" /></fieldset>');
                    break;
                case 'price':
                    $this->add_form_html('<fieldset id="field-' . $field['name'] . '" class="price ' . $fieldset_classes . '">' . $field_label . $field_comment);
                    $this->add_form_html('<input type="text" id="' . $field['name'] . '"' . $field_classes . $field_name . $field_value . ' placeholder="' . $field['placeholder'] . '" /></fieldset>');
                    break;
                case 'file':
                    $this->add_form_html('<fieldset id="field-' . $field['name'] . '" class="file ' . $fieldset_classes . '">' . $field_label . $field_comment);
                    $this->add_form_html('<input type="file" id="' . $field['name'] . '"' . $field_classes . $field_name . $field_value . ' placeholder="' . $field['placeholder'] . '" />');
                    if (isset($field['preview']) && !empty($field['preview'])) {
                        $this->add_form_html('<p class="preview"><a class="preview" href="' . $field['preview']['url'] . '" ></a><a class="preview-remove" href="' . $field['preview']['remove'] . '" ></a></p>');
                    }
                    $this->add_form_html('</fieldset>');
                    break;
                case 'image':
                    $this->add_form_html('<fieldset id="field-' . $field['name'] . '" class="image ' . $fieldset_classes . '">' . $field_label . $field_comment);
                    $this->add_form_html('<input type="file" id="' . $field['name'] . '"' . $field_classes . $field_name . $field_value . ' placeholder="' . $field['placeholder'] . '" />');
                    if (isset($field['preview']) && !empty($field['preview'])) {
                        $this->add_form_html('<div class="preview" src="' . $field['preview']['url'] . '" ><a class="preview-url" href="' . $field['preview']['url'] . '"><a class="preview-remove" href="' . $field['preview']['remove'] . '"></a></a></div>');
                    }
                    $this->add_form_html('</fieldset>');
                    break;
                case 'text':
                default:
                    $this->add_form_html('<fieldset id="field-' . $field['name'] . '" class="text ' . $fieldset_classes . '">' . $field_label . $field_comment);
                    $this->add_form_html('<input type="text" id="' . $field['name'] . '"' . $field_classes . $field_name . $field_value . ' placeholder="' . $field['placeholder'] . '" /></fieldset>');
                    break;
            }
        }
        if (!$fields_only) {
            $this->add_form_html('</form>');
        }

        return $this->get_form_html();
    }

    public function populate_form($fields = false)
    {
        if (!$fields) {
            $fields = (isset($_REQUEST) ? $_REQUEST : []);
        }
        foreach ($this->get_fields() as $field_key => $field) {
            if ($field_type = ipsCore_form_builder::get_field_types($field['type'])) {
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
        foreach (ipsCore_form_builder::get_field_types() as $field_type_key => $field_type) {
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
                $option = explode(' : ', $option);
                $return[] = [
                    'value' => $option[0],
                    'text' => $option[1],
                ];
            }
        }

        return serialize($return);
    }

}
