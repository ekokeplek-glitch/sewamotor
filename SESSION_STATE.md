# SESSION STATE: RYOKOURENT

Dokumen ini melacak status pengerjaan sesi, task aktif, dependensi yang telah terpenuhi, dan rencana task selanjutnya.

---

## 1. Status Sesi Saat Ini
* **Tanggal / Waktu:** 2026-10-07
* **Cabang Git Aktif:** `feature/mobile-responsive-optimization` (dibuat dari `develop`)
* **Daftar Cabang Proyek Terdaftar (sesuai `GIT_WORKFLOW.md`):**
  - `main` (Branch produksi resmi)
  - `develop` (Branch integrasi aktif)
  - `feature/mobile-responsive-optimization` (Fitur optimasi antarmuka seluler, floating mobile bar, dan ergonomi thumb zone)
  - `feature/faq-and-pool-locations` (Fitur halaman FAQ, 2 lokasi pool resmi, dan aturan Bromo CRF)
  - `feature/admin-settings` (Fitur pengaturan harga, nomor WA, dan penyesuaian harga massal)
  - `feature/booking-status-actions` (Fitur quick action perubahan status booking & validasi plat)
  - `feature/admin-dashboard` (Fitur dashboard admin & pelaporan operasional)
  - `feature/capability-access` (Fitur pembatasan akses operator & guard 403 server-side)
  - `feature/user-roles` (Fitur role Operator Ryokourent & pembersihan role)
  - `feature/booking-storage` (Fitur penyimpanan transaksi CPT penyewaan & handler nonce)
  - `feature/cpt-motor` (Fitur CPT & katalog armada motor)
  - `feature/cpt-booking` (Fitur CPT penyewaan & transaksi sewa)
  - `feature/booking-form` (Fitur formulir pemesanan & kalkulasi)
  - `feature/pricing` (Fitur kalkulator tarif harian, mingguan, bulanan)
  - `feature/availability` (Fitur pencegahan double booking & pengecekan stok unit)
  - `feature/whatsapp` (Fitur generator draft pesan & URL WhatsApp)
* **Task Terakhir Selesai:** `TASK-026: Buat Responsive Design & Mobile-First Optimization`
* **Status Task Terakhir:** **DONE (SELESAI)** - `tests/test-responsive-design.php` 34/34 PASS.
* **Task Selanjutnya:** `TASK-027: Buat Validasi Keamanan (Security Hardening)` (Menunggu perintah selanjutnya dari pengguna)

---

## 2. Riwayat Progress Task

| ID Task | Nama Task | Fase | Status | Dependensi | Tanggal Selesai |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **TASK-001** | Analisis Blueprint & Dokumentasi | FASE 0 | **DONE** | - | 2026-09-30 |
| **TASK-002** | Struktur Repository & Kerangka Proyek | FASE 1 | **DONE** | TASK-001 | 2026-09-30 |
| **TASK-003** | Plugin Loader & Helper Dasar | FASE 2 | **DONE** | TASK-002 | 2026-09-30 |
| **TASK-004** | Custom Post Type `motor` | FASE 2 | **DONE** | TASK-003 | 2026-09-30 |
| **TASK-005** | Metabox Spesifikasi & Kuota Motor | FASE 2 | **DONE** | TASK-004 | 2026-09-30 |
| **TASK-006** | Taxonomy Kategori Motor | FASE 2 | **DONE** | TASK-004 | 2026-09-30 |
| **TASK-007** | Tampilan Katalog Motor | FASE 2 | **DONE** | TASK-005, TASK-006 | 2026-09-30 |
| **TASK-008** | Halaman Detail Motor | FASE 2 | **DONE** | TASK-007 | 2026-09-30 |
| **TASK-009** | Custom Post Type `penyewaan` | FASE 3 | **DONE** | TASK-004 | 2026-09-30 |
| **TASK-010** | Status Booking Kustom | FASE 3 | **DONE** | TASK-009 | 2026-09-30 |
| **TASK-011** | Form Booking Dasar | FASE 3 | **DONE** | TASK-005 | 2026-09-30 |
| **TASK-012** | Validasi Pelanggan & Anti-Spam | FASE 3 | **DONE** | TASK-011 | 2026-10-01 |
| **TASK-013** | Kalkulasi Durasi Sewa | FASE 3 | **DONE** | TASK-011 | 2026-10-01 |
| **TASK-014** | Kalkulasi Harga Paket Sewa | FASE 3 | **DONE** | TASK-013 | 2026-10-01 |
| **TASK-015** | Validasi Tanggal dan Jam | FASE 3 | **DONE** | TASK-013 | 2026-10-01 |
| **TASK-016** | Validasi Ketersediaan Unit | FASE 3 | **DONE** | TASK-005, TASK-010 | 2026-10-01 |
| **TASK-017** | Pencegahan Double Booking Atomik | FASE 3 | **DONE** | TASK-016 | 2026-10-01 |
| **TASK-018** | Generator Pesan WhatsApp | FASE 3 | **DONE** | TASK-011, TASK-014 | 2026-10-01 |
| **TASK-019** | Penyimpanan Booking (AJAX & Nonce) | FASE 3 | **DONE** | TASK-017, TASK-018 | 2026-10-06 |
| **TASK-020** | Buat Role Operator | FASE 3 | **DONE** | TASK-009 | 2026-10-07 |
| **TASK-021** | Buat Capability dan Pembatasan Akses | FASE 3 | **DONE** | TASK-020 | 2026-10-07 |
| **TASK-022** | Buat Dashboard Booking & Operasional Armada | FASE 3 | **DONE** | TASK-010, TASK-019 | 2026-10-07 |
| **TASK-023** | Buat Perubahan Status Booking (Quick Actions & Validasi Plat) | FASE 3 | **DONE** | TASK-022 | 2026-10-07 |
| **TASK-024** | Buat Pengaturan Harga dan Nomor WhatsApp (Admin Settings) | FASE 3 | **DONE** | TASK-014, TASK-021 | 2026-10-07 |
| **TASK-025** | Buat Halaman FAQ dan Lokasi Pool | FASE 4 | **DONE** | TASK-007, TASK-024 | 2026-10-07 |
| **TASK-026** | Buat Responsive Design & Mobile-First Optimization | FASE 4 | **DONE** | TASK-011, TASK-025 | 2026-10-07 |
| **TASK-027** | Buat Validasi Keamanan (Security Hardening) | FASE 4 | PENDING | TASK-002 s/d TASK-026 | - |

---

