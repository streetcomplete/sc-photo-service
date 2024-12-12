<?php

require_once 'PhotoNote.class.php';

/** Parses OSM note into the PhotoNote data structure */
class PhotoNoteParser
{
    private $photos_urls;

    public function __construct(array $photos_urls)
    {
        $this->photos_urls = $photos_urls;
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
        $r->photo_ids = array();
        foreach ($this->photos_urls as $photo_url) {
            $search_regex = '~(?<!\S)' . preg_quote(trim($photo_url, '/')) . '/(\d+)\.[a-z]+(?!\S)~i';
            preg_match_all($search_regex, $relevant_comments, $matches);
            $photo_ids = array_map('intval', $matches[1]);
            $r->photo_ids = array_merge($r->photo_ids, $photo_ids);
        }
        return $r;
    }
}