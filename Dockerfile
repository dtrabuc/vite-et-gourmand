# Image de l'application : PHP 8.2 + Apache, extensions et dépendances Composer incluses.
# Rien d'autre que Docker n'est nécessaire sur la machine hôte.
FROM php:8.2-apache

# Bibliothèques système des extensions (zip pour Composer, ssl pour MongoDB)
RUN apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev libssl-dev unzip git \
    && docker-php-ext-install pdo_mysql zip \
    && pecl install mongodb-1.21.1 \
    && docker-php-ext-enable mongodb \
    && a2enmod rewrite headers \
    && apt-get purge -y --auto-remove git \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-vite-gourmand.ini
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Dépendances d'abord (couche mise en cache tant que composer.lock ne change pas)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --no-scripts --no-autoloader

COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && mkdir -p storage/cache public/uploads \
    && chown -R www-data:www-data storage public/uploads

COPY docker/entrypoint.sh /usr/local/bin/vg-entrypoint
RUN sed -i 's/\r$//' /usr/local/bin/vg-entrypoint && chmod +x /usr/local/bin/vg-entrypoint

EXPOSE 80
ENTRYPOINT ["vg-entrypoint"]
CMD ["apache2-foreground"]
