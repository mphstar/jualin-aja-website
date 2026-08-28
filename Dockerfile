ARG PHP_VERSION=8.4
FROM php:${PHP_VERSION}-fpm-alpine

WORKDIR /var/www/html

# Install system dependencies, Node.js 20, Nginx & Supervisor
RUN apk add --no-cache \
    nodejs \
    npm \
    nginx \
    supervisor \
    netcat-openbsd \
    zip \
    unzip \
    curl

# Install pre-compiled PHP extensions secara cepat (bebas kompilasi C lama)
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql gd zip bcmath opcache intl mbstring

# Naikkan batas unggahan. Default PHP-FPM hanya 2M/8M; aplikasi mengizinkan
# PDF ≤ 50MB dan cover ≤ 4MB, jadi beri ruang (64M) untuk body multipart.
RUN printf 'upload_max_filesize = 64M\npost_max_size = 64M\nmax_execution_time = 300\nmax_input_time = 300\nmemory_limit = 512M\n' > "$PHP_INI_DIR/conf.d/99-uploads.ini"

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

RUN mkdir -p /var/log/supervisor /var/www/html/public/uploads \
    && chmod +x /usr/local/bin/entrypoint.sh \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public/uploads

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
