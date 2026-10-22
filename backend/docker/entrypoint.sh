#!/bin/sh
set -e

cd /app

# Tunggu database siap, lalu jalankan migrasi.
attempt=0
until php artisan migrate --force; do
  attempt=$((attempt + 1))
  if [ "$attempt" -ge 30 ]; then
    echo "Database belum siap setelah 30 percobaan. Berhenti."
    exit 1
  fi
  echo "Menunggu database... (percobaan $attempt)"
  sleep 3
done

# Buat akun admin dan data contoh (aman dijalankan berulang).
php artisan db:seed --force

exec "$@"