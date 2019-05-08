<?php // IPS-CORE Uploader

class ipsCore_uploader
{

    public static $upload_directory = '/public/uploads/';
    public static $allowed_types_images = ['jpg','png','jpeg','gif'];
    public static $allowed_types_files = [];

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

    public static function do_upload_file($file, $type) {
        if ($type == 'image') {
            if (ipsCore_uploader::validate_image($file)) {

            }
        } elseif ($type == 'file') {
            if (ipsCore_uploader::validate_file($file)) {

            }
        }
    }

    public static function validate_file($file) {
        return true;
    }

    public static function validate_image($file) {
        $fileType = pathinfo($file,PATHINFO_EXTENSION);
        return true;
    }
}
