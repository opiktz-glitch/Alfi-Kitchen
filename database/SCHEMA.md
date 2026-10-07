# 🗄️ Dokumentasi Database Alfi Kitchen

Dokumen ini menjelaskan struktur tabel dan skema database MySQL yang digunakan dalam aplikasi **Alfi Kitchen**.

## 1. Tabel `settings`
Tabel ini berfungsi sebagai penyimpanan konfigurasi dinamis bergaya *key-value* untuk mengatur konten aplikasi tanpa perlu memodifikasi kode.
- **`id`** (INT, PK, Auto Increment)
- **`setting_key`** (VARCHAR, Unique): Kunci identifikasi unik (contoh: `home_title`, `home_description`, `whatsapp_number`).
- **`setting_value`** (MEDIUMTEXT): Nilai konfigurasi, mendukung teks panjang, emoji, dan karakter multibahasa (utf8mb4).

## 2. Tabel `users`
Tabel untuk menyimpan kredensial akses ke Panel Admin.
- **`id`** (INT, PK, Auto Increment)
- **`username`** (VARCHAR, Unique): Nama pengguna admin.
- **`password`** (VARCHAR): Kata sandi admin yang telah di-hash dengan aman menggunakan `password_hash()` standar bawaan PHP.

## 3. Tabel `products`
Menyimpan daftar produk atau kategori produk utama yang akan ditampilkan di halaman depan (*Homepage*).
- **`id`** (INT, PK, Auto Increment)
- **`name`** (VARCHAR): Nama produk (contoh: "Puding Buah").
- **`slug`** (VARCHAR, Unique): URL SEO-friendly untuk produk (contoh: `puding-buah`). Digunakan untuk fitur *friendly URL*.
- **`price`** (INT): Harga dasar produk.
- **`image`** (VARCHAR): Path/nama file gambar utama produk.
- **`created_at`** (TIMESTAMP): Waktu penambahan produk ke sistem.

## 4. Tabel `product_items`
Menyimpan varian, rincian, atau sub-item yang berada di bawah suatu produk utama.
- **`id`** (INT, PK, Auto Increment)
- **`product_id`** (INT, FK): Merujuk pada `id` di tabel `products` (koneksi parent-child dengan `ON DELETE CASCADE`).
- **`name`** (VARCHAR): Nama varian/item (contoh: "Ukuran Medium").
- **`description`** (TEXT): Deskripsi detail dari item tersebut.
- **`price`** (DECIMAL 10,2): Harga spesifik untuk varian tersebut.
- **`image`** (VARCHAR): Path/nama file gambar varian.
- **`created_at`** (TIMESTAMP): Waktu penambahan varian.

## 5. Tabel `hero_images`
Menyimpan daftar gambar *slider/carousel* yang bergulir di bagian paling atas halaman utama (Hero Section).
- **`id`** (INT, PK, Auto Increment)
- **`image`** (VARCHAR): Path/nama file gambar hero (biasanya JPEG/WebP berukuran besar).
- **`created_at`** (TIMESTAMP): Waktu unggah gambar hero. Urutan penampilan ditentukan dari ID terbaru (DESC).

## 6. Tabel `login_attempts`
Tabel keamanan log untuk melindungi halaman admin dari serangan *Brute Force*.
- **`attempt_key`** (CHAR 64, PK): Gabungan unik dari Hash IP Address pengguna.
- **`attempt_count`** (SMALLINT): Jumlah kegagalan login berturut-turut.
- **`window_started_at`** (INT unsigned): Unix timestamp ketika jendela percobaan pertama kali dimulai.
- **`locked_until`** (INT unsigned): Unix timestamp yang menunjukkan sampai kapan pengguna dari IP ini diblokir.
- **`updated_at`** (INT unsigned): Timestamp pembaruan record terakhir.

---

### Catatan Tambahan:
* Seluruh tabel diatur dengan *character set* `utf8mb4` dan *collation* `utf8mb4_general_ci` / `utf8mb4_unicode_ci` agar sepenuhnya mendung emoji 🎂 dan berbagai simbol Unicode tanpa risiko teks terpotong.
* Gambar aktual **tidak disimpan di database**. Database hanya menyimpan path atau nama file gambar (kolom `image`). File fisik secara utuh disimpan di direktori `/uploads/`.
