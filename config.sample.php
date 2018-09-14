<?php
class Config
{
    const DEBUG = false;

    const DB_HOST = "localhost";
    const DB_NAME = "photoservice";
    const DB_USER = "photoservice_user";
    const DB_PASS = "photoservice_pw";

    const PHOTOS_TMP_DIR = '../photos_tmp';
    const PHOTOS_SRV_DIR = '../photos_srv';
    const PHOTOS_SRV_URL = 'https://example.org/pics';

    const ALLOWED_FILE_TYPES = array(
        'image/jpeg' => '.jpg'
    );

    const MAX_UPLOAD_FILE_SIZE_KB = 5000;
    const MAX_SRV_DIR_SIZE_MB = 2000;

    const MAX_TMP_LIFETIME_HOURS = 24;
    const MAX_LIFETIME_AFTER_NOTE_CLOSED_DAYS = 7;

    const OSM_API_USER = null;
    const OSM_API_PASS = null;
}
