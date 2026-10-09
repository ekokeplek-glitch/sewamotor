Berikut panduan pengujian (testing) langkah-demi-langkah setelah instalasi Ryokourent pada XAMPP di Windows 11 — ditulis untuk pemula, praktis, dan mudah diikuti. Referensi utama untuk langkah ini berasal dari DEPLOYMENT_GUIDE.md di the tabs provided to me (1 tab).

Ringkasan tujuan pengujian
- Verifikasi fungsionalitas inti (booking, penyimpanan transaksi, alokasi plat).
- Pastikan keamanan dasar (HTTPS, header, wp-config hardening).
- Pastikan performa dasar dan perilaku caching/AJAX.
- Verifikasi backup & recovery bekerja.

1) Pemeriksaan awal (smoke test)
1. Jalankan XAMPP Control Panel — pastikan Apache dan MySQL statusnya Running.
2. Buka situs lokal di browser: masuk ke halaman utama (localhost/ryokourent) dan halaman admin (/wp-admin). Hasil yang diharapkan: halaman tampil, login admin berhasil.
3. Periksa plugin dan tema: di Dashboard → Plugins dan Appearance → Themes pastikan ryokourent-core aktif dan child theme aktif tanpa peringatan PHP.

2) Cek konfigurasi WordPress & wp-config
1. Buka Settings → General: pastikan Timezone di-set ke Asia/Jakarta (UTC+7).
2. Settings → Permalinks: pilih “Post name” lalu Save. Hasil: URL artikel/permalink menggunakan /%postname%/.
3. Verifikasi isi wp-config.php:
   - WP_DEBUG = false (tidak menampilkan error di publik).
   - DISALLOW_FILE_EDIT = true.
   - WP_POST_REVISIONS minimal 5 dan memory limit sesuai.
   Jika ada perubahan, restart Apache.

3) Uji alur booking (end-to-end) — versi manual
1. Dari browser ponsel atau mode responsif di DevTools desktop:
   - Buka landing page booking.
   - Pilih tanggal mulai & selesai; verifikasi durasi dan tarif otomatis terhitung.
   - Pilih rute tertentu (contoh: rute Bromo) dan lihat apakah sistem mengunci model armada yang sesuai (mis. Honda Trail CRF 150L).
   - Isi data pemesan (nama, nomor WA, nomor darurat, alamat).
   - Klik tombol “Booking via WhatsApp” atau tombol booking yang tersedia.
   Hasil yang diharapkan: browser membuka aplikasi/halaman WhatsApp (atau menghasilkan deep link) dengan pesan draft berisi kode booking unik (format internal RYK-YYYYMMDD-XXXX atau serupa).
2. Verifikasi penyimpanan:
   - Masuk ke WP-Admin → menu Penyewaan Motor (atau menu booking)
   - Pastikan booking yang dibuat tercatat dengan status awal (mis. status_menunggu).
3. Catatan: jika deployment lokal tidak membuka WhatsApp, cukup pastikan deep-link dibentuk dengan benar (cek atribut link atau query string pada tombol).

4) Uji AJAX & Nonce (penting untuk booking)
1. Buka DevTools → Network, lakukan proses booking yang memicu panggilan AJAX (mis. validasi tanggal/ketersediaan atau refresh nonce).
2. Cari request ke admin-ajax.php atau endpoint plugin; hasil yang diharapkan:
   - Response 200, body JSON berisi hasil validasi/nonce baru.
   - Pastikan permintaan ini tidak dilayani dari cache (cek header Cache-Control / response time).
3. Jika Anda mengaktifkan caching lokal (mis. plugin cache), pastikan pengecualian untuk admin-ajax.php diatur.

5) Uji proteksi double-booking dan kuota unit
1. Di WP-Admin → Master Armada, periksa jumlah unit fisik untuk satu model (misal 3 unit).
2. Lakukan booking paralel menggunakan dua tab/browser/ponsel berbeda untuk periode yang sama hingga mencapai batas unit.
   Hasil: sistem harus menolak booking ke-4 dan menandai model sebagai tidak tersedia untuk tanggal tersebut (double booking prevented).
3. Verifikasi di database (phpMyAdmin) tabel booking bahwa status dan alokasi unit tercatat benar.

