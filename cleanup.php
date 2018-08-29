#!/usr/bin/env php

<?php

    require_once 'config.php';
    require_once 'db_helper.php';
    require_once 'osm_photo_note.php';

    $db_helper = new DBHelper();
    $old_photos = $db_helper->get_and_delete_old_inactive_photos();

    foreach($old_photos as $photo) {
        $file_name = $photo['file_id'] . $photo['file_ext'];
        unlink($PHOTOS_TMP_DIR . '/' . $file_name);
    }

    $active_photos = $db_helper->get_active_photos();
    foreach($active_photos as $photo) {

        $file_path = $PHOTOS_SRV_DIR . '/' . $photo['file_id'] . $photo['file_ext'];
        $osm_note = new OSMPhotoNote($photo['note_id']);

        if($osm_note->http_code == 404 or $osm_note->http_code == 410) {
            $db_helper->delete_photo($photo['file_id']);
            unlink($file_path);
        }

        if($osm_note->http_code == 200) {
            if(!in_array($photo['file_id'], $osm_note->photo_ids)) {
                $db_helper->delete_photo($photo['file_id']);
                unlink($file_path);
            }

            if($osm_note->status === 'closed'
               and strtotime($osm_note->closed_at . ' +' . $MAX_LIFETIME_AFTER_NOTE_CLOSED_DAYS . ' days') > strtotime('now')) {
                $db_helper->delete_photo($photo['file_id']);
                unlink($file_path);
            }
        }

    }

?>
