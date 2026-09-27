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
2. Salin `.env.example` menjadi `.env` dan isi dengan data database lokal
3. Import tabel database via phpMyAdmin (lihat bagian SQL di bawah)
4. Jalankan via XAMPP → `localhost/Alfi_Kitchen`

## 🔐 Keamanan
- File `.env` **tidak** di-commit ke GitHub (sudah ada di `.gitignore`)
- Folder `uploads/` **tidak** di-commit — gambar dikelola langsung di server
- Password admin dapat diubah melalui panel **Admin → Pengaturan**
