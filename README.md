# SC-Photo-Service

This is a photo hosting service intended for [StreetComplete](https://github.com/westnordost/StreetComplete), but it could also be used by other applications.

Abuse is prevented by requiring an association between an uploaded photo and an open, publicly visible OpenStreetMap note. If the associated note is closed or deleted, photo(s) associated with the note are also removed.

Therefore, the software queries the OSM API and parses photo URLs out of notes.

Programming language, repository structure and other design decisions are based on the requirements imposed by the intended hosting infrastructure for StreetComplete.

Requires PHP 5.6 or later.


## How does it work?

- The client (i.e. the StreetComplete app) uploads a photo file to the server.
- The file will be quarantined, but the client immediately receives a URL where the photo can be found once activated.
- This URL can be included in an OSM note to reference the photo.
- After the note is published at OSM, the client sends an activate request to the server including the OSM note ID.
- The server will retrieve the note and looks for URLs referencing the photo.
- If found, the photo will be released from quarantine and hence is accessible via the given URL.

A cleanup cron job periodically fetches notes associated with photos and checks if the note is closed or if the URL vanished (moderated). Photos which are no longer necessary are deleted after a configurable delay.
It is also possible to specify a maximum storage size, which, when exhausted, will lead to the deletion of the oldest photos.


## Deployment

- Copy this repository to a webserver
- Perform appropriate file protection measures if included `.htaccess` file is not used
- Create `config.php` from `config.sample.php` template and fill with production settings
- Create the respective database and folders for file storage
- Configure a cron job (e.g. daily) that executes the `cleanup.php` script


## API Usage

### Uploading a File

In order to upload a photo, POST a request to `upload.php` that contains the raw file data in the body.

On success, it returns a JSON response including the URL where the photo will be reachable once it got activated:

`{'future_url': 'https://example.org/pics/42.jpg'}`

On failure, the JSON will contain an error key, f.e.:

`{'error': 'File type not allowed'}`

### Activating a Photo

To activate photos contained in a certain OSM note, POST a request to `activate.php`, giving it the note ID:

`{"osm_note_id": 1337}`

On success, it will return a JSON with information about the number of found and activated photo URLs:

`{'found_photos': 2, 'activated_photos': 1}`

On failure, the JSON will contain an error key, e.g.:

`{'error': 'Error fetching OSM note'}`

## Example Client Code

In StreetComplete, the following class communicates with the mentioned API, you can take this as an example 

[PhotoServiceApiClient.kt](https://github.com/streetcomplete/StreetComplete/blob/master/app/src/main/java/de/westnordost/streetcomplete/data/osmnotes/PhotoServiceApiClient.kt)
