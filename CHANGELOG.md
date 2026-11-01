# Changelog

Perubahan penting pada Alfi Kitchen dicatat di sini, berdasarkan riwayat commit
repository. Tanggal menunjukkan tanggal perubahan tercatat di Git; entri ini
bukan penanda rilis atau versi aplikasi.

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
