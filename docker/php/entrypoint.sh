#!/bin/sh
set -e

umask 0000
touch storage/logs/laravel.log
chmod -R a+rwX storage bootstrap/cache

[ -f vendor/autoload.php ] || composer install --no-interaction --no-progress

exec "$@"
