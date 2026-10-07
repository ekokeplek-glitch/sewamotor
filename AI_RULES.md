# AI_RULES.md

- Proyek menggunakan WordPress dan PHP.
- Logika bisnis harus berada di plugin ryokourent-core.
- Jangan menaruh fitur penting hanya di functions.php.
- Gunakan prefix ryokourent_ untuk function, hook, dan option.
- Jangan mengubah database secara langsung tanpa migration atau pengecekan.
- Semua input harus disanitasi.
- Semua output harus di-escape.
- Semua aksi admin wajib memakai nonce dan capability check.
- Jangan mengubah file fitur lain tanpa alasan.
- Setiap fitur harus memiliki langkah pengujian.
- Protokol Selesai Task: Setiap kali sebuah task selesai (selain memperbarui SESSION_STATE.md, TASKS.md, dan CHANGELOG.md), AI WAJIB memperbarui berkas docs/HANDOVER.md yang berisi status sesi terkini dan prompt siap salin (*copy-paste*) untuk menuntun AI berikutnya agar proyek dapat berpindah antar AI secara mulus tanpa deviasi alur.
