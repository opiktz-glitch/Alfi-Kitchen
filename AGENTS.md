# Alfi Kitchen — Panduan Agen

## Proyek
- Katalog produk puding, dessert, dan salad buah rumahan. PHP native + MySQL/PDO, CSS vanilla, dan slider sentuh native (tanpa Swiper).
- Pemesanan diarahkan ke WhatsApp; aplikasi belum menyediakan keranjang belanja, pembayaran, atau pelacakan pesanan.
- Halaman utama: `index.php`, `product.php`, `header.php`/`footer.php`, dan tampilan di `views/`.
- Panel admin berada di `admin/` (autentikasi melalui `admin/auth_check.php`, CSRF melalui `admin/csrf_check.php`).

## Teknologi dan konvensi
- Kode saat ini memerlukan PHP 8.0+ (`str_starts_with()`); CI menggunakan PHP 8.2. Jangan menyatakan aplikasi kompatibel dengan PHP 7.4 kecuali kompatibilitas telah dipulihkan dan diuji.
- Tes tidak memerlukan Composer; jalankan `C:\xampp\php\php.exe tests\run_tests.php`.
- Entry point aplikasi memuat `includes/bootstrap.php`: gunakan `config.php` root bila tersedia demi kompatibilitas konfigurasi lama, atau fallback ke `includes/config.php` yang membaca `.env`. Jangan pernah meng-commit kredensial, `.env`, atau `config.php` yang berisi rahasia. `config.example.php` adalah template opsional.
- Database memakai PDO. Migrasi berada di `database/migrations/` (v01–v04), dan dijalankan melalui `php database/migrate.php` atau panel admin.
- Helper berada di `includes/` (konfigurasi, `env_loader`, `seo_helpers`, `fallback_logo`, `DatabaseMigrator`) dan `admin/helpers/db_helper.php`.
- Semua halaman admin harus memuat `auth_check.php` dan memeriksa CSRF pada request POST. Catat aksi melalui `log_admin_action()`.

## Aturan keamanan (jangan dilanggar)
- `thumb.php`: gunakan `resolve_thumbnail_path()`; jangan membaca `$_GET['file']` secara langsung. Hanya layani file di `uploads/` dan file root yang masuk daftar izin.
- Penghapusan gambar: hanya melalui `delete_uploaded_image()` di `admin/helpers/db_helper.php` untuk mencegah path traversal.
- Upload saat ini membatasi ukuran file hingga 5 MB, memvalidasi MIME dan struktur gambar, serta membatasi dimensi hingga 40 juta piksel. Kompresi bersifat best-effort jika GD tersedia. Pertahankan batas ini dan periksa dampak memori/runtime sebelum menaikkannya.
- Penanganan logo belum konsisten: UI dan handler menerima beberapa MIME, tetapi nama tujuan tetap `logo.png`. Sebelum masalah ini diperbaiki, jangan menyatakan bahwa upload logo selain PNG berfungsi; uji format yang diterima ketika mengubah alur ini.
- `.htaccess` memblokir akses langsung ke `.env`, `.sql`, `.log`, `.git*`, dan `database/`. Pertahankan `database/.htaccess` (`Require all denied`).
- Cookie sesi diperketat: masa berlaku 1800 detik, `httponly`, `samesite=Lax`, dan `secure` saat menggunakan HTTPS.
- Deployment saat ini menggunakan FTP tanpa enkripsi pada port 21. Utamakan transport terenkripsi jika didukung; jangan pernah mengekspos atau meng-commit kredensial deployment.

## Pengujian
- Pilih verifikasi terkecil yang benar-benar mencakup perubahan; jangan menjalankan seluruh suite atau mengulang pemeriksaan yang tidak terkait tanpa alasan.
- Untuk perubahan PHP pada helper bersama, autentikasi/CSRF, upload/path, konfigurasi, database/migrasi, atau alur yang berdampak luas, jalankan `C:\xampp\php\php.exe tests\run_tests.php` karena runner lokal menjalankan seluruh suite sekaligus.
- Untuk perubahan tampilan yang terisolasi, verifikasi halaman terkait di browser pada ukuran layar yang terdampak; jalankan tes otomatis hanya jika ada tes terfokus yang mencakup perilaku tersebut.
- Perubahan dokumentasi saja tidak memerlukan tes. Periksa tautan atau fakta dokumentasi bila relevan.
- Jika tes dijalankan, laporkan hasil aktual dan alasan cakupan tes yang dipilih; jangan mengandalkan jumlah tetap.
- Cakupan suite: `SyntaxTest`, `AdminRefactorTest`, `ThumbSecurityTest`, `UploadHelperRefactorTest`, `DatabaseMigratorTest`, `FallbackLogoTest`, `EnvLoaderTest`, `BackupAndLogTest`, dan `SeoAndRoutingTest`.
- CI menjalankan tes sebelum deployment (`.github/workflows/deploy.yml`).
- GD bersifat opsional pada PHP CLI lokal. Jalur fallback tanpa GD lebih mudah diuji daripada perilaku resize/WebP yang memerlukan ekstensi GD aktif.

## Tugas umum
- Menambah migrasi: buat `database/migrations/vNN_*.sql`; perbarui `DatabaseMigrator` bila diperlukan.
- Menambah halaman admin: sertakan `auth_check.php` dan `csrf_check.php`, gunakan `.form-card-sm`, `.form-control`, `.table-responsive`, dan tambahkan tombol “Lihat Website”.
- Menangani gambar: gunakan `upload_helper.php` dan `delete_uploaded_image()`.
- Membuat tautan WhatsApp: gunakan `format_whatsapp_number()` / `whatsapp_url()` dari `includes/seo_helpers.php`.

## Tindak lanjut yang diketahui
- Lihat `docs/ROADMAP.md` untuk temuan berdasarkan prioritas, termasuk transport deployment terenkripsi, versi PHP yang didukung, batas sumber daya pemrosesan gambar, validasi password di server, dan konsistensi format logo.
- Pembatasan ukuran upload sudah diterapkan; jangan cantumkan lagi sebagai pekerjaan tertunda.
- Ide backlog yang masih dipertahankan dari panduan sebelumnya: pencarian cepat di homepage dan mode pemeliharaan (HTTP 503). Keduanya belum tercantum di `docs/ROADMAP.md`.

## Larangan
- Jangan meng-commit `.env`, `config.php`, atau gambar hasil upload.
- Jangan menambahkan blok `<style>` inline di admin; gunakan `admin/assets/css/admin.css`.
- Jangan melewati pemeriksaan autentikasi atau CSRF.
- Jangan menggunakan Swiper; slider native telah menggantikannya.
