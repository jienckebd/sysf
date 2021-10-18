#!/bin/bash

set -e

cd /app/docroot
drush sql-sync @sysf.ide1 @self --create-db -y -v
drush rsync @sysf.ide1:sites/default/files/ @self:sites/default/files/ -y -v
drush cr -v
drush uli -v

echo "Done."
