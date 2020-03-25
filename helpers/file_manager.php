<?php // IPS-CORE Uploader

class ipsCore_file_manager
{

    public static $upload_directory     = 'uploads/';
    public static $max_upload_size      = '5000000';
    public static $allowed_types_images = ['jpg', 'png', 'jpeg', 'gif'];
    public static $allowed_types_files  = ['pdf', 'txt', 'zip', 'twig'];

    public static function get_sent_file($name, $index = false)
    {
        $files = [];
        if (isset($_FILES[$name]) && !empty($_FILES[$name])) {
            foreach ($_FILES[$name] as $property => $file_items) {
                foreach ($file_items as $file_key => $file_property_value) {
                    if (!isset($files[$file_key])) {
                        $files[$file_key] = [
                            'index' => $file_key,
                        ];
                    }
                    if (is_array($file_property_value)) {
                        $file_property_value = reset($file_property_value);
                    }
                    $files[$file_key][$property] = $file_property_value;
                }
            }
        }

        return (!empty($files) ? ($index !== false ? $files[$index] : $files) : false);
    }

    public static function do_upload_file($file_name, $args = [])
    {
        $args = array_merge([
            'index'  => false,
            'type'   => false,
            'rename' => false,
            'path'   => self::$upload_directory,
        ], $args);

        $errors = [];

        $dirCheck = self::check_directory($args['path']);
        if ($dirCheck === true) {
            if ($raw_files = self::get_sent_file($file_name, $args['index'])) {
                if ($args['index'] !== false) {
                    $raw_files = [$raw_files];
                }
                foreach ($raw_files as $raw_file_key => $raw_file) {
                    // Set image details
                    $raw_file['extension'] = strtolower(pathinfo($raw_file['name'], PATHINFO_EXTENSION));
                    if ($args['rename']) {
                        $raw_file['name'] = $args['rename'] . '.' . $raw_file['extension'];
                    }
                    $raw_file['uploadto'] = $args['path'] . basename($raw_file['name']);
                    $raw_file['basename'] = basename($raw_file['uploadto'], "." . $raw_file['extension']);

                    // Get file type
                    if (!$args['type']) {
                        $args['type'] = ipsCore_file_manager::get_file_type($file_name, $raw_file_key);
                    }

                    // Validate file type
                    if ($args['type'] == 'image') {
                        ipsCore_file_manager::validate_image($raw_file, $errors);
                    } elseif ($args['type'] == 'file') {
                        ipsCore_file_manager::validate_file($raw_file, $errors);
                    } else {
                        $errors[] = 'Could not determine file type of "' . $raw_file['basename'] . '".';
                    }

                    if (empty($errors)) {
                        // Update the $file 'uploadto' and 'basename' vars if file with the same name exists
                        if (self::get_unused_name($args['path'], $raw_file['uploadto'], $raw_file['basename'], $raw_file['extension'])) {
                            $raw_file['name'] = $raw_file['basename'] . '.' . $raw_file['extension'];
                        }

                        if ($raw_file["size"] <= self::$max_upload_size) {
                            if (!move_uploaded_file($raw_file["tmp_name"], $raw_file['uploadto'])) {
                                $errors[] = 'There was a problem moving the uploaded file "' . $raw_file['basename'] . '".';
                            }
                        } else {
                            $errors[] = 'The file "' . $raw_file['basename'] . '" is too large, max upload size is: ' . ipsCore::$functions->format_bytes(ipsCore_file_manager::$max_upload_size) . ' bytes.';
                        }
                    }
                    $raw_files[$raw_file_key] = $raw_file;
                }
            } else {
                $errors[] = 'No files found to upload.';
            }
        } else {
            $errors[] = 'Specified upload path does not exist and could not be created.';
        }

        return (empty($errors) ? $raw_files : ['errors' => $errors]);
    }

