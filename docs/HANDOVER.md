# DOKUMEN HANDOVER & PANDUAN KONTINUITAS ANTAR-AI (INTER-AI HANDOVER)

> **Catatan Penting untuk Setiap AI:**
> Berkas ini adalah **Single Source of Truth untuk transisi pengerjaan antar-AI**. Setiap kali Anda menyelesaikan sebuah task, Anda **WAJIB memperbarui berkas ini** agar status sesi dan prompt di bagian bawah selalu menunjuk ke task berikutnya dengan tepat dan siap disalin oleh pengembang.

---

## 1. Status Sesi Terkini

* **Tanggal Pembaruan Terakhir:** 2026-10-07
* **Task Terakhir yang Selesai:** `TASK-023: Buat Perubahan Status Booking (Quick Actions & Validasi Plat)` (Status: **DONE** - 86/86 unit test `test-booking-status-actions.php` PASS).
* **Cabang Git Aktif Terakhir:** `feature/booking-status-actions` (atau `develop`).
* **Task Aktif Selanjutnya:** `TASK-024: Buat Pengaturan Harga dan Nomor WhatsApp (Admin Settings)` (Fase 3: Alur Pemesanan & Operasional).

---

## 2. Hirarki Berkas Acuan Wajib (Knowledge Base)

Setiap AI yang baru masuk ke proyek ini **WAJIB membaca dan mematuhi** berkas-berkas berikut sebelum membuat asumsi atau menulis kode:

| No | Berkas                               | Peran & Kandungan Kritis                                                                                                                                                                   |
| :-: | :----------------------------------- | :----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1 | `BLUEPRINT.md`                     | Aturan bisnis mutlak: durasi 24 jam (+ 2 jam grace period), operasional 07:00–23:00 WIB, rute Bromo wajib CRF 150L, paket harga harian/mingguan/bulanan, dan alur zero-friction WhatsApp. |
| 2 | `ARCHITECTURE.md`                  | Desain sistem modular WordPress (`ryokourent-core`), hooks & filters, isolasi child theme (`generatepress-child`), dan pembagian peran kode.                                           |
| 3 | `DECISIONS.md`                     | Architecture Decision Records (ADR-001 s/d ADR-011): alasan teknis di balik atomic lock, kerahasiaan kuota fisik/plat dari publik, isolasi status metabox, validasi plat nomor, dan quick actions. |
| 4 | `DATA_MODEL.md`                    | Skema CPT `motor`, CPT `penyewaan`, 5 custom post status (`status_menunggu`, dll), dan seluruh prefix meta `_ryokou_`.                                                              |
| 5 | `AI_RULES.md` & `AI_WORKFLOW.md` | Konstitusi pengembangan AI, larangan file terproteksi, anti-overengineering, dan protokol handover.                                                                                        |
| 6 | `SESSION_STATE.md`                 | Catatan pelacakan runtime sesi aktif dan status penyelesaian task.                                                                                                                         |
| 7 | `TASKS.md`                         | Spesifikasi lengkap 30 task terstruktur, kriteria selesai, dependensi, dan pengujian.                                                                                                      |
| 8 | `docs/GIT_WORKFLOW.md`             | Standar branching (`main`, `develop`, `feature/*`) dan format pesan commit (*Conventional Commits*).                                                                               |
| 9 | `docs/REVIEW-ARCHITECTURE.md`      | Catatan audit arsitektur mendalam (reviewOP) untuk pencegahan celah keamanan dan inkonsistensi.                                                                                            |

---

## 3. Rincian Task Selanjutnya: TASK-024

* **Nama Task:** `TASK-024: Buat Pengaturan Harga dan Nomor WhatsApp (Admin Settings)`
* **Tujuan:** Membuat antarmuka pengaturan admin (`manage_ryokourent_settings`) untuk nomor WhatsApp admin resmi, teks default, jam operasional, dan fitur multi-update harga (bulk price adjustment nominal/persentase untuk peak season).
* **File yang Dibuat / Diubah:**
  * `wp-content/plugins/ryokourent-core/admin/admin-settings.php` (baru)
  * `wp-content/plugins/ryokourent-core/includes/settings.php` (baru/diperbarui)
  * `wp-content/plugins/ryokourent-core/includes/pricing.php`
  * `wp-content/plugins/ryokourent-core/tests/test-admin-settings.php` (baru)
* **Dependensi:** TASK-014, TASK-021 (keduanya selesai).
* **Kriteria Selesai:**
  1. Dilindungi nonce `check_admin_referer` dan capability `manage_ryokourent_settings`.
  2. Operator (`manage_ryokourent_bookings` saja) ditolak akses ke halaman settings dengan pesan HTTP 403 Forbidden via `ryokourent_check_settings_permission_or_die()`.
  3. Admin dapat mengubah nomor tujuan WhatsApp dan menerapkan penyesuaian harga bulk per kategori motor.
  4. Penyesuaian memiliki batas nilai angka (tidak boleh menghasilkan harga $\le 0$ atau persentase ekstrem $> 200\%$).
* **Cara Pengujian:** Naikkan harga kategori BeAT +10.000 melalui bulk update, periksa perubahan harga pada katalog. Uji input angka negatif atau tidak valid; pastikan ditolak. Jalankan unit test yang baru dibuat.
* **Risiko:** Salah input formula persentase yang merusak data harga master jika tidak divalidasi batasnya.

---

## 4. Aturan Kerja Wajib untuk AI yang Melanjutkan

