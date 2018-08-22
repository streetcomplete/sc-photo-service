<?php

    function return_error($code, $message) {
        http_response_code($code);
        exit(json_encode(array('error' => $message)));
    }

?>
