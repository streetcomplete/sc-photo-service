#!/usr/bin/env php
<?php
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit(1);
}

require_once 'db_helper.php';
require_once 'osm_photo_note.php';

function deletePhoto($photo)
{
    global $db_helper;
    $file_path = ($photo['note_id']==NULL ? Config::PHOTOS_TMP_DIR : Config::PHOTOS_SRV_DIR) . DIRECTORY_SEPARATOR . $photo['file_id'] . $photo['file_ext'];
    echo "delete photo ".$photo['file_id']."\n";	
	$success = unlink($file_path);
    if ($success) {
        $db_helper->deletePhoto($photo['file_id']);
    } else {
        echo error_get_last()["message"] . "\n";
    }
}

$db_helper = new DBHelper();

$old_inactive_photos = $db_helper->getOldInactivePhotos();
foreach ($old_inactive_photos as $photo) {
    deletePhoto($photo);
}

$active_photos = $db_helper->getActivePhotos();
foreach ($active_photos as $photo) {
    $file_path = Config::PHOTOS_SRV_DIR . DIRECTORY_SEPARATOR . $photo['file_id'] . $photo['file_ext'];
    $osm_note = new OSMPhotoNote($photo['note_id']);

    if ($osm_note->http_code === 404 or $osm_note->http_code === 410) {
        deletePhoto($photo);
    }

    if ($osm_note->http_code === 200) {
        if (!in_array($photo['file_id'], $osm_note->photo_ids)) {
            deletePhoto($photo);
            continue;
        }
        if ($osm_note->status === 'closed' and strtotime($osm_note->closed_at . ' +' . Config::MAX_LIFETIME_AFTER_NOTE_CLOSED_DAYS . ' days') < strtotime('now')) {
            deletePhoto($photo);
        }
    }
}

while (directorySize(Config::PHOTOS_SRV_DIR) > Config::MAX_SRV_DIR_SIZE_MB * 1000000) {
    $oldest_active_photos = $db_helper->getOldestActivePhotos(10);
    foreach ($oldest_active_photos as $photo) {
        deletePhoto($photo);
    }
}
