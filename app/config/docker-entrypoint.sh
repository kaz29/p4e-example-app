#!/usr/bin/env sh
set -e

cd /srv/cms
./bin/cake migrations migrate

exec "$@"
