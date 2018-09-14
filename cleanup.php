#!/usr/bin/env php
<?php
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit();
}

require_once 'db_helper.php';
require_once 'osm_photo_note.php';

$db_helper = new DBHelper();

function deleteFile($file_path)
{
    echo "Deleting $file_path\n";
    if (file_exists($file_path)) {
        unlink($file_path);
    }
}

$old_inactive_photos = $db_helper->getAndDeleteOldInactivePhotos();
foreach ($old_inactive_photos as $photo) {
    $file_path = Config::PHOTOS_TMP_DIR . DIRECTORY_SEPARATOR . $photo['file_id'] . $photo['file_ext'];
    deleteFile($file_path);
}

$active_photos = $db_helper->getActivePhotos();
foreach ($active_photos as $photo) {
    $file_path = Config::PHOTOS_SRV_DIR . DIRECTORY_SEPARATOR . $photo['file_id'] . $photo['file_ext'];
    $osm_note = new OSMPhotoNote($photo['note_id']);

    if ($osm_note->http_code === 404 or $osm_note->http_code === 410) {
        $db_helper->deletePhoto($photo['file_id']);
        deleteFile($file_path);
    }

    if ($osm_note->http_code === 200) {
        if (!in_array($photo['file_id'], $osm_note->photo_ids)) {
            $db_helper->deletePhoto($photo['file_id']);
            deleteFile($file_path);
        }

        if ($osm_note->status === 'closed'
            && strtotime($osm_note->closed_at . ' +' . (int)Config::MAX_LIFETIME_AFTER_NOTE_CLOSED_DAYS . ' days') < strtotime('now')
        ) {
            $db_helper->deletePhoto($photo['file_id']);
            deleteFile($file_path);
        }
    }
}

while (directorySize(Config::PHOTOS_SRV_DIR) > Config::MAX_SRV_DIR_SIZE_MB * 1000000) {
    $oldest_active_photos = $db_helper->getAndDeleteOldestActivePhotos(10);
    foreach ($oldest_active_photos as $photo) {
        $file_path = Config::PHOTOS_SRV_DIR . DIRECTORY_SEPARATOR . $photo['file_id'] . $photo['file_ext'];
        deleteFile($file_path);
    }
}
