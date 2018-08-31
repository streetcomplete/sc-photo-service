<?php

    require_once 'helper.php';

    class OSMPhotoNote {

        private $OSM_NOTES_API = 'https://api.openstreetmap.org/api/0.6/notes/';

        public $note_id;
        public $http_code;
        public $status;
        public $closed_at;
        public $photo_ids;

        function __construct($note_id) {
            require 'config.php';

            $this->note_id = $note_id;

            $note_fetch_url = $this->OSM_NOTES_API . strval($note_id) . '.json';
            $response = fetch_url($note_fetch_url, $OSM_API_USER, $OSM_API_PASS);
            $this->http_code = $response->code;
            if($this->http_code != 200) {
                return;
            }

            $note = json_decode($response->body, true);
            $this->status = $note['properties']['status'];
            if($this->status === 'closed') {
                $this->closed_at = $note['properties']['closed_at'];
            }

            $relevant_comments = "";
            foreach($note['properties']['comments'] as $comment) {
                if(array_key_exists('uid', $comment)) {
                    $relevant_comments .= "\n" . $comment['text'];
                }
            }
            $search_regex = '~(?<!\S)' . preg_quote(trim($PHOTOS_SRV_URL, '/')) . '/(\d+)\.[a-z]+(?!\S)~i';
            preg_match_all($search_regex, $relevant_comments, $matches);
            $this->photo_ids = array_unique(array_map('intval', $matches[1]));
        }

    }

?>
