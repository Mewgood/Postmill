ARG PHP_VERSION=7.4

# ======
# Assets
# ======

FROM node:10-alpine AS postmill_assets

WORKDIR /app

COPY assets assets/
COPY .babelrc package.json postcss.config.js yarn.lock webpack.config.js ./

RUN set -eux; \
    yarn; \
    yarn run build-prod


# ==============
# PHP base image
# ==============

FROM php:${PHP_VERSION}-fpm-alpine AS postmill_php_base

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HTACCESS_PROTECT=0 \
    COMPOSER_HOME="/tmp" \
    COMPOSER_MEMORY_LIMIT=-1

RUN set -eux; \
    apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
        freetype-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libwebp-dev \
        libzip-dev \
        postgresql-dev \
        rabbitmq-c-dev; \
    docker-php-ext-configure gd \
        --with-gd \
        --with-freetype-dir=/usr/include/ \
        --with-jpeg-dir=/usr/include/ \
        --with-png-dir=/usr/include/ \
        --with-webp-dir=/usr/include; \
    docker-php-ext-install -j$(getconf _NPROCESSORS_ONLN) \
        gd \
        intl \
        opcache \
        pdo_pgsql \
        zip; \
    pecl install amqp; \
    pecl install apcu; \
    docker-php-ext-enable \
        amqp \
        apcu; \
    echo 'apc.enable_cli = On' >> "$PHP_INI_DIR/conf.d/zz-postmill.ini"; \
    composer global require symfony/flex \
        --classmap-authoritative \
        --apcu-autoloader \
        --no-progress \
        --no-suggest \
        --prefer-dist; \
    RUNTIME_DEPS="$(scanelf -nBRF '%n#p' /usr/local/lib/php/extensions | \
        tr ',' '\n' | \
        sort -u | \
        awk 'system("[ -e /usr/local/lib/" $1 " ]") != 0 { print "so:" $1 }' \
    )"; \
    apk add --no-cache $RUNTIME_DEPS; \
    apk del --no-network .build-deps; \
    pecl clear-cache;

WORKDIR /app


# ==========
# PHP (prod)
# ==========

FROM postmill_php_base AS postmill_php

COPY composer.* symfony.lock .env LICENSE ./
COPY assets/fonts.json assets/themes.json assets/
COPY bin/console bin/
COPY config config/
COPY docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
COPY public/index.php public/
COPY --from=postmill_assets /app/public/build/*.json public/build/
COPY src src/
COPY templates templates/
COPY translations translations/

ARG APP_BRANCH=""
ARG APP_VERSION=""

ENV APP_BRANCH=${APP_BRANCH} \
    APP_VERSION=${APP_VERSION} \
    APP_ENV=prod \
    DATABASE_URL='pgsql://postmill:secret@db/postmill' \
    LOG_FILE='php://stderr' \
    POSTMILL_WRITE_DIRS="\
        /app/public/media/cache \
        /app/public/submission_images \
        /app/var/cache/prod/http_cache \
        /app/var/cache/prod/pools \
        /app/var/log \
        /app/var/sessions \
    "

RUN set -eux; \
    apk add --no-cache \
        acl \
        su-exec; \
    { \
        echo 'opcache.max_accelerated_files = 20000'; \
        echo 'opcache.validate_timestamps = Off'; \
        echo 'realpath_cache_size = 4096K'; \
        echo 'realpath_cache_ttl = 600'; \
    } >> "$PHP_INI_DIR/zz-postmill.ini"; \
    cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"; \
    composer install \
        --apcu-autoloader \
        --classmap-authoritative \
        --no-dev \
        --no-suggest \
        --prefer-dist; \
    sed -i '/^APP_BRANCH\|APP_VERSION/d' .env; \
    composer dump-env prod; \
    composer clear-cache; \
    mkdir -p $POSTMILL_WRITE_DIRS; \
    chown -R www-data:www-data $POSTMILL_WRITE_DIRS;

VOLUME /app/public/media/cache
VOLUME /app/public/submission_images
VOLUME /app/var

ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm"]


# =====
# Nginx
# =====

FROM nginx:1.17-alpine AS postmill_web

WORKDIR /app

COPY LICENSE .
COPY docker/nginx/conf.d/*.conf /etc/nginx/conf.d/
COPY assets/public/* public/
COPY --from=postmill_assets /app/public/build public/build/
COPY --from=postmill_php /app/public/bundles public/bundles/
COPY --from=postmill_php /app/public/js public/js/


# =========
# PHP (dev)
# =========

FROM postmill_php_base AS postmill_php_debug

RUN set -eux; \
    chmod -R go=u /tmp; \
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS; \
    apk add --no-cache git; \
    cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"; \
    pecl install xdebug; \
    docker-php-ext-enable xdebug; \
    { \
        echo 'xdebug.remote_enable = On'; \
        echo 'xdebug.remote_port = 9001'; \
        echo 'xdebug.remote_host = 172.17.0.1'; \
    } >> "$PHP_INI_DIR/conf.d/zz-postmill.ini"; \
    cat "$PHP_INI_DIR/php.ini" \
        "$PHP_INI_DIR/conf.d/docker-php-ext-xdebug.ini" \
        > "$PHP_INI_DIR/php-debug.ini"; \
    rm "$PHP_INI_DIR/conf.d/docker-php-ext-xdebug.ini"; \
    RUNTIME_DEPS="$(scanelf -nBRF '%n#p' /usr/local/lib/php/extensions | \
        tr ',' '\n' | \
        sort -u | \
        awk 'system("[ -e /usr/local/lib/" $1 " ]") != 0 { print "so:" $1 }' \
    )"; \
    apk add --no-cache $RUNTIME_DEPS; \
    apk del --no-network .build-deps; \
    pecl clear-cache;

CMD php-fpm -c "$PHP_INI_DIR/php-debug.ini"


# ===========
# Nginx (dev)
# ===========

FROM postmill_web AS postmill_web_debug

COPY docker/nginx/docker-entrypoint-debug.sh /usr/local/bin/docker-entrypoint.sh

RUN set -ex; \
    apk add --no-cache openssl; \
    touch /etc/nginx/ssl.conf; \
    chmod go=u /etc/nginx/ssl.conf;

EXPOSE 443

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["nginx", "-g", "daemon off;"]
