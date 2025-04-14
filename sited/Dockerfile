FROM php:8.1-apache

# Activation de mod_rewrite et configuration des overrides .htaccess
RUN a2enmod rewrite
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Installation des extensions PHP nécessaires
RUN docker-php-ext-install pdo pdo_mysql

# Copie du code dans le conteneur
COPY . /var/www/html/

# Activation des erreurs PHP visibles
RUN echo "display_errors=On\nerror_reporting=E_ALL" > /usr/local/etc/php/conf.d/docker-php-errors.ini

# Propriétés sur les fichiers
RUN chown -R www-data:www-data /var/www/html
