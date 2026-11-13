# Panduan Deployment Alfi Kitchen

Panduan ini menjelaskan pembaruan aplikasi di InfinityFree pada
**https://alfikitchen.web.id**. Langkah pertama kali setup dijelaskan terpisah
agar data dan konfigurasi situs produksi tidak tertimpa.

## Prasyarat domain dan hosting

- Tambahkan `alfikitchen.web.id` sebagai domain situs pada akun InfinityFree dan
  arahkan DNS sesuai instruksi panel hosting. Tunggu hingga domain aktif.
- Aktifkan HTTPS/SSL untuk domain dan pastikan sertifikat aktif sebelum
  mengandalkan redirect HTTPS pada `.htaccess`.
- Pilih PHP 8.0 atau lebih baru (CI menggunakan PHP 8.2) dan pastikan PDO
  MySQL serta `fileinfo` tersedia. GD dan ZIP diperlukan untuk fitur
  pemrosesan gambar dan backup gambar.
- Pastikan domain memakai direktori situs yang dituju workflow (`/htdocs/`).
  Jangan mengubah direktori server sebelum memeriksa pemetaan domain di panel.
- Saat domain memakai webroot baru, salin isi `uploads/` dari webroot lama ke
  webroot domain baru dengan FTP/File Manager. Gambar upload tidak disimpan di
  Git dan tidak ikut deployment GitHub Actions; database hanya menyimpan path
  relatif gambar.

## Gambaran deployment saat ini

- Push ke branch `main` memicu GitHub Actions di
  `.github/workflows/deploy.yml`.
- Workflow menyiapkan PHP 8.2, menjalankan `php tests/run_tests.php`, lalu
  menyinkronkan berkas melalui FTP ke direktori server `/htdocs/`.
- Jika tes gagal, langkah deployment tidak dijalankan.
- `config.php`, `.env`, `.env.example`, template konfigurasi, dokumentasi
  Markdown, dan direktori tes dikecualikan dari upload. `dangerous-clean-slate`
  bernilai `false`, jadi berkas lama di server tidak otomatis dihapus.
- FTP port 21 yang dikonfigurasi workflow tidak mengenkripsi kredensial atau
  trafik. Gunakan FTPS atau mekanisme terenkripsi lain jika didukung hosting;
  jangan menaruh kredensial di repository atau percakapan.

Konfirmasikan bahwa `/htdocs/` adalah direktori web yang benar untuk akun FTP
Anda sebelum mengubah workflow. Jangan mengaktifkan penghapusan menyeluruh
sebagai jalan pintas: direktori itu berisi konfigurasi dan gambar produksi yang
tidak disimpan di Git.

## Persiapan sebelum memperbarui situs

1. Pastikan situs masih dapat diakses dan Anda dapat masuk ke panel admin.
2. Dari **Admin → Pengaturan**, unduh backup database dan backup gambar.
   Backup database menyertakan riwayat migrasi serta tabel master/resep HPP
   (termasuk baris bahan dan biaya). Simpan salinannya di tempat aman di luar
   webroot.
3. Jika konfigurasi produksi berada di `config.php` root, pastikan file itu
   tetap ada dan berisi kredensial database serta `SITE_URL` bernilai
   `https://alfikitchen.web.id`. File ini tidak dikirim oleh workflow.
   Jika konfigurasi memakai environment `.env`, pastikan nilainya tersedia
   secara aman pada runtime hosting. Jangan gunakan `.env.example` sebagai
   konfigurasi aktif.
4. Pastikan folder `uploads/` dan `uploads/cache/` beserta gambar produksi
   tersedia di webroot domain aktif. Jika domain baru menggunakan direktori
   berbeda, salin file gambar dari direktori lama dan pertahankan nama/path-nya.
   Upload baru disimpan di server, bukan Git.
5. Untuk perubahan skema, periksa migrasi yang akan dijalankan dan pastikan
   backup database tersedia. Jangan jalankan migrasi SQL secara manual jika
   migrasi yang sama sudah tercatat.

## Menyiapkan GitHub Actions

Workflow memerlukan GitHub Actions Secrets berikut:

| Secret | Nilai |
|---|---|
| `FTP_HOST` | Host FTP yang diberikan panel InfinityFree. |
| `FTP_USER` | Username FTP untuk akun yang dituju. |
| `FTP_PASS` | Password FTP akun tersebut. |

Tambahkan atau perbarui nilainya di **GitHub → repository → Settings → Secrets
and variables → Actions**. Jangan masukkan nilai rahasia ke file workflow.
Workflow saat ini menggunakan port 21, protokol FTP, dan server directory
`/htdocs/`; sesuaikan hanya setelah memastikan pengaturan akun hosting.

## Proses pembaruan

1. Kerjakan perubahan di branch kerja dan jalankan tes lokal:

   ```text
   C:\xampp\php\php.exe tests\run_tests.php
   ```

