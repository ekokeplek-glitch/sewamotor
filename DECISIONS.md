# ARCHITECTURAL DECISION RECORDS (ADR): RYOKOURENT

Dokumen ini mencatat keputusan-keputusan arsitektural penting yang telah disepakati untuk proyek Ryokourent beserta latar belakang, konsekuensi, dan alasannya.

---

### ADR-001: Pemisahan Logika Bisnis ke Plugin `ryokourent-core`
* **Status:** Diterima (Accepted)
* **Konteks:** Seringkali dalam pengembangan WordPress, developer menumpuk seluruh kode CPT, metabox, dan perhitungan logika di `functions.php` tema.
* **Keputusan:** Seluruh CPT (`motor`, `penyewaan`), taksonomi, algoritma ketersediaan armada, kalkulasi harga, dan handler WhatsApp diletakkan di dalam plugin custom `ryokourent-core`. Tema GeneratePress child hanya mengatur gaya visual (CSS) dan layout template.
* **Alasan:** Memastikan integritas data dan logika bisnis tetap utuh jika sewaktu-waktu tema diganti atau diperbarui.

---

### ADR-002: Model Pemesanan Zero-Friction Berbasis WhatsApp (Tanpa Payment Gateway Awal)
* **Status:** Diterima (Accepted)
* **Konteks:** Bisnis rental motor lokal di Indonesia (khususnya Malang & Batu) sangat mengandalkan verifikasi identitas personal (e-KTP asli, tiket kereta/pesawat, akun media sosial aktif) untuk mencegah penggelapan dan pencurian kendaraan.
* **Keputusan:** Sistem booking web tidak menggunakan *checkout payment gateway* langsung pada tahap awal. Formulir web menghasilkan draf pesanan WhatsApp resmi yang terstruktur dan langsung menghubungkan penyewa ke nomor resmi admin via `wa.me`, sekaligus mencatat data booking ke CPT `penyewaan` dengan status `status_menunggu`.
* **Alasan:** Mengurangi friksi pendaftaran bagi pengguna seluler, memberikan fleksibilitas negosiasi jam antar-jemput sesuai sikon, serta memfasilitasi verifikasi dokumen identitas secara langsung dan aman oleh operator.

---

### ADR-003: Kerahasiaan Kuota Unit Fisik dan Plat Nomor di Sisi Publik
* **Status:** Diterima (Accepted)
* **Konteks:** Bisnis rental perlu menampilkan kesan profesional tanpa mengekspos jumlah persis armada yang dimiliki kepada kompetitor atau publik.
* **Keputusan:** Jumlah unit fisik dan daftar plat nomor motor disimpan di meta post CPT `motor` yang hanya dapat diakses oleh user ber-role `administrator` dan `operator`. Di katalog publik, status ketersediaan hanya ditampilkan dalam bentuk label kualitatif (`Tersedia`, `Booking Menipis`, atau `Penuh`).
* **Alasan:** Menjaga kerahasiaan strategi bisnis dan privasi aset armada perusahaan.

---

### ADR-004: Penanganan Khusus Rute Bromo (Kewajiban Trail CRF 150L)
* **Status:** Diterima (Accepted)
* **Konteks:** Medan pasir berbisik dan tanjakan ekstrem di kawasan Gunung Bromo kerap merusak transmisi CVT motor matik dan menimbulkan risiko kecelakaan fatal bagi wisatawan.
* **Keputusan:** Sistem website secara eksplisit melarang penggunaan seluruh jenis motor matik (BeAT, Scoopy, Vario) untuk rute Bromo. Form booking menyertakan validasi: jika rute yang dipilih adalah Bromo, pilihan armada dikunci hanya untuk Honda Trail CRF 150L.
* **Alasan:** Keselamatan jiwa penyewa, perlindungan armada dari kerusakan fatal, dan kepatuhan terhadap aturan keselamatan berkendara di kawasan taman nasional.

---

