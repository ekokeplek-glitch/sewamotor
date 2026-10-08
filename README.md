# Ryokourent - Rental Sepeda Motor Malang Raya & Kota Wisata Batu

Aplikasi dan sistem manajemen rental sepeda motor berbasis WordPress dengan pendekatan *zero-friction WhatsApp booking*, arsitektur modular terpisah antara plugin bisnis custom (`ryokourent-core`) dan child theme (`generatepress-child`), serta kepatuhan penuh terhadap standar keamanan dan performa mobile-first.

---

## 🛵 Profil Bisnis
* **Brand:** Ryokourent
* **Kategori:** Rental Sepeda Motor & Adventure Fleet
* **Wilayah Layanan:** Malang Raya (Kota & Kabupaten Malang) dan Kota Wisata Batu
* **Dua Pool Resmi:**
  * **Pool 1 (Malang):** Jl. MT Haryono Gg. 21 No. 23, Dinoyo, Lowokwaru, Malang
  * **Pool 2 (Kota Batu):** Jl. Belakang Pompa Bensin, Jl. Diponegoro, Batu
* **Jam Operasional:** 07.00 – 23.00 WIB
* **Layanan Antar/Jemput:** Menyesuaikan situasi dan kondisi armada (sikon)

---

## 📂 Struktur Repositori

```text
/
├── README.md                  # Dokumentasi utama proyek
├── BLUEPRINT.md               # Spesifikasi kebutuhan bisnis & wireframe
├── PROJECT_OVERVIEW.md        # Ringkasan tujuan, MVP, dan batasan
├── ARCHITECTURE.md            # Desain arsitektur plugin dan theme
├── DATA_MODEL.md              # Skema CPT, taksonomi, dan meta fields
├── TASKS.md                   # 30 task terstruktur FASE 0 s/d FASE 4
├── AI_WORKFLOW.md             # Pembagian peran AI, git flow, & commit standard
├── AI_RULES.md                # Aturan pengembangan dan tata tertib coding
├── DECISIONS.md               # Architectural Decision Records (ADR)
├── TESTING.md                 # Skenario pengujian (TC-001 s/d TC-020)
├── CHANGELOG.md               # Catatan riwayat perubahan rilis
├── .gitignore                 # Aturan pengabaian file Git
├── .editorconfig              # Standar formatting editor
├── .phpcs.xml.dist            # Standar WordPress Coding Standards (WPCS)
├── .env.example               # Contoh konfigurasi lingkungan
├── CONFIG.example.php         # Contoh konstanta konfigurasi wp-config.php
├── wp-content/
│   ├── plugins/
│   │   └── ryokourent-core/   # Plugin logika bisnis, CPT, dan booking engine
│   └── themes/
│       └── generatepress-child/# Child theme GeneratePress untuk tampilan frontend
├── docs/                      # Panduan operasional, deployment, dan arsitektur
│   ├── DEPLOYMENT_GUIDE.md    # Panduan deployment produksi, LiteSpeed/Nginx, SSL & rollback
│   ├── PRODUCTION_CHECKLIST.md# Checklist pra-peluncuran dan go-live
│   ├── OPERATOR_MANUAL.md     # SOP lapangan staf operator (SLA, verifikasi e-KTP, Bromo)
│   ├── ADMIN_GUIDE.md         # Buku panduan administrator (RBAC, bulk pricing, backup)
│   ├── INSTALLATION_HOSTING.md# Panduan instalasi di cloud hosting / cPanel
│   ├── INSTALLATION_LOCAL.md  # Panduan setup environment lokal XAMPP / Docker
│   ├── GIT_WORKFLOW.md        # Alur kerja Git, branch strategy, dan Conventional Commits
│   ├── CODING_STANDARDS.md    # Standar penulisan kode WordPress (WPCS)
│   ├── PRE_MERGE_CHECKLIST.md # Checklist sebelum merge Pull Request
│   └── HANDOVER.md            # Protokol handover antar AI pengembang
```

---

## ⚡ Fitur Utama
1. **Landing Page Mobile-First:** Kecepatan muat di bawah 1.5 detik dengan GeneratePress child theme.
2. **Katalog & Filter Armada:** Honda BeAT Series, Scoopy-Vario Series, dan Honda Trail CRF 150L.
3. **Aturan Keselamatan Bromo:** Pembatasan tegas bahwa unit matik dilarang ke kaldera pasir Bromo; wajib Trail CRF 150L.
4. **Formulir Booking Interaktif:** Validasi nomor WhatsApp Indonesia, nomor darurat keluarga penjamin, dan kalkulator durasi sewa otomatis.
5. **Generator Pesan WhatsApp:** Menghasilkan draf pesanan resmi berformat rapi langsung ke WhatsApp admin.
6. **CPT & Status Booking:** CPT `motor` untuk armada dan CPT `penyewaan` dengan status `status_menunggu`, `status_dikonfirmasi`, `status_berjalan`, `status_selesai`, `status_dibatalkan`.
7. **Pencegahan Double Booking:** Algoritma pemeriksaan sisa kuota unit fisik terhadap pesanan aktif pada rentang tanggal sewa.
8. **Role-Based Access Control (RBAC):** Pemisahan hak akses Administrator (penuh) dan Operator (operasional harian, proteksi tarif).

---

## 🚀 Panduan Memulai Cepat & Rilis Produksi
1. **Lingkungan Server:** Pastikan server menggunakan PHP $\ge 8.1$ (direkomendasikan PHP 8.2 / 8.3) dengan ekstensi `mysqli`, `curl`, `json`, `mbstring`.
2. **Packaging Rilis Bersih:** Buat arsip rilis produksi tanpa berkas pengujian menggunakan `.gitattributes`:
   ```bash
   git archive --format=zip --prefix=ryokourent-core/ -o ryokourent-core-v1.0.0.zip HEAD:wp-content/plugins/ryokourent-core/
   git archive --format=zip --prefix=generatepress-child/ -o generatepress-child-v1.0.0.zip HEAD:wp-content/themes/generatepress-child/
   ```
3. **Pemasangan Tema:** Pasang tema induk **GeneratePress** di `wp-content/themes/generatepress/`, lalu unggah dan aktifkan **generatepress-child**.
4. **Pemasangan Plugin:** Unggah paket plugin **ryokourent-core** ke `wp-content/plugins/` dan aktifkan via WP-Admin.
5. **Panduan Deployment Lengkap:** Baca panduan menyeluruh di [docs/DEPLOYMENT_GUIDE.md](docs/DEPLOYMENT_GUIDE.md) dan checklist di [docs/PRODUCTION_CHECKLIST.md](docs/PRODUCTION_CHECKLIST.md).

---

## 🛡️ Kebijakan Privasi & Keamanan
* Jumlah unit fisik dan plat nomor bersifat internal dan **tidak ditampilkan ke publik**.
* Foto fisik e-KTP dan dokumen identitas **tidak diunggah ke server web**, melainkan diverifikasi secara privat melalui WhatsApp demi mematuhi privasi data pelanggan.
* Seluruh input disanitasi dan seluruh output di-escape sesuai standar WordPress.
