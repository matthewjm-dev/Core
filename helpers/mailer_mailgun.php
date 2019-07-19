<?php

use Mailgun\Mailgun;

class ipsCore_mailer_mailgun
{
    protected $mailgun = false;
    protected $required_settings = ['mailgun_key', 'mailgun_server', 'mailgun_domain', 'from'];
    protected $mailgun_key = false;
    protected $mailgun_server = false;
    protected $mailgun_domain = false;
    protected $from = false;

    // Construct

    public function __construct()
    {
        if ($this->setup()) {
            if ($this->mailgun_server) {
                $this->mailgun = new Mailgun($this->mailgun_key, null, $this->mailgun_server); // Mailgun::create
            } else {
                $this->mailgun = new Mailgun($this->mailgun_key);
            }
        } else {
            ipsCore::add_error('Failed to setup Mailgun', true);
        }
    }

    // Methods

    public function setup()
    {
        $error = false;

        foreach ($this->required_settings as $setting) {
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

            if ($response = $this->mailgun->sendMessage($this->mailgun_domain, $args)) {
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
