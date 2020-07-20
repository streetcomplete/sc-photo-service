<?php

require_once 'OSMPhotoNote.class.php';

/** Parses OSM note into the OSMPhotoNote data structure */
class OSMPhotoNoteParser
{
	private $photos_url;

    public function __construct(string $photos_url)
    {
        $this->photos_url = $photos_url;
    }
	
    public function parse(string $json): OSMPhotoNote
    {
		$note = json_decode($response->body, true);
		
		$r = new OSMPhotoNote();
		$r->note_id = $note['properties']['id'];
        $r->status = $note['properties']['status'];
        if ($r->status == 'closed') {
            $r->closed_at = $note['properties']['closed_at'];
        }

        $relevant_comments = "";
        foreach ($note['properties']['comments'] as $comment) {
            if (array_key_exists('uid', $comment)) {
                $relevant_comments .= "\n" . $comment['text'];
            }
        }
        $search_regex = '~(?<!\S)' . preg_quote(trim($this->photos_url, '/')) . '/(\d+)\.[a-z]+(?!\S)~i';
        preg_match_all($search_regex, $relevant_comments, $matches);
        $r->photo_ids = array_unique(array_map('intval', $matches[1]));

        return $r;
    }
}