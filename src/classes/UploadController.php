<?php
namespace StreetComplete;

class UploadController extends BaseController
{
    // https://www.slimframework.com/docs/v3/objects/router.html#using-an-invokable-class
    public function __invoke($request, $response, $args)
    {
        $content_length = intval($_SERVER['HTTP_CONTENT_LENGTH']);

        if ($content_length > (int)getenv('MAX_UPLOAD_FILE_SIZE_KB') * 1000) {
            return $response->withJson(['error' => 'Payload too large'], 413);
        }

        $photo = file_get_contents('php://input', false, null, 0, $content_length);
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $file_type = $finfo->buffer($photo);

        if (!array_key_exists($file_type, $this->settings('fileTypes'))) {
            return $response->withJson(['error' => 'File type not allowed'], 415);
        }
        $file_ext = $this->settings('fileTypes')[$file_type];

        try {
            $file_id = $this->db()->newPhoto($file_ext);
            $file_name = strval($file_id) . $file_ext;
            $file_path = getenv('PHOTOS_TMP_DIR') . DIRECTORY_SEPARATOR . $file_name;
            $ret_val = file_put_contents($file_path, $photo);

            if ($ret_val === false) {
                $this->db()->deletePhoto($file_id);
                return $response->withJson(['error' => 'Cannot save file'], 500);
            }
        } catch (\mysqli_sql_exception $e) {
            return $response->withJson(['error' => 'Database failure'], 500);
        }

        return $response->withJson(
            [
            'future_url' => trim(getenv('PHOTOS_SRV_URL'), '/') . '/' . $file_name
            ],
            200
        );

        return $response;
    }
}
