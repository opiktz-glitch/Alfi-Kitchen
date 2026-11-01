# Architecture Alfi Kitchen

Dokumen ini menjelaskan susunan dan alur aplikasi berdasarkan implementasi
repository saat ini. Alfi Kitchen adalah katalog produk berbasis PHP yang
mengarahkan pemesanan ke WhatsApp; aplikasi belum menyediakan keranjang,
pembayaran, atau pelacakan pesanan.

## Gambaran sistem

- **Aplikasi web:** PHP native, HTML/CSS/JavaScript, tanpa framework aplikasi.
- **Database:** MySQL melalui PDO; pengujian migrasi juga mencakup SQLite.
- **Web server:** Apache dengan aturan rewrite dan perlindungan `.htaccess`.
- **Deployment:** GitHub Actions menjalankan tes lalu mengunggah berkas melalui
  FTP ke hosting.
- **Tes:** runner PHP native tersedia di `tests/run_tests.php`.

## Struktur komponen

| Lokasi | Tanggung jawab |
|---|---|
| `index.php`, `product.php`, `about.php`, `privacy.php`, `terms.php` | Halaman publik, pembacaan katalog, dan halaman informasi. |
| `header.php`, `footer.php`, `views/home.view.php` | Kerangka HTML, metadata/SEO, presentasi homepage, serta tautan WhatsApp. |
| `admin/` | Panel pengelolaan produk, varian, gambar hero, pengaturan, log, dan backup. |
| `admin/auth.php`, `admin/auth_check.php`, `admin/csrf_check.php` | Sesi admin, autentikasi halaman, token CSRF, dan helper log aktivitas. |
| `admin/upload_helper.php`, `admin/helpers/db_helper.php` | Validasi/penyimpanan gambar, resolusi path uploads, dan operasi gambar bersama. |
| `includes/` | Koneksi konfigurasi, pemuat environment, migrator database, helper SEO, dan fallback logo. |
| `database/migrations/` | Skema database berurutan dan migrasi yang dilacak oleh `schema_migrations`. |
| `uploads/` | Gambar yang diunggah serta cache thumbnail yang dihasilkan. |
| `tests/` | Tes sintaks, helper, alur admin, keamanan thumbnail/upload, migrasi, SEO, dan log. |

## Alur request

### Homepage

1. `index.php` memuat koneksi database.
2. Controller mengambil kategori produk, gambar hero, pengaturan homepage, dan
   nomor WhatsApp.
3. `views/home.view.php` menampilkan konten melalui kerangka bersama
   `header.php` dan `footer.php`.
4. Gambar hero dan gambar katalog yang relevan diminta melalui `thumb.php`.

### Halaman produk

1. `product.php` menerima slug produk sebagai URL utama dan mencari data dengan
   query PDO berparameter.
2. URL lama berbasis ID diarahkan ke URL slug permanen.
3. Controller menampilkan kategori beserta varian, harga, metadata SEO, dan
   structured data.
4. Tombol pemesanan membentuk tautan WhatsApp dari nomor yang tersimpan di
   pengaturan.

### Panel admin

1. Halaman admin memuat `auth_check.php`, yang memakai helper sesi dari
   `admin/auth.php` dan mengarahkan pengunjung yang belum login ke halaman login.
2. Operasi `POST` memuat `csrf_check.php` untuk memvalidasi token CSRF.
3. Perubahan data dilakukan melalui PDO; perubahan penting dapat dicatat pada
   log aktivitas.
4. Upload divalidasi dan disimpan melalui `admin/upload_helper.php`. Penghapusan
   gambar memakai helper path aman di `admin/helpers/db_helper.php`.

### Penyajian gambar

1. Database menyimpan path relatif gambar, umumnya di bawah `uploads/`.
2. `thumb.php` hanya mengizinkan file gambar di `uploads/` dan gambar root
   tertentu; path diperiksa sebelum file dibaca.
3. Jika ekstensi GD tersedia, endpoint dapat mengubah ukuran dan menyimpan hasil
   cache di `uploads/cache/`. Jika pemrosesan gambar tidak tersedia, endpoint
   menyajikan file gambar tervalidasi dalam format aslinya.

## Model data

- `users`: kredensial admin dengan password hash.
- `products`: kategori produk utama dan slug permanen.
- `product_items`: varian produk, harga, deskripsi, dan gambar; terkait ke
  kategori melalui `product_id`.
- `hero_images`: gambar slider homepage.
- `settings`: konfigurasi dan konten sederhana dalam pasangan key-value.
- `login_attempts`: penghitung percobaan login dan masa lockout.
- `schema_migrations`: versi migrasi yang telah diterapkan.

Skema rinci ada di [`database/SCHEMA.md`](../database/SCHEMA.md). Migrasi tersedia
di `database/migrations/` dan dijalankan melalui `database/migrate.php` atau
panel pengaturan admin.

## Konfigurasi dan operasi

Entry point aplikasi memuat `includes/bootstrap.php`. Jika ada `config.php`
khusus di root, bootstrap menggunakannya untuk kompatibilitas dengan instalasi
yang sudah ada. Jika tidak ada, bootstrap menggunakan `includes/config.php`,
yang membaca `.env` dan menyediakan fallback XAMPP. `config.php` dan `.env`
yang berisi kredensial tidak disimpan di Git. Repository menyediakan
`config.example.php` sebagai template opsional untuk konfigurasi root.

Perintah operasi yang umum:

```text
php database/migrate.php
php database/migrate.php status
php tests/run_tests.php
```

Workflow `.github/workflows/deploy.yml` menjalankan tes sebelum deployment.
Rahasia koneksi deploy disediakan melalui GitHub Actions Secrets.

## Dokumentasi terkait

- [`README.md`](../README.md): setup, keamanan, backup, dan panduan penggunaan.
- [`ROADMAP.md`](ROADMAP.md): pekerjaan lanjutan berdasarkan prioritas.
- [`CHANGELOG.md`](../CHANGELOG.md): ringkasan perubahan terdokumentasi.
