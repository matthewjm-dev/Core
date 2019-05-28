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
            $this->mailgun = Mailgun::create($this->key, $this->server);
        } else {
            $this->mailgun = Mailgun::create($this->key);
        }
    }

    // Methods

    public function send($to, $subject, $content, $from = false)
    {
        if (!$from) {
            $from = $this->from;
        }

        $this->mailgun->messages($this->domain, [
            'from' => $from,
            'to' => $to,
            'subject' => $subject,
            'text' => $content,
        ]);
    }

    public function add_to_queue()
    {

    }

    public function send_queue()
    {

    }
}
