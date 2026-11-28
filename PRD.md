# PRD — Fitur Check-in Kedatangan Booking

## 1. Masalah
Pada aplikasi Booking Fasilitas saat ini, admin hanya dapat membuat, mengubah status (`pending`, `confirmed`, `cancelled`), dan menghapus booking. Belum ada pencatatan kedatangan aktual (*check-in*) saat pemesan tiba di lokasi beserta jumlah peserta yang hadir. Akibatnya admin tidak dapat membedakan booking yang sekadar `confirmed` dengan yang benar-benar sudah hadir, serta belum ada validasi aturan operasional saat tamu datang terlalu awal atau melewati jam sewa.

## 2. Penggunanya Siapa
**Admin / petugas resepsionis fasilitas** yang sudah login ke dashboard aplikasi (autentikasi Sanctum) untuk memverifikasi dan mencatat kedatangan pemesan di meja layanan.

## 3. Out of Scope
- Pembayaran DP/pelunasan atau perhitungan denda keterlambatan.
- Check-in mandiri oleh pelanggan menggunakan QR code atau aplikasi publik tanpa login.
- Pembatalan otomatis (*auto-no-show*) untuk booking yang tidak check-in.
- Laporan rekapitulasi kehadiran bulanan atau ekspor CSV/PDF.

## 4. Acceptance Criteria
1. **AC-1 (Hanya booking `confirmed` yang boleh check-in):** Permintaan `POST /api/bookings/{booking}/check-in` pada booking berstatus `pending` atau `cancelled` harus ditolak dengan status **422 Unprocessable Entity** dan pesan validasi pada field `booking`.
2. **AC-2 (Aturan jendela waktu check-in — aturan bisnis):** Check-in hanya diizinkan pada tanggal `booking_date` yang sama, mulai **30 menit sebelum `start_time`** sampai **sebelum `end_time`** (`start_time - 30 menit <= sekarang < end_time`). Permintaan di luar rentang waktu tersebut (terlalu awal atau sudah lewat) harus ditolak dengan status **422 Unprocessable Entity**.
3. **AC-3 (Satu booking hanya bisa check-in satu kali):** Tabel `booking_check_ins` menegakkan constraint `UNIQUE(booking_id)`. Permintaan check-in kedua untuk booking yang sudah memiliki data check-in harus ditolak dengan status **409 Conflict**.
4. **AC-4 (Validasi input & autentikasi):** `attendee_count` wajib integer bernilai `1..50` (`notes` opsional maks. 255 karakter); input tidak valid ditolak **422**. Permintaan tanpa token Sanctum ditolak **401**, dan `booking` yang tidak ditemukan ditolak **404**.
5. **AC-5 (Check-in berhasil):** Permintaan yang memenuhi semua syarat menyimpan baris baru di `booking_check_ins` (mencatat `checked_in_by` dari user login dan `checked_in_at` waktu saat ini) dan mengembalikan **201 Created**.