## 3. Komponen yang Telah Diimplementasikan pada TASK-025
1. **Pusat Informasi & 7 Poin FAQ Resmi (`public/templates.php`):**
   - Implementasi `ryokourent_get_faq_items()` memuat 7 poin FAQ blueprint:
     1. Dokumen jaminan (e-KTP Asli + 2 pendukung sah).
     2. Larangan keras motor matik ke Lautan Pasir Bromo (alasan transmisi CVT debu, overheat, slip).
     3. Kewajiban unit Honda Trail CRF 150L untuk rute Bromo (suspensi Showa, ban dual-purpose).
     4. Layanan antar-jemput stasiun/hotel fleksibel menyesuaikan sikon.
     5. Jam operasional pelayanan (07:00 – 23:00 WIB, terintegrasi dinamis dengan `ryokourent_get_settings()`).
     6. Aturan 24 jam dan batas toleransi keterlambatan (overtime grace period 2 jam).
     7. Batas wilayah Malang Raya & Kota Batu, kewajiban izin tertulis jika keluar batas wilayah.
   - Render antarmuka FAQ `ryokourent_render_faq_section()` dengan banner syarat dokumen e-KTP dan struktur ARIA accessible (`aria-expanded`, `aria-controls`).
2. **Area Layanan & Dua Lokasi Pool Resmi (`public/templates.php`):**
   - Implementasi `ryokourent_get_pool_details()` dan `ryokourent_render_pool_locations_section()`:
     - Pool 1: Malang Dinoyo (Pusat Kota / Kampus), Jl. MT Haryono Gg. 21 No. 23, Lowokwaru, Kota Malang.
     - Pool 2: Batu Diponegoro (Kota Wisata Batu), Jl. Diponegoro No. 45, Kec. Batu, Kota Wisata Batu.
     - Jam operasional resmi 07:00 – 23:00 WIB, tautan Google Maps dinamis (`rel="noopener noreferrer"`), dan catatan layanan antar-jemput fleksibel sikon.
3. **Peringatan Wajib Keselamatan Rute Bromo (`public/templates.php`):**
   - Implementasi `ryokourent_render_bromo_advisory_banner()` berlatar kontras tinggi dengan peran `role="alert"`, mengarahkan pengguna langsung ke tab filter Trail CRF 150L.
4. **Pendaftaran Shortcodes Publik (`public/shortcodes.php`):**
   - Shortcode `[ryokou_faq]` untuk menampilkan accordion FAQ 7 poin.
   - Shortcode `[ryokou_pools]` untuk menampilkan 2 pool resmi.
   - Shortcode `[ryokou_bromo_advisory]` untuk menampilkan banner peringatan Bromo.
5. **Skrip Interaksi Accordion & Styling Dark Modern:**
   - Skrip `assets/js/ryokourent-filter.js`: handler vanilla JS interaktif tanpa dependensi jQuery, toggle ARIA accordion responsif, dan auto-close sibling item.
   - Stylesheet `assets/css/ryokourent-public.css` & `wp-content/themes/generatepress-child/style.css`: styling dark bertema modern navy/amber, banner dokumen, grid kartu pool, dan transisi halus.
6. **Template Halaman GeneratePress Child Theme:**
   - Berkas template page `wp-content/themes/generatepress-child/templates/template-faq-pool.php` dan file root `template-faq-pool.php`.
7. **Automated Unit Test Suite:**
   - `tests/test-faq-pool.php` (36 pengujian mencakup 7 poin FAQ blueprint, jam dinamis, detail 2 pool, link Google Maps, shortcodes, template child theme, ARIA attributes, dan escaping). 36/36 PASS.

---

## 3. Komponen yang Telah Diimplementasikan pada TASK-024
1. **Modul Pengaturan Terpusat (`includes/settings.php`):**
   - Fungsi getter konfigurasi default dan database (`ryokourent_get_default_settings()`, `ryokourent_get_settings()`, `ryokourent_get_operating_hours()`).
   - Validasi ketat penyimpanan pengaturan umum: format seluler nomor WhatsApp utama (08xx/628xx), nomor WA cadangan, jam operasional pool (07:00-23:00 WIB, format waktu 24-jam, jam tutup > jam buka), serta link Google Maps kedua pool.
   - Algoritma perhitungan penyesuaian harga aman (`ryokourent_calculate_adjusted_price()`):
     - Pembatasan persentase ketat rentang aman $-50\%$ s/d $+200\%$.
     - Penolakan penyesuaian nilai $0$ atau penyesuaian yang menghasilkan harga $\le 0$.
     - Pembulatan persentase otomatis ke kelipatan seribu Rupiah terdekat.
   - Eksekusi pembaruan harga massal armada (`ryokourent_apply_bulk_price_adjustment()`):
     - Pemfilteran per kategori motor (`kategori_motor`) atau seluruh armada (`'all'`).
     - Pemilihan paket tarif granular: Harian (`daily`), Mingguan (`weekly`), Bulanan (`monthly`), atau Semua.
     - Validasi dua tahap (*two-pass validation*): simulasi seluruh unit terlebih dahulu; jika ada 1 unit armada saja yang menghasilkan tarif tidak valid ($\le 0$), seluruh operasi dibatalkan seketika (*fail-safe atomic rollback*).
     - Menghindari kueri `posts_per_page => -1` dengan limit `posts_per_page => 100` dan `no_found_rows => true` (reviewOP M12).
2. **Antarmuka Admin Pengaturan (`admin/admin-settings.php`):**
   - Submenu terdaftar di bawah menu Penyewaan dan menu Armada Motor dengan capability `manage_ryokourent_settings`.
   - Penjaga akses server-side mutlak: Operator diblokir dengan HTTP 403 Forbidden via `ryokourent_check_settings_permission_or_die()`.
   - Tab navigasi responsif: Tab 1 (Kontak WhatsApp & Jam Operasional) dan Tab 2 (Penyesuaian Tarif Massal Peak Season).
   - Seluruh form dilindungi nonce spesifik (`check_admin_referer`) dan seluruh output di-escape (`esc_html`, `esc_attr`, `esc_url`).
   - Pratinjau tabel tarif armada saat ini untuk memudahkan admin melihat harga awal.
3. **Penyempurnaan Pricing Engine (`includes/pricing.php`):**
   - Penambahan fungsi pembantu `ryokourent_update_motor_pricing($motor_id, $daily, $weekly, $monthly)`.
4. **Automated Unit Test Suite:**
   - `tests/test-admin-settings.php` (44 pengujian: RBAC operator vs admin, 403 server-side guard, validasi WA, jam operasional, batasan harga nominal/persentase, penolakan persentase ekstrem, pembatalan atomik two-pass, pendaftaran menu, dan helper pricing). 44/44 PASS.

---

