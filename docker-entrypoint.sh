#!/bin/sh
set -e

# Default port if not set (Railway provides $PORT)
PORT="${PORT:-8080}"
export PORT

# Process nginx config template — substitute $PORT only
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/http.d/default.conf

# Start PHP-FPM in the background
php-fpm -D || { echo "ERROR: php-fpm failed to start"; exit 1; }
sleep 1
pgrep php-fpm > /dev/null || { echo "ERROR: php-fpm is not running"; exit 1; }

# Start Nginx in the foreground
exec nginx -g "daemon off;"
