FROM php:8.1-fpm

WORKDIR ${WORKDIR}

RUN apt-get -y update

RUN apt-get -y install libzip-dev libicu-dev libxml2-dev libpng-dev cron git zip
RUN docker-php-ext-install mysqli
RUN docker-php-ext-install pcntl
RUN docker-php-ext-install zip
RUN docker-php-ext-install intl
RUN docker-php-ext-install fileinfo
RUN docker-php-ext-install soap
RUN docker-php-ext-install gd
RUN pecl install xdebug
RUN docker-php-ext-enable xdebug


# composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

CMD ["php-fpm"]