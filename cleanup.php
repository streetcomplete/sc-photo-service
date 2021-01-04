#!/usr/bin/env php
<?php
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit(1);
}

require_once 'config.php';
require_once 'classes/Photo.class.php';
require_once 'classes/PhotoNote.class.php';
require_once 'classes/PhotosDao.class.php';
require_once 'classes/PhotoNoteFetcher.class.php';

function info($message)
{
    $now = date("Y-m-d H:i:s");
    file_put_contents(Config::CLEANUP_LOG_FILE, "[" . $now . "] " . $message . "\n", FILE_APPEND);
}

function directorySize($dir)
{
    $size = 0;
    $path = realpath($dir);
    if ($path !== false and $path !== '' and file_exists($path)) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object) {
            $size += $object->getSize();
        }
    }
    return $size;
}

function deleteNonReferencedPhotos($photos, $files_dir)
{
    $photo_file_ids = array();
    foreach ($photos as $photo) {
        $photo_file_ids[$photo->file_id] = $photo;
    }

    $file_names = scandir($files_dir);    
    $file_ids = array();
    foreach ($file_names as $file_name) {
        $file_id = substr($file_name, 0, strrpos( $file_name, '.' ));
        if (is_numeric($file_id)) {
            $file_ids[$file_id] = $file_name;
        }
    }
    
    foreach ($file_ids as $file_id => $file_name) {
        if(!array_key_exists($file_id, $photo_file_ids)) {
            deleteFile($files_dir . DIRECTORY_SEPARATOR . $file_name);
        }
    }
    foreach ($photo_file_ids as $file_id => $photo) {
        if(!array_key_exists($file_id, $file_ids)) {
            deletePhotoFromDB($photo->file_id);
        }
    }
}

function deletePhotos($photos)
{
    foreach($photos as $photo) deletePhoto($photo);
}

function deletePhoto($photo)
{
    $photos_dir = $photo->note_id == NULL ? Config::PHOTOS_TMP_DIR : Config::PHOTOS_SRV_DIR;
    $file_path = $photos_dir . DIRECTORY_SEPARATOR . $photo->file_id . $photo->file_ext;
    deletePhotoFromDB($photo->file_id);
    deleteFile($file_path);
}

function deleteFile($file_name)
{
    unlink($file_name);
}

function deletePhotoFromDB($file_id)
{
    global $dao;
    $dao->deletePhoto($file_id);
    info($file_id);
}

$fetcher = new PhotoNoteFetcher(Config::PHOTOS_SRV_URL, Config::OSM_API_USER, Config::OSM_API_PASS);
$mysqli = new mysqli(Config::DB_HOST, Config::DB_USER, Config::DB_PASS, Config::DB_NAME);
$dao = new PhotosDao($mysqli);

$active_photos = $dao->getActivePhotos();
$inactive_photos = $dao->getInactivePhotos();

// delete photo files that are not known in DB and vice-versa
// = clean up after problems/bugs in the past or manual deletions in file system/DB
info("Deleting non-referenced active photos");
deleteNonReferencedPhotos($active_photos, Config::PHOTOS_SRV_DIR);
info("Deleting non-referenced inactive photos");
deleteNonReferencedPhotos($inactive_photos, Config::PHOTOS_TMP_DIR);

// delete photos that have never been activated
info("Deleting never activated photos");
$old_inactive_photos = $dao->getOldInactivePhotos(Config::MAX_TMP_LIFETIME_HOURS);
deletePhotos($old_inactive_photos);

$active_notes = array(); // active_photos associated by photo->note_id
foreach ($active_photos as $photo) {
    if (!array_key_exists($photo->note_id, $active_notes)) {
        $active_notes[$photo->note_id] = array();
    }
    $active_notes[$photo->note_id][] = $photo;
}
// delete activated photos whose associated note has been closed or deleted
info("Deleting photos whose note has been closed or deleted");
foreach ($active_notes as $note_id => $photos) {
    $osm_note = $fetcher->fetch($note_id);
    if (!$osm_note) {
        deletePhotos($photos);
        continue;
    }
    if ($osm_note->status === 'closed' and strtotime($osm_note->closed_at . ' +' . Config::MAX_LIFETIME_AFTER_NOTE_CLOSED_DAYS . ' days') < strtotime('now')) {
        deletePhotos($photos);
        continue;
    }
    foreach ($photos as $photo) {
        if (!in_array($photo->file_id, $osm_note->photo_ids)) {
            deletePhoto($photo);
        } else {
            $dao->touchPhoto($photo->file_id);
        }
    }
    
}

// finally, delete oldest photos first if there is not enough space
info("Deleting oldest photos if above quota");
while (directorySize(Config::PHOTOS_SRV_DIR) > Config::MAX_SRV_DIR_SIZE_MB * 1000000) {
    $oldest_active_photos = $dao->getOldestActivePhotos(10);
    deletePhotos($oldest_active_photos);
}

$mysqli->close();