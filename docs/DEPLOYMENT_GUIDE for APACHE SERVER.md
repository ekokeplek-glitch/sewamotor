Berikut adalah panduan konfigurasi web server Apache untuk sistem rental Ryokourent berbasis WordPress, yang sudah disesuaikan dari panduan LiteSpeed/Nginx. Panduan ini dalam bahasa Indonesia dan dalam format markdown siap pakai:

```markdown
# Panduan Deploy Apache: Sistem Rental Ryokourent Berbasis WordPress

Panduan ini memberikan konfigurasi yang diperlukan untuk menjalankan sistem rental Ryokourent pada lingkungan Apache2 dengan PHP-FPM.

## 1. Prasyarat & Aktivasi Modul Apache
Pastikan server Anda sudah diperbarui dan modul Apache yang dibutuhkan sudah diaktifkan.

```bash
sudo apt update
sudo a2enmod rewrite headers ssl expires deflate proxy_fcgi setenvif
sudo systemctl restart apache2
```

## 2. Optimasi PHP (`php.ini`)
Sistem rental membutuhkan batasan lebih tinggi untuk pemrosesan gambar dan perhitungan booking. Ubah konfigurasi `php.ini` Anda (misal di `/etc/php/8.2/fpm/php.ini`):

- `memory_limit = 256M`
- `upload_max_filesize = 64M`
- `post_max_size = 64M`
- `max_execution_time = 300`

## 3. Konfigurasi Virtual Host
Buat file konfigurasi baru di `/etc/apache2/sites-available/ryokourent.conf`

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    Redirect permanent / https://yourdomain.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName yourdomain.com
    DocumentRoot /var/www/yourdomain.com/public_html

    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/yourdomain.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/yourdomain.com/privkey.pem

    # Header Keamanan
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"

    <Directory /var/www/yourdomain.com/public_html>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Proxy PHP-FPM (sesuaikan versi PHP)
    <FilesMatch ".+\.php$">
        SetHandler "proxy:unix:/run/php/php8.2-fpm.sock|fcgi://localhost"
    </FilesMatch>

    # Blokir akses ke file sensitif
    <FilesMatch "^\.(git|env|htaccess|user\.ini)$">
        Require all denied
    </FilesMatch>

    # Caching untuk performa
    <IfModule mod_expires.c>
        ExpiresActive On
        ExpiresByType image/webp "access plus 1 year"
        ExpiresByType image/jpeg "access plus 1 year"
        ExpiresByType image/png "access plus 1 year"
        ExpiresByType text/css "access plus 1 month"
        ExpiresByType application/javascript "access plus 1 month"
    </IfModule>

    ErrorLog ${APACHE_LOG_DIR}/ryoko_error.log
    CustomLog ${APACHE_LOG_DIR}/ryoko_access.log combined
</VirtualHost>
```

## 4. File `.htaccess` WordPress
Tempatkan file ini di direktori root WordPress (`public_html/.htaccess`) untuk mengatur routing dan mencegah eksekusi skrip di folder uploads.

```apache
# BEGIN Optimasi Ryokourent
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteRule ^index\.php$ - [L]

# Cegah eksekusi skrip di uploads
RewriteRule ^wp-content/uploads/.*\.php$ - [F]

# Permalink WordPress
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END Optimasi Ryokourent

# Kompresi Gzip
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css application/javascript application/json
</IfModule>
```

## 5. Aktifkan Situs & Restart Apache
```bash
sudo a2ensite ryokourent.conf
sudo apache2ctl configtest
sudo systemctl restart apache2
```

---

Konfigurasi Apache ini sudah disesuaikan untuk memenuhi prinsip keamanan, performa, dan fungsi yang ada di panduan deployment asli untuk LiteSpeed dan Nginx, namun untuk lingkungan Apache dengan PHP dan MySQL.

Jika Anda ingin, saya juga bisa menyiapkan bagian konfigurasi MySQL/MariaDB atau membuat file `.md` siap simpan dan deploy. Silakan beri tahu!