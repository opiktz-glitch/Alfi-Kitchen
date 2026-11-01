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

## P2 — Pengerasan keamanan (tambahan)

- [ ] **Tambahkan security headers lanjutan.** Terapkan CSP, HSTS, dan
  Permissions-Policy di `.htaccess` (saat ini baru X-Frame-Options, nosniff,
  dan Referrer-Policy) untuk mencegah XSS, memaksa HTTPS, dan mematikan
  fitur browser yang tidak perlu.
- [ ] **Cegah session fixation.** Panggil `session_regenerate_id(true)` saat
  login sukses agar session ID lama tidak dapat disalahgunakan.
- [ ] **Terapkan rate-limit login.** Tabel `login_attempts` sudah ada; pastikan
  `login.php` memblokir sementara setelah N percobaan gagal per IP/username.
- [ ] **Nonaktifkan display_errors di produksi.** Pastikan error tidak
  ditampilkan ke browser (bocor path/kredensial); simpan ke log server.
- [ ] **Validasi ekstensi file upload.** Selain cek MIME, verifikasi ekstensi
  sesuai dan rename file ke nama acak untuk mencegah eksekusi skrip terselubung.
- [ ] **Paksa redirect HTTP ke HTTPS.** Tambahkan redirect di `.htaccess` agar
  cookie session tidak dikirim plaintext.
- [ ] **Verifikasi integritas backup.** Cek checksum (SHA-256) file backup
  setelah dibuat dan saat restore untuk deteksi korupsi/tampering.

## P2 — Kompatibilitas dan ketahanan runtime

- [ ] **Tetapkan versi minimum PHP yang benar.** Kode memakai
  `str_starts_with()`, yang memerlukan PHP 8.0+, sementara CI menggunakan PHP
  8.2. Konfirmasikan versi PHP di hosting, tetapkan versi minimum yang
  didukung, dan jalankan tes pada versi tersebut.
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

## P3 — SEO tambahan (usulan baru)

- [ ] **Meta dan Open Graph dinamis per produk.** Sediakan title, description, dan
  og:image unik per varian di `product.php`.
- [ ] **BreadcrumbList structured data.** Tambahkan markup breadcrumb
  kategori → produk.
- [ ] **Product schema (Offer).** Sertakan price, availability, image, dan URL
  terstruktur.
- [ ] **Alt text konsisten.** Gunakan deskripsi akurat per gambar produk, bukan
  nama file.
- [ ] **Internal linking.** Tampilkan produk terkait / produk lain dalam kategori.
- [ ] **Image sitemap.** Sertakan elemen `<image:image>` di sitemap untuk
  indeks gambar.
- [ ] **Preconnect/dns-prefetch.** Tambahkan untuk font, WhatsApp, dan CDN jika
  digunakan.
- [ ] **Optimasi font.** Gunakan `font-display: swap` dan subset font.
- [ ] **Minify CSS/JS.** Kurangi payload transfer.
- [ ] **Lazy loading.** Terapkan `loading="lazy"` pada gambar non-LCP.
- [ ] **Pantau Core Web Vitals.** Monitor LCP, CLS, dan INP.
- [ ] **Feed RSS/Atom.** Sediakan feed untuk crawler dan pembaca feed.

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

## P3 — UI dan pengalaman pengguna

- [ ] **Perjelas penyajian pilihan ukuran dan harga.** Susun setiap ukuran
  beserta harganya agar mudah dipindai, dan pastikan harga utama pada kartu
  tidak bertentangan dengan rincian di deskripsi. Tetapkan sumber harga yang
  konsisten sebelum mengubah struktur data produk.
- [ ] **Hubungkan pesanan WhatsApp dengan pilihan yang tepat.** Jika satu produk
  memiliki beberapa ukuran atau pilihan, pastikan pelanggan dapat memilihnya
  dengan jelas dan pesan WhatsApp menyebut pilihan serta harga yang dimaksud.