1. **Fokus Tunggal:** Kerjakan HANYA satu task yang ditugaskan (TASK-024). Jangan menyentuh atau mendahului pengerjaan TASK-025 dst.
2. **Uji Sebelum Klaim Selesai:** Selalu buat file unit test di `wp-content/plugins/ryokourent-core/tests/` dan pastikan seluruh test lolos sebelum menandai status task sebagai **COMPLETED**.
3. **Format Laporan Wajib (8 Poin):**
   * Task
   * Tujuan
   * File dibuat
   * File diubah
   * Implementasi
   * Pengujian
   * Risiko / TODO
   * Status
4. **Pembaruan Berkas Pelacakan:**
   Setelah task selesai, Anda WAJIB memperbarui:
   * `SESSION_STATE.md`
   * `TASKS.md`
   * `CHANGELOG.md`
   * **`docs/HANDOVER.md`** (perbarui status ke TASK-024 DONE dan siapkan prompt untuk TASK-025).

---

## 5. PROMPT SIAP SALIN (READY-TO-USE COPY-PASTE PROMPT)

> **Untuk Pengembang:**
> Salin seluruh teks di dalam blok kutipan berikut dan kirimkan langsung ke AI berikutnya (Claude, ChatGPT, Gemini, atau model lainnya) untuk melanjutkan proyek secara mulus:

```markdown
Halo! Anda bertindak sebagai Lead Software Engineer untuk proyek "Ryokourent" (Platform Rental Motor Malang & Batu berbasis WordPress Native Plugin & Child Theme).

SEBELUM MENULIS KODE, WAJIB BACA & PAHAMI DOKUMEN ARSITEKTUR BERIKUT:
1. `BLUEPRINT.md` : Aturan bisnis rental motor (durasi 24 jam + 2 jam toleransi, operasional 07:00-23:00 WIB, rute Bromo wajib Trail CRF 150L, paket harga, zero-friction WhatsApp).
2. `ARCHITECTURE.md` : Struktur arsitektur modular plugin ryokourent-core, isolasi child theme, hook lifecycle.
3. `DECISIONS.md` : Catatan ADR teknis (ADR-001 s/d ADR-011).
4. `DATA_MODEL.md` : Skema CPT motor, CPT penyewaan, custom status booking, meta prefix _ryokou_.
5. `AI_RULES.md` & `AI_WORKFLOW.md` : Aturan kerja tim, larangan over-engineering, protokol handover berkas docs/HANDOVER.md.
6. `SESSION_STATE.md` : Status riwayat task dan posisi sesi saat ini.
7. `TASKS.md` : Rincian lengkap 30 task proyek dan kriteria selesai.
8. `docs/GIT_WORKFLOW.md` : Standar branching Git dan Conventional Commits.
9. `docs/REVIEW-ARCHITECTURE.md` : Catatan reviewOP arsitektur (nonce, capability, whitelist, validasi plat, hindari posts_per_page -1).

STATUS PROYEK SAAT INI:
- TASK-001 sampai TASK-023 telah SELESAI (DONE).
- Task aktif yang harus Anda kerjakan sekarang adalah: TASK-024: Buat Pengaturan Harga dan Nomor WhatsApp (Admin Settings).

TUGAS ANDA SEKARANG (KERJAKAN HANYA TASK-024):
1. Buat branch fitur dari `develop` (misal `feature/admin-settings`).
2. Buat antarmuka pengaturan admin di `admin/admin-settings.php` dan logika pendukung di `includes/settings.php` & `includes/pricing.php`:
   - Lindungi dengan nonce `check_admin_referer` dan capability `manage_ryokourent_settings`.
   - Gunakan guard server-side `ryokourent_check_settings_permission_or_die()` untuk menolak akses operator (HTTP 403 Forbidden).
   - Sediakan form pengaturan: nomor resmi WhatsApp admin (`ryokourent_wa_number`), jam operasional (default 07:00 - 23:00 WIB), alamat & tautan GMaps pool resmi.
   - Sediakan fitur multi-update harga (bulk price adjustment nominal atau persentase per kategori motor untuk peak season) dengan validasi batas (harga tidak boleh <= 0, persentase tidak boleh ekstrem > 200%).
3. Buat automated unit test di `wp-content/plugins/ryokourent-core/tests/test-admin-settings.php` (ikuti gaya `run_test` pada test yang ada).
4. Perbarui berkas dokumentasi pelacakan HANYA setelah test lulus:
   - `SESSION_STATE.md` (tandai TASK-024 DONE, set next task TASK-025).
   - `TASKS.md` (tandai TASK-024 [DONE]).
   - `CHANGELOG.md` (tambahkan entri TASK-024).
   - `docs/HANDOVER.md` (perbarui status ke TASK-024 DONE dan siapkan prompt untuk TASK-025).
5. Buat commit Git dengan Conventional Commits (misal: `feat(settings): add admin settings and bulk price adjustment`).

CATATAN: Nilai `_ryokou_booking_pickup_loc` (label) berbeda dari key metabox `_ryokou_pickup_location`; jangan diubah diam-diam, usulkan task terpisah.

FORMAT LAPORAN WAJIB (8 Poin):
1. Task
2. Tujuan
3. File dibuat
4. File diubah
5. Implementasi
6. Pengujian (sertakan skenario test suite)
7. Risiko / TODO
8. Status (COMPLETED hanya setelah pengujian valid)

PENTING: Kerjakan HANYA TASK-024. Jangan melompat atau mencampurkan pekerjaan ke task lain. Silakan mulai dengan mempelajari dokumen acuan dan kerjakan TASK-024 sekarang.
```
