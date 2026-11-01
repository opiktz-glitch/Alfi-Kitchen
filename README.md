# Alfi Kitchen

Katalog online puding, dessert, dan salad buah rumahan. Pengunjung dapat melihat
produk dan menghubungi toko untuk memesan melalui WhatsApp. Aplikasi belum
menyediakan keranjang belanja, pembayaran, atau pelacakan pesanan.

## Teknologi dan prasyarat

- PHP 8.0 atau lebih baru; CI proyek menggunakan PHP 8.2.
- MySQL/MariaDB dengan ekstensi PDO MySQL.
- Apache dengan `mod_rewrite` dan dukungan `.htaccess` untuk URL produk.
- Ekstensi PHP `fileinfo` untuk validasi upload. GD digunakan untuk pemrosesan
  gambar/WebP dan ZIP untuk backup gambar; fitur terkait memerlukan ekstensi
  masing-masing.

## Setup lokal

1. Buat database MySQL untuk aplikasi.
2. Salin `.env.example` menjadi `.env`, lalu isi kredensial database dan URL
   situs yang sesuai.
3. Entry point aplikasi menggunakan `includes/bootstrap.php`: jika `config.php`
   khusus sudah tersedia di root, konfigurasi tersebut tetap digunakan;
   jika tidak, aplikasi memakai `includes/config.php` yang membaca `.env`.
   Karena itu, checkout lokal baru cukup menggunakan `.env.example` sebagai
   dasar konfigurasi.
4. Jalankan migrasi dari terminal di root proyek:

   ```text
   C:\xampp\php\php.exe database\migrate.php
   ```

   Untuk melihat status migrasi:

   ```text
   C:\xampp\php\php.exe database\migrate.php status
   ```

5. Pastikan folder `uploads/` dapat ditulis oleh PHP, lalu buka
   `http://localhost/Alfi_Kitchen`.

Jangan meng-commit `.env` atau `config.php`. `config.example.php` tetap tersedia
sebagai template konfigurasi root opsional untuk instalasi yang memerlukannya.
Pada hosting, konfigurasi root yang sudah ada tetap diprioritaskan; tanpa file
tersebut, aplikasi membaca environment `.env` jika tersedia.

## Fitur

- Katalog kategori dan varian produk dengan URL produk berbasis slug.
- Panel admin untuk mengelola produk, gambar hero, konten homepage, dan nomor
  WhatsApp.
- Upload gambar dengan batas 5 MB, validasi MIME/struktur, dan batas dimensi.
- Thumbnail responsif dengan cache; konversi dan kompresi memerlukan dukungan
  GD.
- Ekspor backup database dan gambar dari panel pengaturan admin.
- SEO dasar, sitemap, halaman 404, dan manifest PWA.

## Tes

Jalankan tes native dari root proyek:

```text
C:\xampp\php\php.exe tests\run_tests.php
```

GitHub Actions menjalankan tes sebelum deployment.

## Deployment

Workflow `.github/workflows/deploy.yml` berjalan saat ada push ke branch `main`.
Panduan lengkap update dan pemulihan hosting tersedia di
[DEPLOYMENT.md](docs/DEPLOYMENT.md). Workflow saat ini menggunakan FTP port 21;
transport FTP biasa tidak mengenkripsi kredensial atau trafik.

## Dokumentasi

- [Arsitektur aplikasi](docs/ARCHITECTURE.md)
- [Panduan deployment](docs/DEPLOYMENT.md)
- [Roadmap pengembangan](docs/ROADMAP.md)
- [Riwayat perubahan](CHANGELOG.md)
- [Skema database](database/SCHEMA.md)