## 4. Komponen yang Telah Diimplementasikan pada TASK-023
1. **Matriks Transisi Status Resmi (ADR-011 & `includes/booking.php`):**
   - Alur operasional lapangan: Menunggu -> Dikonfirmasi -> Berjalan -> Selesai.
   - Pembatalan hanya sebelum unit diserahkan (dari Menunggu atau Dikonfirmasi).
   - Status Selesai dan Dibatalkan bersifat final/terminal.
   - Validasi ketat via `ryokourent_validate_status_transition()` yang dipakai bersama oleh tombol quick action dan dropdown metabox (`wp_insert_post_data`).
2. **Quick Actions Terlindungi pada Kolom Admin (`admin/booking-columns.php`):**
   - Kolom "Status & Aksi" merender status terkini dan tombol aksi yang relevan.
   - Dilindungi nonce spesifik per booking (`ryokourent_status_<id>`), capability `manage_ryokourent_bookings` (403 jika tidak berhak), dan sanitasi regex ketat.
   - Input plat nomor dinamis otomatis muncul saat status berada di tahap Dikonfirmasi (menuju Berjalan).
   - Seluruh output di-escape (`esc_html`, `esc_attr`).
   - Handler tangkap POST di hook `load-edit.php` dengan redirect aman dan flash transient notice.
3. **Pengecekan Kuota Atomik & Validasi Plat Nomor:**
   - Transisi ke `status_dikonfirmasi` mengecek ketersediaan kuota secara atomik di dalam `ryokourent_with_motor_lock`, mengecualikan booking itu sendiri (`post__not_in`), dan menolak transisi jika kuota penuh (`quota_full`).
   - Transisi ke `status_berjalan` mewajibkan plat nomor, menormalisasi string plat, dan memvalidasi kecocokan dengan armada fisik serta ketiadaan jadwal bentrok via `ryokourent_validate_allocated_plate()`.
   - Perubahan status otomatis memicu `transition_post_status` untuk membuang cache transient statistik dashboard (`ryokourent_dashboard_stats`).
4. **Automated Unit Test Suite:**
   - `tests/test-booking-status-actions.php` (86 pengujian: alur transisi, validasi plat nomor, kuota penuh, pencegahan double booking, pembatalan, rendering kolom, otorisasi nonce/cap, flash message, dan integrasi metabox). 86/86 PASS.

---

## 4. Komponen yang Telah Diimplementasikan pada TASK-022
1. **Dashboard Operasional (`admin/dashboard.php`):**
   - Submenu "Dashboard" di bawah menu Penyewaan, capability `manage_ryokourent_bookings`; user tanpa capability mendapat HTTP 403.
   - Metrik: Unit Disewa Hari Ini (booking `status_dikonfirmasi`/`status_berjalan` yang rentangnya menyentuh hari ini WIB), Booking Menunggu Konfirmasi, dan Unit Aktif (`status_berjalan`) per lokasi dari `ryokourent_get_pool_locations()`; lokasi tak dikenal masuk "Lainnya".
   - Nilai pickup tersimpan (label form booking atau slug) dipetakan ke slug lewat `ryokourent_dashboard_pickup_to_slug()`.
   - Query efisien (`fields => 'ids'`, `no_found_rows => true`, batas 500, tanpa `-1`) dengan notice jika batas tercapai (reviewOP M12).
   - Cache transient `ryokourent_dashboard_stats` 5 menit; dibuang saat status penyewaan berubah (`transition_post_status`), booking dihapus (`deleted_post`), atau tanggal WIB berganti.
   - Quick link ke daftar `status_menunggu` dan `status_berjalan`; seluruh output di-escape.
2. **Kolom Daftar Penyewaan (`admin/booking-columns.php`):**
   - Kolom Motor, Jadwal Sewa, dan Total (Rp) dengan escaping dan capability check. Perubahan status adalah TASK-023.
3. **Automated Unit Test Suite:**
   - `tests/test-admin-dashboard.php` (28 pengujian: rentang hari, pemetaan lokasi, metrik, parameter query, cache, menu, 403, escaping, kolom). 28/28 PASS.

---

## 3. Komponen yang Telah Diimplementasikan pada TASK-021
1. **Pemetaan Hak Akses CPT Motor (`includes/post-types.php`):**
   - Mengganti `capability_type => 'post'` menjadi `capability_type => array('motor', 'motors')` dengan `map_meta_cap => true`.
   - Memetakan `create_posts => 'create_motors'` agar pembuat model motor baru hanya diizinkan untuk Administrator.
2. **Konfigurasi Hak Akses Role Operator & Administrator (`includes/user-roles.php`):**
   - Memberikan kapabilitas granular ke Operator: `read`, `manage_ryokourent_bookings`, `edit_motors`, `edit_others_motors`, `edit_published_motors`.
   - Mengunci secara ketat: Operator tidak memiliki `create_motors`, `publish_motors`, `delete_motors`, `upload_files`, maupun `manage_ryokourent_settings`.
   - Administrator mendapatkan kapabilitas penuh (`create_motors`, `delete_motors`, `publish_motors`, `manage_ryokourent_settings`, dll).
   - Naikkan versi skema role ke `'2'` untuk sinkronisasi otomatis via `init`.
3. **Fungsi Penjaga Akses Server-Side 403 (`includes/user-roles.php`):**
   - `ryokourent_current_user_can_manage_settings()`: mengecek wewenang admin.
   - `ryokourent_current_user_can_manage_bookings()`: mengecek wewenang sewa/booking.
   - `ryokourent_check_settings_permission_or_die()`: penjaga akses server-side (HTTP 403 Forbidden via `wp_die`) untuk menjaga halaman pengaturan (TASK-024) dan endpoint sensitif dari akses liar operator.
4. **Pembersihan Uninstall (`uninstall.php`):**
   - Menghapus seluruh kapabilitas kustom motor & settings dari role administrator saat plugin di-uninstall.
5. **Pemetaan Hak Akses CPT Penyewaan (`includes/post-types.php`):**
   - Menetapkan secara eksplisit `capability_type => array('penyewaan', 'penyewaans')` dengan `map_meta_cap => true` dan seluruh primitive capabilities dipetakan ke `manage_ryokourent_bookings` (reviewOP K1).
6. **Validasi Alokasi Plat Nomor Unit Fisik (`includes/availability.php` & `includes/meta-boxes.php`):**
   - Implementasi `ryokourent_validate_allocated_plate()` untuk memeriksa bahwa plat nomor yang dialokasikan benar-benar terdaftar pada inventaris model motor tersebut dan tidak bertabrakan dengan pesanan aktif lain pada rentang waktu yang sama (reviewOP M6).
   - Menampilkan peringatan flash notice bagi operator jika terjadi konflik alokasi plat.
   - Mengoptimalkan kueri dropdown armada di metabox penyewaan agar tidak menggunakan unbounded query (`posts_per_page => 100`, `no_found_rows => true`) (reviewOP M12).
