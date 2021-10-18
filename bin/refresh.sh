#!/usr/bin/env bash

drush @sysf.prd ssh '/app/vendor/bin/drush sql-sync @self @sysf.stg --target-dump=/app/files-private/sync.sql.gz --create-db -y -v'
drush @sysf.prd ssh '/app/vendor/bin/drush rsync @self:sites/default/files/ @sysf.stg:sites/default/files/ -y -v'
drush @sysf.stg cr -v
