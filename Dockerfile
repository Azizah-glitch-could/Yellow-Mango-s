FROM php:8.2-apache
RUN docker-php-ext-install pdo pdo_mysql && a2enmod headers rewrite
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html/uploads
CMD sed -i "s/Listen 80/Listen ${PORT:-80}/; s/:80>/:${PORT:-80}>/" /etc/apache2/ports.conf /etc/apache2/sites-enabled/000-default.conf && apache2-foreground