## Momenta new version deployment and migration:
* Create a backup of the current database to S3 +  dropbox using backup script `(1 hour)`
* Properly setup the new application
* Copy files: `(30 minutes)`
    * copy all files from `/var/www/momenta_web/upload` to `/var/www/momenta-new/storage/app/public/upload`
    * copy all files from `/var/www/momenta_web/momenta_api/uploads` to `/var/www/momenta-new/storage/app/public/momenta_api/uploads`
* Down the existing web & api applications
* Create a new database from the current database `(3 hours)`
* Run all db changes to the new database from: [DB_CHANGES](DB_CHANGES.sql) `(4 hours)`
