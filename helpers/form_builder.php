<?php // IPS-CORE Form Builder

class ipsCore_form_builder {

	protected $name;
	protected $method = 'POST';
	protected $action;
	protected $classes = [];
	protected $fields = [];

	// Getters
	public function get_name() { return $this->name; }
	public function get_method() { return $this->method; }
	public function get_action() { return $this->action; }
	public function get_classes() { return $this->classes; }
	private function get_fields() { return $this->fields; }

	// Setters
	public function set_method( $method ) { $this->method = $method; }
	public function set_action( $action ) { $this->action = $action; }
	public function set_classes( $classes ) { $this->classes = $classes; }

	// Construct
	public function __construct( $name ) {
		$this->name = $name;
	}

	// Methods
	private function add_field( $name, $label = NULL, $type, $value = NULL, array $options = [], $placeholder = NULL, $classes = NULL ) {
		$this->fields[ $name ] = [
			'name'        => $name,
			'label'       => $label,
			'type'        => $type,
			'value'       => $value,
			'options'     => $options,
			'placeholder' => $placeholder,
			'classes'     => $classes,
		];
	}

	public function start_section_repeater( $name ) {
		$this->fields[ 'section_start_' . $name ] = [
			'name'        => $name,
			'type'        => 'section_start_repeater',
		];
	}

	public function start_section( $name ) {
		$this->fields[ 'section_start_' . $name ] = [
			'name'        => $name,
			'type'        => 'section_start',
		];
	}

	public function end_section( $name ) {
		$this->fields[ 'section_end_' . $name ] = [
			'name'        => $name,
			'type'        => 'section_end',
		];
	}

	public function add_text( $name, $label = NULL, $value = NULL, $placeholder = NULL, $classes = NULL ) {
		$this->add_field( $name, $label, 'text', $value, [], $placeholder, $classes );
	}

	public function add_number( $name, $label = NULL, $value = NULL, $placeholder = NULL, $classes = NULL ) {
		$this->add_field( $name, $label, 'number', $value, [], $placeholder );
	}

	public function add_password( $name, $label = NULL, $value = NULL, $placeholder = NULL, $classes = NULL ) {
		$this->add_field( $name, $label, 'password', $value, [], $placeholder );
	}

	public function add_select( $name, $label = NULL, array $options = [], $placeholder = NULL, $classes = NULL ) {
		if ( count( $options ) > 1 ) {
			$this->add_field( $name, $label, 'select', NULL, $options, $placeholder );
		} else {
			ipsCore::add_error( 'select input Options requires an array' );
		}
	}

	public function add_radio( $name, $label = NULL, array $options = [], $placeholder = NULL, $classes = NULL ) {
		if ( count( $options ) > 1 ) {
			$this->add_field( $name, $label, 'radio', NULL, $options, $placeholder );
		} else {
			ipsCore::add_error( 'radio input requires 1 of more options' );
		}
	}


	public function add_check( $name, $label = NULL, array $options = [], $placeholder = NULL, $classes = NULL ) {
		$this->add_field( $name, $label, 'checkbox', NULL, $options, $placeholder );
	}

	public function add_textarea( $name, $label = NULL, $value = NULL, $placeholder = NULL, $classes = NULL ) {
		$this->add_field( $name, $label, 'textarea', $value, [], $placeholder );
	}

	public function add_editor( $name, $label = NULL, $value = NULL, $placeholder = NULL, $classes = NULL ) {
		$this->add_field( $name, $label, 'textarea', $value, [], $placeholder, 'editor' );
	}

	public function add_datepicker() {

	}

	public function add_hidden( $name, $value ) {
		$this->add_field( $name, NULL, 'hidden', $value, [], NULL );
	}

	public function add_submit( $name, $value ) {
		$this->add_field( $name, NULL, 'submit', $value, [], NULL );
	}

