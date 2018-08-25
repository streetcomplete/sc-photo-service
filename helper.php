<?php

    function return_error($code, $message) {
        http_response_code($code);
        exit(json_encode(array('error' => $message)));
    }

    function fetch_url($url) {
        $response = new stdClass();
        $options = array(
            CURLOPT_RETURNTRANSFER => TRUE
        );
        $curl = curl_init($url);
        curl_setopt_array($curl, $options);
        $response->body = curl_exec($curl);
        $response->code = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);
        return $response;
    }

?>
