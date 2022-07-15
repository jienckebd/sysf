#!/bin/bash

set -e

cd /app/docroot
drush sql-sync @sysf.prd @self --create-db -y -v
drush rsync @sysf.prd:sites/default/files/ @self:sites/default/files/ -y -v
drush rsync @sysf.prd:/app/files-private/ @self:/app/files-private/ -y -v
drush cr -v
drush search-api:reset-tracker -v
drush search-api:index -v
drush cr -v
drush uli -v

echo "Done."

