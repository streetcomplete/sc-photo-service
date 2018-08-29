# SC-Photo-Service

This is a photo service intended for [StreetComplete](https://github.com/westnordost/StreetComplete). It allows to upload photos during a survey, which are taken to help resolving an OpenStreetMap note. This project exists because previously used photo upload services went offline due to legal problems.

This simple upload service tries to prevent abuse by requiring an association between an uploaded photo and a publicly visible OpenStreetMap note. If the requirements do not longer hold, the file is removed. Therefore, the software queries the OSM API and parses notes for photo URLs.

Programming language, repository structure and other design decisions are based on the requirements imposed by the intended hosting infrastructure for StreetComplete.


## Deployment

- Copy this repository to a webserver
- Perform appropriate file protection measures if included `.htaccess` file is not used
- Create `config.php` from `config.sample.php` template and fill with production settings
- Create the respective database and folders for file storage
- Execute the script `migrate_db.php` once to create the database table
- Configure a cron job (e.g. daily) that executes the `cleanup.php` script


## API Usage

### Uploading a File

In order to upload a photo, POST a request to `upload.php` that contains the raw file data in the body.

On success, it returns a JSON response including the URL where the photo will be reachable once it got activated:

`{'future_url': 'https://example.org/pics/42.jpg'}`

On failure, the JSON will contain an error key, e.g.:

`{'error': 'File type not allowed'}`

### Activating a Photo

To activate photos contained in a certain OSM note, POST a request to `activate.php`, giving it the note ID:

`{"osm_note_id": 1337}`

On success, it will return a JSON with information about the number of found and activated photo URLs:

`{'found_photos': 2, 'activated_photos': 1}`

On failure, the JSON will contain an error key, e.g.:

`{'error': 'Error fetching OSM note'}`
