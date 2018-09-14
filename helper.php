<?php

function returnError($code, $message)
{
    http_response_code($code);
    exit(json_encode(array('error' => $message)));
}

function fetchUrl($url, $user = null, $pass = null)
{
    $response = new stdClass();
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    if ($user !== null and $pass !== null) {
        curl_setopt($curl, CURLOPT_USERPWD, $user . ":" . $pass);
    }
    $response->body = curl_exec($curl);
    $response->code = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);
    return $response;
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
