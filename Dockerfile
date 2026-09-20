# Stage 1: Build Node.js assets
FROM node:24-alpine AS node_builder
WORKDIR /app
COPY package*.json .npmrc ./
RUN npm install
COPY . .
RUN npm run build

# Stage 2: Install PHP dependencies
FROM composer:2 AS composer_builder
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --no-dev --ignore-platform-reqs

# Stage 3: Production Image
FROM php:8.4-fpm-alpine

# Install required system packages for Nginx, Supervisor, and PHP extensions
RUN apk add --no-cache \
    nginx \
    supervisor \
    bash \
    curl \
    git \
    zip \
    unzip

# Install PHP extensions using mlocati/docker-php-extension-installer (reliable for gd, zip, etc.)
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions bcmath exif gd intl mbstring pdo_mysql zip

WORKDIR /var/www/html

# Copy application files
COPY . .

# Copy built assets and vendor directory from previous stages
COPY --from=composer_builder /app/vendor/ ./vendor/
COPY --from=node_builder /app/public/build/ ./public/build/

# Set correct permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Ensure supervisor log directory exists
RUN mkdir -p /etc/supervisor/conf.d

# Copy docker configurations
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/start.sh /usr/local/bin/start.sh

RUN chmod +x /usr/local/bin/start.sh

# Expose port 80 for Render
EXPOSE 80

# Use the custom script as the entrypoint
ENTRYPOINT ["/usr/local/bin/start.sh"]
