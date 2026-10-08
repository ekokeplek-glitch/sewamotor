# PANDUAN ADMINISTRATOR & PENGELOLA SISTEM
## RYOKOURENT — RENTAL MOTOR MALANG & BATU

Dokumen ini merupakan panduan teknis dan operasional untuk pemilik usaha (*business owner*), manajer operasional, dan staf IT yang memegang hak akses **Administrator** di website Ryokourent.

---

## DAFTAR ISI
1. [Struktur Hak Akses & Pembagian Peran (RBAC)](#1-struktur-hak-akses--pembagian-peran-rbac)
2. [Konfigurasi Kontak WhatsApp & Jam Operasional Pool](#2-konfigurasi-kontak-whatsapp--jam-operasional-pool)
3. [Manajemen Tarif Sewa & Penyesuaian Harga Massal (Peak Season)](#3-manajemen-tarif-sewa--penyesuaian-harga-massal-peak-season)
4. [Manajemen Armada Motor, Stok Unit Fisik & Plat Nomor](#4-manajemen-armada-motor-stok-unit-fisik--plat-nomor)
5. [Dashboard Operasional & Pemantauan Performa Armada](#5-dashboard-operasional--pemantauan-performa-armada)
6. [Pembatalan Pesanan oleh Admin (Cancel Booking with Reason)](#6-pembatalan-pesanan-oleh-admin-cancel-booking-with-reason)
7. [Manajemen Akun Staf & Onboarding Operator Baru](#7-manajemen-akun-staf--onboarding-operator-baru)
8. [Keamanan Sistem, Privasi Data (UU PDP) & Prosedur Cadangan (Backup)](#8-keamanan-sistem-privasi-data-uu-pdp--prosedur-cadangan-backup)

---

## 1. STRUKTUR HAK AKSES & PEMBAGIAN PERAN (RBAC)

Arsitektur Ryokourent menerapkan pemisahan tugas secara ketat (*Separation of Concerns* & *Least Privilege*) untuk mencegah kesalahan konfigurasi atau manipulasi tarif oleh staf non-pemilik:

| Fitur / Modul | Administrator | Operator Ryokourent | Pelanggan / Tamu Publik |
| :--- | :---: | :---: | :---: |
| **Lihat Katalog Motor Publik** | Ya | Ya | Ya |
| **Kirim Formulir Booking Online** | Ya | Ya | Ya |
| **Dashboard Operasional Rental** | Ya | Ya | Ditolak (403/Login) |
| **Lihat & Kelola Booking CPT `penyewaan`** | Ya | Ya | Ditolak (404/Login) |
| **Ubah Status Booking & Input Plat** | Ya | Ya | Ditolak |
| **Perpanjang Sewa (+24 Jam)** | Ya | Ya | Ditolak |
| **Batalkan Booking Resmi (Alasan)** | Ya | Ya | Ditolak (Hubungi WA) |
| **Tambah & Edit Armada Motor (`motor`)** | Ya | Ya | Ditolak |
| **Upload Foto Armada** | Ya | Ya | Ditolak |
| **Hapus Model Armada Motor** | **Ya (Eksklusif)** | **DILARANG** | Ditolak |
| **Kelola Kategori Motor (`kategori_motor`)** | **Ya (Eksklusif)** | **DILARANG** | Ditolak |
| **Pengaturan Kontak WA & Jam Pool** | **Ya (Eksklusif)** | **Ditolak (403)** | Ditolak |
| **Penyesuaian Harga Massal (Peak Season)** | **Ya (Eksklusif)** | **Ditolak (403)** | Ditolak |
| **Kelola User, Plugin & Tema WordPress** | **Ya (Eksklusif)** | **Ditolak** | Ditolak |

---

## 2. KONFIGURASI KONTAK WHATSAPP & JAM OPERASIONAL POOL

Pengaturan kontak resmi dan jam operasional dipusatkan pada submenu **Penyewaan Motor -> Pengaturan** (atau **Armada Motor -> Pengaturan**). Halaman ini dilindungi capability `manage_ryokourent_settings`.

### A. Nomor WhatsApp Resmi Perusahaan:
* **Nomor WhatsApp Utama:**
  - Format input: `08xxxxxxxxxx` atau `628xxxxxxxxxx` (10 – 15 digit angka).
  - Digunakan oleh generator tombol CTA "Chat Admin WA", tombol submit formulir pemesanan, dan floating mobile action bar di seluruh halaman web.
  - Sistem memvalidasi regex seluler Indonesia secara ketat sebelum menyimpan ke opsi `ryokourent_wa_number`.
* **Nomor WhatsApp Cadangan:**
  - Digunakan sebagai kontak darurat alternatif operasional jika nomor utama sedang mengalami gangguan.

### B. Jam Operasional Pool (Pelayanan Serah Terima Unit):
* **Jam Buka Pool:** Default `07:00` WIB.
* **Jam Tutup Pool:** Default `23:00` WIB.
* **Aturan Bisnis:** Jam tutup wajib lebih besar daripada jam buka. Validasi backend akan menolak jadwal pemesanan yang dimulai sebelum pukul 07:00 atau selesai setelah pukul 23:00 WIB.

### C. Tautan Lokasi Google Maps Pool:
* **Pool 1 Dinoyo (Malang Kota):** Tautan resmi Google Maps menuju Jl. MT Haryono Gg. 21 No. 23, Dinoyo, Lowokwaru, Kota Malang.
* **Pool 2 Batu (Kota Wisata Batu):** Tautan resmi Google Maps menuju Jl. Diponegoro No. 45, Sisir, Kec. Batu, Kota Wisata Batu.

---

## 3. MANAJEMEN TARIF SEWA & PENYESUAIAN HARGA MASSAL (PEAK SEASON)

Sistem Ryokourent mendukung kalkulasi harga harian, mingguan (diskon paket), dan bulanan dengan jaminan harga termurah (*best-rate guarantee*).

### A. Komponen Tarif pada Setiap Model Motor:
1. **Tarif Harian (24 Jam):** Tarif dasar sewa satu hari (contoh: BeAT Deluxe Rp 85.000, CRF 150L Rp 200.000).
2. **Tarif Mingguan (7 Hari):** Paket diskon 1 minggu (contoh: BeAT Rp 500.000, hemat Rp 95.000 dibanding 7 x Rp 85.000).
3. **Tarif Bulanan (30 Hari):** Paket jangka panjang (contoh: BeAT Rp 1.600.000, hemat Rp 950.000).

### B. Fitur Penyesuaian Harga Massal (Bulk Price Adjustment):
Menjelang musim liburan (*High Season / Peak Season* seperti Libur Lebaran Idul Fitri, Libur Natal & Tahun Baru, atau Libur Sekolah), admin dapat mengubah harga seluruh atau sebagian armada secara serentak dalam hitungan detik tanpa harus mengedit satu per satu motor.

#### Langkah Eksekusi di WP-Admin:
1. Buka menu **Penyewaan Motor -> Pengaturan** -> klik tab **"Penyesuaian Tarif Massal"**.
2. **Pilih Kategori Motor:**
   - Seluruh Armada Motor (`'all'`).
   - Honda BeAT Series (`beat-series`).
   - Honda Scoopy & Vario (`scoopy-vario`).
   - Trail Adventure Bromo (`trail-adventure`).
3. **Pilih Paket Tarif yang Disesuaikan:**
   - Semua Paket (Harian, Mingguan, Bulanan).
   - Paket Harian Saja.
   - Paket Mingguan Saja.
   - Paket Bulanan Saja.
4. **Tentukan Tipe Perubahan:**
   - **Kenaikan Persentase (%):** Contoh `+20%` untuk high season liburan.
   - **Kenaikan Nominal (Rp):** Contoh `+15000` (kenaikan Rp 15.000 per hari).
   - **Penurunan Persentase (%):** Contoh `-10%` untuk promo low season.
   - **Penurunan Nominal (Rp):** Contoh `-10000`.
5. **Klik "Terapkan Penyesuaian Harga Massal"**.

#### Mekanisme Keamanan Perlindungan Tarif (*Two-Pass Fail-Safe Rollback*):
* Sistem menerapkan algoritma simulasi dua tahap:
  - **Tahap 1 (Simulasi Seluruh Unit):** Sistem menghitung harga baru untuk seluruh unit armada yang terdampak.
  - **Batas Nilai Aman:** Kenaikan/penurunan persentase dibatasi ketat dalam rentang aman $-50\%$ hingga $+200\%$. Perhitungan persentase otomatis dibulatkan ke kelipatan seribu Rupiah terdekat.
  - **Atomic Rollback:** Jika terdapat **1 unit armada saja** yang menghasilkan harga tidak valid ($\le 0$ atau menghasilkan error), seluruh transaksi dibatalkan seketika (*fail-safe atomic rollback*). Tidak ada data yang tersimpan setengah-setengah.
  - **Tahap 2 (Eksekusi Nyata):** Jika seluruh unit lolos validasi, nilai baru disimpan secara atomik ke database.

---

## 4. MANAJEMEN ARMADA MOTOR, STOK UNIT FISIK & PLAT NOMOR

Pengelolaan unit fisik dilakukan melalui Custom Post Type **Armada Motor** (`motor`).

### A. Menambahkan Model Motor Baru:
1. Buka menu **Armada Motor -> Tambah Motor Baru**.
2. Masukkan Judul Model Motor (contoh: `Honda PCX 160 ABS`).
3. Tulis deskripsi fasilitas dan karakter motor pada editor utama.
4. Unggah **Foto Utama Motor** (resolusi optimal WebP/JPG, rasio aspek 4:3 atau 16:9).
5. Centang kategori motor yang sesuai (contoh: `Honda Scoopy & Vario`).
6. Lengkapi kotak metabox:
   - **Spesifikasi Teknis:** Kapasitas mesin (cc), jenis transmisi (Otomatis CVT / Manual Kopling), karakter rute jalan.
   - **Kesiapan Rute Bromo:** Centang kotak "Siap Rute Kaldera Bromo" **HANYA JIKA UNIT ADALAH HONDA CRF 150L**. Motor matik dilarang dicentang bromo ready!
   - **Badge Status Publik:** Pilih "Tersedia", "Booking Menipis", atau "Penuh".
   - **Tarif Sewa:** Masukkan tarif Harian, Mingguan, dan Bulanan.

### B. Inventaris Kuota Fisik & Daftar Plat Nomor Kendaraan:
Di panel metabox **Inventaris Unit Fisik & Plat Nomor Kendaraan**:
1. **Total Unit Fisik (`_ryokou_physical_stock`):**
   - Masukkan angka bulat riil unit motor fisik yang dimiliki perusahaan (contoh: `5`).
   - Angka ini digunakan oleh mesin availability backend untuk menghitung batas maksimal pemesanan online.
   - **Privasi:** Nilai ini tersimpan aman di database dan **TIDAK PERNAH DIBOCORKAN KE FRONTEND PUBLIK MAUPUN REST API** (`show_in_rest => false`).
2. **Daftar Plat Nomor Kendaraan (`_ryokou_plate_numbers`):**
   - Masukkan daftar plat nomor kendaraan fisik, **satu nomor plat per baris**.
   - Contoh:
     ```text
     N 4512 AB
     N 4513 CD
     N 4514 EF
     N 4515 GH
     N 4516 IJ
     ```
   - Sistem secara otomatis membersihkan spasi berlebih dan menormalisasi teks ke huruf kapital.
   - Daftar plat ini menjadi sumber data pilihan saat operator mengalokasikan unit sebelum sewa berjalan.

---

## 5. DASHBOARD OPERASIONAL & PEMANTAUAN PERFORMA ARMADA

Submenu **Penyewaan Motor -> Dashboard** menyajikan ringkasan metrik waktu-nyata (*real-time operational overview*) bagi manajemen.

### A. Tiga Metrik Utama:
1. **Unit Disewa Hari Ini:**
   - Jumlah pesanan dengan status `status_dikonfirmasi` dan `status_berjalan` yang rentang tanggalnya aktif menyentuh hari ini dalam zona waktu resmi `Asia/Jakarta` (WIB).
2. **Booking Menunggu Konfirmasi:**
   - Jumlah calon pesanan baru berstatus `status_menunggu` yang memerlukan respon segera oleh tim CS (< 5 menit).
3. **Unit Aktif per Pool:**
   - Sebaran unit yang sedang berstatus `status_berjalan` berdasarkan lokasi penjemputan/pool resmi:
     * Pool 1 Dinoyo (Malang Kota)
     * Pool 2 Batu (Kota Wisata Batu)
     * Titik Antar Lainnya (Stasiun/Hotel)

### B. Optimalisasi Cache Transient Performa:
* Statistik dashboard disimpan dalam cache WordPress transient `ryokourent_dashboard_stats` selama 5 menit untuk mencegah beban kueri berat pada server hosting.
* **Auto-Purge Cache:** Cache otomatis dibuang dan diperbarui seketika setiap kali ada transaksi yang berganti status (`transition_post_status`), transaksi baru dibuat, atau transaksi dihapus.

---

## 6. PEMBATALAN PESANAN OLEH ADMIN (CANCEL BOOKING WITH REASON)

Demi ketertiban pembukuan dan ketersediaan kuota, pembatalan pesanan hanya dapat dieksekusi secara resmi melalui panel admin.

### A. Kebijakan Pembatalan:
* Fitur pembatalan mandiri di sisi publik sengaja ditiadakan; pelanggan diarahkan mengabari pembatalan melalui WhatsApp resmi.
* Pembatalan hanya sah dilakukan **sebelum unit motor diserahkan** (status berada pada tahap `Menunggu Konfirmasi` atau `Dikonfirmasi`). Unit yang sedang berstatus `Sewa Berjalan` tidak dapat dibatalkan, melainkan harus diproses melalui pengembalian unit awal.

### B. Prosedur Eksekusi di WP-Admin:
1. Buka menu **Penyewaan Motor** -> klik pesanan yang ingin dibatalkan.
2. Klik tombol **"Cancel Booking"**.
3. Masukkan catatan alasan pembatalan pada kolom yang tersedia (contoh: *"Pelanggan membatalkan tiket kereta api karena kendala keluarga"* atau *"Gagal verifikasi identitas e-KTP"*).
4. Konfirmasi pembatalan.
5. **Efek Sistem:**
   - Status pesanan berganti ke `status_dibatalkan`.
   - Kuota unit motor fisik seketika dilepaskan kembali ke pool ketersediaan online.
   - Alasan pembatalan tersimpan permanen di meta `_ryokou_cancellation_reason` untuk audit manajemen.

---

## 7. MANAJEMEN AKUN STAF & ONBOARDING OPERATOR BARU

Setiap staf baru wajib dibuatkan akun terpisah dan dilarang berbagi (*sharing*) password akun Administrator.

### A. Membuat Akun Operator Baru:
1. Buka menu **Pengguna (Users) -> Tambah Baru (Add New)**.
2. Isi Username (contoh: `operator.dinoyo`), Email resmi staf, Nama Lengkap.
3. Buat password yang kuat (minimal 12 karakter kombinasi huruf besar, kecil, angka, dan simbol).
4. Pada pilihan **Peranan (Role)**: Pilih **Operator Ryokourent**.
5. Klik **Tambah Pengguna Baru**.

### B. Prosedur Offboarding Staf:
* Jika staf operator mengundurkan diri atau dimutasi, akun wajib segera dinonaktifkan:
  1. Ubah role akun menjadi `Subscriber` atau hapus akun pengguna.
  2. Jika dihapus, pilih opsi atribusikan posting/data sewa ke akun Administrator agar riwayat transaksi tidak hilang.

---

## 8. KEAMANAN SISTEM, PRIVASI DATA (UU PDP) & PROSEDUR CADANGAN (BACKUP)

### A. Kepatuhan Undang-Undang Perlindungan Data Pribadi (UU PDP No. 27/2022):
* Seluruh data transaksi CPT `penyewaan` memuat Data Pribadi Spesifik (Nama Lengkap, Nomor Induk Kependudukan e-KTP, Nomor WhatsApp Pribadi, Kontak Darurat Keluarga, Alamat KTP, Tempat Menginap).
* **Perlindungan Teknis Sistem:**
  - CPT `penyewaan` dikonfigurasi: `public => false`, `publicly_queryable => false`, `show_in_rest => false`, `exclude_from_search => true`. Data ini tidak dapat diindeks oleh Google, tidak dapat dibuka lewat URL publik, dan tidak dapat dibaca lewat REST API publik tanpa token login khusus.
* **Kewajiban Pengelola:** Dilarang mengunduh dan menyebarkan database pelanggan tanpa enkripsi.

### B. Prosedur Pencadangan (Backup Routine):
1. **Jadwal Backup Otomatis:**
   - Basis data MySQL: **Wajib dicadangkan setiap hari** (daily backup pada pukul 02:00 WIB saat traffic rendah).
   - Berkas media WordPress (`wp-content/uploads`): Dicadangkan mingguan.
2. **Penyimpanan Cadangan:**
   - Simpan berkas backup di cloud terpisah (Google Drive / S3 / Remote Storage), bukan di server hosting yang sama.
3. **Uji Pemulihan (Disaster Recovery):**
   - Lakukan uji restore berkas cadangan minimal sekali dalam 3 bulan di lingkungan staging lokal (XAMPP).

---
*Dokumen ini diterbitkan oleh Tim Manajemen Ryokourent Malang & Batu. Wajib menjadi rujukan utama seluruh pengelolaan sistem rental motor.*
