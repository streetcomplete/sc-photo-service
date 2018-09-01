<?php

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    class DBHelper {

        private $mysqli;

        function __construct() {
            require 'config.php';
            $this->mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASSWORD, $DB_DATABASE);
        }

        function __destruct() {
            $this->mysqli->close();
        }

        function create_table() {
            $this->mysqli->query('CREATE TABLE IF NOT EXISTS photos(
                                    file_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                                    file_ext VARCHAR(10) NOT NULL,
                                    creation_time DATETIME NOT NULL,
                                    note_id BIGINT UNSIGNED
                                 )');
        }

        function new_photo($file_ext) {
            $stmt = $this->mysqli->prepare('INSERT INTO photos(file_ext, creation_time)
                                            VALUES (?, NOW())');
            $stmt->bind_param('s', $file_ext);
            $stmt->execute();
            return $this->mysqli->insert_id;
        }

        function delete_photo($file_id) {
            $stmt = $this->mysqli->prepare('DELETE FROM photos WHERE file_id=?');
            $stmt->bind_param('i', $file_id);
            $stmt->execute();
        }

        function get_and_delete_old_inactive_photos() {
            require 'config.php';
            $stmt = $this->mysqli->prepare('SELECT file_id, file_ext FROM photos
                                            WHERE note_id IS NULL
                                            AND creation_time < ADDDATE(NOW(), INTERVAL -? HOUR)');
            $stmt->bind_param('i', $MAX_TMP_LIFETIME_HOURS);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            if(count($result) > 0) {
                $id_list = implode(',', array_column($result, 'file_id'));
                $this->mysqli->query("DELETE FROM photos
                                      WHERE file_id IN ($id_list)");
            }
            return $result;
        }

        function get_and_delete_oldest_active_photos($num) {
            $stmt = $this->mysqli->prepare('SELECT file_id, file_ext FROM photos
                                            WHERE note_id IS NOT NULL
                                            ORDER BY creation_time
                                            LIMIT ?');
            $stmt->bind_param('i', $num);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            if(count($result) > 0) {
                $id_list = implode(',', array_column($result, 'file_id'));
                $this->mysqli->query("DELETE FROM photos
                                      WHERE file_id IN ($id_list)");
            }
            return $result;
        }

        function get_active_photos() {
            $result = $this->mysqli->query('SELECT file_id, file_ext, note_id
                                            FROM photos WHERE note_id IS NOT NULL');
            return $result->fetch_all(MYSQLI_ASSOC);
        }

        function get_inactive_photos($photo_ids) {
            $in = str_repeat('?,', count($photo_ids) - 1) . '?';
            $stmt = $this->mysqli->prepare("SELECT file_id, file_ext FROM photos
                                            WHERE file_id IN ($in)
                                            AND note_id IS NULL");
            $stmt->bind_param(str_repeat('i', count($photo_ids)), ...$photo_ids);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        function activate_photo($photo_id, $note_id) {
            $stmt = $this->mysqli->prepare("UPDATE photos SET note_id = ?
                                            WHERE file_id = ?");
            $stmt->bind_param('ii', $note_id, $photo_id);
            $stmt->execute();
        }

    }

?>
