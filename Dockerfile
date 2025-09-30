# Dockerfile pour Symfony + Railway
FROM php:8.1-fpm-alpine

# Installation des dépendances système
RUN apk add --no-cache \
    git \
    zip \
    unzip \
    curl \
    nginx \
    supervisor

# Installation des extensions PHP
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    opcache

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configuration du répertoire de travail
WORKDIR /var/www/html

# Copie des fichiers de l'application
COPY . .

# Installation des dépendances Composer
RUN composer install --no-dev --optimize-autoloader

# Configuration des permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

# Configuration Nginx
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/default.conf /etc/nginx/conf.d/default.conf

# Configuration Supervisor
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Exposition du port
EXPOSE 8080

# Commande de démarrage
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]