FROM composer:2 AS composer
WORKDIR /build
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

FROM wordpress:php8.3-apache
WORKDIR /var/www/html

COPY --from=composer /build/vendor/ /usr/src/wordpress/wp-content/themes/kobietyoceanow/vendor/
COPY --chown=www-data:www-data style.css functions.php /usr/src/wordpress/wp-content/themes/kobietyoceanow/
COPY --chown=www-data:www-data acf-json/ /usr/src/wordpress/wp-content/themes/kobietyoceanow/acf-json/
COPY --chown=www-data:www-data views/ /usr/src/wordpress/wp-content/themes/kobietyoceanow/views/
COPY --chown=www-data:www-data view/ /usr/src/wordpress/wp-content/themes/kobietyoceanow/view/
COPY --chown=www-data:www-data css/ /usr/src/wordpress/wp-content/themes/kobietyoceanow/css/
COPY --chown=www-data:www-data js/ /usr/src/wordpress/wp-content/themes/kobietyoceanow/js/
