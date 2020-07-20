<?php

require_once 'Photo.class.php';

class PhotosDao
{
    private $mysqli;

    public function __construct($mysqli)
    {
        $this->mysqli = $mysqli;
        $this->createTable();
    }
    
    private function createTable()
    {
        $this->mysqli->query(
            'CREATE TABLE IF NOT EXISTS photos(
                file_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                file_ext VARCHAR(10) NOT NULL,
                creation_time DATETIME NOT NULL,
                note_id BIGINT UNSIGNED,
                last_checked_time DATETIME
            )'
        );
    }
    
    /** Create a new photo. Returns the id of the new photo */
    public function newPhoto(string $file_ext): int
    {
        $stmt = $this->mysqli->prepare('INSERT INTO photos(file_ext, creation_time) VALUES (?, NOW())');
        $stmt->bind_param('s', $file_ext);
        $stmt->execute();
        return $this->mysqli->insert_id;
    }

    /** Activate photo */
    public function activatePhoto(int $photo_id, int $note_id)
    {
        $stmt = $this->mysqli->prepare('UPDATE photos SET note_id = ? WHERE file_id = ?');
        $stmt->bind_param('ii', $note_id, $photo_id);
        $stmt->execute();
    }

    /** Delete photo from database */
    public function deletePhoto(int $file_id)
    {
        $stmt = $this->mysqli->prepare('DELETE FROM photos WHERE file_id=?');
        $stmt->bind_param('i', $file_id);
        $stmt->execute();
    }

    public function touchPhoto(int $photo_id)
    {
        $stmt = $this->mysqli->prepare('UPDATE photos SET last_checked_time = NOW() WHERE file_id = ?');
        $stmt->bind_param('i', $photo_id);
        $stmt->execute();
    }

    /** Return Photos that haven't been activated yet but were created more than x hours ago */
    public function getOldInactivePhotos(int $max_tmp_lifetime_hours): array
    {
        $stmt = $this->mysqli->prepare(
            'SELECT file_id, file_ext, note_id
                FROM photos
                WHERE note_id IS NULL
                AND creation_time < ADDDATE(NOW(), INTERVAL -? HOUR)'
        );
        $stmt->bind_param('i', $max_tmp_lifetime_hours);
        $stmt->execute();
        $result = $stmt->get_result();
        return $this->getResultAsPhotos($result);
    }
    
    public function getOldestActivePhotos(int $num): array
    {
        $stmt = $this->mysqli->prepare(
            'SELECT file_id, file_ext, note_id
                FROM photos
                WHERE note_id IS NOT NULL
                ORDER BY creation_time
                LIMIT ?'
        );
        $stmt->bind_param('i', $num);
        $stmt->execute();
        $result = $stmt->get_result();
        return $this->getResultAsPhotos($result);
    }

    public function getActivePhotos(): array
    {
        $result = $this->mysqli->query(
            'SELECT file_id, file_ext, note_id FROM photos
                WHERE note_id IS NOT NULL
                ORDER BY last_checked_time'
        );
        return $this->getResultAsPhotos($result);
    }

    public function getInactivePhotos(): array
    {
        $result = $this->mysqli->query(
            'SELECT file_id, file_ext, note_id FROM photos
                WHERE note_id IS NULL
                ORDER BY last_checked_time'
        );
        return $this->getResultAsPhotos($result);
    }

    public function getInactivePhotosByIds(array $photo_ids): array
    {
        $in = str_repeat('?,', count($photo_ids) - 1) . '?';
        $stmt = $this->mysqli->prepare(
            "SELECT file_id, file_ext FROM photos
                WHERE file_id IN ($in)
                AND note_id IS NULL"
        );
        $stmt->bind_param(str_repeat('i', count($photo_ids)), ...$photo_ids);
        $stmt->execute();
        $result = $stmt->get_result();
        return $this->getResultAsPhotos($result);
    }
    
    private function getResultAsPhotos($result): array {
        $objs = array();
        while ($obj = $result->fetch_object("Photo")) {
            $objs[] = $obj;
        }
        $result->close();
        return $objs;
    }
}