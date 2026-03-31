#!/bin/sh
set -e

# Default port if not set (Railway provides $PORT)
PORT="${PORT:-8080}"
export PORT

# Process nginx config template — substitute $PORT only
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/http.d/default.conf

# Start PHP-FPM in the background
php-fpm -D

# Start Nginx in the foreground
exec nginx -g "daemon off;"
