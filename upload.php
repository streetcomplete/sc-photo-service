<?php

    require_once 'helper.php';

    header('Content-Type: application/json');

    if($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return_error(405, 'You need to POST a photo');
    }

    $photo = file_get_contents("php://input");
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $file_type = $finfo->buffer($photo);

?>
