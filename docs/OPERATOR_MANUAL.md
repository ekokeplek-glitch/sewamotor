# BUKU PANDUAN OPERATOR & STANDAR OPERASIONAL PROSEDUR (SOP)
## RYOKOURENT — RENTAL MOTOR MALANG & BATU

Dokumen ini merupakan panduan operasional resmi untuk staf dan staf lapangan (Customer Service, Kasir, dan Petugas Pool) yang memiliki akun dengan peran **Operator Ryokourent**. Seluruh prosedur wajib dipatuhi demi menjamin kepuasan pelanggan, keselamatan berkendara, akurasi administrasi, dan perlindungan aset armada.

---

## DAFTAR ISI
1. [Peran, Wewenang & Batasan Akun Operator](#1-peran-wewenang--batasan-akun-operator)
2. [SOP 1: Respon Cepat Konfirmasi WhatsApp (< 5 Menit)](#2-sop-1-respon-cepat-konfirmasi-whatsapp--5-menit)
3. [SOP 2: Verifikasi Ketat Dokumen Persyaratan & Kepatuhan UU PDP](#3-sop-2-verifikasi-ketat-dokumen-persyaratan--kepatuhan-uu-pdp)
4. [SOP 3: Verifikasi Rute Perjalanan & Aturan Keselamatan Ekstrem](#4-sop-3-verifikasi-rute-perjalanan--aturan-keselamatan-ekstrem)
5. [SOP 4: Alur Status Pemesanan & Alokasi Plat Nomor Unit Fisik](#5-sop-4-alur-status-pemesanan--alokasi-plat-nomor-unit-fisik)
6. [SOP 5: Penanganan Overtime & Prosedur Perpanjangan Sewa (+24 Jam)](#6-sop-5-penanganan-overtime--prosedur-perpanjangan-sewa-24-jam)
7. [SOP 6: Prosedur Serah Terima Unit di Pool & Layanan Antar-Jemput](#7-sop-6-prosedur-serah-terima-unit-di-pool--layanan-antar-jemput)
8. [SOP 7: Pengembalian Unit & Pengembalian Dokumen Jaminan](#8-sop-7-pengembalian-unit--pengembalian-dokumen-jaminan)
9. [Penanganan Masalah Lapangan (Troubleshooting & Emergency)](#9-penanganan-masalah-lapangan-troubleshooting--emergency)

---

## 1. PERAN, WEWENANG & BATASAN AKUN OPERATOR

Role **Operator Ryokourent** (`ryokourent_operator`) dirancang secara granular untuk mendukung kelancaran operasional harian armada tanpa membuka celah modifikasi pada sistem inti website.

### A. Hak Akses yang Dimiliki Operator:
* **Penyewaan Motor:** Melihat seluruh daftar transaksi pemesanan, melihat detail data identitas penyewa, melakukan konfirmasi pesanan, memasukkan plat nomor armada, mengubah status pemesanan (Menunggu -> Dikonfirmasi -> Berjalan -> Selesai), dan memproses perpanjangan sewa.
* **Dashboard Operasional:** Memantau ringkasan unit yang disewa hari ini, jumlah booking menunggu konfirmasi, dan sebaran unit aktif per lokasi pool.
* **Armada Motor:** Melihat katalog unit armada, menambah model motor baru (`create_motors`), mengunggah foto armada (`upload_files`), mengedit spesifikasi teknis unit (CC, transmisi, karakter rute, status bromo ready), mengedit tarif reguler unit (harian, mingguan, bulanan), serta memperbarui jumlah unit fisik internal (`_ryokou_physical_stock`) dan daftar plat nomor kendaraan (`_ryokou_plate_numbers`).

### B. Batasan Mutlak Operator (Dilarang & Dikunci oleh Sistem):
* **DILARANG MENGHAPUS MOTOR:** Seluruh fungsi hapus motor (`delete_motors`) dicabut secara permanen. Tombol "Tong Sampah" / "Trash" disembunyikan dari layar admin.
* **DILARANG MENAMBAH / MENGUBAH KATEGORI MOTOR:** Pengelolaan taksonomi `kategori_motor` dikunci eksklusif untuk Administrator. Operator hanya dapat memilih kategori yang sudah ada saat mendaftarkan motor.
* **DILARANG MENGAKSES PENGATURAN GLOBAL:** Halaman "Pengaturan Tarif & WhatsApp" dikunci dengan proteksi server-side HTTP 403 Forbidden. Operator tidak berwenang mengganti nomor WhatsApp resmi perusahaan atau melakukan penyesuaian harga massal (*bulk price adjustment*).
* **DILARANG MENGUBAH PLUGIN, TEMA, ATAU PENGGUNA:** Tidak memiliki akses ke menu Plugins, Appearance, Users, atau Settings bawaan WordPress.

---

## 2. SOP 1: RESPON CEPAT KONFIRMASI WHATSAPP (< 5 MENIT)

Kecepatan respon adalah kunci utama konversi rental motor wisatawan. Setiap formulir yang dikirim melalui website akan mengarahkan pelanggan langsung ke nomor WhatsApp resmi Ryokourent dengan format pesan terstruktur.

### A. Standar Waktu (SLA):
* Waktu respon maksimal: **5 Menit** sejak pesan WhatsApp pelanggan masuk.
* Jam operasional pelayanan CS: **07:00 – 23:00 WIB**.

### B. Mengidentifikasi Pesan Masuk Resmi:
Pesan resmi dari website selalu diawali dengan emotikon standar dan mencantumkan **Kode Booking Unik** (format `RYK-YYYYMMDD-XXXX`), contoh:
```text
🛵 Halo Admin Ryokourent, saya ingin konfirmasi pemesanan sewa motor:

• Kode Booking: *#RYK-20261015-A4B7*
• Model Motor: *Honda BeAT Deluxe*
• Jadwal Sewa: *15/10/2026 08:00 WIB* s/d *17/10/2026 17:00 WIB*
• Durasi: *3 Hari (~57 Jam)*
• Lokasi Ambil: *Pool 1 (Dinoyo)*
• Rute Tujuan: *Wisata Kota Malang & Batu*
• Estimasi Biaya: *Rp 255.000*

👤 Data Penyewa:
• Nama: Budi Santoso
• WhatsApp: 081234567890
• Kontak Darurat: 081987654321
...
```

### C. Alur Tindakan Operator:
1. **Cek Kode Booking di WP-Admin:** Buka menu **Penyewaan Motor** -> cari kode booking terkait. Pastikan statusnya berada pada tahap `Menunggu Konfirmasi`.
2. **Kirim Template Balasan Cepat:**
```text
Halo Kak [Nama Penyewa]! Terima kasih telah menghubungi Ryokourent Malang & Batu 🛵

Pesanan motor [Model Motor] untuk tanggal [Tanggal Sewa] dengan Kode Booking [Kode Booking] telah kami terima di sistem.

Untuk mengamankan jadwal dan unit armada Kakak, mohon mengirimkan foto dokumen persyaratan berikut:
1. Foto e-KTP Asli Penyewa
2. Foto Dokumen Pendukung 1 (SIM C aktif / Paspor)
3. Foto Dokumen Pendukung 2 (KTM / ID Card Kerja / BPJS / KK)
4. Akun Instagram / Media Sosial aktif

Apakah unit ingin diambil langsung di Pool kami atau diantar ke Stasiun/Hotel, Kak? 😊
```

---

## 3. SOP 2: VERIFIKASI KETAT DOKUMEN PERSYARATAN & KEPATUHAN UU PDP

Demi mencegah risiko pencurian, penggelapan armada, atau penyalahgunaan data, verifikasi identitas wajib dijalankan dengan teliti tanpa kompromi.

### A. 3 Dokumen Wajib:
1. **e-KTP Asli Fisik:**
   - Wajib milik penyewa utama yang mengisi formulir.
   - e-KTP fisik **WAJIB DITITIPKAN** kepada petugas Ryokourent selama masa sewa berlangsung dan akan disimpan di brankas aman pool.
2. **Dokumen Pendukung 1 (Identitas Resmi Sah):**
   - SIM C aktif (sangat disarankan bagi pengemudi motor).
   - Paspor (wajib untuk wisatawan mancanegara).
   - NPWP pribadi asli.
3. **Dokumen Pendukung 2 (Verifikasi Pekerjaan/Domisili):**
   - Kartu Tanda Mahasiswa (KTM) aktif.
   - ID Card Karyawan / Kartu Pegawai.
   - Kartu BPJS Kesehatan / Ketenagakerjaan.
   - Kartu Keluarga (foto fisik / fotokopi jelas).

### B. Verifikasi Akun Media Sosial:
* Pelanggan wajib melampirkan tautan profil media sosial aktif (Instagram, TikTok, atau LinkedIn).
* Cek keaslian akun: akun bukan akun baru dibuat (fake account), memiliki interaksi wajar, dan foto profil cocok dengan e-KTP.

### C. Validasi Kontak Darurat:
* Nomor kontak darurat **HARUS KELUARGA KANDUNG** (Orang tua, suami/istri, atau saudara kandung).
* Sistem website secara otomatis menolak jika nomor kontak darurat sama dengan nomor penyewa.
* Operator wajib melakukan tes panggilan ringan atau memastikan nomor kontak darurat aktif di WhatsApp sebelum unit diserahkan.

### D. Kepatuhan Privasi Data (UU PDP No. 27/2022):
* Seluruh foto dokumen identitas pelanggan yang diterima via WhatsApp dilarang disimpan di galeri pribadi operator.
* Dilarang keras menyebarluaskan, memperjualbelikan, atau memanfaatkan data pelanggan di luar keperluan operasional penyewaan Ryokourent.
* Data fisik e-KTP yang dititipkan disimpan di amplop tertutup khusus bernomor booking di brankas pool.

---

## 4. SOP 3: VERIFIKASI RUTE PERJALANAN & ATURAN KESELAMATAN EKSTREM

Topografi Malang Raya memiliki kontur pegunungan dengan rute ekstrem yang memerlukan armada berkarakter khusus demi keselamatan nyawa wisatawan.

### A. Pembagian Karakter Rute:
1. **Rute Standar (Malang Kota & Wisata Batu):**
   - Meliputi: Alun-Alun Malang, Kawasan Kampus (UB/UM/UIN), Kampung Warna-Warni Jodipan, Jatim Park 1/2/3, Museum Angkut, BNS, Selecta, Songgoriti, Alun-Alun Batu.
   - Armada yang Diizinkan: Seluruh motor matik (Honda BeAT Deluxe, BeAT Street, Scoopy, Vario 125, Vario 160, PCX 160) dan Trail CRF 150L.
2. **Rute Ekstrem Wajib Trail Honda CRF 150L:**
   - **Kaldera Bromo:** Meliputi Penanjakan, Lautan Pasir Berbisik, Bukit Teletubbies, Kawah Bromo.
   - **Jalur Cangar - Pacet:** Tanjakan dan turunan curam ekstrem dengan risiko rem blong.
   - **Pantai Malang Selatan:** Jalur lintas selatan dengan kontur berliku tajam dan tanjakan panjang.

### B. LARANGAN KERAS MOTOR MATIK KE BROMO & RUTE EKSTREM:
* **Alasan Teknis & Bahaya Nyata:**
  1. Pasir vulkanik Bromo yang sangat halus terhisap ke dalam box filter udara dan ruang transmisi CVT motor matik, menyebabkan *v-belt* slip total, motor mogok mendadak di tengah lautan pasir, dan mesin *overheat*.
  2. Turunan ekstrem Gunung Bromo dan Jalur Cangar memicu *brake fade* (rem blong) pada motor matik akibat gesekan kampas rem terus-menerus tanpa adanya bantuan *engine brake* manual.
* **Tindakan Operator jika Pelanggan Memilih Rute Bromo dengan Motor Matik:**
  1. Tegaskan secara sopan bahwa motor matik **DILARANG KERAS** masuk kawasan Bromo demi keselamatan jiwa penyewa.
  2. Alihkan pesanan ke unit **Honda Trail CRF 150L** yang dilengkapi suspensi upside down Showa dan ban dual-purpose tapak lebar.
  3. Jika pelanggan menolak beralih ke CRF 150L dan tetap bersikeras membawa matik ke Bromo, **OPERATOR WAJIB MENOLAK PESANAN**.

### C. Batas Wilayah Operasional:
* Armada Ryokourent hanya boleh dioperasikan di wilayah administratif **Kabupaten Malang, Kota Malang, dan Kota Wisata Batu**.
* Membawa unit keluar wilayah Malang Raya (ke Surabaya, Kediri, Blitar, Pasuruan kota, dsb.) **WAJIB MEMPEROLEH IZIN TERTULIS BERMATERAI** dari Admin Ryokourent. Pelanggaran batas wilayah tanpa izin akan memicu penguncian unit / laporan kepolisian.

---

## 5. SOP 4: ALUR STATUS PEMESANAN & ALOKASI PLAT NOMOR UNIT FISIK

Pengelolaan status transaksi di WP-Admin mencerminkan kondisi riil armada di lapangan dan memengaruhi kuota ketersediaan online secara langsung.

```text
[Menunggu Konfirmasi]
        │
        ▼ (Syarat Dokumen Lengkap & DP Diterima)
[Dikonfirmasi]
        │
        ▼ (Unit Diserahkan ke Penyewa + Input Plat Nomor)
[Sewa Berjalan]
        │
        ▼ (Unit Kembali ke Pool & Cek Fisik Lengkap)
[Selesai Sewa]
```

### Langkah Teknis di WP-Admin:

#### 1. Dari `Menunggu Konfirmasi` Menuju `Dikonfirmasi`:
* **Syarat:** Dokumen identitas (e-KTP + 2 pendukung) sudah diverifikasi sah via WhatsApp, dan penyewa telah membayar DP / lunas.
* **Cara Eksekusi:** Di menu **Penyewaan Motor**, klik tombol **"Konfirmasi"** pada kolom Status & Aksi.
* **Perilaku Sistem:** Sistem secara otomatis menjalankan *atomic locking* dan memeriksa sisa kuota fisik model motor tersebut. Jika kuota masih ada, status berubah menjadi **Dikonfirmasi** (warna biru). Jika kuota telah penuh karena pesanan lain, sistem akan menolak perubahan dan memunculkan peringatan error.

#### 2. Dari `Dikonfirmasi` Menuju `Sewa Berjalan`:
* **Syarat:** Pelanggan hadir di pool atau unit siap diantar ke lokasi penjemputan.
* **Input Wajib Plat Nomor:**
  1. Pada baris pesanan, pilih plat nomor armada yang akan dialokasikan dari kotak input / dropdown plat nomor.
  2. Klik tombol **"Mulai Sewa"**.
* **Validasi Otomatis Sistem:**
  - Plat nomor harus benar-benar terdaftar di inventaris unit fisik model motor tersebut.
  - Plat nomor tidak boleh sedang dialokasikan ke pesanan lain yang jadwalnya tumpang tindih (*overlap*). Jika bentrok, sistem akan memblokir dan menampilkan peringatan konflik jadwal plat.
* **Status Berubah:** Status berganti menjadi **Sewa Berjalan** (warna hijau). Unit resmi keluar pool.

#### 3. Dari `Sewa Berjalan` Menuju `Selesai Sewa`:
* **Syarat:** Unit telah kembali ke pool, pemeriksaan fisik 4 sisi selesai, bahan bakar dicek, kelengkapan helm/jas hujan utuh, dan e-KTP diserahkan kembali ke penyewa.
* **Cara Eksekusi:** Klik tombol **"Selesaikan Sewa"**.
* **Efek Sistem:** Kuota motor otomatis dilepaskan kembali ke pool untuk dapat dipesan oleh pelanggan berikutnya. Status ini bersifat final (terminal).

---

## 6. SOP 5: PENANGANAN OVERTIME & PROSEDUR PERPANJANGAN SEWA (+24 JAM)

### A. Aturan Perhitungan Durasi Sewa:
* Durasi sewa dihitung per kelipatan **24 Jam**.
* **Toleransi Overtime (Grace Period):** Ryokourent memberikan batas toleransi keterlambatan pengembalian unit maksimal **2 Jam** secara gratis tanpa dikenakan denda tambahan.
  - Contoh: Sewa mulai pukul 09:00 WIB, jadwal kembali resmi pukul 09:00 WIB esok hari. Jika unit kembali pukul 10:45 WIB (keterlambatan 1 jam 45 menit), penyewa **TIDAK DIKENAKAN DENDA**.

### B. Penanganan Keterlambatan Melebihi Toleransi (> 2 Jam):
1. Jika unit belum kembali setelah melewati toleransi 2 jam, sistem akan menampilkan badge peringatan `[Overtime: +X Jam]` pada daftar admin.
2. Operator segera menghubungi penyewa via WhatsApp untuk menanyakan posisi dan kendala di jalan.
3. **Ketentuan Biaya Overtime Manual (Tidak dihitung otomatis oleh web):**
   - Keterlambatan jam ke-3 hingga jam ke-5: Dikenakan biaya overtime per jam sesuai jenis motor:
     * BeAT Series: **Rp 15.000 / Jam**
     * Scoopy & Vario: **Rp 20.000 / Jam**
     * Trail CRF 150L / PCX: **Rp 25.000 / Jam**
   - Keterlambatan lebih dari 5 jam: Dihitung tarif sewa penuh 1 hari (24 jam).

### C. Prosedur Perpanjangan Sewa Resmi (+24 Jam):
Jika pelanggan mengabari bahwa mereka ingin menambah durasi liburan:
1. Buka halaman edit pesanan di menu **Penyewaan Motor**.
2. Gunakan opsi **"Perpanjang Sewa"** (+1 Hari / +2 Hari kelipatan 24 jam).
3. Sistem akan memverifikasi ketersediaan armada pada jadwal perpanjangan tersebut.
4. Jika disetujui, jadwal sewa baru tersimpan di sistem, akumulasi tarif ditambahkan resmi, dan status overtime dilepas kembali normal.
5. Pelanggan melakukan transfer pembayaran perpanjangan sewa sebelum masa sewa awal habis.

---

## 7. SOP 6: PROSEDUR SERAH TERIMA UNIT DI POOL & LAYANAN ANTAR-JEMPUT

### A. Dua Lokasi Pool Resmi:
1. **Pool 1: Malang Dinoyo (Pusat Kota / Kampus):**
   - Alamat: Jl. MT Haryono Gg. 21 No. 23, Dinoyo, Kec. Lowokwaru, Kota Malang.
   - Titik Strategis: Dekat Universitas Brawijaya (UB), UIN Maliki, Polinema, Stasiun Kotabaru.
2. **Pool 2: Batu Diponegoro (Kota Wisata Batu):**
   - Alamat: Jl. Diponegoro No. 45, Sisir, Kec. Batu, Kota Wisata Batu.
   - Titik Strategis: Dekat Alun-Alun Batu, Jatim Park 1 & 2, Museum Angkut, BNS.

### B. Standar Layanan Antar-Jemput:
* Layanan antar-jemput fleksibel tersedia ke:
  - Stasiun Kereta Api Kotabaru Malang & Stasiun Malang Kota Lama.
  - Terminal Arjosari Malang.
  - Hotel, villa, atau guest house di area Kota Malang dan Kota Batu.
* Petugas lapangan wajib tiba di lokasi pengantaran minimal **15 menit sebelum jam serah terima**.

### C. Checklist Wajib Sebelum Unit Diserahkan:
1. [ ] **Kondisi Fisik & Foto 4 Sisi:** Petugas memotret bodi motor dari sisi depan, belakang, kanan, dan kiri bersama penyewa sebagai bukti awal kondisi motor bebas baret berat.
2. [ ] **Indikator Bahan Bakar:** Pastikan bensin terisi minimal 2 bar / setengah tangki, catat pada formulir serah terima.
3. [ ] **Pemeriksaan Teknis:** Cek fungsi rem depan/belakang, lampu utama, lampu sein, klakson, dan tekanan angin ban.
4. [ ] **Fasilitas Gratis:** Serahkan kelengkapan standar:
   - 2 Helm SNI steril & bersih.
   - 2 Jas Hujan setelan (baju + celana) di bagasi.
   - 1 Phone holder stang motor yang kokoh.
5. [ ] **Tanda Tangan Bukti Sewa:** Pelanggan menandatangani lembar serah terima dan menitipkan e-KTP asli fisik.

---

## 8. SOP 7: PENGEMBALIAN UNIT & PENGEMBALIAN DOKUMEN JAMINAN

1. **Pemeriksaan Kondisi Kembali:**
   - Cocokkan kondisi fisik bodi dengan foto dokumentasi awal saat serah terima.
   - Periksa kelengkapan 2 Helm SNI dan 2 Jas Hujan.
   - Cek indikator bahan bakar (wajib setara dengan posisi saat penyerahan awal).
2. **Penyelesaian Pembayaran & Denda (Jika Ada):**
   - Jika terdapat overtime di luar toleransi 2 jam, kenakan biaya overtime sesuai SOP.
   - Jika terdapat kerusakan akibat kelalaian (baret jatuh, spion patah, ban robek), diskusikan biaya penggantian part orisinil Honda secara kekeluargaan.
3. **Pengembalian e-KTP Fisik:**
   - Ambil amplop jaminan e-KTP dari brankas pool.
   - Pastikan nama pada e-KTP cocok dengan orang yang mengembalikan unit.
   - Serahkan e-KTP kepada pelanggan disertai ucapan terima kasih ramah:
     *"Terima kasih banyak telah mempercayakan perjalanan di Malang & Batu bersama Ryokourent, Kak [Nama Penyewa]! Semoga perjalanan liburannya menyenangkan dan hati-hati di jalan kembali ke kota asal!"*
4. **Pembaruan Sistem:** Ubah status pesanan di WP-Admin menjadi **"Selesai Sewa"**.

---

## 9. PENANGANAN MASALAH LAPANGAN (TROUBLESHOOTING & EMERGENCY)

| Masalah | Gejala / Situasi | Tindakan Operator |
| :--- | :--- | :--- |
| **Kunci Kontak Hilang** | Penyewa menghubungi kunci hilang saat berwisata | Kirim petugas ke lokasi penyewa membawa kunci cadangan resmi. Kenakan biaya penggantian duplikat kunci Rp 50.000. |
| **Ban Bocor / Tertusuk Paku** | Ban kempis di perjalanan | Arahkan penyewa ke tambal ban terdekat (biaya tambal ban ditanggung penyewa). Jika ban robek parah, petugas kirim unit pengganti. |
| **Kecelakaan Ringan / Baret** | Motor lecet akibat terserempet atau jatuh terpeleset | Prioritaskan keselamatan penyewa terlebih dahulu. Foto detail kerusakan, hitung estimasi biaya bengkel AHASS resmi, dan buat berita acara singkat. |
| **Penyewa Tidak Bisa Dihubungi (> 6 Jam)** | Masa sewa habis, kontak WA mati, belum kembali | Hubungi nomor kontak darurat keluarga. Jika tidak ada respon dalam 12 jam, laporkan ke Supervisor/Admin untuk koordinasi pelacakan GPS armada. |
| **Kendala Teknis Mesin Mogok** | Mesin mati di jalan tanpa kelalaian penyewa | Segera kirim unit pengganti setara ke lokasi dalam waktu < 45 menit. Bebaskan biaya overtime yang timbul akibat kendala tersebut. |

---
*Dokumen ini diterbitkan oleh Tim Manajemen Ryokourent Malang & Batu. Wajib dipahami dan dijalankan oleh seluruh staf operasional.*
