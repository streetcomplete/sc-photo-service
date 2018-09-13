<?php
require __DIR__.'/../vendor/autoload.php';

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$dotenv = new Dotenv\Dotenv(__DIR__.'/..');
$dotenv->load();
$dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS']);

if ((bool)getenv('DEBUG')) {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
} else {
    mysqli_report(MYSQLI_REPORT_OFF);
}

$envfileTypes = explode('|', getenv('ALLOWED_FILE_TYPES'));
$fileTypes = [];
foreach ($envfileTypes as $key => $value) {
    $fileType = explode('=', $value);
    if (count($fileType) === 2) {
        $fileTypes[$fileType[0]] = $fileType[1];
    }
}

$app = new \Slim\App(
    [
    'settings' => [
        'displayErrorDetails' => (bool)getenv('DEBUG'),
        'db' => [
            'host' => getenv('DB_HOST'),
            'name' => getenv('DB_NAME'),
            'user' => getenv('DB_USER'),
            'pass' => getenv('DB_PASS'),
        ],
        'fileTypes' => $fileTypes
    ]
    ]
);

$container = $app->getContainer();
$container['db'] = function ($container) {
    $db_settings = $container['settings']['db'];
    $mysqli = new mysqli($db_settings['host'], $db_settings['user'], $db_settings['pass'], $db_settings['name']);
    return new StreetComplete\DB($mysqli);
};
