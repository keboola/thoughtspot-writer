FROM php:7.2
MAINTAINER Miro Cillik <miro@keboola.com>
ENV DEBIAN_FRONTEND noninteractive

# Deps
RUN apt-get update
RUN apt-get install -y wget curl make git bzip2 time libzip-dev zip unzip openssl vim unixodbc-dev

# Driver
ADD . /code
WORKDIR /code
RUN tar -xvf driver/ThoughtSpot_odbc_linux_3.4.tar
ENV LD_LIBRARY_PATH="/code/linux/Lib/Linux_x8664"

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

RUN echo "en_US.UTF-8 UTF-8" > /etc/locale.gen
RUN cp driver/*.ini /etc/



