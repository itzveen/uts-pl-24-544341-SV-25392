# syntax=docker/dockerfile:1

# Image dasar dengan versi yang jelas (bukan latest): FrankenPHP + PHP 8.4
FROM dunglas/frankenphp:1.9-php8.4-bookworm

ENV COMPOSER_ALLOW_SUPERUSER=1

# Ekstensi PHP tambahan: pdo_mysql (koneksi MySQL) dan zip (dipakai Composer)
RUN install-php-extensions pdo_mysql zip \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Composer dengan versi tetap
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# 1) Salin composer.json dan composer.lock SAJA, lalu pasang dependensi.
#    Layer ini baru dibangun ulang kalau dua file itu berubah (cache layer bekerja).
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction

# 2) Baru salin kode aplikasi
COPY . .

RUN composer dump-autoload --no-dev --optimize --no-interaction \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Konfigurasi server FrankenPHP (Caddy) dan skrip startup
COPY docker/Caddyfile /etc/caddy/Caddyfile
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Tidak ada secret di sini: APP_KEY, kredensial DB, dll masuk lewat environment saat container dijalankan.

EXPOSE 80

ENTRYPOINT ["entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile", "--adapter", "caddyfile"]