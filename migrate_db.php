#!/usr/bin/env php
<?php
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit(1);
}
require_once 'db_helper.php';

$db_helper = new DBHelper();
$db_helper->createTable();
