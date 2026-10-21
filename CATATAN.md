# 📝 Catatan Pembaruan, Perbaikan & Rekomendasi — Alfi Kitchen

Dokumen ini mencatat evaluasi perbandingan versi, perbaikan error/regresi yang telah diselesaikan, dan rekomendasi teknis untuk pengembangan aplikasi selanjutnya.

---

## 1. 🔍 Evaluasi & Perbandingan dengan Versi Lama

Versi terbaru membawa peningkatan signifikan dibandingkan versi lama, terutama pada aspek **keamanan**, **arsitektur kode**, dan **standar SEO modern**:

| Aspek | Versi Lama | Versi Baru (Refactor) | Status |
|---|---|---|---|
| **Keamanan `thumb.php`** | Membaca path mentah dari parameter `$_GET['file']`. Rentan eksploitasi *Local File Inclusion (LFI)* / pembacaan file sistem seperti `config.php`. | Divalidasi ketat oleh `resolve_thumbnail_path()`: menolak *traversal* (`..`), *null byte*, path absolut, dan hanya melayani file di folder `uploads/` serta whitelist file statis root. | ✅ Jauh Lebih Aman |
| **Penghapusan Gambar** | Fungsi `unlink()` dipanggil langsung di berbagai file admin tanpa pengujian batas path yang ketat. | Terpusat di `delete_uploaded_image()` pada `admin/helpers/db_helper.php`, memvalidasi bahwa target benar-benar berada di dalam `uploads/` (anti *path traversal*). | ✅ Aman & Konsisten |
| **Autentikasi & CSRF** | Pengecekan session login dan validasi CSRF ditulis berulang-ulang secara manual di setiap file admin. | Sentralisasi melalui `admin/auth_check.php` dan `admin/csrf_check.php`. | ✅ DRY (*Clean Code*) |
| **Styling Admin** | Ratusan baris tag `<style>` inline diduplikasi di setiap file admin. | Disatukan ke satu stylesheet terpusat di `admin/assets/css/admin.css`. | ✅ Mudah Dikelola |
| **Fallback Logo** | String base64 logo (ribuan karakter) ditanam langsung di `header.php` dan `views/home.view.php`. | Dipisah ke `includes/fallback_logo.php` dengan *constant guard* idempotent. | ✅ Template Bersih |
| **SEO Robots & Indexing** | Menggunakan `Disallow: /admin/` di `robots.txt` (Google tetap bisa mengindeks URL jika ada link eksternal). | Menggunakan header HTTP `X-Robots-Tag: noindex, nofollow` di `.htaccess` sesuai rekomendasi resmi Google Search Central. | ✅ Standar Google |

---

## 2. 🛠️ Masalah/Regresi yang Telah Diperbaiki

Selama proses audit dan pengujian, ditemukan beberapa regresi styling dan celah proteksi file yang langsung diperbaiki:

1. **Restorasi Styling Form Input Admin**:
   - *Masalah:* Tag `<input>`, `<textarea>`, `<select>` tidak memiliki class `.form-control` bawaan sehingga tampil mentah/tanpa padding di browser.
   - *Solusi:* Selector input di `admin/assets/css/admin.css` diperluas ke `.form-group input, .form-group textarea, .form-group select`.
2. **Form Card Terpusat (`.form-card-sm`)**:
   - *Masalah:* Halaman tambah/edit kategori dan edit varian jenis produk tidak dibungkus card, sehingga form melebar 1000px di latar polos.
   - *Solusi:* Ditambahkan class `.form-card-sm` (`max-width: 600px; margin: 0 auto;`) dan dibungkus di form terkait.
3. **Pembatasan Ukuran & Rasio Preview Gambar**:
   - *Masalah:* Class `.current-logo`, `.thumb-preview`, dan `.thumb-hero` belum terdefinisi di CSS baru. Preview logo dan gambar varian tampil seukuran resolusi aslinya, sedangkan slider hero menjadi kotak kecil 50px.
   - *Solusi:* Ditambahkan styling `.current-logo` (lingkaran 80x80), `.thumb-preview` (80x80), dan `.thumb-hero` (landscape 120x70 rasio 16:9).
