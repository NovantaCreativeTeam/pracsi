# Stage 1: Build Frontend
FROM node:20-alpine AS build-frontend
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build

# Stage 2: Build Backend & Runtime
FROM php:8.3-fpm-alpine

# Installazione dipendenze di sistema e PHP
RUN apk add --no-cache \
    nginx \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    oniguruma-dev \
    libxml2-dev

RUN docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath gd

# Copia Composer da immagine ufficiale
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copia codice sorgente
COPY . .

# Copia build del frontend dallo stage precedente
COPY --from=build-frontend /app/public/build ./public/build

# Installazione dipendenze PHP (senza dev e senza script)
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Configurazione permessi
RUN chown -R www-data:www-data storage bootstrap/cache public

# Configurazione Nginx
COPY docker/nginx.conf /etc/nginx/http.d/default.conf

# Entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["entrypoint.sh"]
