<?php
require_once 'db_helper.php';
require_once 'helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    returnError(405, 'You need to POST a photo');
}

$content_length = intval($_SERVER['HTTP_CONTENT_LENGTH']);

if ($content_length > Config::MAX_UPLOAD_FILE_SIZE_KB * 1000) {
    returnError(413, 'Payload too large');
}

$photo = file_get_contents('php://input', false, null, 0, $content_length);
$finfo = new finfo(FILEINFO_MIME_TYPE);
$file_type = $finfo->buffer($photo);

if (!array_key_exists($file_type, Config::ALLOWED_FILE_TYPES)) {
    returnError(415, 'File type not allowed');
}

$file_ext = Config::ALLOWED_FILE_TYPES[$file_type];

try {
    $db_helper = new DBHelper();
    $file_id = $db_helper->newPhoto($file_ext);
    $file_name = strval($file_id) . $file_ext;
    $file_path = Config::PHOTOS_TMP_DIR . DIRECTORY_SEPARATOR . $file_name;
    $ret_val = file_put_contents($file_path, $photo);

    if ($ret_val === false) {
        $db_helper->deletePhoto($file_id);
        returnError(500, 'Cannot save file');
    }
} catch (mysqli_sql_exception $e) {
    returnError(500, 'Database failure');
}

http_response_code(200);
exit(json_encode(array('future_url' => trim(Config::PHOTOS_SRV_URL, '/') . DIRECTORY_SEPARATOR . $file_name)));
