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
                                )'
            );
        }

        function new_photo($file_ext) {
            $stmt = $this->mysqli->prepare('INSERT INTO photos(file_ext, creation_time) VALUES (?, NOW())');
            $stmt->bind_param('s', $file_ext);
            $stmt->execute();
            return $this->mysqli->insert_id;
        }

        function delete_photo($file_id) {
            $stmt = $this->mysqli->prepare('DELETE FROM photos WHERE file_id=?');
            $stmt->bind_param('i', $file_id);
            $stmt->execute();
        }

    }

?>
