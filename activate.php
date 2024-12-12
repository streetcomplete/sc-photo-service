<?php
require_once 'config.php';
require_once 'classes/PhotoNoteFetcher.class.php';
require_once 'classes/PhotosDao.class.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    returnError(405, 'You need to POST an OSM note ID');
}

$raw_input = file_get_contents('php://input');
$json_input = json_decode($raw_input, true);

if (!array_key_exists('osm_note_id', $json_input)) {
    returnError(400, 'Invalid request');
}

$note_id = $json_input['osm_note_id'];

if (!is_int($note_id)) {
    returnError(400, 'OSM note ID needs to be numeric');
}

$photos_urls = array(Config::PHOTOS_SRV_URL, ...Config::ALTERNATIVE_PHOTOS_SRV_URLS);
$fetcher = new PhotoNoteFetcher($photos_urls, Config::OSM_OAUTH_TOKEN);
$osm_note = $fetcher->fetch($note_id);

if (!$osm_note) {
    returnError(410, 'Error fetching OSM note');
}

if (count($osm_note->photo_ids) === 0) {
    http_response_code(200);
    exit(json_encode(array('found_photos' => 0, 'activated_photos' => 0)));
}

try {
    $mysqli = new mysqli(Config::DB_HOST, Config::DB_USER, Config::DB_PASS, Config::DB_NAME);
    $dao = new PhotosDao($mysqli);
    $photos = $dao->getInactivePhotosByIds($osm_note->photo_ids);

    foreach ($photos as $photo) {
        $file_name = $photo->file_id . $photo->file_ext;
        $ret_val = rename(
            Config::PHOTOS_TMP_DIR . DIRECTORY_SEPARATOR . $file_name,
            Config::PHOTOS_SRV_DIR . DIRECTORY_SEPARATOR . $file_name
        );

        if ($ret_val === false) {
            returnError(500, 'Cannot move file');
        }

        $dao->activatePhoto($photo->file_id, $note_id);
    }

    $mysqli->close();
} catch (mysqli_sql_exception $e) {
    returnError(500, 'Database failure');
}

http_response_code(200);
exit(json_encode(array(
    'found_photos' => count($osm_note->photo_ids),
    'activated_photos' => count($photos)
)));

function returnError($code, $message)
{
    http_response_code($code);
    exit(json_encode(array('error' => $message)));
}