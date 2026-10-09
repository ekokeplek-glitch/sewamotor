Berikut adalah tutorial lengkap dalam bahasa Indonesia yang berisi panduan deployment Ryokourent di Windows 11 menggunakan XAMPP, disusun khusus untuk pemula dengan istilah komputer umum. Anda bisa menyimpan ini sebagai file bernama `DEPLOYMENT_GUIDE_for_XAMMP_win11.md`.

---

# Panduan Deployment Ryokourent di Windows 11 dengan XAMPP

Panduan ini ditujukan bagi pengguna Windows 11 yang ingin melakukan instalasi dan konfigurasi sistem Ryokourent menggunakan lingkungan server lokal XAMPP.

## 1. Persiapan Sistem
Sebelum memulai, pastikan perangkat Anda memenuhi syarat berikut:
*   **Sistem Operasi:** Windows 11 (64-bit).
*   **Hardware:** Minimal RAM 4GB (8GB direkomendasikan) dan ruang penyimpanan kosong minimal 20GB.
*   **Akses Administrator:** Anda memerlukan hak akses administrator untuk menginstal perangkat lunak dan mengubah file sistem.

## 2. Instalasi dan Konfigurasi XAMPP
1.  **Unduh XAMPP:** Kunjungi [Apache Friends](https://www.apachefriends.org/index.html) dan unduh versi terbaru yang menyertakan **PHP 8.1 atau lebih tinggi**.
2.  **Instalasi:** Jalankan installer. Jika muncul peringatan *User Account Control (UAC)*, klik OK. Instal di direktori default (`C:\xampp`).
3.  **Aktivasi Modul:** Buka **XAMPP Control Panel**, lalu klik tombol **Start** pada modul **Apache** dan **MySQL**.
4.  **Konfigurasi PHP (php.ini):**
    *   Klik tombol **Config** pada baris Apache di XAMPP Control Panel, lalu pilih `PHP (php.ini)`.
    *   Cari (Ctrl+F) dan pastikan ekstensi berikut aktif (hapus tanda titik koma `;` di depannya):
        ```ini
        extension=curl
        extension=gd
        extension=intl
        extension=mbstring
        extension=mysqli
        extension=openssl
        extension=zip
        ```
    *   Ubah nilai berikut untuk performa optimal:
        ```ini
        memory_limit = 256M
        post_max_size = 64M
        upload_max_filesize = 64M
        max_execution_time = 300
        ```
    *   Simpan file dan **Restart** Apache.

## 3. Pengaturan WordPress dan Plugin
1.  **Persiapan Folder:**
    *   Ekstrak file WordPress ke dalam folder `C:\xampp\htdocs\ryokourent`.
    *   Salin folder **Plugin Core Ryokourent** ke `wp-content/plugins/`.
    *   Salin folder **Tema/Child Theme** ke `wp-content/themes/`.
2.  **Pembuatan Database:**
    *   Buka browser dan akses `http://localhost/phpmyadmin`.
    *   Buat database baru dengan nama `db_ryokourent`.
3.  **Konfigurasi wp-config.php:**
    *   Ubah `wp-config-sample.php` menjadi `wp-config.php`.
    *   Edit bagian database:
        ```php
        define( 'DB_NAME', 'db_ryokourent' );
        define( 'DB_USER', 'root' );
        define( 'DB_PASSWORD', '' ); // Default XAMPP kosong
        ```
    *   Tambahkan baris keamanan:
        ```php
        define( 'DISALLOW_FILE_EDIT', true );
        define( 'WP_POST_REVISIONS', 5 );
        ```

## 4. Konfigurasi SSL di XAMPP (Localhost HTTPS)
Untuk menjalankan fitur booking yang memerlukan enkripsi, disarankan mengaktifkan SSL lokal:
1.  Buka folder `C:\xampp\apache\conf\extra\httpd-ssl.conf`.
2.  Secara default, XAMPP sudah menyediakan sertifikat dummy (`server.crt` & `server.key`).
3.  Pastikan `VirtualHost _default_:443` mengarah ke direktori dokumen yang benar.
4.  Akses situs Anda melalui `https://localhost/ryokourent`. Jika muncul peringatan keamanan di browser, klik *Advanced* > *Proceed to localhost (unsafe)*.

## 5. Hardening WordPress
Keamanan tambahan untuk lingkungan Windows:
*   **Izin Folder:** Pastikan folder `wp-content` tidak dapat diakses secara publik dengan menambahkan file `.htaccess` di dalamnya berisi `Options -Indexes`.
*   **Keamanan Database:** Ubah prefix tabel default `wp_` menjadi sesuatu yang unik saat instalasi WordPress (misal: `ryo88_`).
*   **Timezone:** Masuk ke Dashboard WordPress > Settings > General, ubah *Timezone* menjadi **UTC+7** atau **Jakarta**.

## 6. Backup dan Pemulihan
*   **Backup Database:** Buka phpMyAdmin, pilih database, klik tab **Export**, dan simpan file `.sql` secara rutin.
*   **Backup File:** Salin folder `C:\xampp\htdocs\ryokourent` ke drive eksternal atau cloud storage (Google Drive/OneDrive).
*   **Pemulihan:** Jika terjadi error, hapus database lama, buat database baru dengan nama yang sama, dan gunakan fitur **Import** di phpMyAdmin untuk mengunggah file `.sql` terakhir.

## 7. Checklist Go-Live
Sebelum sistem digunakan secara penuh:
- [ ] Apache & MySQL berstatus "Running" (Hijau) di Control Panel.
- [ ] Halaman login WordPress (`/wp-admin`) dapat diakses.
- [ ] Plugin Ryokourent aktif dan tidak ada notifikasi error PHP.
- [ ] Permalink diatur ke **Post name** (Settings > Permalinks).
- [ ] Form pemesanan/booking berfungsi dan mengirimkan data ke database.

---

Panduan ini dibuat untuk membantu pengguna Windows 11 dengan XAMPP agar dapat menjalankan Ryokourent dengan baik dan aman. Jika Anda ingin, saya bisa bantu buatkan file markdown ini untuk Anda.