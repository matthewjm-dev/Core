<?php // IPS-CORE Uploader

class ipsCore_uploader
{

    public static $upload_directory = 'uploads/';
    public static $max_upload_size = '500000';
    public static $allowed_types_images = ['jpg', 'png', 'jpeg', 'gif'];
    public static $allowed_types_files = ['pdf'];

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

    public static function do_upload_file($file_name, $type)
    {
        $errors = [];

        $raw_file = ipsCore_uploader::get_sent_file($file_name);
        $raw_file['uploadto'] = ipsCore_uploader::$upload_directory . $raw_file['name'];
        $raw_file['extension'] = strtolower(pathinfo($raw_file['name'], PATHINFO_EXTENSION));

        if ($type == 'image') {
            ipsCore_uploader::validate_image($raw_file, $errors);
        } elseif ($type == 'file') {
            ipsCore_uploader::validate_file($raw_file, $errors);
        }

        if (empty($errors)) {
            if (file_exists(ipsCore_uploader::$upload_directory)) {
                if (!file_exists($raw_file['uploadto'])) {
                    if ($raw_file["size"] <= ipsCore_uploader::$max_upload_size) {
                        if (!move_uploaded_file($raw_file["tmp_name"], $raw_file['uploadto'])) {
                            $errors[] = 'There was a problem moving the uploaded file.';
                        }
                    } else {
                        $errors[] = 'That file is too large, max upload size is: ' . ipsCore_uploader::$max_upload_size . ' bytes.';
                    }
                } else {
                    $errors[] = 'A File with that name already exists.';
                }
            } else {
                $errors[] = 'The uploads directory does not appear to exist.';
            }
        }

        return (empty($errors) ? $raw_file : ['errors' => $errors]);
    }

    public static function validate_file($raw_file, &$errors)
    {
        $data = false;

        if (in_array($raw_file['extension'], ipsCore_uploader::$allowed_types_files)) {
            $check = getimagesize($raw_file["tmp_name"]);

            if ($check === false) {
                $errors[] = 'Uploaded Image type is not allowed (image extension faked?).';
            }
        } else {
            $errors[] = 'Uploaded File type is not allowed.';
        }
    }

    public static function validate_image(&$raw_file, &$errors)
    {
        if (in_array($raw_file['extension'], ipsCore_uploader::$allowed_types_images)) {
            $data = getimagesize($raw_file["tmp_name"]);

            if ($data === false) {
                $errors[] = 'Uploaded Image type is not allowed (image extension faked?).';
            } else {
                $raw_file['img_width'] = $data[0];
                $raw_file['img_height'] = $data[1];
            }
        } else {
            $errors[] = 'Uploaded Image type is not allowed.';
        }
    }
}
