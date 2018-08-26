#!/usr/bin/env php

<?php

    require_once 'config.php';
    require_once 'db_helper.php';

    $db_helper = new DBHelper();
    $old_photos = $db_helper->get_and_delete_old_inactive_photos();

    foreach($old_photos as $photo) {
        $file_name = $photo['file_id'] . $photo['file_ext'];
        unlink($PHOTOS_TMP_DIR . '/' . $file_name);
    }

?>
