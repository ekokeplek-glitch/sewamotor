# TESTING SCENARIOS & QUALITY ASSURANCE: RYOKOURENT

Dokumen ini memuat skenario pengujian komprehensif dari tahap pengembangan awal hingga kesiapan produksi untuk sistem rental motor Ryokourent.

---

## 1. Lingkup & Sasaran Pengujian
1. **Integritas Plugin & Theme:** Plugin aktif tanpa fatal error atau notice.
2. **Keamanan & Otorisasi:** Nonce validation, sanitasi input, escaping output, proteksi akses role Operator vs Admin.
3. **Logika Bisnis & Validasi:**
   * Perhitungan durasi sewa akurat.
   * Perhitungan tarif harian, mingguan, dan bulanan akurat.
   * Pencegahan pemilihan tanggal masa lalu / jam di luar operasional (07.00 - 23.00 WIB).
   * Validasi armada Bromo (kewajiban Trail CRF 150L).
   * Validasi nomor WhatsApp & kontak darurat.
   * Pengecekan ketersediaan kuota unit & pencegahan *double booking*.
4. **Generator WhatsApp:** Format draf pesan rapi, data lengkap, dan tautan `wa.me` valid.
5. **Responsivitas & UI/UX Mobile:** Tampilan bebas overflow pada viewport smartphone, tombol sentuh ergonomis.

---

## 2. Tabel Matriks Pengujian Skenario

