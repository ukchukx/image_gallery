FROM php:7.0.4-fpm

RUN apt-get update && apt-get install -o Acquire::ForceIPv4=true -y libmcrypt-dev \
    mysql-client libmagickwand-dev --no-install-recommends \
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    && docker-php-ext-install mcrypt pdo_mysql
