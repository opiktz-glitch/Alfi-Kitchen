# Roadmap Alfi Kitchen

Daftar ini disusun dari analisis kode dan hasil tes lokal pada 1 November 2026.
Prioritas menunjukkan urutan pengerjaan yang disarankan; temuan perlu diverifikasi
kembali pada konfigurasi hosting sebelum perubahan produksi.

## P1 — Keandalan setup dan keamanan deploy

- [x] **Sediakan bootstrap konfigurasi yang konsisten.** Entry point kini
  memakai `includes/bootstrap.php`, yang menggunakan konfigurasi root lama
  jika tersedia atau fallback ke `includes/config.php` dan `.env`. Verifikasi
  setup baru tanpa `config.php` root sebelum deploy.
- [ ] **Amankan koneksi deployment.** Workflow saat ini menggunakan FTP biasa
  pada port 21. Beralih ke FTPS/SFTP jika didukung hosting, lalu verifikasi
  deploy berhasil. Jika hosting tidak mendukung koneksi terenkripsi, dokumentasikan
  risiko dan rotasi/batasi kredensial FTP.

## P2 — Kompatibilitas dan ketahanan runtime

- [ ] **Tetapkan versi minimum PHP yang benar.** Kode memakai
  `str_starts_with()`, yang memerlukan PHP 8.0+, sedangkan panduan proyek
  menyebut PHP 7.4+. Putuskan versi minimum, sesuaikan kode atau dokumentasi,
  dan jalankan tes pada versi yang didukung.
- [ ] **Batasi biaya pemrosesan gambar.** Tinjau batas 40 juta piksel pada upload
  dan parameter lebar publik di `thumb.php`. Tetapkan batas dimensi keluaran dan
  jumlah piksel yang aman untuk memori hosting; uji resize, fallback tanpa GD,
  cache, serta gambar besar.

## P3 — Validasi dan konsistensi fitur

- [ ] **Validasi password di server.** Terapkan batas panjang password pada
  handler pengubahan password, bukan hanya atribut HTML. Uji input terlalu
  pendek, valid, dan tidak valid.
- [ ] **Samakan format logo yang diterima.** UI menerima beberapa format,
  sedangkan nama tujuan tetap `logo.png` dan helper mewajibkan ekstensi sesuai
  MIME. Jika logo memang harus PNG, terima dan validasi PNG saja serta
  tampilkan pesan yang sesuai; uji format yang diterima dan ditolak.

## P2 — SEO dan pengindeksan

- [ ] **Pastikan crawler dapat mengambil sitemap produksi.** Verifikasi
  `https://kitchen.pojokberkah.online/sitemap.xml` di Google Search Console.
  Jika hosting menyajikan challenge HTML atau gagal untuk crawler, minta
  dukungan hosting memeriksa akses crawler ke sitemap; jangan menganggap
  respons browser biasa membuktikan Googlebot dapat mengaksesnya.
- [ ] **Perbaiki akurasi `lastmod` sitemap.** Homepage saat ini menggunakan
  waktu pembuatan sitemap, sehingga berubah pada setiap request; produk
  menggunakan `created_at`, bukan waktu pembaruan konten. Gunakan timestamp
  perubahan yang benar (misalnya kolom `updated_at` setelah ditambahkan dan
  dipelihara), atau hilangkan `lastmod` jika tanggal perubahan tidak tersedia.
- [ ] **Pantau pengindeksan dengan Google Search Console.** Verifikasi domain,
  kirim sitemap, dan tinjau status indeks, canonical pilihan Google, serta
  kegagalan crawling secara berkala.

## P3 — Kualitas konten dan metadata SEO

- [ ] **Tingkatkan konten halaman kategori/produk.** Pastikan halaman kategori
  dan setiap varian memiliki nama serta deskripsi yang unik, akurat, dan
  bermanfaat bagi calon pembeli; hindari konten tipis yang hanya berisi nama
  dan harga.
- [ ] **Audit structured data produk.** Cocokkan nama, deskripsi, harga,
  ketersediaan, gambar, dan URL terstruktur dengan informasi yang benar-benar
  terlihat di halaman. Tambahkan alamat atau jam buka bisnis hanya jika
  informasinya benar dan memang ingin dipublikasikan.
- [ ] **Optimalkan gambar pada halaman produk.** Sajikan ukuran gambar sesuai
  kebutuhan tampilan, tetapkan dimensi untuk mencegah layout shift, gunakan
  lazy loading untuk gambar di luar viewport, dan tulis alt text yang
  menjelaskan gambar secara akurat.
- [ ] **Tinjau metadata dan tautan internal.** Pastikan setiap halaman publik
  mempunyai judul, deskripsi, canonical, dan Open Graph yang unik serta sesuai
  konten. Pastikan kategori produk mudah ditemukan dari homepage.

## Verifikasi dasar saat perubahan

- Jalankan `C:\xampp\php\php.exe tests\run_tests.php` dan pastikan semua tes
  lulus.
- Uji setup dari checkout bersih tanpa `config.php` lokal maupun kredensial
  dalam repository.
- Verifikasi workflow deploy dan konfigurasi runtime pada staging sebelum
  merilis perubahan ke hosting produksi.
