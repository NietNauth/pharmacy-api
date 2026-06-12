#!/bin/sh

# Cache configurations
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start supervisor
exec supervisord -c /etc/supervisord.conf
