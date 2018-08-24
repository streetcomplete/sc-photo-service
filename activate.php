<?php

    require_once 'config.php';
    require_once 'helper.php';
    require_once 'db_helper.php';

    $OSM_NOTES_API = "https://api.openstreetmap.org/api/0.6/notes/";

    header('Content-Type: application/json');

    if($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return_error(405, 'You need to POST an OSM note ID');
    }

    $raw_input = file_get_contents('php://input');
    $json_input = json_decode($raw_input, true);

    if(!array_key_exists('osm_note_id', $json_input)) {
        return_error(400, 'Invalid request');
    }

    $note_id = $json_input['osm_note_id'];

    if(!is_int($note_id)) {
        return_error(400, 'OSM note ID needs to be numeric');
    }

    $note_fetch_url = $OSM_NOTES_API . strval($note_id) . '.json';
    $note_raw = http_get($note_fetch_url, NULL, $response_info);

    if($response_info->response_code != 200) {
        return_error($response_info->response_code, 'Error fetching OSM note');
    }

    $note_json = json_decode($note_raw);

?>
