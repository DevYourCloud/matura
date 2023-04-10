#!/bin/bash
set -e

usermod -u `stat -c %u /var/www` www-data || true
groupmod -g `stat -c %g /var/www` www-data || true

exec docker-php-entrypoint "$@"