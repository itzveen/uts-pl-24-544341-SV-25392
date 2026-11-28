# ERD — Fitur Check-in Kedatangan Booking

## Diagram Relasi

```
+----------------------+       1     0..1  +-------------------------+
|       bookings       |-------------------|    booking_check_ins    |
+----------------------+                   +-------------------------+
| PK id                |                   | PK id                   |
|    customer_name     |                   | FK booking_id (UNIQUE)  |
|    facility          |                   | FK checked_in_by        |
|    booking_date      |                   |    attendee_count       |
|    start_time        |                   |    checked_in_at        |
|    end_time          |                   |    notes (NULLABLE)     |
|    status            |                   |    created_at           |
|    notes (NULLABLE)  |                   |    updated_at           |
|    created_at        |                   +-------------------------+
|    updated_at        |                               | N
+----------------------+                               |
                                                       | 1
                                           +-------------------------+
                                           |          users          |
                                           +-------------------------+
                                           | PK id                   |
                                           |    name                 |
                                           |    email (UNIQUE)       |
                                           |    password             |
                                           +-------------------------+
```

## Detail Tabel Baru: `booking_check_ins`

| Kolom | Tipe Data | Constraint | Keterangan |
|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | `PRIMARY KEY`, `AUTO_INCREMENT` | ID unik catatan check-in |
| `booking_id` | `BIGINT UNSIGNED` | `NOT NULL`, `UNIQUE`, `FK -> bookings.id ON DELETE CASCADE` | Menjamin 1 booking maksimal 1 kali check-in di level database |
| `checked_in_by` | `BIGINT UNSIGNED` | `NOT NULL`, `FK -> users.id ON DELETE RESTRICT` | ID petugas/admin yang melakukan check-in |
| `attendee_count` | `TINYINT UNSIGNED` | `NOT NULL` | Jumlah peserta yang hadir (1–50) |
| `checked_in_at` | `TIMESTAMP` | `NOT NULL` | Waktu aktual check-in dicatat |
| `notes` | `VARCHAR(255)` | `NULLABLE` | Catatan tambahan petugas saat check-in |
| `created_at` | `TIMESTAMP` | `NULLABLE` | Waktu record dibuat |
| `updated_at` | `TIMESTAMP` | `NULLABLE` | Waktu record diubah |

## Aturan yang Ditegakkan di Level Database
1. **Unique constraint (`booking_check_ins_booking_id_unique`):** Mencegah duplikasi check-in untuk satu booking secara atomik di database.
2. **Foreign key `booking_id` (`ON DELETE CASCADE`):** Tidak boleh ada check-in yatim (*orphan*) tanpa booking; jika booking dihapus, data check-in ikut terhapus.
3. **Foreign key `checked_in_by` (`ON DELETE RESTRICT`):** Memastikan setiap check-in tercatat dilakukan oleh user yang valid dan user tidak bisa dihapus sembarangan bila masih memiliki riwayat check-in.
4. **Nullability:** `booking_id`, `checked_in_by`, `attendee_count`, dan `checked_in_at` wajib terisi (`NOT NULL`), sedangkan `notes` bersifat opsional (`NULLABLE`).