7. **Dokumentasi Keputusan Arsitektur (`DECISIONS.md`):**
   - Pencatatan ADR-009 (Manajemen Custom Post Status Booking via Metabox Terisolasi & Filter `wp_insert_post_data`) dan ADR-010 (Validasi Alokasi Plat Nomor Unit Fisik & Pencegahan Konflik Plat Ganda).
8. **Automated Unit Test Suite:**
   - `tests/test-capabilities-access.php` (23 skenario verifikasi hak akses operator, larangan create/delete motor, larangan tarif, 403 guard, dan integritas metabox). Seluruh test PASS.

---

## 3. Komponen yang Telah Diimplementasikan pada TASK-020
1. **Modul Role Operator (`wp-content/plugins/ryokourent-core/includes/user-roles.php`):**
   - Role `ryokourent_operator` berlabel "Operator Ryokourent" dengan whitelist capability mutlak: `read` dan `manage_ryokourent_bookings`. Tidak ada hak edit tema, plugin, user, maupun pengaturan/tarif (`manage_ryokourent_settings` tetap eksklusif administrator).
   - `ryokourent_register_operator_role()`: idempoten; pada role yang sudah ada mencabut capability di luar whitelist, memulihkan yang hilang, dan menyelaraskan label lama ("Ryokourent Operator").
   - `ryokourent_install_operator_role()` (aktivasi) dan `ryokourent_maybe_sync_operator_role()` (hook `init`, hanya berjalan saat versi skema role `ryokourent_roles_version` berubah, sehingga tidak menimpa perubahan manual).
   - `ryokourent_deactivate_operator_role()`: role dihapus hanya bila tidak dipakai user (`absent` / `kept` / `removed`).
2. **Wiring (`ryokourent-core.php`, `uninstall.php`):**
   - Activation hook memuat `user-roles.php` secara eksplisit (modul baru dimuat pada `plugins_loaded`) dan menggantikan `add_role` inline sebelumnya.
   - Deactivation hook memanggil pembersihan aman.
   - `uninstall.php`: user operator dipindah ke `subscriber` (role lain dipertahankan), role dihapus, capability kustom dicabut dari administrator, opsi `ryokourent_roles_version` dihapus. Pembersihan ini belum mendukung multisite.
3. **Automated Unit Test Suite:**
   - `wp-content/plugins/ryokourent-core/tests/test-user-roles.php` (40 pengujian: whitelist, registrasi, idempotensi, pencabutan capability berlebih, sinkronisasi label & versi, deaktivasi aman, uninstall, dan wiring berkas utama). Dieksekusi di PHP lokal: 40/40 PASS.
4. **Di luar lingkup (TASK-021):** pemberian capability ke administrator, pembatasan akses halaman admin (403), dan peninjauan hak edit post `motor` untuk operator.

---

## 3. Komponen yang Telah Diimplementasikan pada TASK-019
1. **Mesin Penyimpanan Transaksi Booking CPT `penyewaan` (`includes/booking.php`):**
   - Fungsi `ryokourent_generate_booking_code()`: menghasilkan kode booking terstandarisasi yang unik dan mudah dikenali dengan format `RYK-YYYYMMDD-XXXX` (misal: `RYK-20261001-A4B7`).
   - Fungsi `ryokourent_save_booking_entry($clean_data)`: menyimpan data transaksi langsung ke CPT `penyewaan` dengan status `status_menunggu` dan judul otomatis `[KODE] - [NAMA]`. Eksekusi dibungkus mutlak di dalam *atomic lock* armada untuk menjamin ketiadaan celah *race condition*.
   - Menyimpan seluruh metadata transaksi per `DATA_MODEL.md` (kode booking, identitas lengkap penyewa, jadwal sewa, unit motor, lokasi, rute tujuan, tarif sewa, breakdown harga, dan catatan sewa).
2. **Handler AJAX & Penanganan Nonce Kompatibel Cache Server (`includes/booking.php` & `assets/js/ryokourent-booking.js`):**
   - Mendaftarkan endpoint AJAX `ryokourent_process_booking` (dan alias `ryokourent_submit_booking`) untuk pengunjung tanpa login (`nopriv`) maupun user terotentikasi.
   - Mengirimkan header `X-LiteSpeed-Cache-Control: no-cache` dan `nocache_headers()` untuk memastikan respon AJAX tidak di-cache oleh LiteSpeed / Nginx / Varnish.
   - Penanganan nonce kedaluwarsa: jika nonce halaman yang di-cache sudah kedaluwarsa, server mengembalikan error 403 `invalid_nonce` beserta payload `refreshed_nonce`. Frontend JavaScript secara transparan memperbarui token dan mengulang pengiriman formulir (*transparent single-retry*) tanpa membebani pengunjung dengan reload halaman manual.
   - Endpoint `ryokourent_refresh_nonce` untuk pengambilan nonce baru secara dinamis.
3. **Integrasi Deep Link WhatsApp & Informasi Kode Booking:**
   - Memasukkan kode unik pemesanan ke dalam draf pesan WhatsApp resmi (`• Kode Booking: *#RYK-...*`).
   - Frontend menampilkan pesan konfirmasi ramah dengan nomor pesanan sebelum mengarahkan pengunjung ke WhatsApp Admin.
4. **Automated Unit Test Suite:**
   - `wp-content/plugins/ryokourent-core/tests/test-booking-storage.php` (12 assertions pengujian format kode booking, atribut CPT, metadata lengkap, penanganan nonce kedaluwarsa 403, dan tautan deep link WhatsApp).

---

## 3. Komponen yang Telah Diimplementasikan pada TASK-018
1. **Modul Generator Pesan & Tautan WhatsApp (`wp-content/plugins/ryokourent-core/includes/whatsapp.php`):**
   - Fungsi `ryokourent_get_official_wa_number()`: menentukan nomor WhatsApp admin secara aman di sisi server (dari opsi `ryokourent_wa_number` dengan fallback default). Client dilarang memanipulasi nomor tujuan admin.
   - Fungsi `ryokourent_build_whatsapp_message($data)`: menyusun draf pesan WhatsApp resmi berformat rapi, ber-emotikon terstruktur (🛵, 📋, 👤, 🔒), mencakup rincian jadwal, durasi sewa, lokasi serah terima unit, rute tujuan, estimasi biaya sewa, identitas penyewa lengkap (nama, WA, kontak darurat keluarga terpisah, alamat KTP, tempat menginap di Malang/Batu, medsos, dan catatan perlengkapan), serta klausul privasi UU PDP.
   - Fungsi `ryokourent_get_whatsapp_url($data, $phone)`: menghasilkan tautan resmi `https://wa.me/{nomor}?text={encoded_text}` dengan `rawurlencode()` (RFC 3986) sehingga seluruh teks, emoji, baris baru, dan spasi aman dari risiko pemotongan karakter di browser mobile maupun desktop.
   - Endpoint AJAX `ryokourent_get_whatsapp_draft` untuk generator pesan dinamis.