4. **Tabel Responsive di `admin/manage_items.php`**:
   - *Masalah:* Tabel varian produk belum dibungkus `.table-responsive` sehingga berisiko terpotong pada layar ponsel.
   - *Solusi:* Dibungkus dengan `<div class="table-responsive">`.
5. **Proteksi Folder `database/` & File Sensitif**:
   - *Masalah:* Folder `database/` tidak memiliki file `.htaccess` sehingga file `.sql` bisa diunduh langsung lewat URL publik.
   - *Solusi:* Dibuat `database/.htaccess` (`Require all denied`) dan ditambahkan aturan blokir file sensitif (`.sql`, `.log`, `.env`, `phpunit.xml`) di root `.htaccess`.
6. **Standardisasi `export_db.php` & `export_images.php`**:
   - *Masalah:* Masih menggunakan pengecekan session manual lama.
   - *Solusi:* Diselaraskan menggunakan `require 'auth_check.php';` dan didaftarkan ke test suite.
7. **Kompatibilitas Subfolder `.htaccess`**:
   - *Masalah:* Regex `^/(admin/...)` hanya aktif di domain root.
   - *Solusi:* Diperbarui menjadi `(^|/)(admin/|login\.php|logs/|database/)` agar aktif baik di localhost (`localhost/Alfi_Kitchen/`) maupun domain produksi.
8. **Sistem Migrasi Database Berversi (Database Versioning)**:
   - Dibuat class `includes/DatabaseMigrator.php` untuk melacak file migrasi di `database/migrations/` via tabel `schema_migrations`.
   - Dibuat CLI runner `database/migrate.php` (`php database/migrate.php` dan `php database/migrate.php status`).
   - Disediakan panel web di `admin/settings.php` agar admin InfinityFree dapat menerapkan migrasi database cukup dengan sekali klik tombol tanpa harus membuka phpMyAdmin cPanel.
   - Migrasi awal disusun rapi: `v01_initial_schema.sql`, `v02_create_login_attempts.sql`, `v03_settings_utf8mb4.sql`, `v04_add_product_slug.sql`.
9. **Standardisasi Unit Test Native (Zero-Dependency) Sesuai Alur Perubahan**:
   - *Penyesuaian Standar:* Karena aplikasi berbasis Native PHP (tanpa Composer/PHPUnit di hosting/XAMPP), `tests/bootstrap.php` dilengkapi polyfill `PHPUnit\Framework\TestCase` dan `SimpleRunner` mandiri. Dengan demikian, unit test dapat dijalankan instan menggunakan `php tests/run_tests.php` tanpa perlu install Composer, sekaligus tetap 100% kompatibel jika kelak dipasang PHPUnit.
   - *Fokus Alur Perubahan:* Pengujian difokuskan pada modul yang aktif diubah: `SyntaxTest` (linting file baru/terdampak), `AdminRefactorTest` (arsitektur CSS & guard auth/CSRF), `ThumbSecurityTest` (anti-LFI & traversal), `UploadHelperRefactorTest` (uploads root & aman unlink), `DatabaseMigratorTest` (versioning v01..v04 & eksekusi SQL), dan `FallbackLogoTest` (ekstraksi logo).
   - *Eliminasi Test Tidak Perlu:* Menghapus ratusan pengecekan repetitif non-fungsional (seperti 17 test terpisah hanya untuk string class CSS, loop linting ganda, dan string check nama fungsi legacy).
10. **Pemisahan Konfigurasi Lingkungan (`.env`) & Template Git (Selesai — Rekomendasi 1)**:
    - Dibuat `includes/env_loader.php`: modul parser ringan (zero-dependency) untuk membaca format `.env` (mendukung `KEY=VALUE`, komentar `#`, quote sanitization, dan fallback `env()`).
    - Dibuat `.env.example` dan `config.example.php` sebagai template resmi di repository Git.
    - File `config.php` diperbarui agar otomatis memuat `.env` jika tersedia, dengan fallback fleksibel ke `config.local.php` atau default XAMPP.
    - File `.env` dipastikan tetap berada di dalam `.gitignore` dan terlindungi dari akses HTTP langsung via `.htaccess`.
    - Dibuat unit test `tests/Unit/EnvLoaderTest.php` untuk memvalidasi parser dan keamanan `.env`.
