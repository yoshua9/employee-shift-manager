FROM php:8.2-apache

# PDO MySQL driver + URL rewriting for the front controller
RUN docker-php-ext-install pdo_mysql && a2enmod rewrite

# Serve from /public; includes/, api/, sql/ stay outside the web root
COPY docker/vhost.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