	public function render() {
		$html = '<form id="' . $this->get_name() . '"
					class="' . implode( ' ', $this->get_classes() ) . '"
					action="' . $this->get_action() . '"
					method="' . $this->get_method() . '">';

		foreach ( $this->get_fields() as $field ) {
			$value = '';
			$placeholder = '';
			$label = '';
			$first = true;

			$field_id = 'id="' . $field[ 'name' ] . '"';
			$field_name = ' name="' . $field['name'] . '"';

			$field_classes = '';
			if ( isset( $field['classes'] ) ) {
				$field_classes = ' class="' . $field['classes'] . '"';
			}

			$field_label = '';
			if ( isset( $field['label'] ) ) {
				$field_label = '<label for="' . $field['name'] . '">' . $field['label'] . '</label>';
			}

			$field_value = '';
			if ( isset( $field['value'] ) ) {
				$field_value = ' value="' . $field['value'] . '"';
			}

			switch ( $field['type'] ) {
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
					$html .= '<fieldset id="field-' . $field['name'] . '">' . $field_label;
					$html .= '<input type="password" ' . $field_id . $field_classes . $field_name . $field_value . ' placeholder="' . $field['placeholder'] . '" /></fieldset>';
				break;
				case 'select':
					$html .= '<fieldset id="field-' . $field['name'] . '">' . $field_label;
					if ( $field['options'] ) {
						$html .= '<select ' . $field_id . $field_classes . $field_name . '>';
						foreach ( $field['options'] as $option ) {
							$option_selected = ( isset( $option['selected'] ) ) ? 'selected' : '';
							$option_disabled = ( isset( $option['disabled'] ) && $option['disabled'] === true ) ? ' disabled' : '';
							$html .= '<option value="' . $option['value'] . '" ' . $option_selected . $option_disabled . '>' . $option['text'] . '</option>';
						}
						$html .= '</select>';
					} else {
						$html .= '<p>No Options</p>';
					}
					$html .= '</fieldset>';
				break;
				case 'radio':
					$html .= '<fieldset id="field-' . $field['name'] . '">' . $field_label;
					foreach ( $field['options'] as $option ) {
						$option_id = ( $first ) ? '' . $field_id . '' : '';
						$option_checked = ( $option['value'] ) ? 'checked' : '';
						$html .= '<label class="radiofield"><input' . $field_classes . ' type="radio" ' . $option_id . ' name="' . $field['name'] . '[]" value="' . $option['value'] . '" ' . $option_checked . ' />' . $option['text'] . '</label>';
						$first = false;
					}
					$html .= '</fieldset>';
				break;
				case 'checkbox':
					$html .= '<fieldset id="field-' . $field['name'] . '">' . $field_label;
					foreach ( $field['options'] as $option ) {
						$option_id = ( $first ) ? '' . $field_id . '' : '';
						$option_checked = ( isset( $option['checked'] ) && $option['checked'] == true ) ? 'checked' : '';
						$html .= '<label class="checkfield"><input' . $field_classes . ' type="checkbox" ' . $option_id . ' name="' . $field['name'] . '[]" value="' . $option['value'] . '" ' . $option_checked . ' />' . $option['text'] . '</label>';
						$first = false;
					}
					$html .= '</fieldset>';
				break;
				case 'textarea':
					$html .= '<fieldset id="field-' . $field['name'] . '">' . $field_label;
					$html .= '<textarea ' . $field_id . $field_classes . $field_name . '>' . $field['value'] . '</textarea></fieldset>';
				break;
				case 'editor':
					$html .= '<fieldset id="field-' . $field['name'] . '">' . $field_label;
					$html .= '<textarea ' . $field_id . $field_classes . $field_name . '>' . $field['value'] . '</textarea></fieldset>';
				break;
				case 'datepicker':

				break;
				case 'hidden':
					$html .= '<input type="hidden" ' . $field_id . $field_name . $field_value . ' />';
				break;
				case 'submit':
					//$html .= '<fieldset id="field-' . $field['name'] . '"><input type="submit" ' . $field_id . $field_classes . $field_name . $field_value . ' /></fieldset>';
					$html .= '<fieldset id="field-' . $field['name'] . '"><button ' . $field_id . $field_classes . $field_name . '>' . $field['value'] . '</button></fieldset>';
				break;
				case 'number':
				default:
					$html .= '<fieldset id="field-' . $field['name'] . '">' . $field_label;
					$html .= '<input type="number" ' . $field_id . $field_classes . $field_name . $field_value . ' placeholder="' . $field['placeholder'] . '" /></fieldset>';
				break;
				case 'text':
				default:
					$html .= '<fieldset id="field-' . $field['name'] . '">' . $field_label;
					$html .= '<input type="text" ' . $field_id . $field_classes . $field_name . $field_value . ' placeholder="' . $field['placeholder'] . '" /></fieldset>';
				break;
			}
		}
		$html .= '</form>';

		return $html;
	}

}