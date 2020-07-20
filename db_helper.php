<?php
require_once 'config.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
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

}
