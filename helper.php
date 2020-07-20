<?php

function returnError($code, $message)
{
    http_response_code($code);
    exit(json_encode(array('error' => $message)));
}

function directorySize($dir)
{
    $size = 0;
    $path = realpath($dir);
    if ($path !== false and $path !== '' and file_exists($path)) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object) {
            $size += $object->getSize();
        }
    }
    return $size;
}
