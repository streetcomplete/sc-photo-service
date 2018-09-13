#!/usr/bin/env php
<?php
require_once __DIR__.'/../src/app.php';

use Slim\Http\Environment;

// convert all the command line arguments into a URL
$argv = $GLOBALS['argv'];
array_shift($argv);
$request_uri = '/' . implode('/', $argv);

// Set up the environment so that Slim can route
$container->get('environment')->set('REQUEST_URI', $request_uri);
$container->get('environment')->set('REQUEST_METHOD', 'GET');
$container->get('settings')->set('displayErrorDetails', false);

// CLI-compatible not found error handler
$container['notFoundHandler'] = function ($container) {
    return function ($request, $response) use ($container) {
        return $container['response']->withStatus(404)
            ->withHeader('Content-Type', 'text/plain')
            ->write("Error: Cannot route to {$container->environment['PATH_INFO']}");
    };
};

// CLI-compatible error handler
$container['errorHandler'] = function ($container) {
    return function ($request, $response, $exception) use ($container) {
        return $container['response']->withStatus(500)
            ->withHeader('Content-Type', 'text/plain')
            ->write($exception->getMessage());
    };
};

$container['notAllowedHandler'] = function ($container) {
    return function ($request, $response, $error) use ($container) {
        return $response->withStatus(403)
            ->withHeader('Content-Type', 'text/plain')
            ->write('Method not allowed');
    };
};

$container['phpErrorHandler'] = function ($container) {
    return function ($request, $response, $error) use ($container) {
        return $container['response']
            ->withStatus(500)
            ->withHeader('Content-Type', 'text/plain')
            ->write($error);
    };
};

require_once __DIR__.'/../src/commands.php';

$app->run();
