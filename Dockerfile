# Utiliser l'image de base PHP 8.2
FROM php:8.2-fpm

# Installer les extensions nécessaires pour Symfony
RUN apt-get update && apt-get install -y \
        libzip-dev \
        zip \
        unzip \
        libicu-dev \
  && docker-php-ext-install zip pdo pdo_mysql intl

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définir le répertoire de travail
WORKDIR /var/www/symfony
