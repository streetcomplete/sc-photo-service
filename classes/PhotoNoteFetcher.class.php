<?php
require_once 'PhotoNoteParser.class.php';
require_once 'PhotoNote.class.php';

/** Fetches photo notes from the OSM API 0.6
 *
 * See https://wiki.openstreetmap.org/wiki/API_v0.6#Read:_GET_.2Fapi.2F0.6.2Fnotes.2F.23id */
class PhotoNoteFetcher
{
    const OSM_NOTES_API = 'https://api.openstreetmap.org/api/0.6/notes/';

    private $osm_auth_token;
    private $parser;

    public function __construct(array $photos_urls, string $osm_auth_token = null)
    {
        $this->osm_auth_token = $osm_auth_token;
        $this->parser = new PhotoNoteParser($photos_urls);
    }

    public function fetch(int $note_id): ?PhotoNote
    {
        $url = self::OSM_NOTES_API . strval($note_id) . '.json';
        $response = $this->fetchUrl($url, $this->osm_auth_token);
        if ($response->code == 404 || $response->code == 410) {
            return null;
        }
        else if ($response->code != 200) {
            throw new Exception('OSM API returned error code ' . $response->code);
        }
        return $this->parser->parse($response->body);
    }
    
    function fetchUrl($url, string $auth_token = null)
    {
        $response = new stdClass();
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'StreetComplete Photo Service'); 
        if ($auth_token !== null) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: Bearer ".$auth_token));
        }
        $response->body = curl_exec($curl);
        $response->code = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);
        return $response;
    }
}