<?php
require_once 'OSMPhotoNoteParser.class.php';
require_once 'OSMPhotoNote.class.php';

/** Fetches photo notes from the OSM API 0.6
 *
 * See https://wiki.openstreetmap.org/wiki/API_v0.6#Read:_GET_.2Fapi.2F0.6.2Fnotes.2F.23id */
class OSMPhotoNoteFetcher
{
    const OSM_NOTES_API = 'https://api.openstreetmap.org/api/0.6/notes/';

    private $user;
    private $pass;
    private $parser;

    public function __construct(string $photos_url, string $user = null, string $pass = null)
    {
        $this->user = $user;
        $this->pass = $pass;
        $this->parser = new OSMPhotoNoteParser($photos_url);
    }

    public function fetch(int $note_id): ?OSMPhotoNote
    {
        $url = self::OSM_NOTES_API . strval($note_id) . '.json';
        $response = fetchUrl($url, $this->user, $this->pass);
        if ($response->code == 404 || $response->code == 410) {
            return null;
        }
		else if ($response->code != 200) {
            throw new Exception('OSM API returned error code ' . $response->code);
        }
		return $this->parser->parse($response->body);
    }
    
    function fetchUrl($url, $user = null, $pass = null)
    {
        $response = new stdClass();
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'StreetComplete Photo Service'); 
        if ($user !== null and $pass !== null) {
            curl_setopt($curl, CURLOPT_USERPWD, $user . ":" . $pass);
        }
        $response->body = curl_exec($curl);
        $response->code = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);
        return $response;
    }
}