# DOKUMEN HANDOVER & PANDUAN KONTINUITAS ANTAR-AI (INTER-AI HANDOVER)

> **Catatan Penting untuk Setiap AI:**
> Berkas ini adalah **Single Source of Truth untuk transisi pengerjaan antar-AI**. Setiap kali Anda menyelesaikan sebuah task, Anda **WAJIB memperbarui berkas ini** agar status sesi dan prompt di bagian bawah selalu menunjuk ke task berikutnya dengan tepat dan siap disalin oleh pengembang.

---

## 1. Status Sesi Terkini

* **Tanggal Pembaruan Terakhir:** 2026-10-07
* **Task Terakhir yang Selesai:** `TASK-025: Buat Halaman FAQ dan Lokasi Pool` (Status: **DONE** - 54/54 unit test `test-faq-pool.php` PASS).
* **Cabang Git Aktif Terakhir:** `feature/faq-and-pool-locations` (siap dimerge ke `develop`).
* **Task Aktif Selanjutnya:** `TASK-026: Buat Responsive Design & Mobile-First Optimization` (Fase 4: Antarmuka Publik & Optimasi Mobile).

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

## 3. Rincian Task Selanjutnya: TASK-026

* **Nama Task:** `TASK-026: Buat Responsive Design & Mobile-First Optimization`
* **Tujuan:** Mengoptimalkan seluruh elemen UI (katalog, form booking, floating mobile bar < 15% viewport, navigasi) agar tampil sempurna di resolusi smartphone 360px - 430px.
* **File yang Dibuat / Diubah:**
  * `wp-content/themes/generatepress-child/style.css`
  * `wp-content/plugins/ryokourent-core/assets/css/ryokourent-public.css`
  * `wp-content/plugins/ryokourent-core/assets/js/ryokourent-filter.js`
  * `wp-content/plugins/ryokourent-core/tests/test-responsive-design.php` (baru)
* **Dependensi:** TASK-011, TASK-025.
* **Kriteria Selesai:**
  1. Tidak ada horizontal overflow pada viewport sempit (360px s/d 430px).
  2. Floating action bar WhatsApp dan CTA nyaman dijangkau satu tangan (*thumb zone*) serta tinggi maksimal < 15% tinggi viewport.
  3. Modul katalog, modal spesifikasi, formulir booking, kartu lokasi pool, dan accordion FAQ tampil proporsional tanpa penumpukan teks.
  4. Seluruh touch target berukuran minimal 44x44px sesuai pedoman aksesibilitas seluler.
* **Cara Pengujian:** Jalankan unit test css/responsif, periksa styling, serta audit emulasi mobile (viewport 360x640, 390x844, 412x915).
* **Risiko:** Floating bar menutupi tombol penting pada form atau footer.

---

## 4. Aturan Kerja Wajib untuk AI yang Melanjutkan

1. **Fokus Tunggal:** Kerjakan HANYA satu task yang ditugaskan (TASK-026). Jangan menyentuh atau mendahului pengerjaan TASK-027 dst.
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
   * **`docs/HANDOVER.md`** (perbarui status ke TASK-026 DONE dan siapkan prompt untuk TASK-027).

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
- TASK-001 sampai TASK-025 telah SELESAI (DONE).
- Task aktif yang harus Anda kerjakan sekarang adalah: TASK-026: Buat Responsive Design & Mobile-First Optimization.

TUGAS ANDA SEKARANG (KERJAKAN HANYA TASK-026):
1. Buat branch fitur dari `develop` (misal `feature/mobile-responsive-optimization`).
2. Optimalkan seluruh elemen UI (katalog, formulir booking, modal detail motor, lokasi pool, FAQ, floating bar WhatsApp) untuk kenyamanan smartphone 360px - 430px di `wp-content/themes/generatepress-child/style.css` dan `wp-content/plugins/ryokourent-core/assets/css/ryokourent-public.css`.
3. Pastikan floating mobile bar tidak melebihi 15% viewport height dan tombol touch target minimal 44x44px tanpa horizontal scroll.
4. Buat automated unit test di `wp-content/plugins/ryokourent-core/tests/test-responsive-design.php`.
5. Perbarui berkas dokumentasi pelacakan HANYA setelah test lulus:
   - `SESSION_STATE.md` (tandai TASK-026 DONE, set next task TASK-027).
   - `TASKS.md` (tandai TASK-026 [DONE]).
   - `CHANGELOG.md` (tambahkan entri TASK-026).
   - `docs/HANDOVER.md` (perbarui status ke TASK-026 DONE dan siapkan prompt untuk TASK-027).
6. Buat commit Git dengan Conventional Commits (misal: `feat(responsive): optimize mobile viewport and floating bar`).

FORMAT LAPORAN WAJIB (8 Poin):
1. Task
2. Tujuan
3. File dibuat
4. File diubah
5. Implementasi
6. Pengujian (sertakan skenario test suite)
7. Risiko / TODO
8. Status (COMPLETED hanya setelah pengujian valid)

PENTING: Kerjakan HANYA TASK-026. Jangan melompat atau mencampurkan pekerjaan ke task lain. Silakan mulai dengan mempelajari dokumen acuan dan kerjakan TASK-026 sekarang.
```

