FROM php:8.1.1-apache as build

RUN apt-get update && apt-get install -y git curl zip unzip &&\
    apt-get clean &&\
    rm -rf /var/cache/apt/lists
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
WORKDIR /var/www/
COPY app/composer.lock composer.lock
COPY app/composer.json composer.json
RUN composer install --no-dev

FROM php:8.1.1-apache as prod
RUN apt-get update && apt-get install -y libpq-dev &&\
    apt-get clean &&\
    rm -rf /var/cache/apt/lists &&\
    docker-php-ext-install pgsql pdo pdo_pgsql &&\
    a2enmod headers &&\
    sed -ri -e 's/^([ \t]*)(<\/VirtualHost>)/\1\tHeader set Access-Control-Allow-Origin "*"\n\1\2/g' /etc/apache2/sites-available/*.conf
    
WORKDIR /var/www/
COPY --from=build /var/www/vendor vendor
COPY app /var/www/
EXPOSE 80
