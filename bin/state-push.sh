#!/bin/bash

set -e


# drush sql-sync @self @sysf.prd --target-dump=/app/files-private/sync.sql.gz --create-db -y -v
# drush rsync @self:/app/files-private/ @sysf.prd:/app/files-private/ -y -v
# drush @sysf.prd cr -v
# drush @sysf.prd uli -v

drush sql-sync @self @sysf.dev --target-dump=/app/files-private/sync.sql.gz --create-db -y -v
drush rsync @self:sites/default/files/ @sysf.dev:sites/default/files/ -y -v
drush @sysf.dev cr -v
drush @sysf.dev uli -v

drush sql-sync @self @sysf.stg --target-dump=/app/files-private/sync.sql.gz --create-db -y -v
drush rsync @self:sites/default/files/ @sysf.stg:sites/default/files/ -y -v
drush @sysf.stg cr -v
drush @sysf.stg uli -v

echo "Done."
