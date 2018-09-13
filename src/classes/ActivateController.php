<?php
namespace StreetComplete;

class ActivateController extends BaseController
{
    // https://www.slimframework.com/docs/v3/objects/router.html#using-an-invokable-class
    public function __invoke($request, $response, $args)
    {
        $json_input = $request->getParsedBody();

        if (!array_key_exists('osm_note_id', $json_input)) {
            return $response->withJson(['error' => 'Invalid request'], 400);
        }

        $note_id = $json_input['osm_note_id'];

        if (!is_int($note_id)) {
            return $response->withJson(['error' => 'OSM note ID needs to be numeric'], 400);
        }

        $osm_note = new OsmPhotoNote($note_id);

        if ($osm_note->http_code != 200) {
            return $response->withJson(['error' => 'Error fetching OSM note'], $osm_note->http_code);
        }

        if ($osm_note->status !== 'open') {
            return $response->withJson(['error' => 'OSM note is already closed'], 403);
        }

        if (count($osm_note->photo_ids) == 0) {
            return $response->withJson(
                [
                    'found_photos' => 0,
                    'activated_photos' => 0
                ],
                200
            );
        }

        try {
            $photos = $this->db()->getInactivePhotos($osm_note->photo_ids);

            foreach ($photos as $photo) {
                $file_name = $photo['file_id'] . $photo['file_ext'];
                $ret_val = rename(getenv('PHOTOS_TMP_DIR') . DIRECTORY_SEPARATOR . $file_name, getenv('PHOTOS_SRV_DIR') . DIRECTORY_SEPARATOR . $file_name);

                if ($ret_val === false) {
                    return $response->withJson(['error' => 'Cannot move file'], 500);
                }

                $this->db()->activatePhoto($photo['file_id'], $note_id);
            }

            return $response->withJson(
                [
                    'found_photos' => count($osm_note->photo_ids),
                    'activated_photos' => count($photos)
                ],
                200
            );
        } catch (mysqli_sql_exception $e) {
            return $response->withJson(['error' => 'Database failure'], 500);
        }
    }
}
