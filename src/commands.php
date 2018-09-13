<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require_once __DIR__.'/helper.php';

$app->get(
    '/',
    function (Request $request, Response $response, array $args) {
        $routes = $this->get('router')->getRoutes();
        echo "usage: php ./bin/cli.php <command>\n\n";
        foreach ($routes as $route) {
            echo "  {$route->getName()}\n";
        }
    }
);

$app->get(
    '/hello/{name}',
    function (Request $request, Response $response, array $args) {
        echo "Hello, {$args['name']}\n";
    }
)->setName('hello <name>');

$app->get(
    '/migrate',
    function (Request $request, Response $response, array $args) {
        if ($this->get('db')->migrate()) {
            echo 'Database created!';
        }
    }
)->setName('migrate         Migrates the Database');

$app->get(
    '/cleanup',
    function (Request $request, Response $response, array $args) {
        $old_inactive_photos = $this->get('db')->getAndDeleteOldInactivePhotos();
        foreach ($old_inactive_photos as $photo) {
            $file_path = getenv('PHOTOS_TMP_DIR') . DIRECTORY_SEPARATOR . $photo['file_id'] . $photo['file_ext'];
            echo "Deleting $file_path\n";
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }

        $active_photos = $this->get('db')->getActivePhotos();
        foreach ($active_photos as $photo) {
            $file_path = getenv('PHOTOS_SRV_DIR') . DIRECTORY_SEPARATOR . $photo['file_id'] . $photo['file_ext'];
            $osm_note = new StreetComplete\OsmPhotoNote($photo['note_id']);

            if ($osm_note->http_code == 404 or $osm_note->http_code == 410) {
                $this->get('db')->deletePhoto($photo['file_id']);
                echo "Deleting $file_path\n";
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }

            if ($osm_note->http_code == 200) {
                if (!in_array($photo['file_id'], $osm_note->photo_ids)) {
                    $this->get('db')->deletePhoto($photo['file_id']);
                    echo "Deleting $file_path\n";
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                }
                $expired = strtotime($osm_note->closed_at . ' +' . (int)getenv('MAX_LIFETIME_AFTER_NOTE_CLOSED_DAYS') . ' days');
                if ($osm_note->status === 'closed' && $expired < strtotime('now')) {
                    $this->get('db')->deletePhoto($photo['file_id']);
                    echo "Deleting $file_path\n";
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                }
            }
        }

        while (directorySize(getenv('PHOTOS_SRV_DIR')) > (int)getenv('MAX_SRV_DIR_SIZE_MB') * 1000000) {
            $oldest_active_photos = $this->get('db')->getAndDeleteOldestActivePhotos(10);
            foreach ($oldest_active_photos as $photo) {
                $file_path = getenv('PHOTOS_SRV_DIR') . DIRECTORY_SEPARATOR . $photo['file_id'] . $photo['file_ext'];
                echo "Deleting $file_path\n";
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
        }
    }
)->setName('cleanup         Deletes old images');
