FROM php:8.3-fpm-alpine

# Install Nginx and required PHP extensions
RUN apk add --no-cache nginx msmtp gettext && \
    docker-php-ext-install opcache

# Create required directories
RUN mkdir -p /run/nginx /var/log/nginx

# Copy Nginx config template
COPY nginx.conf.template /etc/nginx/templates/default.conf.template

# Copy all site files
COPY index.html /usr/share/nginx/html/
COPY privacy.html /usr/share/nginx/html/
COPY privacy-en.html /usr/share/nginx/html/
COPY privacy-fr.html /usr/share/nginx/html/
COPY send-mail.php /usr/share/nginx/html/
COPY dr-berenyi-kristof.png /usr/share/nginx/html/
COPY kristof-ulos.jpg /usr/share/nginx/html/
COPY berenyi-kristof.jpg /usr/share/nginx/html/
COPY csorba-dorottya.jpg /usr/share/nginx/html/
COPY logo.png /usr/share/nginx/html/
COPY translations.js /usr/share/nginx/html/
COPY office-bg.jpg /usr/share/nginx/html/

# Copy entrypoint script
COPY docker-entrypoint.sh /docker-entrypoint.sh
RUN chmod +x /docker-entrypoint.sh

# Expose the dynamic port (Railway)
EXPOSE $PORT

ENTRYPOINT ["/docker-entrypoint.sh"]