### ADR-005: Pemilihan GeneratePress & JavaScript Vanilla Ringan
* **Status:** Diterima (Accepted)
* **Konteks:** Sebagian besar wisatawan mengakses website rental melalui koneksi internet 4G smartphone saat sedang dalam perjalanan.
* **Keputusan:** Menggunakan GeneratePress sebagai tema induk dengan child theme, dipadukan dengan JavaScript vanilla murni untuk form handler, live preview WhatsApp, dan filter katalog. Menghindari ketergantungan jQuery berat dan page builder kompleks (seperti Elementor).
* **Alasan:** Menjamin waktu muat halaman *under 1.5 seconds* (PageSpeed score 95-100) dan konsumsi data seluler yang sangat hemat.

---

### ADR-006: Role-Based Access Control (RBAC) Khusus Operator
* **Status:** Diterima (Accepted)
* **Konteks:** Staf operasional lapangan hanya bertugas memproses booking dan memantau ketersediaan armada, bukan mengelola pengaturan website atau mengubah tarif rental.
* **Keputusan:** Membuat role baru `ryokourent_operator` dengan capability `manage_ryokourent_bookings`. Role ini tidak memiliki hak akses ke menu tema, plugin settings, atau manajemen pengguna lain. Administrator diberikan capability `manage_ryokourent_bookings` dan `manage_ryokourent_settings`.
* **Alasan:** Mencegah perubahan konfigurasi yang tidak disengaja dan meningkatkan keamanan sistem operasional.

---

### ADR-007: Penyimpanan Data Identitas Pelanggan Tanpa Upload Dokumen Fisik di Server
* **Status:** Diterima (Accepted)
* **Konteks:** Menyimpan foto e-KTP dan kartu identitas pelanggan di direktori publik `wp-content/uploads/` berisiko tinggi terhadap kebocoran data pribadi (UU PDP).
* **Keputusan:** Formulir web hanya mencatat data teks (Nama, Alamat KTP, Tempat Menginap, No. HP, Kontak Darurat, Akun Medsos). Foto fisik dokumen identitas dikirimkan langsung oleh pelanggan melalui chat WhatsApp yang terenkripsi *end-to-end* kepada admin.
* **Alasan:** Mematuhi prinsip perlindungan privasi data pribadi dan menghindari kerentanan kebocoran file dokumen di server hosting.

---

### ADR-008: Validasi Server-Side Mutlak & Pencegahan Race Condition Double Booking
* **Status:** Diterima (Accepted)
* **Konteks:** Perhitungan harga, jam operasional, dan durasi di sisi frontend (JavaScript) rentan dimanipulasi melalui browser DevTools. Selain itu, pengecekan ketersediaan armada rentan race condition jika dua pelanggan memesan armada terakhir secara bersamaan atau saat admin mengonfirmasi pesanan.
* **Keputusan:**
  1. Server selalu menghitung ulang durasi, memvalidasi jam operasional (07:00-23:00 WIB), dan menentukan total tarif secara mutlak di backend; nilai harga dari client diabaikan.
  2. Pengecekan ketersediaan kuota dilakukan di dua titik: saat submit pesanan awal dan saat status diubah menjadi `status_dikonfirmasi` oleh admin.
  3. Proteksi atomic lock (misal `GET_LOCK` MySQL atau transient lock per model motor) dipasang pada jalur kritis pemesanan dan konfirmasi.
  4. Seluruh meta internal motor (`_ryokou_physical_stock` dan `_ryokou_plate_numbers`) dinonaktifkan dari REST API publik (`show_in_rest => false`) untuk mencegah kebocoran data armada.
* **Alasan:** Menjamin integritas finansial, keakuratan jadwal operasional, dan perlindungan privasi inventaris armada dari scraping publik.

---

### ADR-009: Manajemen Custom Post Status Booking via Metabox Terisolasi & Filter `wp_insert_post_data`
* **Status:** Diterima (Accepted)
* **Konteks:** Sesuai tinjauan arsitektur (reviewOP K3), custom post status WordPress tidak otomatis muncul di dropdown edit post klasik dan berisiko tereset ke `draft` jika disimpan tanpa antarmuka khusus. Mendaftarkan status secara publik juga berisiko membocorkan data PII pemesanan.
* **Keputusan:**
  1. Seluruh custom post status (`status_menunggu`, `status_dikonfirmasi`, `status_berjalan`, `status_selesai`, `status_dibatalkan`) didaftarkan dengan `public => false` dan `exclude_from_search => true`.
  2. Disediakan dropdown selektor status terisolasi di metabox sisi samping CPT `penyewaan`.
  3. Status disimpan dan dipertahankan secara persisten melalui filter `wp_insert_post_data` yang dilindungi verifikasi nonce dan capability check `manage_ryokourent_bookings`.
