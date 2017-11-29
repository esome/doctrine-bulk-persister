FROM php:7.0-apache

RUN echo "deb  http://deb.debian.org/debian stretch main" > /etc/apt/sources.list \
    && apt-get update && apt-get install -y git unzip \
    && docker-php-ext-install pdo pdo_mysql \
    && curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer \
    && mkdir -p /root/.ssh/ \
    && ssh-keyscan -H gitlab.esome.info >> /root/.ssh/known_hosts

COPY docker/php.ini /usr/local/etc/php/
COPY docker/ssh/id_rsa /root/.ssh/id_rsa
COPY docker/ssh/id_rsa.pub /root/.ssh/id_rsa.pub

COPY . /esome

RUN chmod 0600 /root/.ssh/id_rsa \
    && cp -R /root/.ssh /var/www \
    && cd /esome/

WORKDIR /esome
