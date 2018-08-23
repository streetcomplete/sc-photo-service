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
                                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                                    creation_time DATETIME NOT NULL,
                                    file_name VARCHAR(50) NOT NULL,
                                    note_id BIGINT UNSIGNED
                                )'
            );
        }

        function insert_photo($file_name) {
            $stmt = $this->mysqli->prepare('INSERT INTO photos(creation_time, file_name) VALUES (NOW(), ?)');
            $stmt->bind_param('s', $file_name);
            $stmt->execute();
        }

    }

?>