2. **Integrasi Pratinjau Interaktif Frontend (`public/forms.php`, `assets/js/ryokourent-booking.js`, `assets/css/ryokourent-public.css`):**
   - Menambahkan boks pratinjau langsung `#ryokou-wa-preview-text` di formulir publik sebelum tombol submit.
   - Menambahkan event listener reaktif pada seluruh input formulir (nama, tanggal, pilihan motor, rute, kontak, alamat, catatan) untuk memperbarui draf pesan WhatsApp secara *real-time*.
   - Menyertakan data `adminWa`, `wa_url`, dan `wa_message` pada respons AJAX submit formulir booking.
3. **Automated Unit Test Suite:**
   - `wp-content/plugins/ryokourent-core/tests/test-whatsapp-generator.php` (16 pengujian komprehensif struktur pesan, emotikon, normalisasi nomor admin 628..., tautan `wa.me`, integrasi `rawurlencode()`, dan round-trip decode tanpa kehilangan karakter).

---

## 3. Komponen yang Telah Diimplementasikan pada TASK-016 & TASK-017
1. **Modul Ketersediaan & Perlindungan Privasi Stok (`wp-content/plugins/ryokourent-core/includes/availability.php`):**
   - Fungsi internal `ryokourent_get_motor_physical_stock($motor_id)` untuk mengakses kuota unit fisik secara tertutup dari publik.
   - Fungsi `ryokourent_count_overlapping_bookings`: kueri efisien (`fields => 'ids'`) untuk mendeteksi tumpang tindih waktu sewa ($S_{booking} < E \text{ dan } E_{booking} > S$) pada status yang mengonsumsi kuota (`status_dikonfirmasi`, `status_berjalan`).
   - Fungsi `ryokourent_check_availability`: mengembalikan `true` jika $(\text{PhysicalStock} - \text{ActiveOverlappingBookings}) > 0$.
   - Endpoint AJAX publik `ryokourent_check_unit_availability`: secara ketat hanya mengembalikan status boolean `available: true/false` dan pesan ramah. Angka kuota fisik internal tidak pernah diekspos ke publik.
2. **Pencegahan Double Booking Atomik pada Dua Titik Kritis (`includes/availability.php` & `includes/booking.php`):**
   - Fungsi pengunci `ryokourent_with_motor_lock($motor_id, $callback, $timeout)`: mengeksekusi operasi kritis di dalam `GET_LOCK` MySQL atau transient per motor ID dengan pelepasan mutlak pada blok `finally { RELEASE_LOCK }` untuk menjamin ketiadaan risiko deadlock.
   - **Titik Kritis 1 (Online Form Submit):** Validasi submit formulir di `ryokourent_validate_booking_submission()` mengeksekusi pengecekan kuota di dalam lock. Jika armada telah habis terpesan, formulir ditolak dengan status HTTP 400 (`unit_fully_booked`).
   - **Titik Kritis 2 (Konfirmasi Operator):** Fungsi `ryokourent_confirm_booking()` dan hook `transition_post_status` memblokir perubahan status menjadi `status_dikonfirmasi` jika kuota armada telah habis terisi booking lain pada jadwal terkait, mengembalikan status ke status awal, dan memunculkan admin flash notice.
3. **Automated Unit Test Suite:**
   - `wp-content/plugins/ryokourent-core/tests/test-availability.php` (8 pengujian skenario simulasi 3 booking pada stok 3 menghasilkan available: false, tanggal tidak bertabrakan, status yang tidak memotong kuota).
   - `wp-content/plugins/ryokourent-core/tests/test-atomic-lock.php` (7 pengujian eksekusi lock, pelepasan finally, simulasi 2 request bersamaan pada 1 unit tersisa, dan proteksi konfirmasi operator).

---

## 3. Komponen yang Telah Diimplementasikan pada TASK-015
1. **Validasi Jadwal & Jam Operasional Pool Backend (`wp-content/plugins/ryokourent-core/includes/booking.php`):**
   - Menegakkan batas jam pelayanan serah terima unit di pool secara ketat antara pukul 07:00 – 23:00 WIB. Jam mulai 02:00 WIB atau di luar rentang resmi langsung ditolak dengan status HTTP 400 (`invalid_schedule`).
   - Mencegah pemilihan tanggal & waktu mulai di masa lalu (`start_datetime < now_wib`) dengan buffer pengisian formulir 15 menit.
   - Memvalidasi rentang waktu sewa: waktu selesai wajib setelah waktu mulai (`end_datetime > start_datetime`) dan durasi sewa minimal 1 jam.
   - Kompatibilitas lintas platform: menangani format standar ISO string (`Y-m-d\TH:i`) dari perangkat seluler Android dan iOS.
2. **Validasi Interaktif & Progressive Enhancement Frontend (`wp-content/plugins/ryokourent-core/assets/js/ryokourent-booking.js` & `public/forms.php`):**
   - Penambahan atribut `min` pada field input `datetime-local` di markup HTML (`forms.php`) dan pembaruan dinamis `endInput.min = startInput.value` di JavaScript saat waktu mulai diubah.
   - Pengecekan instan di client-side: mencegah tanggal masa lalu dan jam di luar operasional pool dengan penandaan visual `.has-error` dan pesan peringatan di bawah input.
   - Pengecekan pada event form submit agar tidak mengirimkan form dengan jadwal salah.
3. **Automated Unit Test Suite:**
   - `wp-content/plugins/ryokourent-core/tests/test-operating-hours.php` (14 pengujian skenario penolakan jam 02:00 WIB, jam operasional 07:00–23:00 WIB, tanggal masa lalu, tanggal terbalik, format ISO, dan integrasi submission AJAX).

---

## 3. Komponen yang Telah Diimplementasikan pada TASK-014
1. **Modul Pricing Server-Side (`wp-content/plugins/ryokourent-core/includes/pricing.php`):**
   - Fungsi `ryokourent_calculate_optimal_rental_price`:
     - Menghitung tarif sewa otomatis di sisi server secara *tamper-proof* berdasarkan kombinasi termurah (*best-rate guarantee*) dari paket bulanan (30 hari), paket mingguan (7 hari), dan harian (24 jam).
     - Menangani kasus diskon bertingkat (misal: sewa 6 hari otomatis mengambil tarif paket mingguan Rp 500.000 jika lebih hemat daripada 6 x Rp 85.000 = Rp 510.000).
     - Menghitung sewa jangka menengah (misal: 35 hari = 1 Bulan Rp 1.600.000 + 5 Hari Rp 425.000 = Rp 2.025.000).
     - Mendeteksi harga placeholder/kosong (`daily <= 0`) dan secara elegan menandai `requires_consultation => true` dengan label `"Konsultasi Admin WA"`.
   - Fungsi `ryokourent_calculate_booking_quote`:
     - Menggabungkan durasi sewa presisi, toleransi keterlambatan 2 jam, data tarif motor, dan menghasilkan objek quote lengkap.
   - Endpoint AJAX `ryokourent_get_price_quote` untuk kalkulasi tarif server-authoritative secara instan.
