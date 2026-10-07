# DOKUMEN HANDOVER & PANDUAN KONTINUITAS ANTAR-AI (INTER-AI HANDOVER)

> **Catatan Penting untuk Setiap AI:**
> Berkas ini adalah **Single Source of Truth untuk transisi pengerjaan antar-AI**. Setiap kali Anda menyelesaikan sebuah task, Anda **WAJIB memperbarui berkas ini** agar status sesi dan prompt di bagian bawah selalu menunjuk ke task berikutnya dengan tepat dan siap disalin oleh pengembang.

---

## 1. Status Sesi Terkini

* **Tanggal Pembaruan Terakhir:** 2026-10-07
* **Task Terakhir yang Selesai:** `TASK-026: Buat Responsive Design & Mobile-First Optimization` (Status: **DONE** - 34/34 unit test `test-responsive-design.php` PASS).
* **Cabang Git Aktif Terakhir:** `feature/mobile-responsive-optimization` (siap dimerge ke `develop`).
* **Task Aktif Selanjutnya:** `TASK-027: Buat Validasi Keamanan (Security Hardening)` (Fase 4: Audit Keamanan & Hardening WordPress).

---

## 2. Hirarki Berkas Acuan Wajib (Knowledge Base)

Setiap AI yang baru masuk ke proyek ini **WAJIB membaca dan mematuhi** berkas-berkas berikut sebelum membuat asumsi atau menulis kode:

| No | Berkas                               | Peran & Kandungan Kritis                                                                                                                                                                   |
| :-: | :----------------------------------- | :----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1 | `BLUEPRINT.md`                     | Aturan bisnis mutlak: durasi 24 jam (+ 2 jam grace period), operasional 07:00–23:00 WIB, rute Bromo wajib CRF 150L, paket harga harian/mingguan/bulanan, dan alur zero-friction WhatsApp. |
| 2 | `ARCHITECTURE.md`                  | Desain sistem modular WordPress (`ryokourent-core`), hooks & filters, isolasi child theme (`generatepress-child`), dan pembagian peran kode.                                           |
| 3 | `DECISIONS.md`                     | Architecture Decision Records (ADR-001 s/d ADR-012): alasan teknis di balik atomic lock, isolasi status, validasi plat nomor, quick action, dan batas penyesuaian harga massal.        |
| 4 | `DATA_MODEL.md`                    | Skema CPT `motor`, CPT `penyewaan`, 5 custom post status (`status_menunggu`, dll), dan seluruh prefix meta `_ryokou_`.                                                              |
| 5 | `AI_RULES.md` & `AI_WORKFLOW.md` | Konstitusi pengembangan AI, larangan file terproteksi, anti-overengineering, dan protokol handover.                                                                                        |
| 6 | `SESSION_STATE.md`                 | Catatan pelacakan runtime sesi aktif dan status penyelesaian task.                                                                                                                         |
| 7 | `TASKS.md`                         | Spesifikasi lengkap 30 task terstruktur, kriteria selesai, dependensi, dan pengujian.                                                                                                      |
| 8 | `docs/GIT_WORKFLOW.md`             | Standar branching (`main`, `develop`, `feature/*`) dan format pesan commit (*Conventional Commits*).                                                                               |
| 9 | `docs/REVIEW-ARCHITECTURE.md`      | Catatan audit arsitektur mendalam (reviewOP) untuk pencegahan celah keamanan dan inkonsistensi.                                                                                            |

---

## 3. Rincian Task Selanjutnya: TASK-027

* **Nama Task:** `TASK-027: Buat Validasi Keamanan (Security Hardening)`
* **Tujuan:** Melakukan audit keamanan menyeluruh pada seluruh file plugin `ryokourent-core`: sanitasi seluruh input (`sanitize_text_field`, `sanitize_key`, `absint`), escaping seluruh output (`esc_html`, `esc_attr`, `esc_url`), verifikasi nonce pada setiap request POST/AJAX, proteksi eksekusi langsung file PHP (`defined('ABSPATH') || exit;`), dan capability check ketat.
* **File yang Dibuat / Diubah:**
  * Seluruh file di `wp-content/plugins/ryokourent-core/`
  * `wp-content/plugins/ryokourent-core/tests/test-security-hardening.php` (baru)
* **Dependensi:** TASK-002 s/d TASK-026.
* **Kriteria Selesai:**
  1. Seluruh 100% file PHP di plugin memiliki proteksi akses langsung `ABSPATH`.
  2. Seluruh endpoint form, AJAX handler, dan admin post action terlindungi nonce dan pengecekan capability spesifik.
  3. Seluruh output dinamis dieksekusi dengan escaping kontekstual (`esc_html`, `esc_attr`, `esc_url`).
  4. Lolos unit test keamanan XSS, CSRF, dan direct script execution prevention.
* **Cara Pengujian:** Jalankan unit test `tests/test-security-hardening.php`.
* **Risiko:** False positive atau ada escaping ganda pada teks berformat HTML sah.

