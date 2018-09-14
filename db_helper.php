<?php
if (!file_exists('config.php')) {
    exit('Please copy \'config.sample.php\' to \'config.php\'');
}
require_once 'config.php';

if (Config::DEBUG) {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
} else {
    mysqli_report(MYSQLI_REPORT_OFF);
}

class DBHelper
{
    private $_connection;

    public function __construct()
    {
        $this->_connection = new mysqli(Config::DB_HOST, Config::DB_USER, Config::DB_PASS, Config::DB_NAME);
    }

    public function __destruct()
    {
        $this->_connection->close();
    }

    public function createTable()
    {
        $this->_connection->query(
            'CREATE TABLE IF NOT EXISTS photos(
                file_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                file_ext VARCHAR(10) NOT NULL,
                creation_time DATETIME NOT NULL,
                note_id BIGINT UNSIGNED
            )'
        );
    }

    public function newPhoto($file_ext)
    {
        $stmt = $this->_connection->prepare(
            'INSERT INTO photos(file_ext, creation_time)
                VALUES (?, NOW())'
        );
        $stmt->bind_param('s', $file_ext);
        $stmt->execute();
        return $this->_connection->insert_id;
    }

    public function deletePhoto($file_id)
    {
        $stmt = $this->_connection->prepare('DELETE FROM photos WHERE file_id=?');
        $stmt->bind_param('i', $file_id);
        $stmt->execute();
    }

    public function getAndDeleteOldInactivePhotos()
    {
        $stmt = $this->_connection->prepare(
            'SELECT file_id, file_ext FROM photos
                WHERE note_id IS NULL
                AND creation_time < ADDDATE(NOW(), INTERVAL -? HOUR)'
        );
        $stmt->bind_param('i', Config::MAX_TMP_LIFETIME_HOURS);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        if (count($result) > 0) {
            $id_list = implode(',', array_column($result, 'file_id'));
            $this->_connection->query(
                "DELETE FROM photos
                    WHERE file_id IN ($id_list)"
            );
        }
        return $result;
    }

    public function getAndDeleteOldestActivePhotos($num)
    {
        $stmt = $this->_connection->prepare(
            'SELECT file_id, file_ext FROM photos
                WHERE note_id IS NOT NULL
                ORDER BY creation_time
                LIMIT ?'
        );
        $stmt->bind_param('i', $num);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        if (count($result) > 0) {
            $id_list = implode(',', array_column($result, 'file_id'));
            $this->_connection->query(
                "DELETE FROM photos
                    WHERE file_id IN ($id_list)"
            );
        }
        return $result;
    }

    public function getActivePhotos()
    {
        $result = $this->_connection->query(
            'SELECT file_id, file_ext, note_id
                FROM photos WHERE note_id IS NOT NULL'
        );
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getInactivePhotos($photo_ids)
    {
        $in = str_repeat('?,', count($photo_ids) - 1) . '?';
        $stmt = $this->_connection->prepare(
            "SELECT file_id, file_ext FROM photos
                WHERE file_id IN ($in)
                AND note_id IS NULL"
        );
        $stmt->bind_param(str_repeat('i', count($photo_ids)), ...$photo_ids);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function activatePhoto($photo_id, $note_id)
    {
        $stmt = $this->_connection->prepare(
            "UPDATE photos SET note_id = ?
                WHERE file_id = ?"
        );
        $stmt->bind_param('ii', $note_id, $photo_id);
        $stmt->execute();
    }
}