2. **Integrasi Validasi Form Submission (`wp-content/plugins/ryokourent-core/includes/booking.php`):**
   - Menghitung ulang total tarif secara mutlak di backend pada fungsi `ryokourent_validate_booking_submission()` tanpa mempercayai nilai harga yang dikirim dari browser DevTools.
   - Menyertakan `total_price`, `formatted_price`, `requires_consultation`, dan `price_breakdown` ke dalam data pemesanan.
3. **Automated Unit Test Suite:**
   - `wp-content/plugins/ryokourent-core/tests/test-pricing-calculation.php` (13 pengujian skenario sewa 1 hari, 3 hari, 7 hari, 35 hari, optimasi 6 hari, dan motor tanpa harga).

---

## 3. Komponen yang Telah Diimplementasikan pada TASK-013
1. **Modul Kalkulasi Jadwal & Durasi Server-Side (`wp-content/plugins/ryokourent-core/includes/booking.php`):**
   - Fungsi `ryokourent_validate_rental_schedule`:
     - Memvalidasi rentang tanggal dan jam sewa di zona waktu `Asia/Jakarta` (WIB).
     - Menegakkan batas jam operasional serah terima unit (07:00 – 23:00 WIB). Menolak waktu terlalu pagi (< 07:00) atau terlalu malam (> 23:00).
     - Memvalidasi `end_datetime > start_datetime` dan durasi sewa minimal 1 jam.
     - Menghitung durasi jam presisi dan hari sewa tagihan dengan toleransi *overtime* 2 jam (24 jam = 1 hari, 26 jam = 1 hari, 26.5 jam = 2 hari, 56.5 jam = 3 hari).
     - Mengembalikan label ringkasan format Indonesia (misal: "3 Hari (~56.5 Jam)").
   - Integrasi ke `ryokourent_validate_booking_submission`: menyertakan `duration_hours`, `billable_days`, dan `duration_label` ke dalam objek data bersih (*clean_data*).
   - Penambahan endpoint AJAX real-time `ryokourent_calculate_duration` untuk perhitungan durasi dari server.
2. **Kalkulator Durasi Interaktif Client-Side (`wp-content/plugins/ryokourent-core/assets/js/ryokourent-booking.js`):**
   - Event listener live (`change`/`input`) pada input `#start_datetime` dan `#end_datetime`.
   - Validasi instan jam operasional 07:00–23:00 WIB dengan pesan peringatan di bawah input.
   - Pengecekan waktu selesai harus lebih akhir dari waktu mulai.
   - Perenderan dinamis label durasi real-time pada `#ryokou-live-duration` serta pembaruan estimasi biaya harian.
   - Pengecekan jadwal sewa pada submit form agar tidak mengirimkan jadwal yang tidak valid.
3. **Automated Unit Test Suite:**
   - `wp-content/plugins/ryokourent-core/tests/test-duration-calculation.php` (17 pengujian skenario durasi, toleransi 2 jam, jam operasional, dan rentang tanggal).

---

## 3. Komponen yang Telah Diimplementasikan pada TASK-012
1. **Modul Validasi Backend & Anti-Spam (`wp-content/plugins/ryokourent-core/includes/booking.php`):**
   - Validasi data pelanggan: Nama lengkap e-KTP (min 3 chars), nomor WhatsApp seluler Indonesia (format `08...`/`628...`, 10–15 digit), alamat KTP, dan tempat menginap di Malang/Batu.
   - Aturan khusus pemisahan kontak: Nomor kontak darurat keluarga wajib berbeda secara mutlak dari nomor WhatsApp penyewa.
   - Perlindungan Anti-Spam Honeypot: Field `ryokourent_hp` tersembunyi; jika terisi otomatis memblokir submission dengan status HTTP 400 (`spam_bot_detected`).
   - Perlindungan Rate-Limiting: Berbasis WordPress transients per IP hash (maksimal 5 kali submit per 10 menit, blokir status HTTP 429).
   - Validasi CSRF Token Nonce (`ryokourent_booking_form_action`).
   - Endpoint AJAX resmi: `wp_ajax_ryokourent_submit_booking` dan `wp_ajax_nopriv_ryokourent_submit_booking`.
2. **Validasi Interaktif Frontend (`wp-content/plugins/ryokourent-core/assets/js/ryokourent-booking.js`):**
   - Validasi real-time saat user mengetik (*input/blur*) untuk nama, format seluler Indonesia, kontak darurat terpisah, dan alamat.
   - Intersepsi pengiriman formulir via AJAX `fetch()` dengan indikator status loading pada tombol submit dan scrolling ke input error pertama.
   - Penanganan respons server: menampilkan banner `.ryokou-form-alert` dan penandaan visual `.has-error` per input field.
3. **Pendaftaran Aset & Styling:**
   - Registrasi dan enqueue `ryokourent-booking` script dengan localized configuration `ryokouBookingConfig` pada `public/shortcodes.php`.
   - Penambahan styling CSS untuk alert error/success dan indikator input invalid pada `assets/css/ryokourent-public.css`.
4. **Unit Test Suite:**
   - `wp-content/plugins/ryokourent-core/tests/test-booking-validation.php`.

---

## 3. Komponen yang Telah Diimplementasikan pada TASK-011
1. **Formulir Pemesanan HTML5 (`wp-content/plugins/ryokourent-core/public/forms.php`):**
   - 3 Langkah pemesanan terstruktur:
     - Langkah 1: Pilihan Rute Perjalanan (Malang Kota & Wisata Batu vs Trip Kaldera Bromo) & Dropdown Model Motor dinamis.
     - Langkah 2: Jadwal Sewa (Tanggal & Jam Mulai/Selesai 07.00 - 23.00 WIB) & Live Summary Card durasi serta estimasi tarif total.
     - Langkah 3: Data Identitas Pelanggan sesuai e-KTP (Nama, WhatsApp, Kontak Darurat Keluarga, Alamat KTP, Tempat Menginap di Malang/Batu, Media Sosial, Catatan Tambahan).
   - Perlindungan Keamanan & Anti-Spam: Field honeypot `ryokourent_hp` tersembunyi dan verifikasi nonce `ryokourent_booking_nonce`.
   - Tombol Submit CTA: Langsung terhubung ke WhatsApp Admin resmi dengan draf pesan terstruktur sesuai blueprint §8.