---

## 4. Aturan Kerja Wajib untuk AI yang Melanjutkan

1. **Fokus Tunggal:** Kerjakan HANYA satu task yang ditugaskan (TASK-027). Jangan menyentuh atau mendahului pengerjaan TASK-028 dst.
2. **Uji Sebelum Klaim Selesai:** Buat file unit test di `wp-content/plugins/ryokourent-core/tests/` dan pastikan seluruh test lolos sebelum menandai status task sebagai **COMPLETED**.
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
   * **`docs/HANDOVER.md`** (perbarui status ke TASK-027 DONE dan siapkan prompt untuk TASK-028).

---

## 5. PROMPT SIAP SALIN (READY-TO-USE COPY-PASTE PROMPT)

> **Untuk Pengembang:**
> Salin seluruh teks di dalam blok kutipan berikut dan kirimkan langsung ke AI berikutnya (Claude, ChatGPT, Gemini, atau model lainnya) untuk melanjutkan proyek secara mulus:

```markdown
Halo! Anda bertindak sebagai Lead Software Engineer untuk proyek "Ryokourent" (Platform Rental Motor Malang & Batu berbasis WordPress Native Plugin & Child Theme).

SEBELUM MENULIS KODE, WAJIB BACA & PAHAMI DOKUMEN ARSITEKTUR BERIKUT:
1. `BLUEPRINT.md` : Aturan bisnis rental motor (durasi 24 jam + 2 jam toleransi, operasional 07:00-23:00 WIB, rute Bromo wajib Trail CRF 150L, paket harga, zero-friction WhatsApp).
2. `ARCHITECTURE.md` : Struktur arsitektur modular plugin ryokourent-core, isolasi child theme, hook lifecycle.
3. `DECISIONS.md` : Catatan ADR teknis (ADR-001 s/d ADR-012).
4. `DATA_MODEL.md` : Skema CPT motor, CPT penyewaan, custom status booking, meta prefix _ryokou_.
5. `AI_RULES.md` & `AI_WORKFLOW.md` : Aturan kerja tim, larangan over-engineering, protokol handover berkas docs/HANDOVER.md.
6. `SESSION_STATE.md` : Status riwayat task dan posisi sesi saat ini.
7. `TASKS.md` : Rincian lengkap 30 task proyek dan kriteria selesai.
8. `docs/GIT_WORKFLOW.md` : Standar branching Git dan Conventional Commits.
9. `docs/REVIEW-ARCHITECTURE.md` : Catatan reviewOP arsitektur (nonce, capability, whitelist, validasi plat, hindari posts_per_page -1).

STATUS PROYEK SAAT INI:
- TASK-001 sampai TASK-026 telah SELESAI (DONE).
- Task aktif yang harus Anda kerjakan sekarang adalah: TASK-027: Buat Validasi Keamanan (Security Hardening).

TUGAS ANDA SEKARANG (KERJAKAN HANYA TASK-027):
1. Buat branch fitur dari `develop` (misal `feature/security-hardening`).
2. Lakukan audit menyeluruh pada seluruh berkas `wp-content/plugins/ryokourent-core/`:
   - Pastikan setiap file PHP memiliki pengecekan `if (!defined('ABSPATH')) exit;`.
   - Pastikan seluruh input request disanitasi (`sanitize_text_field`, `sanitize_key`, `absint`, `sanitize_textarea_field`).
   - Pastikan seluruh output di-escape secara aman (`esc_html`, `esc_attr`, `esc_url`, `esc_textarea`).
   - Pastikan seluruh endpoint POST, metabox save, status transition, dan AJAX dilindungi nonce (`check_admin_referer`, `wp_verify_nonce`, `check_ajax_referer`) dan capability check (`manage_ryokourent_bookings` / `manage_ryokourent_settings`).
3. Buat automated unit test di `wp-content/plugins/ryokourent-core/tests/test-security-hardening.php`.
4. Perbarui berkas dokumentasi pelacakan HANYA setelah test lulus:
   - `SESSION_STATE.md` (tandai TASK-027 DONE, set next task TASK-028).
   - `TASKS.md` (tandai TASK-027 [DONE]).
   - `CHANGELOG.md` (tambahkan entri TASK-027).
   - `docs/HANDOVER.md` (perbarui status ke TASK-027 DONE dan siapkan prompt untuk TASK-028).
5. Buat commit Git dengan Conventional Commits (misal: `feat(security): implement security hardening, nonce audit, and escaping`).

FORMAT LAPORAN WAJIB (8 Poin):
1. Task
2. Tujuan
3. File dibuat
4. File diubah
5. Implementasi
6. Pengujian (sertakan skenario test suite)
7. Risiko / TODO
8. Status (COMPLETED hanya setelah pengujian valid)

PENTING: Kerjakan HANYA TASK-027. Jangan melompat atau mencampurkan pekerjaan ke task lain. Silakan mulai dengan mempelajari dokumen acuan dan kerjakan TASK-027 sekarang.
```

