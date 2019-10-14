ARG PHP_VERSION=7.3

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


# ===
# PHP
# ===

FROM php:${PHP_VERSION}-fpm-alpine AS postmill_php
ARG DEVELOPMENT=0

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
    echo 'apc.enable_cli = On' >> "$PHP_INI_DIR/conf.d/postmill.ini"; \
    if [ "$DEVELOPMENT" -eq 1 ]; then \
        pecl install xdebug; \
        docker-php-ext-enable xdebug; \
        mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"; \
        { \
            echo 'xdebug.remote_enable = On'; \
            echo 'xdebug.remote_port = 9001'; \
            echo 'xdebug.remote_host = 172.17.0.1'; \
        } >> "$PHP_INI_DIR/conf.d/postmill.ini"; \
        apk add --no-cache git; \
    else \
        mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"; \
        { \
            echo 'opcache.max_accelerated_files = 20000'; \
            echo 'opcache.validate_timestamps = 0'; \
            echo 'realpath_cache_ttl = 4096K'; \
            echo 'realpath_cache_ttl = 600'; \
        } >> "$PHP_INI_DIR/conf.d/postmill.ini"; \
    fi; \
    RUNTIME_DEPS="$(scanelf -nBRF '%n#p' /usr/local/lib/php/extensions | \
        tr ',' '\n' | \
        sort -u | \
        awk 'system("[ -e /usr/local/lib/" $1 " ]") != 0 { print "so:" $1 }' \
    )"; \
    apk add --no-cache $RUNTIME_DEPS; \
    apk del --no-network .build-deps; \
    pecl clear-cache;

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HTACCESS_PROTECT=0 \
    COMPOSER_HOME="/tmp" \
    POSTMILL_WRITE_DIRS="\
        /app/public/media/cache \
        /app/public/submission_images \
        /app/var \
        "

RUN set -eux; \
    umask 000; \
    composer global require symfony/flex \
        --classmap-authoritative \
        --apcu-autoloader \
        --no-progress \
        --no-suggest \
        --prefer-dist;

WORKDIR /app

COPY composer.* symfony.lock .env LICENSE ./
COPY assets/fonts.json assets/themes.json assets/
COPY bin/console bin/
COPY config config/
COPY public public/
COPY src src/
COPY templates templates/
COPY translations translations/

# in development, it is assumed /app will overridden by a bind mount, so only
# install production dependencies
RUN set -eux; \
    mkdir -p $POSTMILL_WRITE_DIRS; \
    chmod 777 $POSTMILL_WRITE_DIRS; \
    chmod +x bin/console; \
    APP_ENV=prod composer install \
        --apcu-autoloader \
        --classmap-authoritative \
        --no-dev \
        --no-suggest \
        --prefer-dist; \
    composer clear-cache; \
    sed -i '/^APP_BRANCH\|APP_VERSION/d' .env; \
    mv .env .env.docker-defaults; \
    chmod -R 777 var vendor; \
    rm -rf var/cache/prod var/log/prod; \
    echo '<?php return [];' > .env.local.php;

VOLUME /app/public/media/cache
VOLUME /app/public/submission_images
VOLUME /app/var

COPY --from=postmill_assets /app/public/build/*.json public/build/

ARG APP_BRANCH=""
ARG APP_VERSION=""

ENV APP_BRANCH=${APP_BRANCH} \
    APP_VERSION=${APP_VERSION} \
    APP_ENV=prod \
    DATABASE_URL='pgsql://postmill:secret@db/postmill'

COPY docker/php/docker-entrypoint.sh /usr/local/bin/

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]


# =====
# Nginx
# =====

FROM nginx:1.17-alpine AS postmill_web

COPY docker/nginx/docker-entrypoint.sh /usr/local/bin/

RUN set -ex; \
    apk add --no-cache openssl; \
    touch /etc/nginx/ssl.conf; \
    chmod +x /usr/local/bin/docker-entrypoint.sh; \
    chmod 777 /etc/nginx/ssl.conf;

WORKDIR /app

COPY LICENSE .
COPY docker/nginx/conf.d/default.conf /etc/nginx/conf.d/
COPY public/apple-touch-icon-precomposed.png public/favicon.ico public/robots.txt public/
COPY --from=postmill_assets /app/public/build public/build/
COPY --from=postmill_php /app/public/bundles public/bundles/
COPY --from=postmill_php /app/public/js public/js/

EXPOSE 80
EXPOSE 443

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["nginx", "-g", "daemon off;"]
