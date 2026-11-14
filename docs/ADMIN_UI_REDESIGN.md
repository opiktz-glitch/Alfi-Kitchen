# Rencana & Rekomendasi Redesain UI Admin Alfi Kitchen

Dokumen ini berisi catatan komprehensif rencana peremajaan antarmuka (UI/UX) panel admin Alfi Kitchen agar lebih **minimalis, ramah pengguna (user-friendly), hemat halaman (context-preserving), dan optimal di perangkat mobile**.

---

## 1. Latar Belakang & Tujuan
Panel admin saat ini memiliki beberapa kendala ergonomi:
- **Boros Halaman:** Mengelola produk dan varian ukuran/harga terpecah di 3–4 halaman berbeda (`products.php` ➔ `manage_items.php` ➔ `edit_item.php`).
- **Mobile Cluttered:** Bilah navigasi bawah (*bottom nav*) di layar HP memuat 7 tab sekaligus, membuat teks sangat kecil (10px) dan area sentuh berdesakan.
- **Formulir Ringan Terfragmentasi:** Aksi sederhana seperti menambah bahan baku atau mengubah harga membuka halaman penuh baru.

### Prinsip Desain:
1. **Minimalis & Bersih:** Ruang putih yang seimbang (*breathing space*), palet warna hangat Alfi Kitchen, tipografi terstruktur.
2. **Context-Preserving (Hemat Halaman):** Pengguna menyelesaikan tugas dalam satu konteks layar tanpa putus alur kerja.
3. **Mobile-First Ergonomics:** Target sentuh minimal 44×44px, navigasi jempol yang nyaman, maksimal 4 tab navigasi utama.
4. **Ramah Pengguna Awam:** Istilah lugas bahasa Indonesia, status visual yang jelas, dan umpan balik aksi seketika.

---

## 2. Rincian Rencana Kerja (Fase 1 – Fase 4)

### Fase 1: Navigasi Ramping & Mobile Ergonomics [SELESAI]
Fokus pada perbaikan struktur navigasi desktop dan mobile agar tidak sesak dan nyaman digunakan:
- **Restrukturisasi Bottom Nav Mobile:**
  - Pangkas dari 7 tab menjadi **4 menu utama**:
    1. `Beranda` (`dashboard.php`)
    2. `Produk` (`products.php` — mencakup kategori, varian, dan slider)
    3. `Dapur HPP` (`hpp_recipes.php` — mencakup kalkulasi resep dan bahan baku)
    4. `Setelan` (`settings.php` — mencakup info toko, WA, log, dan database)
  - Ukuran ikon & font meningkat proporsional, nyaman untuk navigasi jempol (*one-thumb reach*).
- **Pengelompokan Sidebar Desktop:**
  - Menata 7 menu ke dalam kategori yang rapi:
    - *Menu Utama:* Dashboard
    - *Katalog & Konten:* Produk & Varian, Kelola Slider
    - *Dapur & Kalkulasi:* Kalkulator HPP, Master Bahan
    - *Sistem:* Pengaturan, Log Aktivitas
  - Menambahkan profil ringkas & tombol cepat (*Lihat Website*, *Logout*) di bagian bawah sidebar.
- **Pembersihan Header:**
  - Mengurangi elemen redundan di header setiap halaman agar ruang vertikal konten lebih lega.

---

### Fase 2: Katalog Produk Terpadu (Master-Detail View) [SELESAI]
Fokus pada eliminasi perpindahan halaman antara Kategori dan Varian Produk:
- **Tampilan Bersarang (Expandable / Accordion Table):**
  - Halaman `products.php` menampilkan daftar kategori.
  - Setiap kategori dapat diklik (*expand*) untuk langsung menampilkan daftar varian (ukuran, harga, status ketersediaan) di baris yang sama.
- **Aksi Cepat Inline:**
  - Toggle status ketersediaan (Aktif / Habis) langsung dari daftar tanpa pindah halaman.
  - Edit harga varian cepat (*inline edit* atau modal mini).
- **Hasil Pengguna:** Pengguna dapat meninjau seluruh harga menu Alfi Kitchen dalam 1 layar tanpa klik bolak-balik.

---

### Fase 3: Input Cepat Berbasis Modal / Drawer (No Page Reload) [SELESAI]
Fokus pada penanganan form sederhana tanpa memutus alur kerja:
- **Slide-over Drawer / Modal Dialog:**
  - Form tambah/edit varian produk dibuka dalam popup modal atau drawer samping yang bersih.
  - Form tambah master bahan baku HPP dibuka langsung dari halaman daftar bahan.
  - Upload gambar hero slider baru menggunakan modal ringkas.
- **Keamanan & Konsistensi:**
  - Tetap menyertakan token CSRF dan validasi di backend.
  - State halaman utama tetap terjaga (filter/pencarian tidak tereset).

---

### Fase 4: Konsolidasi Pengaturan & Sistem (Tabbed Single-Page) [SELESAI]
Fokus pada penyatuan utilitas sistem yang terpisah-pisah:
- **Tabbed Interface di `settings.php`:**
  - Mengelompokkan pengaturan ke dalam 4 tab navigasi horizontal:
    1. *Info Toko & WhatsApp:* Nomor WA, jam operasional, teks sapaan.
    2. *Tampilan & Logo:* Upload logo, favicon, warna tema.
    3. *Cadangan & Database:* Ekspor database `.sql`, backup gambar, status migrasi.
    4. *Riwayat Aktivitas:* Log aktivitas admin (mengintegrasikan isi `activity_log.php`).
- **Efisiensi:** Mengurangi kompleksitas struktur admin dan mempermudah pemeliharaan jangka panjang.

---

## 3. Standar Teknis & Batasan Keamanan
Seluruh implementasi fase 1 – 4 wajib mematuhi panduan [AGENTS.md](file:///c:/xampp/htdocs/Alfi_Kitchen/AGENTS.md):
- **Keamanan:** Autentikasi (`auth_check.php`), validasi token POST (`csrf_check.php`), dan audit log (`log_admin_action()`).
- **CSS:** Menggunakan vanilla CSS di `admin/assets/css/admin.css` (tanpa style inline).
- **Pengujian:** Selalu verifikasi perubahan dengan suite tes `C:\xampp\php\php.exe tests\run_tests.php`.

