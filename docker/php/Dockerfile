ARG PHP_VERSION=7.3
FROM php:${PHP_VERSION}-fpm-alpine

ARG DEVELOPMENT=1

RUN set -eux && \
    apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
        freetype-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libwebp-dev \
        libzip-dev \
        postgresql-dev \
        rabbitmq-c-dev && \
    docker-php-ext-configure gd \
        --with-gd \
        --with-freetype-dir=/usr/include/ \
        --with-jpeg-dir=/usr/include/ \
        --with-png-dir=/usr/include/ \
        --with-webp-dir=/usr/include && \
    docker-php-ext-install -j$(getconf _NPROCESSORS_ONLN) \
        gd \
        intl \
        opcache \
        pdo_pgsql \
        zip && \
    pecl install amqp && \
    pecl install apcu && \
    docker-php-ext-enable \
        amqp \
        apcu && \
    if [ "$DEVELOPMENT" -eq 1 ]; then \
        pecl install xdebug; \
        docker-php-ext-enable xdebug; \
        mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"; \
        { \
            echo 'xdebug.remote_enable = On'; \
            echo 'xdebug.remote_port = 9001'; \
            echo 'xdebug.remote_host = 172.17.0.1'; \
        } >> "$PHP_INI_DIR/conf.d/docker-php-ext-xdebug.ini"; \
    else \
        mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"; \
    fi && \
    RUNTIME_DEPS="$(scanelf -nBRF '%n#p' /usr/local/lib/php/extensions | \
        tr ',' '\n' | \
        sort -u | \
        awk 'system("[ -e /usr/local/lib/" $1 " ]") != 0 { print "so:" $1 }' \
    )" && \
    apk add --no-cache \
        $RUNTIME_DEPS \
        git && \
    apk del --no-network .build-deps && \
    rm -rf /tmp/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER 1
ENV COMPOSER_HTACCESS_PROTECT 0
ENV COMPOSER_HOME /tmp

RUN set -eux && \
    umask 000 && \
    composer global require symfony/flex

ENV COMPOSER_MEMORY_LIMIT -1
