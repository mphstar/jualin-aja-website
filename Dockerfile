ARG PHP_VERSION=8.4
FROM php:${PHP_VERSION}-fpm-alpine

WORKDIR /var/www/html

# Install system dependencies, Node.js 20, Nginx, Supervisor & PHP extensions
RUN apk add --no-cache \
    nodejs \
    npm \
    nginx \
    supervisor \
    netcat-openbsd \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    zip \
    unzip \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        gd \
        zip \
        bcmath \
        opcache \
        intl \
        mbstring

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy project files
COPY . .

# 1. Install PHP dependencies terlebih dahulu (agar vendor/autoload.php siap)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# 2. Install Node dependencies & Build Frontend Assets (React/Vite + Wayfinder)
RUN npm ci && npm run build

# 3. Hapus node_modules setelah build agar ukuran container tetap kecil
RUN rm -rf node_modules

# Copy Nginx & Supervisor configuration
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
