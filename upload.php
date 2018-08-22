<?php

    require_once 'config.php';
    require_once 'helper.php';

    header('Content-Type: application/json');

    if($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return_error(405, 'You need to POST a photo');
    }

    $photo = file_get_contents("php://input");
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $file_type = $finfo->buffer($photo);

    if(!array_key_exists($file_type, $ALLOWED_FILE_TYPES)) {
        return_error(415, 'File type not allowed');
    }

    $file_name = bin2hex(random_bytes(16)) . $ALLOWED_FILE_TYPES[$file_type];
    file_put_contents($PHOTOS_TMP_DIR . '/' . $file_name, $photo);

?>
