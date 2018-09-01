<?php

    $DB_HOST = "localhost";
    $DB_DATABASE = "photoservice";
    $DB_USER = "photoservice_user";
    $DB_PASSWORD = "photoservice_pw";

    $PHOTOS_TMP_DIR = '../photos_tmp';
    $PHOTOS_SRV_DIR = '../photos_srv';
    $PHOTOS_SRV_URL = 'https://example.org/pics';

    $ALLOWED_FILE_TYPES = array(
        'image/jpeg' => '.jpg'
    );

    $MAX_UPLOAD_FILE_SIZE_KB = 5000;
    $MAX_SRV_DIR_SIZE_MB = 2000;

    $MAX_TMP_LIFETIME_HOURS = 24;
    $MAX_LIFETIME_AFTER_NOTE_CLOSED_DAYS = 7;

    $OSM_API_USER = NULL;
    $OSM_API_PASS = NULL;

?>
