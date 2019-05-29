<?php

use Mailgun\Mailgun;

class ipsCore_mailer_mailgun
{
    protected $mailgun = false;
    protected $key = MAILGUN_KEY;
    protected $server = MAILGUN_SERVER;
    protected $domain = MAILGUN_DOMAIN;
    protected $from = MAILER_FROM;

    // Construct

    public function __construct()
    {
        if ($this->server) {
            $this->mailgun = new Mailgun($this->key, $this->server); // Mailgun::create
        } else {
            $this->mailgun = new Mailgun($this->key);
        }
    }

    // Methods

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
