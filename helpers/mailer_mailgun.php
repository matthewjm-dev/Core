<?php

use Mailgun\Mailgun;

class ipsCore_mailer_mailgun
{
    protected $mailgun = false;
    protected $required_settings = ['mailgun_key', 'mailgun_server', 'mailgun_domain'];
    protected $key = false;
    protected $server = false;
    protected $domain = false;
    protected $from = false;

    // Construct

    public function __construct()
    {
        if ($this->setup()) {

            if ($this->server) {
                $this->mailgun = new Mailgun($this->key, $this->server); // Mailgun::create
            } else {
                $this->mailgun = new Mailgun($this->key);
            }
        } else {
            ipsCore::add_error('Failed to setup Mailgun', true);
        }
    }

    // Methods

    public function setup() {
        $error = false;

        foreach($this->required_settings as $setting) {
            if (isset(ipsCore::$app->mailer[$setting])) {
                $this->{$setting} = ipsCore::$app->mailer[$setting];
            } else {
                $error = true;
                ipsCore::add_error('Require Mailgun setting missing in App config: ' . $setting);
            }
        }

        if (!$error) {
            return true;
        }
        return false;
    }

    public function send($to, $subject, $content, $from = false, $args = [])
    {
        if ($this->mailgun) {
            if (!$from) {
                $from = $this->from;
            }

            $args = array_merge([
                'from' => $from,
                'to' => $to,
                'subject' => $subject,
                'html' => $content,
            ], $args);

            if( $response = $this->mailgun->sendMessage($this->domain, $args) ) {
                return true;
            }
        }
        return false;
    }

    public function add_to_queue()
    {

    }

    public function send_queue()
    {

    }
}
