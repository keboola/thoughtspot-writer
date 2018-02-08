FROM php:7.2
MAINTAINER Miro Cillik <miro@keboola.com>
ENV DEBIAN_FRONTEND noninteractive

# Deps
RUN apt-get update
RUN apt-get install -y wget curl make git bzip2 time libzip-dev zip unzip openssl vim unixodbc-dev sshpass

# Driver
ADD . /code
WORKDIR /code

# @todo: maybe save driver to our S3?
RUN wget -O thoughtspot_odbc_linux.tar.gz https://thoughtspot.egnyte.com/dd/hFbkjmVbDZ
RUN gzip -df thoughtspot_odbc_linux.tar.gz
RUN ls -la
RUN tar -xvf thoughtspot_odbc_linux.tar
ENV LD_LIBRARY_PATH="/code/linux/Lib/Linux_x8664"
RUN echo "en_US.UTF-8 UTF-8" > /etc/locale.gen
RUN cp driver/*.ini /etc/

# PHP
RUN docker-php-ext-install pdo
RUN set -x \
    && docker-php-source extract \
    && cd /usr/src/php/ext/odbc \
    && phpize \
    && sed -ri 's@^ *test +"\$PHP_.*" *= *"no" *&& *PHP_.*=yes *$@#&@g' configure \
    && ./configure --with-unixODBC=shared,/usr \
    && docker-php-ext-install odbc \
    && docker-php-source delete

# Composer
WORKDIR /root
RUN cd \
  && curl -sS https://getcomposer.org/installer | php \
  && ln -s /root/composer.phar /usr/local/bin/composer

# Main
ADD . /code
WORKDIR /code
RUN echo "memory_limit = -1" >> /usr/local/etc/php/php.ini
RUN echo "date.timezone = \"Europe/Prague\"" >> /usr/local/etc/php/php.ini
RUN composer selfupdate && composer install --no-interaction

CMD php ./run.php --data=/data
