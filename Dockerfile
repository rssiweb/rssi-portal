FROM php:8.1.1-apache

RUN apt-get update && apt-get install -y libpq-dev &&\
    docker-php-ext-install pgsql pdo pdo_pgsql &&\
    apt-get clean &&\
    rm -rf /var/cache/apt/lists

COPY app /var/www/html
EXPOSE 80