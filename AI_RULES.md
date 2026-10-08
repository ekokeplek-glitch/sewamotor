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
- Protokol Selesai Task: Setiap kali sebuah task selesai dan teruji, AI WAJIB memperbarui SESSION_STATE.md (sumber kebenaran status), TASKS.md, dan CHANGELOG.md. Berkas docs/HANDOVER.md hanya berisi prompt siap salin untuk AI berikutnya; AI cukup memperbarui bagian "Task berikutnya" pada prompt tersebut dan tidak menulis status sesi di sana.
