<?php

    require_once 'config.php';
    require_once 'helper.php';
    require_once 'db_helper.php';

    header('Content-Type: application/json');

    if($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return_error(405, 'You need to POST a photo');
    }

    $photo = file_get_contents('php://input');
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $file_type = $finfo->buffer($photo);

    if(!array_key_exists($file_type, $ALLOWED_FILE_TYPES)) {
        return_error(415, 'File type not allowed');
    }

    $file_ext = $ALLOWED_FILE_TYPES[$file_type];

    try {

        $db_helper = new DBHelper();
        $file_id = $db_helper->new_photo($file_ext);
        $file_name = strval($file_id) . $file_ext;
        $file_path = $PHOTOS_TMP_DIR . '/' . $file_name;
        $ret_val = file_put_contents($file_path, $photo);

        if($ret_val === FALSE) {
            $db_helper->delete_photo($file_id);
            return_error(500, 'Cannot save file');
        }

    } catch(mysqli_sql_exception $e) {
        return_error(500, 'Database failure');
    }

    http_response_code(200);
    exit(json_encode(array('future_url' => trim($PHOTOS_SRV_URL, '/') . '/' . $file_name)));

?>
