#!/usr/bin/env bash

git add -A; git commit -m "WIP."; git push origin master

/app/vendor/bin/blt artifact:deploy --branch=master-build --no-interaction