* **Alasan:** Menjamin integritas status pesanan sewa, mencegah reset status tidak disengaja oleh editor WordPress standar, dan melindungi data privasi pelanggan.

---

### ADR-010: Validasi Alokasi Plat Nomor Unit Fisik & Pencegahan Konflik Plat Ganda
* **Status:** Diterima (Accepted)
* **Konteks:** Sesuai tinjauan arsitektur (reviewOP M6), operator dapat salah mengalokasikan plat nomor yang bukan milik model motor bersangkutan atau mengalokasikan satu unit fisik yang sama ke dua booking berbeda pada rentang waktu bersamaan.
* **Keputusan:**
  1. Dibuat fungsi validasi `ryokourent_validate_allocated_plate()` yang memverifikasi kecocokan plat terhadap inventaris unit fisik model motor terkait (`_ryokou_plate_numbers`).
  2. Memeriksa ketiadaan konflik plat nomor pada seluruh booking aktif (`status_dikonfirmasi`, `status_berjalan`) yang bertabrakan jadwal sewanya.
  3. Jika terdeteksi konflik atau ketidakcocokan plat, sistem memunculkan flash warning notice di admin tanpa merusak alur penyimpanan.
* **Alasan:** Menjamin integritas logistik operasional armada di lapangan dan mencegah komplikasi serah terima unit di pool/stasiun.

---

### ADR-011: Matriks Transisi Status Booking, Quick Action Terlindungi, dan Validasi Plat Serah Terima Unit
* **Status:** Diterima (Accepted)
* **Konteks:** Sesuai tinjauan arsitektur (reviewOP K2, M6, M7), alur operasional rental motor membutuhkan transisi status yang teratur dan aman: Menunggu -> Dikonfirmasi -> Berjalan -> Selesai -> Dibatalkan. Transisi langsung tanpa aturan berisiko menyebabkan lompatan status tidak sah (misal langsung Menunggu ke Selesai), pembatalan saat unit sudah di jalan, atau alokasi plat nomor fiktif/ganda saat serah terima. Selain itu, pengecekan kuota wajib dilakukan ulang secara atomik saat status diubah ke `status_dikonfirmasi`.
* **Keputusan:**
  1. Menetapkan matriks transisi resmi via `ryokourent_get_status_transitions()`:
     - `status_menunggu` -> `status_dikonfirmasi`, `status_dibatalkan`
     - `status_dikonfirmasi` -> `status_berjalan`, `status_dibatalkan`
     - `status_berjalan` -> `status_selesai`
     - `status_selesai` -> terminal/final
     - `status_dibatalkan` -> terminal/final
  2. Menyediakan tombol quick action di kolom tabel admin CPT `penyewaan` (`admin/booking-columns.php`) yang dilindungi nonce spesifik per booking (`ryokourent_status_<id>`), capability check `manage_ryokourent_bookings`, dan whitelist status.
  3. Saat transisi ke `status_dikonfirmasi`, sistem mengecek ketersediaan kuota unit secara atomik di dalam lock `ryokourent_with_motor_lock()`, dengan mengecualikan booking itu sendiri (`post__not_in`). Jika kuota penuh, transisi ditolak dengan kode `quota_full`.
  4. Saat transisi ke `status_berjalan` (serah terima), plat nomor wajib diisi, dinormalisasi (huruf kapital, spasi tunggal, tanpa simbol), dan divalidasi dengan `ryokourent_validate_allocated_plate()` di dalam lock.
  5. Seluruh aturan transisi dan validasi plat diterapkan seragam baik melalui tombol quick action maupun melalui filter dropdown metabox (`wp_insert_post_data`).
  6. Perubahan status memicu hook `transition_post_status` yang secara otomatis membuang cache transient statistik dashboard (`ryokourent_dashboard_stats`).
* **Alasan:** Menjamin alur operasional armada motor berjalan tertib, aman, bebas overbooking race condition, dan bebas kesalahan alokasi plat nomor di lapangan.

