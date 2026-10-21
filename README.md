# 🍮 Alfi Kitchen

Website toko online **Alfi Kitchen** — puding, dessert, dan salad buah homemade berkualitas.

## 🛠️ Teknologi
- PHP (Native)
- MySQL / PDO
- Swiper.js (Slider)
- CSS Vanilla

## 🚀 Deploy Otomatis
Project ini menggunakan **GitHub Actions** untuk auto-deploy ke InfinityFree setiap kali ada push ke branch `main`.

### Setup Secrets di GitHub
Sebelum deploy, tambahkan 3 secrets di **Settings → Secrets → Actions**:

| Secret | Keterangan |
|---|---|
| `FTP_HOST` | FTP Host dari cPanel InfinityFree |
| `FTP_USER` | FTP Username dari cPanel |
| `FTP_PASS` | FTP Password dari cPanel |

## ⚙️ Konfigurasi Lingkungan (.env)
1. Salin file template `.env.example` menjadi `.env`:
   ```bash
   cp .env.example .env
   ```
2. Sesuaikan kredensial database & URL situs di file `.env` (host, dbname, user, password, SITE_URL).
3. Atau gunakan `config.example.php` disalin ke `config.php` jika server tidak memakai format `.env`.
4. Jalankan migrasi database otomatis:
   ```bash
   php database/migrate.php
   ```
5. Akses via browser → `http://localhost/Alfi_Kitchen`

## 🔐 Keamanan
- File `.env` dan `config.php` **tidak** di-commit ke Git (sudah masuk `.gitignore`) dan tidak ikut ditimpa oleh deploy FTP.
- Akses HTTP langsung ke file `.env`, `.sql`, `.log`, `.git*` diblokir oleh aturan ketat di root `.htaccess`.
- Folder `uploads/` tidak meng-commit gambar; `.gitkeep` dan `.htaccess` ikut deploy untuk mempertahankan folder dan memblokir eksekusi skrip
- Upload gambar dibatasi maksimal 5 MB dan divalidasi berdasarkan MIME serta struktur gambar di server; logo hanya menerima PNG
- Password admin dapat diubah melalui panel **Admin → Pengaturan**
- Terapkan [database/login_attempts.sql](database/login_attempts.sql) pada database lokal dan hosting sebelum memakai pembatasan percobaan login baru
- Terapkan [database/settings_utf8mb4.sql](database/settings_utf8mb4.sql) sekali pada database lokal dan hosting sebelum menyimpan teks homepage dari CMS
- Terapkan [database/product_slug_migration.sql](database/product_slug_migration.sql) sekali pada database lokal dan hosting untuk mengaktifkan URL produk ramah-SEO (`/produk/{slug}`) — aman di InfinityFree; bagian trigger tidak diperlukan karena regenerasi slug ditangani di PHP (`admin/edit_product.php`)

## 💾 Backup
- **Database** dapat diekspor via tombol **📥 Backup Database** di **Admin → Pengaturan**
- **Gambar** dapat diekspor via tombol **🖼️ Backup Semua Gambar** di **Admin → Pengaturan**

## ⚡ Optimasi Performa & SEO
- **WebP On-the-Fly**: Semua gambar produk, logo, dan slider dikompresi serta dikonversi ke format WebP secara otomatis oleh `thumb.php` berdasarkan resolusi layar.
- **Auto-Compression Upload**: Pengunggahan gambar di panel admin otomatis dipangkas dimensinya (maks. 1600px untuk hero/produk) tanpa kehilangan kualitas visual, menghemat >70% penyimpanan.
- **Preload LCP (Largest Contentful Paint)**: Gambar Hero utama di-preload secara dinamis untuk menjamin skor PageSpeed sempurna, bebas *render-blocking*.
- **Aksesibilitas (A11Y)**: Kode HTML telah dioptimalkan (kontras warna rasio 4.5:1 standar WCAG AA, penambahan atribut `aria-label`, serta resolusi `width`/`height` eksplisit pada gambar) demi kemudahan Screen Reader.

## 📝 Catatan Pembaruan & Rekomendasi
Dokumentasi lengkap mengenai evaluasi refaktor kode, perbaikan keamanan (anti-LFI & CSRF), audit tampilan admin, serta daftar rekomendasi teknis dapat dilihat pada [CATATAN.md](CATATAN.md).