| ID | Skenario | Input Uji | Hasil yang Diharapkan | Hasil Aktual | Status | Catatan |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **TC-001** | Aktivasi Plugin Core | Aktifkan `ryokourent-core` di WP-Admin | Plugin aktif tanpa pesan error atau warning PHP | Plugin teraktivasi sukses, role operator & capability admin tersinkronisasi, rewrite flush tereksekusi tanpa PHP notice/warning | **PASSED** | Teruji via `test-qa-matrix.php` & lifecycle tests |
| **TC-002** | Registrasi CPT Motor | Buka WP-Admin -> Armada Motor | Menu muncul dengan ikon mobil/motor, form input motor siap | CPT `motor` terdaftar dengan `dashicons-car`, posisi menu 25, `has_archive => 'motor'`, dan dukungan custom-fields | **PASSED** | Verifikasi upload featured image armada & Gutenberg REST aktif |
| **TC-003** | Penyimpanan Data Teknis Motor | Isi spesifikasi cc, transmisi, harga harian/mingguan/bulanan, stok fisik | Data tersimpan utuh di post meta dan tampil kembali saat diedit | Data teknis cc, transmisi, harga IDR, stok fisik, dan plat nomor tersimpan utuh di post meta dengan verifikasi nonce | **PASSED** | Sanitasi integer, teks, dan array plat per baris terverifikasi |
| **TC-004** | Proteksi Data Kuota Fisik di Frontend | Buka katalog publik dan view-source HTML | Stok fisik (angka) dan plat nomor tidak muncul di DOM publik | Angka unit fisik dan plat nomor tidak pernah dirender ke HTML publik maupun REST publik (`show_in_rest => false`) | **PASSED** | Hanya badge status "Tersedia" / "Menipis" / "Penuh" yang tampil |
| **TC-005** | Filter Kategori Katalog | Klik tab "BeAT Series", "Scoopy-Vario", "Trail Adventure" | Grid motor memfilter unit secara instan tanpa reload halaman | Filter vanilla JS instan menyembunyikan/menampilkan kartu motor sesuai data-category tanpa lag atau reload halaman | **PASSED** | Diuji pada mobile viewport (390px) & desktop grid |
| **TC-006** | Validasi Input Wajib Form Booking | Submit form dalam keadaan seluruh field kosong | Form menolak submit, border merah pada field wajib, pesan peringatan | Pengiriman diblokir di sisi client (HTML5/JS) dan backend (`ryokourent_validate_booking_submission`), mengembalikan 400 Bad Request | **PASSED** | Highlight field merah `.has-error` dan scroll ke input pertama |
| **TC-007** | Validasi Format Nomor WhatsApp | Input No. WA: `12345` atau `abcdefgh` | Ditolak dengan pesan: "Nomor WhatsApp tidak valid (Gunakan format 08xx / 62xx)" | Nomor diuji regex `^(08\|628)[0-9]{8,12}$`; input non-standar ditolak dengan kode `invalid_phone_format` | **PASSED** | Menerima format standar seluler Indonesia (08xx / 628xx) |
| **TC-008** | Validasi Kontak Darurat Unik | Input No. Darurat sama persis dengan No. WhatsApp pelanggan | Ditolak dengan pesan: "Kontak darurat harus berbeda dari nomor penyewa" | Sistem membandingkan string nomor; nomor yang sama ditolak dengan kode `emergency_phone_same` | **PASSED** | Memastikan kontak darurat keluarga benar-benar terpisah |
| **TC-009** | Validasi Tanggal Sewa Kronologis | Set Tanggal Selesai lebih awal dari Tanggal Mulai | Ditolak dengan pesan: "Tanggal selesai tidak boleh mendahului tanggal mulai" | Ditolak seketika oleh `ryokourent_validate_rental_schedule` dengan kode `end_before_start`; input min di JS otomatis terkunci | **PASSED** | Durasi minimum 1 jam ditegakkan |
| **TC-010** | Validasi Jam Operasional Pool | Pilih jam sewa: `03:00 WIB` atau `23:45 WIB` | Ditolak dengan pesan: "Layanan sewa & ambil unit hanya pukul 07.00 - 23.00 WIB" | Jam di luar rentang 07:00–23:00 WIB ditolak dengan kode `invalid_operating_hours` | **PASSED** | Selaras dengan jam operasional resmi Pool Dinoyo & Pool Batu |
| **TC-011** | Aturan Wajib Bromo untuk Skutik | Pilih rute "Kaldera Bromo" dengan motor "Honda BeAT" | Muncul peringatan keras larangan matik ke Bromo dan otomatis merekomendasikan Trail CRF 150L | UI mengunci dropdown hanya ke Trail CRF 150L; banner alert merah ARIA muncul mengedukasi bahaya transmisi CVT di pasir Bromo | **PASSED** | Sesuai ADR-004 demi keselamatan wisatawan |
| **TC-012** | Kalkulator Durasi Sewa Otomatis | Mulai: 02/10 08:30, Selesai: 04/10 17:00 | Label menampilkan: "Estimasi Durasi: 3 Hari (~57 Jam)" | Durasi 56.5 jam terhitung tepat 3 hari tagihan dengan label "3 Hari (~56.5 Jam)" memperhitungkan toleransi overtime 2 jam | **PASSED** | Toleransi overtime proporsional (24 jam + 2 jam grace period) |
| **TC-013** | Kalkulasi Tarif Sewa Harian | BeAT Deluxe (Rp 85.000/hari) x 3 hari sewa | Estimasi Total Biaya menampilkan: "Rp 255.000" | Kalkulasi server-side menghasilkan Rp 255.000 dengan format `ryokourent_format_rupiah` (titik ribuan) | **PASSED** | Server-authoritative: client dilarang manipulasi total harga |
| **TC-014** | Pengecekan Ketersediaan Unit (Stok Tersedia) | Pesan unit Vario 160 (stok fisik 5, booking aktif 2) | Form menyetujui, tombol booking aktif, status ketersediaan lolos | Kueri overlap mendeteksi 2 booking aktif pada stok 5; mengembalikan `available: true` | **PASSED** | Kueri efisien `fields => ids` & `no_found_rows => true` |
| **TC-015** | Pencegahan Double Booking (Stok Penuh) | Pesan unit CRF 150L (stok fisik 3, booking aktif 3 pada tanggal sama) | Submit diblokir dengan pesan: "Unit penuh pada tanggal tersebut" | Atomic lock memblokir pemesanan dengan pesan error `unit_fully_booked` (HTTP 400); operator dicegah konfirmasi jika kuota penuh | **PASSED** | Proteksi 2 titik (submit online & perubahan status operator) |
| **TC-016** | Penyimpanan CPT Penyewaan via AJAX | Klik "Kirim Pesanan ke WhatsApp" | Post baru terbuat pada CPT `penyewaan` dengan status `status_menunggu` dan kode `RYK-...` | Transaksi CPT `penyewaan` tersimpan dengan kode format `RYK-YYYYMMDD-XXXX`, status `status_menunggu`, dan seluruh PII tersimpan rapi | **PASSED** | Dilindungi nonce, honeypot spam bot, dan rate limit IP |
| **TC-017** | Format & Encoding Pesan WhatsApp | Verifikasi link URL redirect WhatsApp | Pesan rapi berformat teks blueprint, emotikon utuh, URL ter-encode dengan benar | Format pesan terstruktur dengan emoji (🛵, 📋, 👤, 🔒), kode booking tertera, URL `https://wa.me/` memakai `rawurlencode` RFC 3986 | **PASSED** | Tidak ada karakter terpotong di browser mobile / desktop |
| **TC-018** | Pembatasan Akses Role Operator | Login sebagai akun Operator, akses halaman Pengaturan Tarif | Akses ditolak (403 Forbidden / "Anda tidak memiliki wewenang") | Akses menu Pengaturan Tarif ditolak dengan HTTP 403 Forbidden via `ryokourent_check_settings_permission_or_die()`; tombol hapus motor & kelola kategori tersembunyi | **PASSED** | Hak operator terisolasi ke armada motor & booking sewa |
| **TC-019** | Perubahan Status Booking oleh Operator | Ubah status dari "Menunggu" -> "Dikonfirmasi" dan masukkan plat motor | Status berubah warna jadi biru, plat nomor tersimpan di meta booking | Quick actions terlindungi nonce per ID; plat nomor divalidasi ke inventaris unit dan jadwal bentrok via `ryokourent_validate_allocated_plate()` | **PASSED** | Quick action admin list table & metabox status tersinkron |
| **TC-020** | Audit Responsivitas & Thumb-Zone Mobile | Buka web di smartphone (layar 375px - 414px) | Floating bar < 15% layar, tombol WA mudah dijangkau jempol, tidak ada horizontal scroll | Floating mobile bar fixed bottom dengan tinggi 64px (< 15% viewport), tombol sentuh >= 48px, padding thumb-zone aman | **PASSED** | Lolos uji responsivitas mobile-first |

---

## 3. Tahapan Pengujian Produksi (Pre-Launch Checklist)
* [ ] Konfigurasi timezone WordPress diatur ke `Asia/Jakarta` (WIB, UTC+7).
* [ ] Permalink diatur ke format `/post-name/`.
* [ ] Mode `WP_DEBUG` dimatikan (`false`) dan `WP_DEBUG_LOG` dimatikan di production.
* [ ] Nomor WhatsApp resmi Ryokourent telah diperbarui di halaman pengaturan admin.
* [ ] 7 model motor telah diinput dengan spesifikasi dan foto asli resolusi optimal (WebP).
* [ ] Kuota unit fisik awal dan daftar plat nomor motor telah diverifikasi oleh tim operasional.
* [ ] Akun staf operator telah dibuat dengan password kuat dan role `Ryokou Operator`.
* [ ] Sertifikat SSL aktif (HTTPS) dan redirect HTTP -> HTTPS berjalan lancar.
