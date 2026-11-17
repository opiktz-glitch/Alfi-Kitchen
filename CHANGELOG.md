# Changelog

Perubahan penting pada Alfi Kitchen dicatat di sini, berdasarkan riwayat commit
repository. Tanggal menunjukkan tanggal perubahan tercatat di Git; entri ini
bukan penanda rilis atau versi aplikasi.

## 2026-11-17

### Added
- **Optimasi UI Tab Slider Beranda Versi Laptop (Opsi A) ([admin/settings.php](file:///c:/xampp/htdocs/Alfi_Kitchen/admin/settings.php#tab-slider))**:
  - Restrukturisasi tata letak layar laptop/desktop menjadi layout asimetris 360px : 1fr (`.settings-grid-slider`), memberikan ruang pandang yang jauh lebih luas dan proporsional untuk banner promosi.
  - Mengganti format tabel teks standar menjadi **Galeri Kartu Banner Visual (16:9)** (`.hero-cards-grid`) lengkap dengan badge urutan slide (`★ Slide #1 (Utama)`, `Slide #2`, dst.), nama berkas, dan tombol hapus terintegrasi.
  - Menambahkan **Modern Drag & Drop Zone** (`.hero-dropzone`) dengan efek hover dinamis dan dukungan seret file langsung dari desktop.
  - Menambahkan **Instant Pre-Upload Live Preview** dengan pembacaan gambar seketika via `FileReader` sebelum tombol unggah ditekan, lengkap dengan informasi ukuran file dan tombol ganti/batal.
  - Menambahkan kartu panduan spesifikasi banner ideal (Rasio 16:9, resolusi 1280×720 / 1600×900 px, dan area teks aman).
  - Styling modular tersimpan rapi di [admin/assets/css/modules/_navigation.css](file:///c:/xampp/htdocs/Alfi_Kitchen/admin/assets/css/modules/_navigation.css) tanpa inline style dan tetap adaptif 1 kolom di layar mobile.

## 2026-11-15

### Added
- **Fitur Lembar Resep HPP Lengkap & Pratinjau Modal (Sesuai Sampel PDF)**:
  - Membuat halaman rincian cetak `admin/hpp_view.php` bergaya lembar resep fisik profesional sesuai contoh PDF referensi ("HPP Creamy Ice Jelly"), mencakup 4 seksi utama:
    1. **Bahan Baku**: Keterangan nama bahan, takaran jumlah, satuan (`gram`, `ml`, `pcs`), harga beli kemasan acuan (Rp), dan total biaya bahan per takaran resep (Rp).
    2. **Operasional**: Keterangan biaya (persentase dari bahan atau flat/per-jar) beserta subtotal biaya operasional (Rp).
    3. **Ringkasan HPP**: Total bahan & operasional, pembulatan harga per 1 resep (batch), dan HPP pokok per jar.
    4. **Rencana Margin & Rekomendasi Harga Jual**: Simulasi margin terstruktur (50%, 75%, 100%, 200%, serta persentase target toko) yang menghitung laba, harga jual 1 resep, dan harga jual rekomendasi per jar.
  - Menambahkan tombol aksi **`Lihat`** berikon mata (`category-action-view`) di setiap baris daftar resep pada `admin/hpp_recipes.php`, lengkap dengan dukungan responsif desktop dan mobile (Smart Compact 2-baris).
  - Menambahkan modal dialog interaktif `#hpp-view-modal` dengan `<iframe>` di `admin/hpp_recipes.php` untuk pratinjau lembar resep instan secara *pop-up* tanpa reload halaman, dengan kontrol tutup via tombol close, klik latar belakang modal, atau tombol Escape keyboard.
  - Menambahkan tombol **`Lihat Lembar Resep`** di header editor resep `admin/hpp_edit.php` untuk resep yang sudah tersimpan.
  - Menambahkan tombol aksi cetak (`window.print()`) dan dukungan gaya cetak `@media print` pada berkas CSS modular `_hpp_calculator.css` sehingga hasil cetak atau simpan ke file PDF menjadi bersih, rapi, dan menyembunyikan tombol antarmuka/navigasi.
  - Memperkaya helper `admin_icon()` di `admin/auth.php` dengan ikon SVG `view` (mata) dan `print` (printer).
  - Memperbarui query `hpp_get_recipe()` di `admin/helpers/hpp_recipe_repository.php` agar menyertakan data spesifikasi `pack_size` dan `pack_price` master bahan.
  - Menambahkan pengujian otomatis di `tests/Unit/HppRecipeListPageTest.php` dan `tests/Unit/AdminRefactorTest.php` (total 219 tes lulus 100%).
  - **Optimasi Tampilan Khusus Mobile / HP**:
    - Deteksi ukuran layar sentuh pada tombol `[Lihat]` di `admin/hpp_recipes.php`: otomatis membuka halaman penuh (`hpp_view.php?id=...`) di HP (≤ 768px) untuk menghindari scroll ganda dan keterbatasan iframe modal, sementara tampilan desktop (> 768px) tetap menggunakan modal popup.
    - Struktur tabel adaptif (Opsi A) di HP: kolom `Jumlah` dan `Satuan` digabung menjadi kolom `Takaran` (e.g. `200 gram`), merampingkan tabel dari 5 menjadi 4 kolom sehingga muat pas di layar 360px tanpa geser horizontal.
    - Menambahkan bilah aksi melayang bawah (*Sticky Bottom Floating Bar*) pada `hpp_view.php` dengan tombol lebar `Cetak / Simpan PDF` yang siap dijangkau ibu jari, tombol `Edit`, tombol `Daftar`, serta tips panduan ekspor PDF di HP.
    - Pada saat dicetak (`@media print`), format tabel otomatis kembali ke tata letak standar 5 kolom dokumen fisik.
- **Penyeragaman dan Pengerasan Format Upload Logo**:
  - Menyelaraskan form input di `admin/settings.php` (`accept="image/png"`) dan handler backend `store_uploaded_image()` agar secara konsisten hanya menerima file gambar format PNG murni (`image/png`, `image/x-png`).
  - Menghindari konflik penamaan file tujuan tetap `logo.png` dengan format non-PNG yang sebelumnya diterima di form.
  - Memberikan pesan kesalahan yang jelas dan spesifik (`Format file tidak didukung. File harus berupa gambar PNG yang valid.`) jika format file yang diunggah tidak sesuai.
  - Menambahkan pencatatan aktivitas admin `UPDATE_LOGO` pada file log aktivitas.
  - Memperbarui panduan agen `AGENTS.md` dan menandai checklist selesai di `docs/ROADMAP.md` untuk item session fixation, rate limit login, dan konsistensi logo.
  - Menambahkan unit test regresi di `tests/Unit/UploadHelperRefactorTest.php` untuk memastikan upload logo menolak file non-PNG.
- **Refactoring Arsitektur CSS Modular Panel Admin**:
  - Memecah berkas tunggal monolitik `admin/assets/css/admin.css` (4.045 baris) menjadi 8 modul terstruktur dan terfokus di direktori `admin/assets/css/modules/` (`_variables.css`, `_components.css`, `_modals.css`, `_navigation.css`, `_hpp_editor.css`, `_hpp_calculator.css`, `_responsive_admin.css`, `_responsive_hpp.css`).
  - Memastikan seluruh berkas kode di seluruh proyek berada di bawah 1.000 baris (maksimal ~750 baris).
  - Menyederhanakan `admin/assets/css/admin.css` menjadi entry-point ringkas berisi deklarasi `@import`.
  - Menambahkan modul `tests/helpers.php` dengan fungsi `resolve_css_imports()` untuk harmonisasi otomatis unit test terhadap berkas CSS modular.
  - Memperbarui panduan agen `AGENTS.md` untuk mengizinkan penambahan styling modular di `admin/assets/css/modules/`.
- **Optimasi Antarmuka Mobile (Smart Compact UI di Layar HP)**:
  - Eliminasi *nested scrollbar* pada Tab 1 (Resep & Bahan) dan Tab 2 (Biaya Operasional) di editor HPP sehingga seluruh tab bergulir seragam menggunakan satu *page scroll*.
  - Tata letak **Smart Compact 2-Baris** pada kartu Bahan Baku di editor HPP: dropdown bahan & icon tombol hapus di Baris 1, input qty & subtotal di Baris 2 (hemat ruang vertikal ~63%).
  - Tata letak **Smart Compact** pada kartu Biaya Operasional di editor HPP: nama biaya & icon hapus di Baris 1, jenis perhitungan & nilai input di Baris 2, subtotal biaya di Baris 3/footer (hemat ruang vertikal ~56%).
  - Tata letak **Smart Compact 2-Baris** pada Master Bahan Baku (`admin/hpp_ingredients.php`): nama bahan, catatan, dan icon aksi di Baris 1; spesifikasi kemasan dan harga kemasan di Baris 2 (hemat ruang vertikal ~65%).
  - Tata letak **Smart Compact 2-Baris** pada Manajemen Produk (`admin/products.php`): thumbnail & nama kategori di kiri dan 3 icon aksi (Kelola, Edit, Hapus) di kanan atas Baris 1; badge jumlah varian interaktif di Baris 2 (hemat ruang vertikal ~65%).
  - Tata letak **Smart Compact Mobile** pada Dashboard (`admin/dashboard.php`): grid 2x2 pada 4 kartu statistik, grid 2x2 pada 4 kartu akses cepat, dan feed timeline ringkas anti-overflow pada log aktivitas terbaru (hemat ruang vertikal ~65%).
  - Tata letak **Smart Compact 2-Baris** pada Halaman Utama Kalkulator HPP (`admin/hpp_recipes.php`): nama resep, varian, dan 3 icon aksi di Baris 1; HPP per jar dan harga jual saat ini berdampingan langsung di Baris 2 (hemat ruang vertikal ~66%).
  - Tata letak **Smart Compact** pada Tab Pengaturan (`admin/settings.php`): pill tab bar yang ringkas, formulir kontak & footer yang lebih padat, serta tombol Backup Database & Gambar disajikan 2 kolom berdampingan (*side-by-side*).
  - Seluruh tampilan desktop/laptop (> 768px) pada seluruh halaman admin tetap 100% utuh tanpa perubahan.
- **Penyempurnaan Bahasa Ramah Pengguna (User-Friendly Wording)**:
  - Mengganti istilah teknis `"Sesuai snapshot"` menjadi **`✓ Biaya Terkini`** pada daftar resep HPP.
  - Memperjelas status peringatan menjadi **`⚠️ Perlu Update (Harga bahan berubah)`**.
  - Mengubah opsi pembaruan resep di editor menjadi **`🔄 Perbarui biaya resep mengikuti harga beli bahan terbaru di toko`**.
  - Notifikasi peringatan di atas editor resep disesuaikan dengan penjelasan praktis yang mudah dimengerti pemilik dapur.
- **Restore Database (.sql) & Restore Gambar (.zip)**:
  - Modul backend `admin/helpers/backup_helper.php` dengan parser verifikasi versi skema migrasi `schema_migrations`, whitelist tabel resmi, anti-lockout sesi admin, dan proteksi Zip Slip.
  - Skrip handler `admin/restore_db.php` dan `admin/restore_images.php`.
  - Panel Restore Database dan Restore Gambar di Tab 3 Pengaturan (`admin/settings.php`).
  - Integrasi notifikasi toast flash message di `admin/settings.php` via `flash_message.php`.
  - Widget "Riwayat Restore Terakhir" (`get_latest_restore_activity`) di Tab 3 untuk membaca status pemulihan terakhir dari `logs/admin_activity.log`.
  - Loading state dan double-submit lock (`data-submit-lock`, `data-loading-label`) pada tombol aksi restore.
- **Kalkulator HPP — 3 Tab & Live Custom Markup**:
  - Restrukturisasi form editor resep (`admin/hpp_edit.php`) menjadi 3 Tab Terarah: `1. Resep & Bahan`, `2. Biaya Operasional`, dan `3. Kalkulasi & Harga Jual`.
  - Live HPP Badge pada header tombol Tab 3 yang ter-update secara *realtime* saat takaran bahan atau biaya diedit.
  - Tombol navigasi langkah antar-tab (`Lanjut: Biaya Operasional →`, `← Bahan`, `Lanjut: Hasil Kalkulasi →`, `← Kembali ke Biaya`).
  - Skenario Markup Kustom Manual interaktif di posisi paling atas daftar skenario, dengan input persentase bebas, kalkulasi laba/margin seketika, tombol "Gunakan", dan penyimpanan otomatis di `localStorage`.
- **Navigasi & Viewport Mobile**:
  - Menu `Master Bahan` ditambahkan ke navigasi bawah mobile (bottom nav) sehingga genap 5 menu utama (Beranda, Produk, Kalkulator, Master Bahan, Setelan).
  - Penambahan `<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">` dan `<meta name="robots" content="noindex, nofollow">` pada seluruh halaman form admin agar tidak zoom out ke tampilan desktop di layar HP.
- **Unit Testing**:
  - Penambahan suite tes `DatabaseRestoreTest`, `HppRecipeListPageTest`, dan `TestRunnerCliTest`. Total tes mencapai 215 unit test dengan tingkat kelulusan 100%.

### Changed
- Scroll pada Tab Kalkulasi HPP diubah menjadi satu scrollbar utama (*single page scroll*) dengan menghapus aturan `overflow-y: auto` dan `max-height` pada `.hpp-calculation-card` untuk mengeliminasi *nested scrollbar*.

## 2026-11-01

### Added
- Panduan agen `AGENTS.md` di root proyek.
- Saran keamanan (CSP/HSTS/Permissions-Policy, session fixation, rate-limit login, display_errors off, validasi ekstensi upload, redirect HTTPS, integritas backup) dan SEO tambahan (meta/OG dinamis per produk, breadcrumb schema, Product schema Offer, alt text konsisten, internal linking, image sitemap, preconnect, optimasi font, minify CSS/JS, lazy loading, Core Web Vitals, feed RSS/Atom) dicatat di `docs/ROADMAP.md`.
- Sidebar navigasi admin dengan status aktif, plus navigasi mobile (bottom nav).
- Notifikasi toast non-blocking di pojok kanan atas untuk flash message admin.
- Sticky header tabel, indikator scroll horizontal, dan sort kolom (klik header).
- Drag & drop upload dengan preview thumbnail sebelum submit.
- Breadcrumbs di semua halaman admin.
- Ikon konsisten via Lucide SVG (CDN) menggantikan emoji campur.
- Skala spacing/typography (4/8px), card-based dashboard, dan statistik di atas dashboard.
- Info footer (Instagram, Facebook, TikTok, jam operasional, alamat, email, Google Maps) dapat diinput via panel admin.
- Logo di kolom kiri footer dengan ukuran proporsional (52px) tanpa border.

### Changed
- Footer diubah menjadi layout grid 3-4 kolom responsif dengan ikon sosial berwarna brand (Instagram gradient, Facebook biru, TikTok hitam), garis pemisah di atas copyright dihapus.
- Flash message diganti toast auto-dismiss 3,2 detik.

### Fixed
- Path logo footer diperbaiki (`uploads/logo.png`) agar gambar tampil.

## Riwayat tambahan (tanggal tidak dicatat)

Ringkasan ini mencakup perubahan dari catatan evaluasi teknis sebelumnya.
Tanggal rilis tidak dicantumkan karena catatan sumber tidak mencatat tanggal
untuk setiap perubahan.

### Added
- Pengelola migrasi database berversi, runner CLI dan panel admin untuk
  menerapkan migrasi, serta migrasi awal skema, login attempts, UTF-8, dan slug.
- Pemuat `.env` tanpa dependensi eksternal beserta template konfigurasi dan
  perlindungan file rahasia.
- Suite tes PHP native tanpa dependensi Composer/PHPUnit.
- Cooldown untuk ekspor backup, rotasi log aktivitas, dan aksi untuk
  membersihkan log dengan proteksi CSRF.
- Halaman 404 khusus, manifest dan ikon PWA, serta metadata aplikasi mobile.
- Navigasi pratinjau situs yang konsisten pada halaman admin.

### Changed
- Validasi path thumbnail dan penghapusan gambar dipusatkan untuk membatasi
  akses file ke lokasi yang diizinkan.
- Autentikasi admin dan pemeriksaan CSRF dipusatkan; stylesheet dan fallback
  logo dipisahkan ke helper khusus.
- Cache browser dan thumbnail WebP, preload serta ukuran responsif gambar hero,
  dan fallback format gambar berdasarkan dukungan browser ditambahkan.
- SEO, robots, sitemap, security headers, cookie sesi, dan normalisasi nomor
  WhatsApp diperbarui.
- Konfigurasi test dan helper SEO dipindahkan ke direktori yang sesuai.

### Fixed
- Styling form dan preview gambar admin, ukuran card, dan tampilan tabel varian
  pada layar kecil diperbaiki.
- Akses HTTP ke file sensitif dan folder database dibatasi; konfigurasi noindex
  diperbaiki agar berfungsi pada instalasi root maupun subfolder.

## 2026-10-21

### Added
- PWA manifest, ikon aplikasi, dan metadata untuk perangkat mobile.
- Migrasi database berversi, pemuat konfigurasi `.env`, pembatasan percobaan
  backup, serta rangkaian tes native.
- Tombol pratinjau situs dan navigasi yang konsisten pada panel admin.
- Security headers, cookie sesi yang diperketat, normalisasi URL WhatsApp, dan
  quality gate tes pada CI.

### Changed
- Slider homepage menggunakan implementasi touch native, tanpa dependensi
  Swiper.
- Thumbnail responsif menggunakan `srcset`, caching disk WebP, dan optimasi
  preload gambar hero.
- Cache gambar WebP disiapkan saat deploy; logo disajikan secara statis untuk
  mengurangi beban CPU.
- Konfigurasi SEO dan rewrite diperbarui, termasuk pemulihan `robots.txt`
  statis guna menghindari error 502.
- Logging login dipusatkan dan fallback WebP browser ditingkatkan.

## 2026-10-07

### Added
- Dokumentasi skema database serta pembaruan README untuk optimasi performa
  dan SEO.
- Sitemap dengan image map dan informasi `lastmod`.
- Label aksesibilitas untuk tombol WhatsApp dan peningkatan kontras warna
  mengikuti WCAG AA.

### Changed
- Optimasi gambar hero, preload LCP, thumbnail WebP, dan pemuatan font serta
  aset slider.

## 2026-09-29

### Changed
- Slug produk dipertahankan sebagai URL permanen meskipun nama produk diubah.
- Trigger slug lama ditandai sebagai legacy.
