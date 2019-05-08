<?php // IPS-CORE Uploader

class ipsCore_uploader
{

    public static $upload_directory = '/public/uploads/';

    public static function get_sent_file($name)
    {
        $file = false;
        if (isset($_FILES['files']) && !empty($_FILES['files'])) {
            $file = [];
            foreach ($_FILES['files'] as $key => $value) {
                $file[$key] = $_FILES['files'][$key][$name];
            }
        }

        return $file;
    }

    public static function do_upload_file($file) {

    }

    public static function validate_file() {

    }

    public static function validate_image() {

    }
}
