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

---

## 3. 💡 Rekomendasi Perbaikan & Pengembangan Selanjutnya

Berikut rekomendasi teknis yang disarankan untuk pengembangan bertahap berikutnya:

### A. Prioritas Tinggi (Keamanan & Lingkungan)
1. **Pemisahan Konfigurasi Lingkungan (`.env`)**:
   - Saat ini kredensial database di `config.php` masih ditulis langsung.
   - *Rekomendasi:* Gunakan format `.env` atau pemisahan file config lokal (`config.local.php`) yang masuk ke `.gitignore` agar kredensial database localhost dan hosting cPanel tidak saling timpa saat commit/deploy Git.
2. **Proteksi Tambahan pada Endpoint Backup**:
   - File `admin/export_db.php` dan `admin/export_images.php` melakukan kompresi dan query dump seluruh database.
   - *Rekomendasi:* Berikan batasan jeda waktu (rate limit / cooldown) per sesi admin agar tidak dapat diunduh berulang kali secara berurutan dalam waktu singkat demi menghemat beban CPU & RAM hosting.

### B. Prioritas Menengah (Fitur & Pemeliharaan)
1. **Rotasi Log Aktivitas Admin (`admin/activity_log.php`)**:
   - Saat ini file `logs/admin_activity.log` terus bertambah setiap ada penambahan atau pengubahan produk.
   - *Rekomendasi:* Buat mekanisme rotasi log otomatis jika ukuran file mencapai > 2 MB (misal di-rename menjadi `admin_activity_YYYY-MM.log`), atau sediakan tombol *"Bersihkan Log"* di panel admin.
2. **Validasi File WebP di Browser Lama**:
   - Logika GD fallback di `thumb.php` sudah sangat bagus (melayani file asli jika GD WebP tidak tersedia).
   - *Rekomendasi:* Pada tag `<img>` di frontend, pertimbangkan memanfaatkan tag `<picture>` dengan fallback format PNG/JPG untuk kompatibilitas peramban legacy jika diperlukan.

### C. Alur Pengujian Mandiri (Testing)
Setiap kali melakukan perubahan kode di area admin, helper, atau thumbnail, jalankan test suite otomatis melalui terminal:
```bash
# Melalui PowerShell / CMD di folder project:
C:\xampp\php\php.exe tests\run_tests.php
```
*Status saat ini:* **110 test lulus, 0 gagal.**
