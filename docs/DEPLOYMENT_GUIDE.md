# PANDUAN DEPLOYMENT & CHECKLIST PRODUKSI (RYOKOURENT)

Dokumen ini merupakan panduan resmi standar operasional prosedur (SOP) untuk menyebarkan (*deploy*), mengonfigurasi, mengamankan, dan memelihara sistem **Ryokourent** (Rental Sepeda Motor Malang Raya & Kota Wisata Batu) pada lingkungan server produksi (*live production*).

---

## DAFTAR ISI
1. [Arsitektur Rilis & Persyaratan Sistem](#1-arsitektur-rilis--persyaratan-sistem)
2. [Pembuatan Paket Rilis Bersih (Production Packaging)](#2-pembuatan-paket-rilis-bersih-production-packaging)
3. [Konfigurasi Web Server (LiteSpeed & Nginx)](#3-konfigurasi-web-server-litespeed--nginx)
4. [Konfigurasi SSL, HTTPS & Keamanan Header](#4-konfigurasi-ssl-https--keamanan-header)
5. [Konfigurasi WordPress, wp-config.php & Permalink](#5-konfigurasi-wordpress-wp-configphp--permalink)
6. [Checklist Go-Live & Verifikasi Pasca-Deployment](#6-checklist-go-live--verifikasi-pasca-deployment)
7. [Strategi Backup Otomatis (Database & Media)](#7-strategi-backup-otomatis-database--media)
8. [Prosedur Pemulihan Bencana & Rollback (Disaster Recovery)](#8-prosedur-pemulihan-bencana--rollback-disaster-recovery)

---

## 1. Arsitektur Rilis & Persyaratan Sistem

Sistem Ryokourent dirancang dengan pendekatan modular yang memisahkan logika bisnis rental dari tema tampilan frontend:
* **Plugin Inti (`ryokourent-core`):** Menangani seluruh logika bisnis, CPT `motor`, CPT `penyewaan`, kalkulasi tarif sewa harian/mingguan/bulanan, validasi ketersediaan armada, pencegahan *double booking* atomik, generator deep link WhatsApp resmi, dan manajemen hak akses (RBAC).
* **Tema Anak (`generatepress-child`):** Menangani antarmuka visual mobile-first, single view motor, landing page responsif, floating mobile bar, dan integrasi aset CSS/JS ringan tanpa dependensi pustaka berat.
* **Tema Induk (`generatepress`):** Tema WordPress resmi yang bersih, aman, dan berkinerja tinggi.

### Persyaratan Minimum Server Produksi:
| Komponen | Spesifikasi Minimum | Rekomendasi Produksi |
| :--- | :--- | :--- |
| **Sistem Operasi** | Linux (Ubuntu LTS 22.04 / 24.04 / AlmaLinux 9) | Cloud VPS / Dedicated Server / LiteSpeed Hosting |
| **Web Server** | LiteSpeed Enterprise / OpenLiteSpeed / Nginx 1.24+ | LiteSpeed Web Server (kompatibel penuh LSCache) |
| **PHP Runtime** | PHP 8.1 | **PHP 8.2 atau PHP 8.3** (OPcache aktif) |
| **Ekstensi PHP Wajib** | `mysqli`, `curl`, `json`, `mbstring`, `intl`, `xml`, `zip`, `gd`/`imagick` | Memory limit: $\ge 256\text{ MB}$ (Disarankan $512\text{ MB}$) |
| **Database Server** | MariaDB 10.5+ / MySQL 8.0+ | Collation: `utf8mb4_unicode_520_ci` |
| **Sertifikat SSL** | TLS 1.2 / TLS 1.3 | Let's Encrypt Wildcard / Cloudflare SSL / AutoSSL |
| **Penyimpanan** | SSD / NVMe Storage $\ge 20\text{ GB}$ | NVMe Storage dengan I/O throughput tinggi |
| **Lokasi Data Center** | Indonesia (Jakarta / IDC3D) | Latensi $< 35\text{ ms}$ untuk Malang & Batu |

---

## 2. Pembuatan Paket Rilis Bersih (Production Packaging)

Paket produksi **DILARANG KERAS** menyertakan file pengujian unit (`tests/`), berkas konfigurasi linter (`.phpcs.xml.dist`, `eslint`), catatan kerja internal AI (`SESSION_STATE.md`, `AI_WORKFLOW.md`), atau repositori Git.

Repositori telah dikonfigurasi dengan berkas `.gitattributes` (`export-ignore`) untuk memastikan packaging bersih secara otomatis.

### A. Perintah Packaging dengan Git Archive (Direkomendasikan)
Jalankan perintah ini di mesin lokal atau pipeline CI/CD dari branch `main`:

```bash
# 1. Pastikan Anda berada di branch produksi resmi dan repositori bersih
git checkout main
git pull origin main

# 2. Buat arsip rilis zip untuk plugin ryokourent-core (bersih dari folder tests/)
git archive --format=zip --prefix=ryokourent-core/ -o dist/ryokourent-core-v1.0.0.zip HEAD:wp-content/plugins/ryokourent-core/

# 3. Buat arsip rilis zip untuk theme generatepress-child
git archive --format=zip --prefix=generatepress-child/ -o dist/generatepress-child-v1.0.0.zip HEAD:wp-content/themes/generatepress-child/

# 4. Buat bundle paket rilis lengkap
git archive --format=zip -o dist/ryokourent-full-release-v1.0.0.zip HEAD
```

### B. Checklist Verifikasi Integritas Berkas Sebelum Rilis:
1. Pastikan folder `wp-content/plugins/ryokourent-core/tests/` **TIDAK ADA** di dalam `ryokourent-core-v1.0.0.zip`.
2. Pastikan file `uninstall.php` menyertakan guard pengaman:
   ```php
   if (!defined('WP_UNINSTALL_PLUGIN')) {
       exit;
   }
   ```
3. Pastikan seluruh file PHP plugin memiliki direct execution guard:
   ```php
   if (!defined('ABSPATH')) {
       exit;
   }
   ```
4. Pastikan file `style.css` pada child theme merujuk ke Template `generatepress`.

---

## 3. Konfigurasi Web Server (LiteSpeed & Nginx)

### A. Konfigurasi LiteSpeed Web Server / cPanel (`.htaccess`)

Sistem Ryokourent menggunakan panggilan AJAX frontend untuk verifikasi booking (`ryokourent_process_booking`) dan refresh token nonce (`ryokourent_refresh_nonce`). Panggilan ini harus dikecualikan dari static caching.

Tambahkan atau pastikan aturan berikut berada di file `.htaccess` root WordPress:

```apache
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress

# BEGIN LiteSpeed Cache Rules for Ryokourent
<IfModule Litespeed>
RewriteEngine On
CacheLookup on

# 1. Jangan cache request AJAX WordPress (sangat krusial untuk nonce & validasi booking)
RewriteCond %{REQUEST_URI} ^/wp-admin/admin-ajax\.php [NC]
RewriteRule .* - [E=Cache-Control:no-cache]

# 2. Jangan cache halaman dashboard dan admin
RewriteCond %{REQUEST_URI} ^/wp-admin/ [NC]
RewriteRule .* - [E=Cache-Control:no-cache]

# 3. Jangan cache jika terdapat cookie sesi staf WordPress
RewriteCond %{HTTP_COOKIE} (wordpress_logged_in_|comment_author_) [NC]
RewriteRule .* - [E=Cache-Control:no-cache]

# 4. Header kontrol cache untuk respons dinamis Ryokourent
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>
</IfModule>
# END LiteSpeed Cache Rules
```

### B. Konfigurasi Nginx Server Block

Bila menggunakan Nginx sebagai reverse proxy atau web server utama, gunakan konfigurasi server block berikut:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name ryokourent.com www.ryokourent.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name ryokourent.com www.ryokourent.com;

    root /var/www/ryokourent;
    index index.php index.html;

    # SSL Certificates
    ssl_certificate /etc/letsencrypt/live/ryokourent.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/ryokourent.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Security Headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Logging
    access_log /var/log/nginx/ryokourent_access.log;
    error_log /var/log/nginx/ryokourent_error.log;

    # WordPress Permalinks
    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    # Pass PHP scripts to FastCGI server
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;

        # Bypass cache on AJAX requests
        if ($uri ~* "/wp-admin/admin-ajax.php") {
            set $skip_cache 1;
        }
    }

    # Blokir akses langsung ke file tersembunyi (.git, .env, .htaccess)
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }

    # Blokir eksekusi skrip PHP di folder uploads (Mencegah upload malware)
    location ~* /wp-content/uploads/.*\.php$ {
        deny all;
        access_log off;
        log_not_found off;
    }

    # Cache browser untuk aset statis (CSS, JS, WebP, Font)
    location ~* \.(jpg|jpeg|png|gif|webp|svg|css|js|ico|woff|woff2|ttf)$ {
        expires 30d;
        add_header Cache-Control "public, no-transform";
        access_log off;
    }
}
```

---

## 4. Konfigurasi SSL, HTTPS & Keamanan Header

1. **Pemasangan Sertifikat SSL:**
   * Gunakan Let's Encrypt via Certbot pada VPS:
     ```bash
     sudo certbot --nginx -d ryokourent.com -d www.ryokourent.com
     ```
   * Atau aktifkan fitur AutoSSL / cPanel SSL Certificate Manager.
2. **Paksa HTTPS di Level WordPress:**
   * Di dashboard WP-Admin -> **Pengaturan** -> **Umum**:
     * Alamat WordPress (URL): `https://ryokourent.com`
     * Alamat Situs (URL): `https://ryokourent.com`
3. **HTTP Strict Transport Security (HSTS):**
   * Pastikan header HSTS terpasang dengan nilai minimal 1 tahun (`max-age=31536000`).

---

## 5. Konfigurasi WordPress, wp-config.php & Permalink

### A. Pengerasan Keamanan `wp-config.php` untuk Produksi

Buka file `wp-config.php` di root instalasi WordPress produksi, lalu pastikan parameter berikut dikonfigurasikan dengan benar:

```php
// =============================================================================
// KONFIGURASI KEAMANAN & PRODUKSI RYOKOURENT
// =============================================================================

// 1. Matikan seluruh tampilan error ke publik (Cegah Information Disclosure)
define('WP_DEBUG', false);
define('WP_DEBUG_LOG', false);
define('WP_DEBUG_DISPLAY', false);
@ini_set('display_errors', 0);

// 2. Cegah penyuntingan file tema & plugin dari WP-Admin (Cegah Backdoor Injection)
define('DISALLOW_FILE_EDIT', true);

// 3. Paksa penggunaan HTTPS pada panel administrasi
define('FORCE_SSL_ADMIN', true);

// 4. Batasi revisi posting untuk menjaga ukuran database tetap ramping
define('WP_POST_REVISIONS', 5);

// 5. Tingkatkan memory limit untuk operasional lancar
define('WP_MEMORY_LIMIT', '256M');
define('WP_MAX_MEMORY_LIMIT', '512M');

// 6. Kunci URL situs untuk mencegah modifikasi database yang tidak disengaja
define('WP_HOME', 'https://ryokourent.com');
define('WP_SITEURL', 'https://ryokourent.com');
```

### B. Konfigurasi Zona Waktu & Permalink
1. **Zona Waktu Resmi:**
   * Masuk ke WP-Admin -> **Pengaturan** -> **Umum**.
   * Set **Zona Waktu** ke `Asia/Jakarta` (UTC+7) agar kalkulasi jam operasional pool (07:00 – 23:00 WIB) sinkron secara akurat dengan sistem rental.
2. **Struktur Permalink:**
   * Masuk ke WP-Admin -> **Pengaturan** -> **Permalink**.
   * Pilih opsi **Nama Tulisan** (`/%postname%/`) lalu klik **Simpan Perubahan**.
3. **Mekanisme Flush Rewrite Rules:**
   * Plugin `ryokourent-core` secara otomatis menjalankan `flush_rewrite_rules()` saat aktivasi melalui hook `register_activation_hook` di `ryokourent-core.php`.
   * Jika dilakukan update file secara manual via FTP/SSH tanpa re-aktivasi plugin, jalankan flush manual via WP-CLI:
     ```bash
     wp rewrite flush --hard
     ```
     atau buka halaman WP-Admin -> **Pengaturan** -> **Permalink** dan klik tombol **Simpan Perubahan**.

---

## 6. Checklist Go-Live & Verifikasi Pasca-Deployment

Lakukan checklist menyeluruh berikut sebelum membuka akses situs ke wisatawan publik:

### Checklist Pra-Peluncuran (Pre-Launch):
- [ ] **SSL & Domain:** Domain mengarah ke IP hosting produksi, sertifikat SSL valid dan gembok hijau aktif di browser.
- [ ] **Tema:** Tema induk `generatepress` dan tema anak `generatepress-child` telah aktif.
- [ ] **Plugin:** Plugin `ryokourent-core` telah aktif tanpa peringatan PHP.
- [ ] **Pengaturan Kontak:** Nomor WhatsApp admin resmi telah diisi di menu **Pengaturan Ryokou** (format `628xxxxxxxxxx`).
- [ ] **Master Armada Motor:** 7 model motor (BeAT Deluxe, BeAT CBS, BeAT Street, Scoopy, Vario 125, Vario 160, Trail CRF 150L) telah terbit dengan foto asli (format WebP) dan spesifikasi lengkap.
- [ ] **Inventaris Internal:** Total unit fisik (`_ryokou_physical_stock`) dan nomor plat kendaraan (`_ryokou_plate_numbers`) telah terisi pada masing-masing unit motor.
- [ ] **Lokasi Pool & Jam Buka:** Dua lokasi pool (Pool Dinoyo Malang & Pool Diponegoro Batu) telah terverifikasi dengan link Google Maps aktif dan jam 07:00 – 23:00 WIB.
- [ ] **Akun Staf Operator:** Akun staf lapangan telah dibuat menggunakan role `Operator Ryokourent` (`ryokourent_operator`).
- [ ] **Uji Pembatasan Hak Akses (RBAC):** Login sebagai staf Operator dan pastikan:
  - Menu Plugin, Tema, Pengguna, dan Pengaturan Ryokou **TIDAK BISA DIAKSES** (menghasilkan HTTP 403 jika dipaksa via URL).
  - Operator dapat melihat CPT Penyewaan dan mengubah status booking.

### Checklist Pengujian Fungsionalitas Pasca-Deployment (Smoke Test):
- [ ] **Uji Form Booking Smartphone:**
  - Buka halaman landing page dari browser ponsel (Chrome Android & Safari iOS).
  - Pilih tanggal mulai dan selesai; pastikan durasi sewa dan tarif otomatis terhitung.
  - Pilih rute Bromo; pastikan sistem otomatis mengunci model armada ke **Honda Trail CRF 150L**.
  - Isi identitas lengkap, nomor WhatsApp, nomor darurat keluarga yang berbeda, dan alamat menginap.
  - Klik tombol booking WhatsApp; pastikan browser mengarahkan ke deep link `https://wa.me/` dengan draf pesan rapi dan kode booking unik (`RYK-YYYYMMDD-XXXX`).
- [ ] **Uji Penyimpanan Transaksi:**
  - Periksa menu **Penyewaan Motor** di WP-Admin; pastikan data pemesanan tadi tersimpan dengan status `status_menunggu`.
- [ ] **Uji Ketersediaan Kuota:**
  - Simulasikan pesanan hingga batas kuota unit tercapai; pastikan status ketersediaan berubah menjadi tidak tersedia dan mencegah pemesanan ganda (*double booking*).
- [ ] **Uji Alokasi Plat Nomor:**
  - Saat pesanan diubah ke `status_berjalan`, pastikan sistem memvalidasi alokasi plat nomor fisik yang sah dan menolak nomor plat bentrok.
- [ ] **Uji Kecepatan Muat:**
  - Jalankan pengujian di Google PageSpeed Insights; skor performa mobile harus $\ge 90$ dengan LCP $< 2.0\text{ detik}$.

---

## 7. Strategi Backup Otomatis (Database & Media)

Kehilangan data transaksi penyewaan atau data armada merupakan risiko fatal bagi operasional bisnis rental. Terapkan strategi backup 3-2-1:

### A. Otomatisasi Backup Database MySQL Harian via Cron Job
Jadwalkan eksekusi backup database setiap hari pada pukul **02:00 WIB** dini hari (saat jam operasional pool tutup):

```bash
# Tambahkan ke crontab server (crontab -e)
0 2 * * * mysqldump -u [DB_USER] -p'[DB_PASSWORD]' [DB_NAME] --single-transaction --quick | gzip > /var/backups/ryokourent/db_backup_$(date +\%Y\%m\%d_\%H\%M\%S).sql.gz

# Hapus backup yang lebih lama dari 30 hari secara otomatis
0 3 * * * find /var/backups/ryokourent/ -name "db_backup_*.sql.gz" -type f -mtime +30 -delete
```

### B. Otomatisasi Backup File & Media Uploads Mingguan
Jadwalkan backup folder `wp-content/uploads/` setiap hari Minggu pukul **03:30 WIB**:

```bash
# Backup berkas media uploads mingguan
30 3 * * 0 tar -czf /var/backups/ryokourent/uploads_backup_$(date +\%Y\%m\%d).tar.gz -C /var/www/ryokourent/wp-content uploads/
```

### C. Sinkronisasi ke Penyimpanan Eksternal Cloud (Offsite Backup)
Gunakan `rclone` atau AWS CLI untuk menyinkronkan folder `/var/backups/ryokourent/` ke Google Drive, Wasabi, atau AWS S3 terenkripsi:

```bash
# Sinkronkan ke cloud storage setiap hari pukul 04:00 WIB
0 4 * * * rclone sync /var/backups/ryokourent/ remote_backup:ryokourent-backups/
```

---

## 8. Prosedur Pemulihan Bencana & Rollback (Disaster Recovery)

Jika terjadi kegagalan sistem, pembaruan plugin bermasalah, atau server mengalami gangguan, ikuti langkah rollback berikut:

### Skenario 1: Rollback Pembaruan Plugin / Tema yang Bermasalah
Jika rilis baru menyebabkan galat pada WordPress:
1. Akses server melalui SSH atau File Manager cPanel.
2. Masuk ke direktori plugin:
   ```bash
   cd /var/www/ryokourent/wp-content/plugins/
   ```
3. Ganti nama folder plugin yang bermasalah untuk menonaktifkannya sementara:
   ```bash
   mv ryokourent-core ryokourent-core-failed
   ```
4. Ekstrak paket rilis versi stabil sebelumnya (misal versi 1.0.0):
   ```bash
   unzip /var/backups/releases/ryokourent-core-v1.0.0.zip -d /var/www/ryokourent/wp-content/plugins/
   ```
5. Bersihkan cache server:
   ```bash
   wp litespeed-purge all || wp cache flush
   ```

### Skenario 2: Pemulihan Basis Data (Database Recovery)
Jika terjadi kerusakan data atau pembatalan transaksi fatal:
1. Pastikan situs diubah ke mode perawatan (*maintenance mode*):
   ```bash
   wp maintenance-mode activate
   ```
2. Ekstrak dan impor file cadangan database MySQL terakhir:
   ```bash
   gunzip < /var/backups/ryokourent/db_backup_YYYYMMDD_HHMMSS.sql.gz | mysql -u [DB_USER] -p'[DB_PASSWORD]' [DB_NAME]
   ```
3. Nonaktifkan mode perawatan:
   ```bash
   wp maintenance-mode deactivate
   ```
4. Lakukan verifikasi data di WP-Admin menu Penyewaan Motor.

### Skenario 3: Pemulihan Total Situs (Full Server Disaster Recovery)
1. Siapkan instalasi server baru dengan PHP 8.2+ dan Web Server LiteSpeed/Nginx.
2. Buat database baru dan pulihkan database dari backup cloud terbaru.
3. Unduh berkas WordPress inti dan ekstrak paket rilis plugin `ryokourent-core` serta theme `generatepress-child`.
4. Pulihkan folder `wp-content/uploads/` dari arsip tar cadangan.
5. Konfigurasikan `wp-config.php` dengan kredensial database baru.
6. Jalankan flush permalink:
   ```bash
   wp rewrite flush --hard
   ```
7. Uji form pemesanan dan koordinasikan status dengan staf Operator lapangan.

---

*Dokumen ini diterbitkan oleh Tim Pengembang Ryokourent dan wajib diperbarui pada setiap rilis versi mayor.*
