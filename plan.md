# Plan Implementasi Fitur Check-in Booking

Setiap langkah di bawah ini berdiri sendiri, memiliki kriteria selesai/gagal yang jelas, dan di-commit menjadi **satu commit** secara berurutan.

## Langkah 1 — Dokumen Spesifikasi (`PRD.md`, `erd.md`, `openapi.yaml`, `plan.md`)
- **Lingkup:** Menulis PRD (maks. 1 halaman, 4 bagian, minimal 3 acceptance criteria yang dapat gagal termasuk aturan jendela waktu check-in), ERD (`booking_check_ins` beserta relasi dan constraint DB), kontrak OpenAPI (`POST /api/bookings/{booking}/check-in`), dan rencana kerja ini.
- **Kriteria selesai:** Keempat file tersimpan di root repository, PRD memuat 4 bagian wajib dan aturan bisnis jendela waktu, `erd.md` mencantumkan constraint DB, dan `openapi.yaml` mendokumentasikan kode `201`, `401`, `404`, `409`, `422`.
- **Commit:** `docs: tambah PRD.md, erd.md, openapi.yaml, dan plan.md untuk fitur check-in booking`

## Langkah 2 — Migrasi Database `booking_check_ins`
- **Lingkup:** Membuat file migrasi Laravel untuk tabel `booking_check_ins` dengan kolom `booking_id` (`unique`, FK `cascade`), `checked_in_by` (FK `restrict`), `attendee_count`, `checked_in_at`, dan `notes` (`nullable`).
- **Kriteria selesai:** `php artisan migrate:fresh` di `backend/` berhasil tanpa error dan skema tabel `booking_check_ins` sesuai `erd.md`.
- **Commit:** `feat(db): tambah migrasi tabel booking_check_ins dengan FK dan unique constraint`

## Langkah 3 — Tes Fitur Check-in Lebih Dulu (Red Phase)
- **Lingkup:** Menulis `backend/tests/Feature/BookingCheckInTest.php` yang menguji seluruh acceptance criteria (sukses `201`, status bukan `confirmed` ditolak `422`, di luar jendela waktu 30 menit sebelum `start_time` s.d. sebelum `end_time` ditolak `422`, duplikat check-in ditolak `409`, tanpa token ditolak `401`, `attendee_count` tidak valid ditolak `422`).
- **Kriteria selesai:** File tes ter-commit sebelum kode implementasi, dan menjalankan `php artisan test` menghasilkan status **FAILED (merah)** pada tes check-in karena endpoint/model belum ada.
- **Commit:** `test: tambah feature test check-in booking sesuai acceptance criteria (red)`

## Langkah 4 — Implementasi Backend Check-in (Green Phase)
- **Lingkup:** Membuat model `BookingCheckIn`, relasi `checkIn()` di `Booking`, `CheckInBookingRequest`, `BookingCheckInResource`, `BookingCheckInController`, serta mendaftarkan route `POST /api/bookings/{booking}/check-in` (dan menyertakan ringkasan `check_in` di `BookingResource`).
- **Kriteria selesai:** Seluruh test di `php artisan test` lulus **100% (hijau)** **tanpa mengubah isi `BookingCheckInTest.php`**.
- **Commit:** `feat(api): implementasi model, controller, dan route POST /api/bookings/{booking}/check-in`

## Langkah 5 — Integrasi Frontend Vue untuk Check-in
- **Lingkup:** Memperbarui `frontend/src/views/BookingsView.vue` (dan komponen modal check-in `CheckInModal.vue`) agar menampilkan status check-in pada daftar booking serta menyediakan tombol & modal check-in untuk booking berstatus `confirmed`.
- **Kriteria selesai:** Build frontend (`npm run build`) berhasil tanpa error dan fitur check-in dapat dijalankan end-to-end melalui `docker compose up --build` dari browser.
- **Commit:** `feat(frontend): tambah tombol dan modal check-in booking di BookingsView`
