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

## ⚙️ Konfigurasi Lokal (XAMPP)
1. Clone repository ini
2. Sesuaikan kredensial database di `config.php` (host, dbname, user, password)
3. Import tabel database via phpMyAdmin
4. Jalankan via XAMPP → `localhost/Alfi_Kitchen`

## 🔐 Keamanan
- File `config.php` **tidak** di-deploy ulang oleh GitHub Actions — wajib dikonfigurasi manual di server hosting
- Folder `uploads/` tidak meng-commit gambar; `.gitkeep` dan `.htaccess` ikut deploy untuk mempertahankan folder dan memblokir eksekusi skrip
- Upload gambar dibatasi maksimal 5 MB dan divalidasi berdasarkan MIME serta struktur gambar di server; logo hanya menerima PNG
- Password admin dapat diubah melalui panel **Admin → Pengaturan**
- Terapkan [database/login_attempts.sql](database/login_attempts.sql) pada database lokal dan hosting sebelum memakai pembatasan percobaan login baru

## 💾 Backup
- **Database** dapat diekspor via tombol **📥 Backup Database** di **Admin → Pengaturan**
- **Gambar** dapat diekspor via tombol **🖼️ Backup Semua Gambar** di **Admin → Pengaturan**