2. **Shortcode `[ryokou_booking_form]` (`wp-content/plugins/ryokourent-core/public/shortcodes.php`):**
   - Pendaftaran shortcode mandiri dengan parameter `form_id`, `selected_motor`, dan `title`.
3. **Logika Interaktif Kuncian Bromo (`assets/js/ryokourent-filter.js`):**
   - Saat opsi rute Bromo dipilih, pilihan model motor otomatis terkunci hanya ke Trail CRF 150L (`data-is-bromo="yes"`) dan menonaktifkan unit matik.
   - Perhitungan durasi real-time dengan toleransi overtime 2 jam.

---

## 4. Komponen yang Telah Diimplementasikan pada TASK-010
1. **Pendaftaran 5 Status Booking Kustom (`includes/post-types.php`):**
   - `status_menunggu` (Menunggu Konfirmasi - belum menahan kuota)
   - `status_dikonfirmasi` (Dikonfirmasi - menahan kuota ketersediaan)
   - `status_berjalan` (Sewa Berjalan - menahan kuota ketersediaan)
   - `status_selesai` (Selesai Sewa - kuota dilepas kembali)
   - `status_dibatalkan` (Dibatalkan - kuota dilepas)
   - Parameter keamanan: `public => false`, `exclude_from_search => true`, slug $\le 20$ karakter.
2. **Metabox Status Dropdown & Filter Anti-Reset (`includes/meta-boxes.php`):**
   - Dropdown status pemesanan dengan indikator warna visual dan keterangan efek kuota.
   - Filter `wp_insert_post_data` memastikan status kustom tidak ter-reset ke status default saat diedit dari antarmuka klasik WP-Admin.

---

## 5. Komponen yang Telah Diimplementasikan pada TASK-009
1. **Custom Post Type `penyewaan` (`includes/post-types.php`):**
   - Registrasi CPT internal untuk transaksi pemesanan dengan menu sidebar `dashicons-calendar-alt`.
   - Keamanan PII ketat (UU PDP): `public => false`, `publicly_queryable => false`, `show_in_rest => false` (mencegah akses atau kebocoran data pelanggan melalui REST API publik atau feed RSS).
   - Pemetaan hak akses RBAC: seluruh kapabilitas dipetakan ke `manage_ryokourent_bookings`.
2. **Metabox Data Pelanggan & Alokasi Plat (`includes/meta-boxes.php`):**
   - Panel Data Identitas Pelanggan (Nama, WhatsApp dengan tombol direct chat, kontak darurat, alamat KTP, tempat menginap).
   - Panel Rincian Armada, Jadwal Sewa, Total Tarif, dan Alokasi Plat Nomor Unit Fisik.


---

## 3. Komponen yang Telah Diimplementasikan pada TASK-008
1. **Template Single Post Motor (`wp-content/themes/generatepress-child/templates/single-motor.php` & `single-motor.php`):**
   - Header & breadcrumbs "Kembali ke Katalog Armada".
   - Media showcase & floating status badges (Tersedia / Booking Menipis / Penuh).
   - Spesifikasi teknis terstruktur: Kapasitas mesin (cc), transmisi, karakter rute, dan sistem bahan bakar.
   - Kotak edukasi & peringatan rute Bromo adaptif (peringatan larangan skutik ke pasir Bromo vs unit Trail CRF 150L resmi Bromo).
   - Fasilitas standar gratis (2 Helm SNI steril, 2 Jas Hujan setelan, phone holder stang, dan bantuan darurat jalan).
   - Sticky Pricing Sidebar: Tarif resmi harian dengan catatan toleransi overtime 2 jam, paket mingguan 7 hari, paket bulanan 30 hari.
   - Quick rental requirements: e-KTP asli, 2 dokumen pendukung, dan akun media sosial.
   - Dual CTA Action: Tombol booking cepat langsung memilih unit dan tombol Chat WhatsApp Admin terformat rapi.
2. **Filter Template Hierarchy (`wp-content/themes/generatepress-child/functions.php`):**
   - Hook `single_template` untuk resolusi otomatis template `templates/single-motor.php` dan fallback ke root child theme.
   - Enqueue dinamis stylesheet publik ketika post single motor dikunjungi.
3. **Styling Elegan Tema Gelap (`wp-content/themes/generatepress-child/style.css`):**
   - Layout grid 2 kolom desktop dengan sticky sidebar dan 1 kolom responsif pada smartphone.

---

## 4. Komponen yang Telah Diimplementasikan pada TASK-007
1. **Shortcode `[ryokou_catalog]` (`wp-content/plugins/ryokourent-core/public/shortcodes.php`):**
   - Mendaftarkan shortcode fleksibel dengan parameter `kategori`, `limit`, `show_filter`, dan `columns`.
   - Enqueue aset CSS dan JS secara terisolasi hanya pada halaman yang memuat katalog.
   - Localize script configuration (`ajaxUrl`, `waNumber`, `bookingAnchor`).
2. **Template Renderer Motor Card & Grid (`wp-content/plugins/ryokourent-core/public/templates.php`):**
   - Fungsi `ryokourent_render_motor_card()` dan `ryokourent_render_catalog_grid()`.
   - Menampilkan 7 armada resmi blueprint dengan fallback yang kokoh jika basis data WordPress belum terisi.
   - Format harga rapi (`Rp xx.xxx / 24 Jam` atau placeholder `Tanya Admin`).
   - Badges status ketersediaan (hijau, amber, merah) dan penanda rute Bromo.
   - Dual action button: "Sewa Sekarang" dan "Chat WA" dengan format pesan WhatsApp yang di-encode rapi.
   - Banner garansi 4 poin kepercayaan di bawah grid katalog.
3. **Desain Mobile-First & Filter Interaktif (`assets/css/ryokourent-public.css` & `assets/js/ryokourent-filter.js`):**
   - Tab filter pills kategori tanpa reload halaman (*zero jQuery, pure vanilla JS*).
   - Tampilan *empty state* interaktif dengan tombol reset filter.
   - Integrasi auto-select dropdown armada form booking saat tombol "Sewa Sekarang" diklik.

---

## 5. Komponen yang Telah Diimplementasikan pada TASK-006
1. **Taxonomy `kategori_motor` (`wp-content/plugins/ryokourent-core/includes/taxonomies.php`):**
   - Terdaftar secara hierarkis untuk CPT `motor` dengan slug `kategori-motor`.
   - Dukungan Block Editor Gutenberg (`show_in_rest => true`).
   - Proteksi kapabilitas: `manage_ryokourent_settings` untuk manipulasi term dan `edit_posts` untuk penugasan term.
