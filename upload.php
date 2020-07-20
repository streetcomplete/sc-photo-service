<?php
require_once 'config.php';
require_once 'classes/PhotosDao.class.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    returnError(405, 'You need to POST a photo');
}

$max_content_length = Config::MAX_UPLOAD_FILE_SIZE_KB * 1000;
$content_length = intval($_SERVER['HTTP_CONTENT_LENGTH']);

if ($content_length > 0 and $content_length > $max_content_length) {
    returnError(413, 'Payload too large');
}

$photo = file_get_contents('php://input', false, null, 0, $max_content_length);

$finfo = new finfo(FILEINFO_MIME_TYPE);
$file_type = $finfo->buffer($photo);

if (!array_key_exists($file_type, Config::ALLOWED_FILE_TYPES)) {
    returnError(415, 'File type not allowed');
}

$file_ext = Config::ALLOWED_FILE_TYPES[$file_type];

try {
    $mysqli = new mysqli(Config::DB_HOST, Config::DB_USER, Config::DB_PASS, Config::DB_NAME);
    $dao = new PhotosDao($mysqli);
    $file_id = $dao->newPhoto($file_ext);
    $file_name = strval($file_id) . $file_ext;
    $file_path = Config::PHOTOS_TMP_DIR . DIRECTORY_SEPARATOR . $file_name;
    $ret_val = file_put_contents($file_path, $photo);

    if ($ret_val === false) {
        $dao->deletePhoto($file_id);
        returnError(500, 'Cannot save file');
    }
    $mysqli->close();
} catch (mysqli_sql_exception $e) {
    returnError(500, 'Database failure');
}

http_response_code(200);
exit(json_encode(array(
    'future_url' => trim(Config::PHOTOS_SRV_URL, '/') . DIRECTORY_SEPARATOR . $file_name
)));

function returnError($code, $message)
{
    http_response_code($code);
    exit(json_encode(array('error' => $message)));
}