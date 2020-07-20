<?php

require_once 'PhotoNote.class.php';

/** Parses OSM note into the PhotoNote data structure */
class PhotoNoteParser
{
    private $photos_url;

    public function __construct(string $photos_url)
    {
        $this->photos_url = $photos_url;
    }
    
    public function parse(string $json): PhotoNote
    {
        $note = json_decode($json, true);
        
        $r = new PhotoNote();
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