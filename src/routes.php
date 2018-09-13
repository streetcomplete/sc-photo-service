<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->get(
    '/',
    function (Request $request, Response $response, array $args) {
        return $response->withJson(['error' => 'asd']);
    }
);

$app->post('/upload', StreetComplete\UploadController::class);
$app->post('/activate', StreetComplete\ActivateController::class);
