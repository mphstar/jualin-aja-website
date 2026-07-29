#!/bin/sh
set -e

echo "==> Inisialisasi container Laravel..."

# Salin .env.docker ke .env jika .env belum ada
if [ ! -f /var/www/html/.env ]; then
    echo "==> Menyalin .env.docker ke .env..."
    cp /var/www/html/.env.docker /var/www/html/.env
fi

# Tunggu basis data MySQL siap
if [ "$DB_CONNECTION" = "mysql" ]; then
    echo "==> Menunggu MySQL (${DB_HOST}:${DB_PORT:-3306}) siap..."
    while ! nc -z "$DB_HOST" "${DB_PORT:-3306}"; do
        sleep 2
    done
    echo "==> MySQL telah siap!"
fi

# Buat kunci aplikasi jika belum diset
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
    echo "==> Menghasilkan APP_KEY..."
    php artisan key:generate --force
fi

# Jalankan migrasi basis data
echo "==> Menjalankan migrasi basis data..."
php artisan migrate --force

# Buat tautan simbolik storage
if [ ! -d /var/www/html/public/storage ]; then
    echo "==> Membuat storage:link..."
    php artisan storage:link --force
fi

# Optimasi cache Laravel jika di lingkungan produksi
if [ "$APP_ENV" = "production" ]; then
    echo "==> Membuat cache konfigurasi & rute..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

# Atur hak akses direktori storage & cache
echo "==> Mengatur hak akses folder storage & bootstrap/cache..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

echo "==> Aplikasi siap! Memulai Supervisor..."
exec "$@"
