<?php
require_once 'db_helper.php';
require_once 'osm_photo_note.php';
require_once 'helper.php';

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

$osm_note = new OSMPhotoNote($note_id);

if ($osm_note->http_code !== 200) {
    returnError($osm_note->http_code, 'Error fetching OSM note');
}

if ($osm_note->status !== 'open') {
    returnError(403, 'OSM note is already closed');
}

if (count($osm_note->photo_ids) === 0) {
    http_response_code(200);
    exit(json_encode(array('found_photos' => 0, 'activated_photos' => 0)));
}

try {
    $db_helper = new DBHelper();
    $photos = $db_helper->getInactivePhotos($osm_note->photo_ids);

    foreach ($photos as $photo) {
        $file_name = $photo['file_id'] . $photo['file_ext'];
        $ret_val = rename(
            Config::PHOTOS_TMP_DIR . DIRECTORY_SEPARATOR . $file_name,
            Config::PHOTOS_SRV_DIR . DIRECTORY_SEPARATOR . $file_name
        );

        if ($ret_val === false) {
            returnError(500, 'Cannot move file');
        }

        $db_helper->activatePhoto($photo['file_id'], $note_id);
    }

    http_response_code(200);
    exit(json_encode(array('found_photos' => count($osm_note->photo_ids), 'activated_photos' => count($photos))));
} catch (mysqli_sql_exception $e) {
    returnError(500, 'Database failure');
}
