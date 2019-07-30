#!/bin/sh
set -e

# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
    set -- php-fpm "$@"
fi

# import default env variables
# this wouldn't be necessary if `composer dump-env prod` didn't choke on
# interpolated commands, but alas.
if [ ! -f '.env' ]; then
    _EXISTING_ENV="$(export -p)"

    set +e -o allexport
    . ./.env.docker-defaults
    set -e +o allexport

    # restore variables overridden in the Dockerfile
    eval "$_EXISTING_ENV"
    unset _EXISTING_ENV
fi

if expr "$1" : 'bin/.*\|composer\|php\|php-fpm\|vendor/bin/.*' > /dev/null; then
    if [ ! -d "var/cache/${APP_ENV}" ]; then
        if [ "$APP_ENV" != 'prod' ]; then
            composer install --prefer-dist --no-scripts --no-suggest --no-interaction
        fi

        bin/console cache:clear
    fi
fi

exec "$@"
