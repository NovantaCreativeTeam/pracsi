#!/bin/sh

# Ottimizzazione Laravel
php artisan storage:link
php artisan package:discover --ansi
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Migrazioni (opzionale, dipende se vuoi farle al boot)
# php artisan migrate --force

# Avvio Nginx in background
nginx -g "daemon off;" &

# Avvio PHP-FPM in foreground
php-fpm
