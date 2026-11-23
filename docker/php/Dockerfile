FROM php:8.6-fpm-alpine

RUN apk add --no-cache git unzip libpq libzip icu-libs oniguruma \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS linux-headers libpq-dev libzip-dev icu-dev oniguruma-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql intl zip bcmath pcntl mbstring \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/backend

EXPOSE 9000
ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
