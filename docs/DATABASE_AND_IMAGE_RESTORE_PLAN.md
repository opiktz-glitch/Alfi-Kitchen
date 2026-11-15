# Rencana & Tahapan Implementasi: Restore Database & Restore Gambar

Dokumen ini berisi spesifikasi teknis, standar keamanan *defensive programming*, dan tahapan pengerjaan untuk fitur **Restore Database (.sql)** dan **Restore Gambar (.zip)** pada panel admin Alfi Kitchen.

Fitur ini dirancang untuk dieksekusi pada sesi berikutnya (besok).

---

## 1. Prinsip Utama & Kebijakan Integritas Skema

1. **Wajib Sinkron dengan Versi Skema Migrasi (`schema_migrations`):**
   - Database Alfi Kitchen menggunakan skema berversi (`DatabaseMigrator`), saat ini pada versi `v05_hpp.sql`.
   - **Aturan Ketat:** File backup `.sql` **HANYA BOLEH** di-restore jika daftar versi migrasi di dalam file backup cocok 100% dengan versi migrasi aktif di sistem.
   - Jika versi file backup berbeda (misal: backup dari versi `v04` lama, atau file database eksternal yang tidak memiliki tabel `schema_migrations`), proses restore **DITOLAK SEKETIKA** sebelum ada perubahan apapun di database.
2. **Atomic & Non-Destructive on Failure:**
   - Proses pembacaan file dilakukan dengan validasi *pre-flight*. Jika file tidak valid, rusak, atau skema tidak cocok, database berjalan tidak disentuh sama sekali.
3. **Anti-Lockout Sesi Admin:**
   - File backup berisi data tabel `users`. Handler restore wajib mengamankan kredensial sesi admin yang sedang login agar admin tidak terlempar keluar (*locked out*) setelah proses restore selesai.
4. **Pencegahan Path Traversal & Zip Slip pada Gambar:**
   - Ekstraksi arsip ZIP wajib diverifikasi per entri. Semua target ekstraksi harus berada di dalam direktori `uploads/`. Entri berbahaya (`../`, file skrip `.php`, `.htaccess`, `.sh`) langsung dibuang.

---

## 2. Arsitektur Teknis

### A. Format Header Backup (`admin/export_db.php`)
Menyematkan metadata standar di bagian teratas output `.sql`:
```sql
-- ALFI_KITCHEN_BACKUP
-- SCHEMA_VERSION: v05_hpp.sql
-- MIGRATIONS: v01_initial_schema.sql,v02_create_login_attempts.sql,v03_settings_utf8mb4.sql,v04_add_product_slug.sql,v05_hpp.sql
-- EXPORTED_AT: 2026-11-14 20:30:00
```

### B. Validasi Pre-Flight Restore Database (`admin/restore_db.php`)
1. **Validasi Ekstensi & MIME:** Hanya menerima file `.sql` maks. 10 MB.
2. **Ekstraksi Versi Migrasi:** Membaca header metadata atau baris `INSERT INTO schema_migrations`.
3. **Pencocokan Versi:** Membandingkan array migrasi file backup dengan hasil `(new DatabaseMigrator($pdo))->getAppliedMigrations()`.
4. **Whitelist Tabel Resmi (11 Tabel):**
   - `users`
   - `login_attempts`
   - `products`
   - `product_items`
   - `hero_images`
   - `settings`
   - `schema_migrations`
   - `hpp_ingredients`
   - `hpp_recipes`
   - `hpp_recipe_lines`
   - `hpp_recipe_costs`
   Perintah di luar tabel ini (misal `DROP DATABASE`, `GRANT`, tabel acak) akan memicu pembatalan restore.
5. **Eksekusi Bertahap:** Menjalankan statement SQL secara teratur dengan penanganan timeout hosting shared.

### C. Validasi Pre-Flight Restore Gambar (`admin/restore_images.php`)
1. **Validasi File:** Ekstensi `.zip`, MIME `application/zip`, ukuran maks. 50 MB.
2. **Sanitasi Path Zip Slip:**
   ```php
   $targetPath = realpath($uploadsDir) . DIRECTORY_SEPARATOR . $cleanRelativePath;
   if (!str_starts_with($targetPath, realpath($uploadsDir))) {
       // Tolak atau lewati file traversal
   }
   ```
3. **Whitelist Ekstensi Gambar:** Hanya `.jpg`, `.jpeg`, `.png`, `.webp`, `.gif`, `.svg`. File executable dibuang.
4. **Pencatatan Audit:** Mencatat jumlah gambar yang berhasil dipulihkan ke `activity_log`.

---

## 3. Rencana Antarmuka Pengguna (UI)

Di halaman **Pengaturan** (`admin/settings.php`) &rarr; **Tab 3: Database & Backup**:
- **Kartu 1: Unduh Backup (Export)**
  - Tombol *Backup Database* (`.sql`) dan *Backup Semua Gambar* (`.zip`).
- **Kartu 2: Restore Database (.sql)**
  - Badge info: `Versi Skema Sistem: v05_hpp.sql (Aktif)`.
  - Input file `.sql`.
  - Peringatan visual: *"Restore akan menggantikan seluruh data katalog produk, resep HPP, dan setelan dengan data dari file backup. Versi skema backup harus sama persis dengan versi sistem saat ini."*
  - Tombol `Restore Database` berwarna bahaya/peringatan dengan konfirmasi dialog `onsubmit`.
- **Kartu 3: Restore Gambar (.zip)**
  - Input file `.zip`.
  - Keterangan: *"Mengekstrak dan memulihkan file gambar ke folder uploads/."*
  - Tombol `Restore Gambar` dengan konfirmasi.

---

## 4. Tahapan Pengerjaan (Implementation Steps) [SELESAI]

| Tahap | Fokus Pekerjaan | Berkas Terkait | Status |
| :--- | :--- | :--- | :---: |
| **Tahap 1** | **Pembaruan Header Export DB:** Menambahkan metadata skema migrasi dan timestamp pada output backup `.sql`. | `admin/export_db.php`, `admin/helpers/backup_helper.php` | ✅ Selesai |
| **Tahap 2** | **Modul Backend Restore Database:** Handler validasi upload, parser verifikasi migrasi, filter whitelist tabel, sinkronisasi sesi admin, dan eksekusi SQL aman. | `admin/restore_db.php` | ✅ Selesai |
| **Tahap 3** | **Modul Backend Restore Gambar:** Handler upload ZIP, proteksi anti-Zip Slip (path traversal), filter ekstensi aman, ekstraksi ke `uploads/`. | `admin/restore_images.php` | ✅ Selesai |
| **Tahap 4** | **Antarmuka Panel Admin:** Penambahan kartu Restore Database dan Restore Gambar di Tab 3 Pengaturan dengan validasi CSRF dan konfirmasi interaktif. | `admin/settings.php` | ✅ Selesai |
| **Tahap 5** | **Pengujian Komprehensif (Unit Tests):** Membuat test suite untuk memverifikasi penolakan backup beda versi skema, penolakan file traversal, dan izin restore skema identik. | `tests/Unit/DatabaseRestoreTest.php`, `tests/run_tests.php` | ✅ Selesai |