2. **Seeding Kategori Default Idempoten:**
   - Term: `beat-series` (Honda BeAT Series), `scoopy-vario` (Honda Scoopy & Vario), dan `trail-adventure` (Trail Adventure (Bromo)).
   - Helper `ryokourent_get_motor_categories()` untuk kemudahan querying term di admin maupun frontend.

---

## 3. Komponen yang Telah Diimplementasikan pada TASK-005

1. **Metabox Spesifikasi & Karakter Armada (`ryokourent_motor_specs_metabox`):**
   - Field Kapasitas Mesin (`_ryokou_engine_cc`), Transmisi (`_ryokou_transmission`), Karakter Rute (`_ryokou_route_character`).
   - Checkbox Bromo Ready (`_ryokou_is_bromo_ready`) dengan pesan peringatan keras bahwa rute Bromo hanya untuk unit Trail CRF 150L.
   - Badge Status Publik (`_ryokou_status_label`: Tersedia, Booking Menipis, Penuh).
2. **Metabox Tarif Sewa Armada (`ryokourent_motor_pricing_metabox`):**
   - Field Tarif Harian 24 Jam (`_ryokou_price_daily`), Mingguan 7 Hari (`_ryokou_price_weekly`), Bulanan 30 Hari (`_ryokou_price_monthly`).
   - Proteksi hak akses: Hanya Administrator (`manage_ryokourent_settings` / `manage_options`) yang dapat mengedit tarif; untuk Operator field terkunci otomatis (*disabled*).
3. **Metabox Inventaris Unit Fisik & Plat Nomor (`ryokourent_motor_stock_metabox`):**
   - Total Unit Fisik (`_ryokou_physical_stock`) dan Daftar Plat Nomor Kendaraan (`_ryokou_plate_numbers`).
   - Tertutup dari REST API publik (`show_in_rest => false`), sanitasi pembersihan plat nomor huruf kapital per baris.
   - Proteksi hak akses Administrator (`manage_ryokourent_settings`).
4. **Keamanan & Guard Penyimpanan (`ryokourent_save_motor_meta_data`):**
   - Guard `DOING_AUTOSAVE`.
   - Verifikasi nonce `wp_verify_nonce($_POST['ryokourent_motor_meta_nonce'], 'ryokourent_save_motor_meta_action')`.
   - Validasi `post_type === 'motor'` dan `current_user_can('edit_post', $post_id)`.
   - Validasi hak akses khusus untuk field sensitif (harga & kuota fisik).

---

## 4. Komponen yang Telah Diimplementasikan pada TASK-004

1. **Custom Post Type `motor` (`includes/post-types.php`):**
   - Registrasi CPT `motor` dengan label bahasa Indonesia lengkap.
   - Dukungan fitur: `title`, `editor`, `thumbnail`, `excerpt`, `custom-fields`.
   - Konfigurasi `public => true`, `has_archive => 'motor'`, `show_in_rest => true` (Block Editor Gutenberg), menu icon `dashicons-car`, posisi menu 25.
   - Kustomisasi pesan pembaruan (`post_updated_messages`).

2. **Skema & Sanitasi Meta Fields (`includes/meta-fields.php`):**
   - Pendaftaran 10 meta fields resmi sesuai `DATA_MODEL.md` via `register_post_meta()`:
     - `_ryokou_engine_cc` (integer, sanitasi angka kapasitas)
     - `_ryokou_transmission` (whitelist: Otomatis CVT / Manual Kopling)
     - `_ryokou_route_character` (sanitasi string deskripsi)
     - `_ryokou_is_bromo_ready` (boolean 0/1 untuk proteksi armada Bromo)
     - `_ryokou_price_daily`, `_ryokou_price_weekly`, `_ryokou_price_monthly` (integer IDR)
     - `_ryokou_physical_stock` (kuota unit fisik internal)
     - `_ryokou_plate_numbers` (daftar plat multi-baris dinormalisasi)
     - `_ryokou_status_label` (whitelist: Tersedia / Booking Menipis / Penuh)
   - Capability checks pada callback autentikasi (`current_user_can('edit_post', $post_id)`).
   - Helper fungsi pembaca data: `ryokourent_get_motor_meta()` dan `ryokourent_is_motor_bromo_ready()`.

3. **Kustomisasi Kolom Admin List Table (`admin/motor-columns.php`):**
   - Penambahan kolom: Foto Thumbnail, Model Motor, Spesifikasi Mesin, Tarif Harian, Unit Fisik, Rute Bromo, Status Publik, dan Tanggal.
   - Escaping output ketat (`esc_html`, `esc_attr`, `esc_url`) pada semua data kolom.
   - Pengecekan capability `current_user_can('edit_posts')`.
   - Sortable columns untuk Tarif Harian dan Jumlah Unit Fisik dengan penanganan query `pre_get_posts`.

---

## 4. Batasan & Aturan Keamanan Terjaga
* **Prefix:** Seluruh fungsi dan hook menggunakan prefix `ryokourent_`.
* **Sanitasi & Escaping:** Setiap input disanitasi sebelum disimpan; setiap output diescape.
* **Audit REVIEW-ARCHITECTURE.md (Lolos 100%):**
  - **K1 & M9:** Role `ryokourent_operator` dan hak akses `manage_ryokourent_bookings` + `manage_ryokourent_settings` diatur aktif pada aktivasi dan `admin_init`.
  - **K2 & ADR-008:** Pengecekan 2 titik dan mekanisme atomic lock didokumentasikan di `DATA_MODEL.md` dan `DECISIONS.md`.
  - **K3:** Penanganan status kustom `public => false` dengan panjang slug $\le 20$ karakter disematkan ke metabox dan filter `wp_insert_post_data`.
  - **K4:** Meta fields internal `_ryokou_physical_stock` dan `_ryokou_plate_numbers` diproteksi `show_in_rest => false` dan respon AJAX ketersediaan hanya boolean.
  - **K5:** Kalkulasi durasi (24 jam + 2 jam grace period), operasional 07:00-23:00 WIB, dan penetapan harga dikunci mutlak di server backend.
  - **K6:** Snippet `functions.php` pada `BLUEPRINT.md` §7A ditandai *superseded* dan disatukan ke plugin `ryokourent-core`.
  - **M1 s/d M15:** Penanganan cache nonce, honeypot anti-spam, format `https://wa.me/`, penyesuaian bulk price, guard autosave, dan sinkronisasi struktur folder arsitektur telah selesai diperbarui tanpa ada yang terlewat.
* **File Terlindungi (Tidak Disentuh):**
  - `booking.php` (TIDAK DIUBAH)
  - `pricing.php` (TIDAK DIUBAH)
  - `availability.php` (TIDAK DIUBAH)
  - `whatsapp.php` (TIDAK DIUBAH)
* **Kompilasi & Build:** Build applet berhasil tanpa error.