    public static function validate_file($raw_file, &$errors)
    {
        if (in_array($raw_file['extension'], ipsCore_file_manager::$allowed_types_files)) {
            $check = filesize($raw_file["tmp_name"]);

            if ($check === false) {
                $errors[] = 'Uploaded File type is not allowed (image extension faked?).';
            }
        } else {
            $errors[] = 'Uploaded File type is not allowed.';
        }
    }

    public static function validate_image(&$raw_file, &$errors)
    {
        if (in_array($raw_file['extension'], ipsCore_file_manager::$allowed_types_images)) {
            $name = (!empty($raw_file["tmp_name"]) ? $raw_file["tmp_name"] : $raw_file["name"]);
            $data = getimagesize($name);

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

    public static function get_unused_name($path, &$uploadto, &$basename, $extension) {
        $has_changed = false;
        $i = 2;
        $temp_uploadto = $uploadto;
        $temp_basename = $basename;
        while (file_exists($temp_uploadto)) {
            $has_changed = true;
            $temp_basename = $basename . '-' . $i;
            $temp_uploadto = $path . $temp_basename . '.' . $extension;

            $i++;
        }
        $uploadto = $temp_uploadto;
        $basename = $temp_basename;

        return $has_changed;

        /*$i = 2;
$temp_basename = $raw_file['basename'];
$temp_uploadto = $raw_file['uploadto'];
while (file_exists($temp_uploadto)) {
    $temp_basename = $raw_file['basename'] . '-' . $i;
    $temp_uploadto = ipsCore_file_manager::$upload_directory . $temp_basename . '.' . $raw_file['extension'];

    $i++;
}
$raw_file['basename'] = $temp_basename;
$raw_file['uploadto'] = $temp_uploadto;

if ($raw_file["size"] <= ipsCore_file_manager::$max_upload_size) {
    if (!move_uploaded_file($raw_file["tmp_name"], $raw_file['uploadto'])) {
        $errors[] = 'There was a problem moving the uploaded file "' . $raw_file['basename'] . '".';
    }
} else {
    $errors[] = 'The file "' . $raw_file['basename'] . '" is too large, max upload size is: ' . ipsCore::$functions->format_bytes(ipsCore_file_manager::$max_upload_size) . ' bytes.';
}*/
    }

    public static function do_delete_file($filename)
    {
        $filename = ltrim($filename, '/');

        if (!file_exists($filename)) {
            return true;
        }

        if (unlink(ltrim($filename, '/'))) {
            return true;
        }

        return false;
    }

    public static function get_file_type($file_name, $index = 0)
    {
        if ($raw_files = ipsCore_file_manager::get_sent_file($file_name)) {
            $raw_files[$index]['extension'] = strtolower(pathinfo($raw_files[$index]['name'], PATHINFO_EXTENSION));

            return ipsCore_file_manager::get_file_type_from_extension($raw_files[$index]['extension']);
        }

        return false;
    }

    public static function get_file_type_from_extension($extension)
    {
        if (in_array($extension, ipsCore_file_manager::$allowed_types_images)) {
            return 'image';
        } elseif (in_array($extension, ipsCore_file_manager::$allowed_types_files)) {
            return 'file';
        }

        return false;
    }

    public static function get_sent_file_by_name($file_name, $name)
    {
        if ($raw_files = ipsCore_file_manager::get_sent_file($file_name)) {
            foreach ($raw_files as $raw_file) {
                if ($raw_file['name'] == $name) {
                    return $raw_file;
                }
            }
        }

        return false;
    }

    /**
     * get Upload Directory
     * - Retrieves user upload directory from global $config and appends passed path
     *
     * @param string $path
     * @return string
     */
    public static function get_upload_directory($path = '') {
        return self::$upload_directory . $path;
    }

    /**
     * checkDirectory Static Function
     * - Checks that the given directory exists, and attempts to create it if not.
     *
     * @param $path
     * @return bool|string
     */
    public static function check_directory($path) {
        if (!file_exists(self::get_upload_directory($path))) {
            if (!mkdir(self::get_upload_directory($path), 0777, true)) {
                return 'The specified uploads directory is missing and could not be created.';
            }
        }

        return true;
    }
}
