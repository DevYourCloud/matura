FROM php:8.1-fpm
RUN apt-get update -y && apt-get install -y vim libzip-dev zip unzip libicu-dev jhead 
RUN apt-get update -y && apt-get install -y libxml2-dev 
RUN docker-php-ext-install pdo_mysql mysqli xml bcmath zip intl

RUN curl -sS https://getcomposer.org/installer | php
RUN mv composer.phar /usr/local/bin/composer

COPY php.ini /usr/local/etc/php/php.ini
COPY php-cli.ini /usr/local/etc/php/php-cli.ini
COPY www.conf /usr/local/etc/php-fpm.d/www.conf

COPY entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh

COPY --chown=www-data:www-data . /var/www

WORKDIR /var/www

RUN chown www-data:www-data /var/www

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

CMD ["php-fpm"]