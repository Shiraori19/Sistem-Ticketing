#!/bin/sh
set -e

# Sesuaikan port Nginx dengan variabel lingkungan $PORT dari Render (default: 10000)
PORT=${PORT:-10000}
sed -i "s/listen 10000;/listen $PORT;/g" /etc/nginx/http.d/default.conf 2>/dev/null || true

# Pastikan seluruh struktur folder storage & cache tersedia
mkdir -p /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/storage/app/public \
         /var/www/html/storage/app/vault \
         /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Generate APP_KEY jika belum terisi
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Tautkan symbolic link storage
php artisan storage:link --force 2>/dev/null || true

# Tunggu koneksi database siap (maksimal 60 detik)
if [ -n "$DB_HOST" ]; then
    echo "Menunggu koneksi database PostgreSQL di $DB_HOST:$DB_PORT..."
    MAX_TRIES=30
    COUNT=0
    until php -r "try { new PDO('pgsql:host='.getenv('DB_HOST').';port='.(getenv('DB_PORT')?:'5432').';dbname='.getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD')); exit(0); } catch (Exception \$e) { exit(1); }" 2>/dev/null; do
        COUNT=$((COUNT + 1))
        if [ $COUNT -ge $MAX_TRIES ]; then
            echo "Koneksi database belum siap setelah batas waktu, melanjutkan proses..."
            break
        fi
        sleep 2
    done
    echo "Koneksi database PostgreSQL terverifikasi siap."
fi

# Jalankan migrasi dan seeder awal
echo "Menjalankan migrasi dan seeder..."
php artisan migrate --force --seed || true

# Optimasi konfigurasi, route, dan view untuk production
php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true
php artisan view:cache 2>/dev/null || true

# Jalankan Background Queue Worker untuk kompresi PDF dan pengolahan file
php artisan queue:work --tries=3 --timeout=90 &

# Jalankan PHP-FPM di background (daemon)
php-fpm -D

# Jalankan Nginx di foreground sebagai proses utama kontainer
exec nginx -g "daemon off;"

