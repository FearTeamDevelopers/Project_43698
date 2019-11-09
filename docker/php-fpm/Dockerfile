FROM php:7.2-fpm-alpine

RUN apk update && apk upgrade \
        && apk add --no-cache --virtual .build-dependencies $PHPIZE_DEPS zlib-dev cyrus-sasl-dev git autoconf g++ libtool make pcre-dev \
        && apk add --no-cache tini libintl icu icu-dev libxml2-dev freetype-dev libjpeg-turbo-dev libpng-dev gmp gmp-dev \
            libmemcached-dev imagemagick-dev libzip-dev libssh2 libssh2-dev libxslt-dev bzip2-dev \
        && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
        && docker-php-ext-install mysqli bcmath zip gd bz2 pdo pdo_mysql simplexml opcache sockets mbstring intl soap iconv \
        && docker-php-ext-enable mysqli \
        && apk del .build-dependencies \
        && docker-php-source delete \
        && rm -rf /tmp/* /var/cache/apk/*

COPY ./docker/php-fpm/php.ini "$PHP_INI_DIR/php.ini"
RUN curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer && composer global require hirak/prestissimo --no-plugins --no-scripts