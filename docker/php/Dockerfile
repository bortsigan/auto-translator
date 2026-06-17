# syntax=docker/dockerfile:1.7
FROM php:8.4-fpm-alpine

# System deps + PHP extensions needed by Laravel + MySQL + Redis
RUN apk add --no-cache \
        bash git curl libzip-dev oniguruma-dev icu-dev \
        zlib-dev libpng-dev jpeg-dev freetype-dev \
        mysql-client autoconf g++ make linux-headers \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql mbstring zip intl gd bcmath opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && pecl install pcov \
    && docker-php-ext-enable pcov \
    && apk del autoconf g++ make linux-headers

# PCOV is installed but off by default so it doesn't slow regular requests.
# Enable per-command with: php -d pcov.enabled=1 artisan test --coverage
RUN echo 'pcov.enabled=0' > /usr/local/etc/php/conf.d/zz-pcov.ini \
    && echo 'pcov.directory=/var/www/html/app' >> /usr/local/etc/php/conf.d/zz-pcov.ini

# OPcache tuning for production-style performance in dev as well.
RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.enable_cli=0'; \
        echo 'opcache.memory_consumption=192'; \
        echo 'opcache.interned_strings_buffer=16'; \
        echo 'opcache.max_accelerated_files=20000'; \
        echo 'opcache.validate_timestamps=1'; \
        echo 'opcache.revalidate_freq=2'; \
    } > /usr/local/etc/php/conf.d/zz-opcache.ini

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy app source (compose mounts override this for dev hot-reload).
COPY . .

# Install deps if a lock file is present and vendor isn't already vendored.
RUN if [ -f composer.lock ] && [ ! -d vendor ]; then \
        composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader; \
    fi

RUN mkdir -p storage/framework/{cache,sessions,testing,views} bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
