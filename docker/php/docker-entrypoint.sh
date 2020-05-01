#!/bin/sh
set -e

# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
    set -- php-fpm "$@"
fi

if [ -z "${POSTMILL_SKIP_MIGRATIONS+}" ] && ( \
    [ "$1" = 'php-fpm' ] || \
    [ "$1" = 'php' ] || \
    [ "$1" = 'bin/console' ] \
); then
    RUN_MIGRATIONS=1
fi

if [ -n "$SU_USER" ] && [ "$(id -u)" -eq 0 ]; then
    mkdir -p $POSTMILL_WRITE_DIRS
    setfacl -R -m u:www-data:rwX -m u:"$SU_USER":rwX $POSTMILL_WRITE_DIRS
    setfacl -dR -m u:www-data:rwX -m u:"$SU_USER":rwX $POSTMILL_WRITE_DIRS
    chmod go+w /proc/self/fd/1 /proc/self/fd/2
    set -- su-exec "$SU_USER" "$@"
fi

if [ -n "$RUN_MIGRATIONS" ]; then
    echo "Waiting for db to be ready..."
    until bin/console doctrine:query:sql "SELECT 1" > /dev/null 2>&1; do
        sleep 1
    done

    bin/console doctrine:migrations:migrate --no-interaction
fi

exec docker-php-entrypoint "$@"
