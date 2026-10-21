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
14. **Sentralisasi Log Login & Kompatibilitas Fallback WebP (Selesai — Rekomendasi B.2)**:
    - Diintegrasikan pencatatan login berhasil di `login.php` dengan fungsi terpusat `log_admin_action()` di `admin/auth.php` sehingga otomatis mewarisi fitur rotasi log > 2 MB.
    - Ditambahkan deteksi kemampuan browser klien pada `thumb.php` (memeriksa `HTTP_ACCEPT` untuk `image/webp`): jika browser klien mendukung WebP, server menyajikan WebP terkompresi; jika browser lawas tidak mendukung WebP, server otomatis menyajikan format JPEG/PNG aslinya.
    - Dihapus file konfigurasi editor eksternal `.clineignore` untuk semakin merampingkan root direktori.

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
2. **[SELESAI ✅] Kompatibilitas File WebP di Browser Lama**:
   - Telah diselesaikan di level server pada `thumb.php`: server otomatis mengecek header peramban (`HTTP_ACCEPT`) dan menyajikan format JPEG/PNG asli secara dinamis jika browser pengunjung adalah peramban legacy yang belum mendukung WebP.

### C. Prioritas Keamanan Web & CI/CD
1. **[SELESAI ✅] HTTP Security Headers**:
   - Diterapkan via `.htaccess` dengan header `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, dan `Referrer-Policy: strict-origin-when-cross-origin` untuk mencegah MIME-sniffing dan clickjacking.
2. **[SELESAI ✅] Hardened Session Cookies**:
   - Pengaturan sesi di `admin/auth.php` dan `login.php` diperketat dengan parameter `lifetime: 1800`, `path: /`, `httponly: true`, `samesite: Lax`, serta `secure: true` otomatis ketika diakses via HTTPS.
3. **[SELESAI ✅] Normalisasi Nomor WhatsApp Otomatis**:
   - Fungsi `format_whatsapp_number()` dan `whatsapp_url()` di `includes/seo_helpers.php` mengonversi format lokal (seperti `0812...` atau `+62...`) menjadi format internasional murni `62812...` untuk tautan `wa.me/` di footer dan halaman 404 tanpa resiko link rusak.
4. **[SELESAI ✅] CI/CD Quality Gate Otomatis**:
   - Workflow `.github/workflows/deploy.yml` kini mengeksekusi PHP unit test suite secara otomatis sebelum proses rilis/FTP dijalankan.

### D. Usulan Pengembangan Baru (Dikerjakan Bertahap Satu per Satu)
1. **[SELESAI ✅] Usulan 1: Progressive Web App (PWA)**:
   - Dibuat file standar `manifest.webmanifest`, integrasi link manifest dan meta tag mobile web app di `header.php`, konfigurasi theme-color `#fff8ef`, background-color `#fff8ef`, icons 192x192 & 512x512, serta caching & MIME type di `.htaccess` agar website dapat diinstal ke layar utama HP Android & iOS (*Add to Home Screen*).
2. **[SELESAI ✅] Usulan 2: Navigasi Cepat Admin Panel**:
   - Menambahkan tombol *"Lihat Website ↗"* di seluruh header halaman admin (`index.php`, `manage_hero.php`, `settings.php`, `activity_log.php`, `manage_items.php`, `add_product.php`, `edit_product.php`, `edit_item.php`), class CSS `.btn-site-preview` di `admin.css`, serta penyeragaman tombol navigasi *"Dashboard"* dan *"Logout"*.
3. **[ANTRIAN 📋] Usulan 3: Proteksi Batas Ukuran File Upload Admin**:
   - Pembatasan ukuran upload maksimal 5MB di `admin/upload_helper.php` dengan pesan error yang jelas untuk mencegah PHP memory exhaustion.
4. **[ANTRIAN 📋] Usulan 4: Filter / Pencarian Menu Cepat di Beranda**:
   - Kotak pencarian interaktif tanpa reload di `views/home.view.php` untuk memfilter puding/dessert secara seketika.
5. **[ANTRIAN 📋] Usulan 5: Mode Toko Libur / Pemeliharaan (Maintenance Mode)**:
   - Opsi toko libur di `admin/settings.php` dengan respon HTTP 503 ramah SEO Google.

### E. Alur Pengujian Mandiri & Verifikasi Runtime
Setiap kali melakukan perubahan kode di area admin, helper, konfigurasi, atau tampilan, jalankan test suite dan verifikasi runtime:
```bash
# Melalui PowerShell / CMD di folder project:
C:\xampp\php\php.exe tests\run_tests.php
```
*Status saat ini:* **101 unit test esensial lulus 100%, 0 gagal.**
*Verifikasi Runtime Live:* Seluruh halaman (`index.php`, `about.php`, `privacy.php`, `terms.php`, `404.php`, `login.php`, `thumb.php`, `sitemap.php`, `robots.php`, `manifest.webmanifest`, dan area admin) telah diverifikasi langsung via Apache & PHP runtime menghasilkan status 200/404 normal dengan header keamanan dan navigasi aktif.




