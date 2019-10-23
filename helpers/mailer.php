<?php

class ipsCore_mailer
{

    public function send($to, $subject, $content, $from = false)
    {
        if (is_array($to)) {
            $to = implode(',', $to);
        }

        if (!$from) {
            $from = ipsCore::$app->mailer['from'];
        }

        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= 'From: ' . $from . "\r\n";

        mail($to, $subject, $content, $headers);
    }

}
