#!/bin/sh
set -e

umask 0000
touch storage/logs/laravel.log
chmod -R a+rwX storage bootstrap/cache

exec "$@"