6) Uji alokasi plat nomor & perubahan status
1. Buat booking, lalu sebagai Operator ubah status booking ke “status_berjalan” (atau status yang memicu alokasi plat).
2. Pastikan sistem meminta/menetapkan nomor plat fisik dan menolak jika terjadi bentrok (satu plat digunakan dua pesanan beririsan).
3. Periksa catatan log atau kolom meta di tabel terkait untuk memastikan nomor plat tersimpan.

7) Pengujian akses & RBAC (hak akses)
1. Buat akun staf/operator dengan role terbatas (ryokourent_operator).
2. Login sebagai operator:
   - Cek bahwa operator dapat melihat dan mengubah status booking tetapi tidak dapat mengakses menu Plugins/Themes/Users/Settings (harus mendapatkan 403 atau tidak muncul menu).
3. Coba akses URL admin langsung untuk menu yang dibatasi; hasil yang diharapkan: akses ditolak atau redirect.

8) Pengujian SSL & header keamanan (lokal)
1. Akses situs via HTTPS (https://localhost/ryokourent). Di browser, periksa panel security/connection:
   - HTTPS aktif (meskipun sertifikat lokal self-signed).
   - HSTS header bila Anda menambahkannya di konfigurasi Apache.
2. Di DevTools → Network → Response Headers, pastikan header seperti:
   - X-Frame-Options: SAMEORIGIN
   - X-Content-Type-Options: nosniff
   - Referrer-Policy: strict-origin-when-cross-origin
   Jika belum ada, tambahkan di konfigurasi Apache atau .htaccess.

9) Pengujian performa dasar & LCP
1. Gunakan DevTools → Performance dan Lighthouse (di browser) untuk mengukur waktu muat halaman mobile:
   - Target LCP < 2.0 detik dan Performance score mendekati 90 untuk produksi; pada mesin lokal mungkin lebih lambat, tapi bandingkan baseline sebelum/ sesudah optimasi.
2. Optimasi sederhana: aktifkan GZIP/mod_deflate di Apache, gunakan gambar WebP, minimalkan jumlah plugin berat.

10) Pengujian backup & pemulihan
1. Backup DB:
   - Di phpMyAdmin, Export database; simpan file .sql.
2. Backup file:
   - Salin folder C:\xampp\htdocs\ryokourent (terutama wp-content\uploads).
3. Simulasi pemulihan DB:
   - Hapus beberapa data test atau drop database.
   - Buat ulang database dan Import file .sql yang tadi diekspor.
   - Hasil diharapkan: data kembali seperti semula dan aplikasi bekerja.
4. Catatan: ujicoba restore diperlukan agar yakin prosedur pemulihan bisa berjalan saat darurat.

11) Pengujian rollback plugin/tema
1. Jika versi plugin terbaru bermasalah:
   - Di C:\xampp\htdocs\ryokourent\wp-content\plugins — ganti nama folder ryokourent-core menjadi ryokourent-core-failed untuk menonaktifkannya.
   - Upload / ekstrak versi stabil sebelumnya ke folder plugins.
   - Clear cache dan login kembali; verifikasi fungsi kembali normal.

12) Log & debugging (lokal)
1. Periksa file log Apache (C:\xampp\apache\logs\error.log) untuk error PHP/Apache.
2. Aktifkan WP_DEBUG_LOG sementara (tidak menampilkan error ke publik) untuk menemukan masalah: ini menulis ke wp-content/debug.log.
   - Setelah debugging selesai, matikan kembali WP_DEBUG.

13) Checklist akhir sebelum produksi (atau memberi demo)
- Semua uji fungsional berjalan (booking, alokasi, RBAC).
- Backup dan restore berhasil.
- SSL/Headers dasar terpasang.
- Permalinks dan timezone sudah benar.
- Operator dan staf terlatih untuk alur admin.

Ingin saya buat:
- Checklist uji yang bisa dicentang (printable) dalam format markdown?
- Skrip pengujian otomatis sederhana (mis. skrip PHP/JS untuk memanggil endpoint booking dan memeriksa response)?
- Panduan langkah demi langkah dengan screenshot untuk tiap langkah pengujian?

Pilih salah satu atau sebutkan kebutuhan Anda, saya bantu susun selanjutnya.