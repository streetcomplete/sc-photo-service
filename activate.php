<?php

    require_once 'config.php';
    require_once 'helper.php';
    require_once 'db_helper.php';

    $OSM_NOTES_API = "https://api.openstreetmap.org/api/0.6/notes/";
    $PHOTO_URL_SEARCH_REGEX = '~(?<!\S)' . preg_quote(trim($PHOTOS_SRV_URL, '/')) . '/(\d+)\.[a-z]+(?!\S)~i';

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
    $response = fetch_url($note_fetch_url);

    if($response->code != 200) {
        return_error($response->code, 'Error fetching OSM note');
    }

    $note = json_decode($response->body, true);

    if($note['properties']['status'] !== 'open') {
        return_error(403, 'OSM note is already closed');
    }

    $relevant_comments = "";

    foreach($note['properties']['comments'] as $comment) {
        if(array_key_exists('uid', $comment)) {
            $relevant_comments .= "\n" . $comment['text'];
        }
    }

    preg_match_all($PHOTO_URL_SEARCH_REGEX, $relevant_comments, $matches);
    $photo_ids = array_unique(array_map('intval', $matches[1]));

?>