- [ ] **Tambahkan pencarian dan filter katalog.** Sediakan pencarian cepat dan,
  bila jumlah produk sudah memerlukannya, filter kategori atau pengurutan harga
  agar produk mudah ditemukan di ponsel maupun desktop.
- [ ] **Pastikan tombol WhatsApp mengambang tidak menghalangi konten.**
  Pertahankan label aksesibel untuk pembaca layar, target sentuh yang memadai,
  dan jarak aman dari tombol maupun informasi di bagian bawah layar.
- [ ] **Tampilkan informasi pemesanan yang terverifikasi.** Jelaskan cara
  bertanya tentang ketersediaan serta informasi pengiriman atau pengambilan
  hanya jika ketentuannya sudah dipastikan dan berlaku saat ini.
- [ ] **Uji pengalaman pada perangkat nyata.** Periksa keterbacaan, target
  sentuh, posisi tombol saat keyboard terbuka, dan penggunaan pada koneksi
  lambat di beberapa ukuran ponsel.

## P3 — UI panel admin

- [x] **Ringkas navigasi dashboard.** Pisahkan tautan sekunder seperti slider,
  pengaturan, dan log aktivitas dari aksi utama tambah kategori agar alur
  mengelola katalog lebih mudah dipahami.
- [x] **Perjelas alur pengelolaan kategori dan varian.** Utamakan aksi untuk
  mengelola produk/varian, jadikan edit kategori sebagai aksi sekunder, dan
  pisahkan tombol hapus untuk mengurangi salah tekan.
- [x] **Tampilkan jumlah varian dan thumbnail yang konsisten** agar daftar
  kategori lebih mudah dipindai.
- [x] **Optimalkan daftar kategori admin untuk ponsel.** Pada layar sempit,
  daftar ditampilkan sebagai kartu bertumpuk dengan tombol yang mudah disentuh.
- [x] **Tambahkan pencarian kategori** dan pertahankan kata kunci saat
  menghapus kategori dari hasil pencarian.
- [x] **Sederhanakan hierarki visual.** Samakan ukuran, target sentuh, fokus,
  dan gaya tombol berdasarkan prioritas aksi.
- [x] **Berikan umpan balik tindakan yang jelas.** Tampilkan pesan setelah
  perubahan kategori/varian dan sertakan nama entitas pada konfirmasi hapus.
- [x] **Buat empty state yang membantu** untuk katalog kategori dan daftar
  varian, dengan tautan langsung ke aksi berikutnya.

## P3 — Konsistensi dan pemeliharaan kode

- [ ] **Hindari transisi CSS global.** Aturan `transition: all` pada selector
  global di `header.php` memengaruhi hampir semua elemen. Ganti secara bertahap
  dengan transisi properti spesifik hanya pada komponen yang memiliki efek
  interaksi, lalu verifikasi tampilan dan performa halaman.
- [ ] **Kurangi CSS inline di halaman publik secara bertahap.** Pindahkan style
  presentasi seperti kartu produk di `product.php` ke stylesheet yang sesuai
  ketika halaman tersebut dikerjakan, agar aturan responsif dan tampilan lebih
  mudah dipelihara. Pertahankan larangan `<style>` inline pada halaman admin.
- [ ] **Terapkan konvensi format secara konsisten pada perubahan baru.**
  Samakan indentasi, spasi, dan penamaan variabel mengikuti pola di file yang
  sedang disentuh. Hindari formatting massal yang tidak terkait; rapikan kode
  lama secara oportunistis saat mengubah bagian tersebut.

## Verifikasi dasar saat perubahan

- Jalankan `C:\xampp\php\php.exe tests\run_tests.php` dan pastikan semua tes
  lulus.
- Uji setup dari checkout bersih tanpa `config.php` lokal maupun kredensial
  dalam repository.
- Verifikasi workflow deploy dan konfigurasi runtime pada staging sebelum
  merilis perubahan ke hosting produksi.
