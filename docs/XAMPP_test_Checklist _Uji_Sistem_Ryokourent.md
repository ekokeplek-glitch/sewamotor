Berikut adalah checklist uji yang bisa dicentang untuk memastikan sistem Ryokourent berjalan dengan baik setelah instalasi. Checklist ini disusun dalam format markdown agar mudah dicetak atau digunakan dalam dokumen digital.

---

# Checklist Uji Sistem Ryokourent

## 1. Pemeriksaan Awal (Smoke Test)
- [ ] Apache dan MySQL berjalan di XAMPP Control Panel (status Running)
- [ ] Halaman utama Ryokourent dapat diakses di browser (`http://localhost/ryokourent`)
- [ ] Halaman admin WordPress dapat diakses (`http://localhost/ryokourent/wp-admin`)
- [ ] Plugin core Ryokourent aktif tanpa error di Dashboard → Plugins
- [ ] Tema child Ryokourent aktif tanpa error di Dashboard → Appearance → Themes

## 2. Konfigurasi WordPress & wp-config
- [ ] Timezone di Settings → General sudah diatur ke Asia/Jakarta (UTC+7)
- [ ] Permalink diatur ke “Post name” di Settings → Permalinks
- [ ] WP_DEBUG diset ke false di wp-config.php
- [ ] DISALLOW_FILE_EDIT diset ke true di wp-config.php
- [ ] WP_POST_REVISIONS diatur minimal 5 di wp-config.php
- [ ] WP_MEMORY_LIMIT dan WP_MAX_MEMORY_LIMIT diatur minimal 256M

## 3. Uji Alur Booking Manual
- [ ] Booking dapat dilakukan melalui halaman booking di browser (desktop/mobile)
- [ ] Pemilihan tanggal mulai dan selesai berfungsi dan tarif otomatis terhitung
- [ ] Sistem mengunci model armada sesuai rute yang dipilih
- [ ] Form data pemesan (nama, nomor WA, nomor darurat, alamat) dapat diisi dan disubmit
- [ ] Tombol booking via WhatsApp menghasilkan deep link dengan kode booking unik
- [ ] Booking tercatat di WP-Admin dengan status awal yang benar

## 4. Uji AJAX dan Nonce
- [ ] Request AJAX ke admin-ajax.php berhasil dengan response 200
- [ ] Response AJAX berisi data valid dan nonce baru
- [ ] Cache tidak mengganggu request AJAX (bypass cache untuk admin-ajax.php)

## 5. Uji Proteksi Double Booking dan Kuota Unit
- [ ] Jumlah unit armada sesuai dengan data master armada
- [ ] Booking paralel tidak melebihi kuota unit yang tersedia
- [ ] Sistem menolak booking kelebihan kuota dan menandai unit tidak tersedia

## 6. Uji Alokasi Plat Nomor dan Perubahan Status Booking
- [ ] Status booking dapat diubah oleh operator sesuai alur
- [ ] Sistem menetapkan nomor plat fisik dengan validasi bentrok
- [ ] Nomor plat tersimpan dengan benar di database

## 7. Uji Hak Akses Operator (RBAC)
- [ ] Operator dengan role terbatas dapat mengakses menu booking dan status
- [ ] Operator tidak dapat mengakses menu Plugins, Themes, Users, atau Settings
- [ ] Akses URL admin yang dibatasi menghasilkan penolakan atau redirect

## 8. Uji SSL dan Header Keamanan (Jika SSL Aktif)
- [ ] Situs dapat diakses melalui HTTPS (`https://localhost/ryokourent`)
- [ ] Sertifikat SSL terpasang (meskipun self-signed)
- [ ] Header keamanan seperti X-Frame-Options, X-Content-Type-Options, Referrer-Policy aktif

## 9. Uji Performa Dasar
- [ ] Waktu muat halaman utama kurang dari 2 detik (LCP < 2.0s)
- [ ] Skor performa Lighthouse mendekati 90 pada mode mobile

## 10. Uji Backup dan Pemulihan
- [ ] Backup database berhasil dilakukan dan file .sql tersimpan
- [ ] Backup folder wp-content/uploads berhasil dilakukan
- [ ] Pemulihan database dari backup berjalan lancar tanpa error

## 11. Uji Rollback Plugin/Tema
- [ ] Plugin/tema dapat dinonaktifkan dengan mengganti nama folder
- [ ] Versi stabil plugin/tema dapat diupload dan diaktifkan kembali
- [ ] Fungsi sistem kembali normal setelah rollback

## 12. Log dan Debugging
- [ ] File log Apache tidak berisi error kritis
- [ ] WP_DEBUG_LOG aktif saat debugging dan file debug.log tercipta
- [ ] WP_DEBUG dimatikan setelah debugging selesai

---

Checklist ini dapat digunakan sebagai panduan lengkap untuk memastikan instalasi dan konfigurasi Ryokourent berjalan dengan baik dan siap digunakan. Jika Anda ingin, saya juga bisa membantu membuatkan versi checklist ini dalam format lain seperti Excel atau PDF.