2. Tinjau perubahan dan pastikan tidak ada `.env`, `config.php`, kredensial,
   backup, atau gambar produksi yang masuk commit.
3. Gabungkan perubahan yang sudah ditinjau ke `main` dan push ke GitHub.
4. Buka tab **Actions** di GitHub. Pastikan job tes lulus dan langkah FTP
   selesai dengan sukses.
5. Jika perubahan mencakup migrasi database, masuk ke situs sebagai admin,
   buka **Admin → Pengaturan → Versi & Migrasi Database**, periksa daftar
   migrasi pending, lalu jalankan migrasi. Ambil backup sebelum langkah ini.
6. Lakukan pemeriksaan pascadeploy di browser:
   - `https://alfikitchen.web.id`
   - `https://alfikitchen.web.id/produk/SLUG-PRODUK`
   - halaman login admin dan panel pengaturan
   - gambar hero, gambar produk, tombol WhatsApp, dan sitemap
   - redirect domain alias/`www` dan HTTP menuju domain kanonis HTTPS
7. Pastikan log Actions dan respons situs tidak menunjukkan kegagalan. Hapus
   cache thumbnail melalui mekanisme terkelola hanya bila perlu; jangan hapus
   gambar sumber.

## Setup pertama kali atau pemulihan

Bagian ini hanya untuk instalasi baru atau pemulihan yang memang disengaja.
Jangan gunakan untuk pembaruan rutin situs produksi.

1. Buat database MySQL dari panel hosting dan catat nama database, host, serta
   username/password yang diberikan hosting.
2. Sediakan konfigurasi database di environment `.env` yang aman atau siapkan
   `config.php` root berdasarkan `config.example.php`. Bootstrap memilih
   konfigurasi root jika tersedia dan memakai `includes/config.php` jika tidak.
   Jangan commit atau mengirim kredensial lewat workflow.
3. Pastikan `SITE_URL` menggunakan
   `https://alfikitchen.web.id`, ekstensi PDO MySQL aktif, serta folder
   upload dapat ditulis oleh PHP.
4. Deploy berkas aplikasi. Jalankan migrasi dari CLI jika tersedia; jika tidak,
   gunakan bagian migrasi di panel admin setelah akun admin dapat masuk.
5. **Migrasi tidak membuat akun admin awal.** Pastikan akun berpassword hash
   telah disiapkan melalui prosedur aman sebelum mengandalkan halaman login.
   Jangan gunakan atau mendokumentasikan password default.
6. Verifikasi HTTPS, rewrite URL produk, upload/thumbnail, backup, dan halaman
   admin sebelum mengumumkan situs siap digunakan.

## Migrasi dan perubahan database

- File migrasi baru harus menggunakan pola `vNN_nama_perubahan.sql` di
  `database/migrations/`.
- Jalankan tes migrator lokal, backup database produksi, deploy file aplikasi,
  lalu terapkan migrasi pending satu kali melalui panel admin atau CLI.
- Migrasi pada MySQL dapat mengubah skema produksi dan tidak selalu dapat
  dibatalkan otomatis. Buat rencana pemulihan sebelum menjalankannya.
- Jangan menghapus tabel `schema_migrations` atau mengubah status migrasi
  produksi untuk mengatasi error tanpa memahami dampaknya.

## Pemeriksaan jika deployment gagal

- **Tes gagal:** baca output job, perbaiki atau batalkan perubahan, lalu
  jalankan ulang tes lokal. Workflow seharusnya berhenti sebelum upload.
- **Koneksi FTP gagal:** periksa status hosting, host/user/password/port,
  direktori tujuan, serta dukungan protokol FTP. Jangan menaruh password pada
  log atau commit.
- **HTTP 500 atau koneksi database gagal:** periksa environment database atau
  `config.php` root, kredensial, `SITE_URL`, dan log PHP di panel hosting.
  Jangan tampilkan kredensial atau stack trace kepada pengunjung.
- **Halaman produk atau rewrite gagal:** periksa dukungan Apache `mod_rewrite`
  dan apakah `.htaccess` di webroot aktif.
- **Gambar gagal tampil:** pastikan file sumber masih ada di `uploads/`,
  ekstensi GD tersedia jika konversi diharapkan, dan path gambar di database
  valid.
- **Migrasi gagal:** hentikan penerapan berikutnya, simpan pesan error,
  periksa status `schema_migrations`, lalu pulihkan dari backup atau siapkan
  perbaikan migrasi yang ditinjau.

## Checklist pascadeploy

- [ ] Tes CI lulus dan transfer FTP selesai.
- [ ] Konfigurasi database, database, dan gambar produksi tetap tersedia.
- [ ] Homepage, URL produk, HTTPS, dan tautan WhatsApp berfungsi.
- [ ] Tidak ada migrasi pending yang seharusnya diterapkan.
- [ ] Panel admin dapat diakses dan upload/backup berfungsi.
- [ ] Backup terbaru tersimpan di luar direktori publik.