11. **Proteksi Cooldown Rate-Limit Endpoint Backup & Rotasi Log (Selesai — Rekomendasi 2 & B.1)**:
    - Ditambahkan fungsi `check_backup_cooldown()` di `admin/auth.php` dengan jeda minimal 60 detik per jenis backup (`database` & `images`) untuk mencegah server overload di InfinityFree.
    - Endpoint `export_db.php` dan `export_images.php` otomatis me-redirect ke `settings.php` dengan pesan alert informatif jika masih dalam masa cooldown.
    - Ditambahkan fitur rotasi log otomatis pada `log_admin_action()` jika file log mencapai > 2 MB.
    - Disediakan tombol *"Bersihkan Log"* dengan konfirmasi dan proteksi CSRF di `admin/activity_log.php`.
    - Dibuat unit test `tests/Unit/BackupAndLogTest.php` untuk menguji fungsionalitas ini secara otomatis.
12. **Perapihan Root Direktori (Relokasi Helper & Konfigurasi Testing)**:
    - Memindahkan `seo_helpers.php` dari root direktori ke `includes/seo_helpers.php` agar sejajar dengan modul helper lainnya.
    - Memindahkan konfigurasi pengujian `phpunit.xml` ke dalam folder `tests/phpunit.xml`.
    - Memperbarui seluruh dependensi path require di controller publik (`index.php`, `header.php`, `product.php`, `robots.php`, `sitemap.php`) dan admin (`add_product.php`, `edit_product.php`).
13. **Browser Caching, Dynamic Robots & Custom 404 Page**:
    - Dikonfigurasikan `mod_expires` dan `Cache-Control` pada `.htaccess` untuk menyimpan aset statis (gambar, font, css, js) di cache browser hingga 1 bulan demi menghemat CPU hits & bandwidth InfinityFree.
    - Dihapus file fisik `robots.txt` agar aturan rewrite ke `robots.php` dinamis aktif penuh sesuai domain yang digunakan.
    - Dibuat halaman error ramah pengunjung `404.php` dengan tema visual Alfi Kitchen dan didaftarkan pada `.htaccess` (`ErrorDocument 404 404.php`).

---

## 3. 💡 Rekomendasi Perbaikan & Pengembangan Selanjutnya

Berikut status rekomendasi teknis untuk pengembangan aplikasi:

### A. Prioritas Tinggi (Keamanan & Lingkungan)
1. **[SELESAI ✅] Pemisahan Konfigurasi Lingkungan (`.env`)**:
   - Telah diimplementasikan menggunakan `includes/env_loader.php`, `.env.example`, dan `config.example.php`. Kredensial lokal terlindungi dan tidak saling timpa saat deployment.
2. **[SELESAI ✅] Proteksi Tambahan pada Endpoint Backup (Rate Limit / Cooldown)**:
   - File `admin/export_db.php` dan `admin/export_images.php` kini dilindungi fungsi `check_backup_cooldown()` dengan jeda waktu 60 detik demi menjaga stabilitas CPU & RAM hosting.

### B. Prioritas Menengah (Fitur & Pemeliharaan)
1. **[SELESAI ✅] Rotasi Log Aktivitas Admin & Tombol Bersihkan Log**:
   - Rotasi otomatis aktif jika ukuran log > 2 MB, dan tombol *"Bersihkan Log"* berproteksi CSRF telah tersedia di `admin/activity_log.php`.
2. **Validasi File WebP di Browser Lama**:
   - Logika GD fallback di `thumb.php` sudah sangat bagus (melayani file asli jika GD WebP tidak tersedia).
   - *Rekomendasi:* Pada tag `<img>` di frontend, pertimbangkan memanfaatkan tag `<picture>` dengan fallback format PNG/JPG untuk kompatibilitas peramban legacy jika diperlukan.

### C. Alur Pengujian Mandiri (Testing)
Setiap kali melakukan perubahan kode di area admin, helper, konfigurasi, atau thumbnail, jalankan test suite otomatis melalui terminal:
```bash
# Melalui PowerShell / CMD di folder project:
C:\xampp\php\php.exe tests\run_tests.php
```
*Status saat ini:* **88 unit test esensial lulus 100%, 0 gagal.**

