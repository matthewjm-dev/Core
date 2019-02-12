<?php // IPS-CORE Form Process

global $ips_core_form;
$ips_core_form = new ips_core_form();

class ips_core_form {
	public $errors = array();
	public $success = 'Thank you for your message. We will get back to you as soon as possible';

	public function __construct() {

		global $es_woo;

		$forms = array(
			'es_contact_form_ajax' => true,
			'es_lost_password_ajax' => array($es_woo->account, 'process_lost_password_form'),
			'es_reset_password_ajax' => array($es_woo->account, 'process_reset_password_form'),
			'es_newsletter_signup' => 'newsletter',
		);

		foreach ( $forms as $action => $method ) {
			if ( $method === true ) {
				$method = 'process_forms_ajax';
			}

			if ( is_array( $method ) ) {
				$callback = $method;
			} else {
				$callback = array($this, $method);
			}

			add_action( 'wp_ajax_nopriv_' . $action, $callback );
			add_action( 'wp_ajax_' . $action, $callback );
		}

		// Hook on to the 'wp' action to process the forms so the $post variable is available
		add_action( 'wp', array( $this, 'process_forms' ) );
	}

	public function process_forms_ajax() {
		if ( ! isset( $_POST[ 'action' ] ) ) {
			return false;
		}

		switch ( $_POST[ 'action' ] ) {
			case 'es_contact_form_ajax':
				$this->process_contact_form( true );
				break;
			case 'es_lost_password_ajax':
				$this->process_lost_password_form(true);
				break;
			case 'es_reset_password_ajax':
				$this->process_reset_password_form(true);
				break;
			default:
				break;
		}

		$json = array();

		if ( empty( $this->errors ) ) {
			$json[ 'success' ] = 'Thanks for your message. We will be in touch as soon as possible';
		} else {
			$json[ 'errors' ] = $this->errors;
		}

		wp_send_json( $json );
	}

	public function process_forms() {
		if ( ! isset( $_POST[ 'action' ] ) ) {
			return false;
		}

		switch ( $_POST[ 'action' ] ) {
			case 'es_contact_form_ajax':
				$this->process_contact_form();
				break;
			default:
				break;
		}
	}

	public function newsletter($contact_form = false) {

		if ( ! $contact_form ) {

			if ( empty( $_POST[ 'email' ] ) || ! is_email( $_POST[ 'email' ] ) ) {
				$this->errors[ 'email' ] = 'Please enter a valid email address';
			} else {
				$email = $_POST[ 'email' ];
			}
		} else {
			$email = $_POST[ 'contact_email' ];
		}

		if(empty($this->errors)) {

			include_once(get_template_directory() . '/includes/createsend/csrest_subscribers.php');

			$wrap = new CS_REST_Subscribers('CLIENT_ID_HERE', array('api_key' => 'API_KEY_HERE'));

			$result = $wrap->add(array(
				'EmailAddress' => $email,
				'Resubscribe' => true
			));

			$newsletter_success = 'Thanks for signing up to our newsletter';

			if($result->http_status_code == 201) {
				$newsletter_success = 'Thanks for signing up to our newsletter';
			} else {
				//$this->errors[''] = 'An unknown error occurred. Please try again later';
			}
		}

		$json = array();

		if(!empty($this->errors)) {

			if($contact_form) {
				return false;
			}

			$json['errors'] = $this->errors;

		} else {

			if($contact_form) {
				return true;
			}

			$json['success'] = 'Thanks for signing up to our newsletter!';
		}

		wp_send_json( $json );
	}

	private function process_contact_form($is_ajax = false) {

		if($this->validate_contact_form()) {

			$post = get_post();

			if(!empty($_POST['newsletter'])) {
				$this->newsletter(true);
			}

			require_once(get_template_directory() . '/classes/class.phpmailer.php');

			$mail = new PHPMailer();

			if(!empty($_POST['contact_email'])) {
				$mail->addReplyTo($_POST['contact_email']);
			}

			$admin_email = 'info@muddybumbikes.com';

			$mail->SetFrom($admin_email, 'Muddybum Bikes Website');

			$mail->addAddress($admin_email);

			$mail->addBcc('andy@exleysmith.com');

			$mail->Subject = 'New Website Enquiry';

			$mail->Body = "<p>You have received a message from the Contact Form on the Muddybum Bikes website:<p>";

			$mail->Body .= '<p>' .
								'Name: ' . strip_tags($_POST['contact_name']) . '<br>' .
								'Email: ' . strip_tags($_POST['contact_email']) . '<br>';

			$mail->Body .= '</p>';

			$mail->Body .= '<p>Message:<br>';
			$mail->Body .= htmlspecialchars($_POST['contact_message']);
			$mail->Body .= '</p>';

			$mail->isHTML(true);

			if($mail->send()) {
				if(!$is_ajax) {
					wp_redirect(add_query_arg('success', '1', get_permalink($post->ID)), 302);
				}
			} else {
				$this->errors['mail'] = 'There was a problem sending your message. Please try again later';
			}
		}
	}

	private function validate_contact_form() {
		if ( ! empty( $_POST[ 'contact_url' ] ) ) {
			$this->errors[ 'contact_url' ] = 'Anti-spam field should be left blank';

			return false;
		}

		if ( empty( $_POST[ 'contact_name' ] ) ) {
			$this->errors[ 'contact_name' ] = 'Please tell us your name';
		}

		if ( empty( $_POST[ 'contact_email' ] ) || ! is_email( $_POST[ 'contact_email' ] ) ) {
			$this->errors[ 'contact_email' ] = 'Please enter a valid email address';
		}

		if ( empty( $_POST[ 'contact_message' ] ) ) {
			$this->errors[ 'contact_message' ] = 'Please enter a message';
		}

		if ( empty( $this->errors ) ) {
			return true;
		} else {
			return false;
		}
	}
}
