#!/bin/sh
set -e

# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
    set -- php-fpm "$@"
fi

if [ "$1" = 'php-fpm' ] || [ "$1" = 'php' ] || [ "$1" = 'bin/console' ]; then
    # seized from API Platform

    if [ -n "$USER_UID" ] || [ -n "$USER_GID" ]; then
        echo "Setting permissions for $USER_UID:$USER_GID"
        chown -R "${USER_UID}:${USER_UID}" $POSTMILL_WRITE_DIRS
    fi

    echo "Waiting for db to be ready..."
    until bin/console doctrine:query:sql "SELECT 1" > /dev/null 2>&1; do
        sleep 1
    done

    bin/console doctrine:migrations:migrate --no-interaction
fi

exec docker-php-entrypoint "$@"
