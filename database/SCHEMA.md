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

## 7. Tabel `schema_migrations`
Tabel pelacak versi database otomatis untuk mencatat file migrasi yang telah dieksekusi.
- **`version`** (VARCHAR 190, PK): Nama file migrasi (contoh: `v01_initial_schema.sql`, `v04_add_product_slug.sql`).
- **`applied_at`** (TIMESTAMP): Waktu eksekusi migrasi diterapkan.

## 8. Tabel kalkulator HPP

Migrasi `v05_hpp.sql` menambahkan tabel internal admin berikut. Data HPP dan resep tidak ditampilkan kepada pengunjung; hanya harga varian yang diterapkan admin melalui aksi khusus yang tersimpan di `product_items.price`.

### `hpp_ingredients`

Master bahan dan harga kemasan terbaru.
- `id` (INT, PK)
- `name` (VARCHAR 120, unik)
- `base_unit` (ENUM: `g`, `ml`, `pcs`)
- `pack_size` (DECIMAL 12,3): isi kemasan dalam satuan dasar
- `pack_price` (INT): harga kemasan dalam rupiah
- `note` (VARCHAR 255, nullable)
- `updated_at` (TIMESTAMP)

### `hpp_recipes`

Informasi resep, jumlah hasil, parameter pembulatan, dan ringkasan hasil hitung terakhir.
- `id` (INT, PK), `name` (VARCHAR 120), `variant` (VARCHAR 40)
- `yield_jars` (SMALLINT), `jar_ml` (SMALLINT, nullable), `round_step` (INT)
- `note` (VARCHAR 255, nullable)
- `product_item_id` (INT, nullable, unik): varian produk yang dapat menerima harga melalui aksi admin eksplisit; jika varian dihapus nilainya menjadi `NULL`
- `total_cost`, `hpp_recipe`, `hpp_per_jar` (INT): nilai rupiah cache untuk daftar admin
- `created_at`, `updated_at` (TIMESTAMP)

### `hpp_recipe_lines`

Baris bahan resep dengan harga satuan snapshot agar perubahan master tidak mengubah resep tanpa tindakan admin.
- `id` (INT, PK), `recipe_id` (INT, FK ke resep, `ON DELETE CASCADE`)
- `ingredient_id` (INT, nullable, FK ke master bahan, `ON DELETE RESTRICT`)
- `custom_name` (VARCHAR 120, nullable): nama item bebas saat `ingredient_id` kosong
- `qty` (DECIMAL 12,3), `unit_cost` (DECIMAL 14,6), `sort_order` (SMALLINT)

### `hpp_recipe_costs`

Baris biaya operasional resep.
- `id` (INT, PK), `recipe_id` (INT, FK ke resep, `ON DELETE CASCADE`)
- `label` (VARCHAR 80)
- `calc_type` (ENUM: `pct_bahan`, `per_jar`, `flat`)
- `amount` (DECIMAL 12,4), `sort_order` (SMALLINT)

Migrasi juga mengisi default `hpp_default_costs`, `hpp_margins`, dan `hpp_round_step` di `settings` menggunakan `INSERT IGNORE`, sehingga nilai yang sudah dikonfigurasi tidak tertimpa saat migrasi diulang.

Penyimpanan resep dilakukan melalui helper server-side: harga bahan master dihitung ulang di PHP untuk baris baru, sedangkan baris resep yang dipertahankan tetap menggunakan snapshot sampai admin meminta pembaruan dari master. Header resep, ringkasan hasil kalkulasi, baris bahan, dan biaya operasional disimpan dalam satu transaksi. Proses simpan resep tidak mengubah `product_items.price`; perubahan harga publik adalah aksi terpisah yang memerlukan persetujuan admin.

Editor admin `admin/hpp_edit.php` menyediakan kalkulasi langsung untuk bahan, biaya operasional, HPP per resep/per jar, serta skenario markup. JavaScript hanya memberi pratinjau; server menghitung ulang saat menyimpan. Pembaruan snapshot bahan memerlukan pilihan eksplisit, dan editor tidak menerapkan atau mengubah harga publik.

Daftar resep admin `admin/hpp_recipes.php` menampilkan HPP per jar, harga varian publik yang terhubung, dan status perbedaan harga master terhadap snapshot. Duplikasi menyalin resep, baris bahan, snapshot, dan biaya ke resep baru tanpa menyalin relasi varian. Penghapusan resep menghapus data anak melalui foreign key; harga publik produk yang pernah terhubung tetap tidak berubah.

Editor menyediakan aksi terpisah untuk menerapkan harga ke varian produk. Admin dapat mengisi harga final, memakai rekomendasi markup, atau membulatkan ke atas ke Rp500/Rp1.000. Harga lama dan baru ditampilkan pada langkah konfirmasi; harga di bawah HPP diberi peringatan tetapi tetap memerlukan konfirmasi. Setelah konfirmasi, relasi resep-varian dan `product_items.price` diperbarui dalam satu transaksi. Harga yang berubah sejak pratinjau dan varian yang sudah terkait dengan resep lain ditolak.

---

## 🚀 Alur Migrasi Database Berversi (Database Versioning)

Aplikasi Alfi Kitchen menggunakan sistem migrasi terstandarisasi di folder `database/migrations/`:
1. `v01_initial_schema.sql`: Struktur awal tabel `users`, `settings`, `products`, `product_items`, `hero_images`.
2. `v02_create_login_attempts.sql`: Pembuatan tabel rate limiting login admin.
3. `v03_settings_utf8mb4.sql`: Konversi kolom setting ke charset `utf8mb4` untuk mendukung emoji.
4. `v04_add_product_slug.sql`: Penambahan kolom `slug` dan unique index untuk URL ramah SEO.
5. `v05_hpp.sql`: Tabel master bahan, resep, baris bahan, biaya operasional, serta pengaturan default kalkulator HPP.

### Cara Menambah Migrasi Baru:
Cukup buat file SQL baru di folder `database/migrations/` dengan format:
`vXX_deskripsi_perubahan.sql` (contoh: `v05_add_product_discount.sql`).

### Cara Menjalankan Migrasi:
- **Di Laptop / XAMPP (CLI):**
  ```bash
  # Cek status migrasi
  php database/migrate.php status

  # Terapkan migrasi
  php database/migrate.php
  ```
- **Di Hosting InfinityFree (Web):**
  Masuk ke panel **Admin → Pengaturan**, periksa bagian **Versi & Migrasi Database**, lalu klik tombol **🚀 Jalankan Migrasi Database**.

---

### Catatan Tambahan:
* Seluruh tabel diatur dengan *character set* `utf8mb4` dan *collation* `utf8mb4_general_ci` / `utf8mb4_unicode_ci` agar sepenuhnya mendukung emoji 🍮 dan berbagai simbol Unicode tanpa risiko teks terpotong.
* Gambar aktual **tidak disimpan di database**. Database hanya menyimpan path atau nama file gambar (kolom `image`). File fisik secara utuh disimpan di direktori `/uploads/`.
