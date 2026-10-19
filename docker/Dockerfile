FROM php:8.2-apache-bookworm

# Extensiones que Nuva necesita para MariaDB, textos en español y Google.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev libcurl4-openssl-dev unzip \
    && docker-php-ext-install pdo_mysql mbstring curl \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/nuva
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress --no-scripts --no-plugins

COPY app ./app
COPY public ./public
COPY database/demo ./database/demo
COPY demostracion ./demostracion
COPY docker/apache/nuva.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php/nuva.ini /usr/local/etc/php/conf.d/nuva.ini
COPY docker/start.sh /usr/local/bin/nuva-start
RUN mkdir -p /var/lib/php/sessions \
    && mkdir -p /var/www/nuva/public/assets/uploads/environments \
    && chown www-data:www-data /var/www/nuva/public/assets/uploads/environments \
    && chown www-data:www-data /var/lib/php/sessions \
    && chmod 700 /var/lib/php/sessions \
    && chmod 755 /usr/local/bin/nuva-start

CMD ["nuva-start"]
