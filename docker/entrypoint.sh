#!/bin/sh

echo "==> Inisialisasi container Laravel..."

# Salin .env.docker ke .env jika .env belum ada
if [ ! -f /var/www/html/.env ]; then
    echo "==> Menyalin .env.docker ke .env..."
    cp /var/www/html/.env.docker /var/www/html/.env
fi

# Tunggu basis data MariaDB/MySQL siap (maksimal 30 detik)
echo "==> Menunggu database (${DB_HOST:-db}:3306) siap..."
n=0
until [ $n -ge 15 ]
do
   nc -z "${DB_HOST:-db}" "${DB_PORT:-3306}" >/dev/null 2>&1 && break
   n=$((n+1))
   sleep 2
done

# Buat kunci aplikasi jika belum diset
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
    echo "==> Menghasilkan APP_KEY..."
    php artisan key:generate --force || true
fi

# Jalankan migrasi basis data
echo "==> Menjalankan migrasi basis data..."
php artisan migrate --force || echo "Migrasi akan dicoba ulang saat basis data siap..."

# Seed data awal. Di production DatabaseSeeder memanggil SeederProduksi yang
# idempotent (firstOrCreate), jadi aman diulang setiap container naik tanpa
# menduplikasi data atau menimpa kata sandi yang sudah diganti.
echo "==> Menjalankan seeder data awal..."
php artisan db:seed --force || echo "Seeder gagal atau tidak diperlukan..."

# Buat tautan simbolik storage
if [ ! -d /var/www/html/public/storage ]; then
    echo "==> Membuat storage:link..."
    php artisan storage:link --force || true
fi

# Bersihkan cache lama
echo "==> Membersihkan cache..."
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

# Atur hak akses direktori storage & cache
echo "==> Mengatur hak akses folder storage & bootstrap/cache..."
mkdir -p /var/log/supervisor /var/www/html/storage/logs
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

echo "==> Aplikasi siap! Memulai Supervisor..."
exec "$@"
