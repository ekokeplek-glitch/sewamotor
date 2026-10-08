# HANDOVER: PROMPT JEMBATAN ANTAR-AI

> **Fungsi tunggal:** menyediakan prompt siap salin agar AI yang berbeda (untuk optimasi peran) bisa melanjutkan proyek tanpa kehilangan konteks.
> **Bukan** tempat status. Status sesi, cabang Git, riwayat task, dan hasil pengujian hanya ada di `SESSION_STATE.md`. Spesifikasi task hanya ada di `TASKS.md`.

**Tugas AI setiap selesai task:** ganti hanya blok `TARGET PEKERJAAN` pada prompt di bawah (task berikutnya, peran AI, catatan khusus). Jangan mengubah bagian lain.

---

## PROMPT SIAP SALIN

```markdown
Anda bertindak sebagai engineer untuk proyek "Ryokourent": rental motor Malang & Batu, WordPress native, plugin `ryokourent-core` + child theme `generatepress-child`. Anda melanjutkan pekerjaan AI sebelumnya, jadi jangan berasumsi: baca dulu, baru bertindak.

## TARGET PEKERJAAN
- Task: FINAL RELEASE & PR REVIEW (Semua 30 Task Selesai - Penggabungan Branch develop & main)
- Peran Anda (lihat `AI_WORKFLOW.md` §1): AI Reviewer & Release Engineer
- Catatan khusus (opsional): Seluruh implementasi FASE 0 s/d FASE 4 (TASK-001 s/d TASK-030) telah selesai 100%. Verifikasi PR dan persiapan tag rilis v1.0.0.

## URUTAN BACA (WAJIB, SEBELUM MENULIS KODE)
1. `SESSION_STATE.md`: SUMBER KEBENARAN status (cabang aktif, task terakhir, task selanjutnya, riwayat). Jika dokumen lain bertentangan soal status, ikuti berkas ini.
2. `TASKS.md`: spesifikasi task target (tujuan, file, dependensi, kriteria selesai, cara pengujian, risiko).
3. `AI_RULES.md` dan `AI_WORKFLOW.md`: aturan kerja, pembagian peran, Human Review Gate, checklist PR.
4. `BLUEPRINT.md`, `ARCHITECTURE.md`, `DATA_MODEL.md`, `DECISIONS.md`: aturan bisnis, struktur, skema, ADR.
5. `GIT_WORKFLOW.md`: branching dan Conventional Commits.
6. `REVIEW-ARCHITECTURE.md`: catatan reviewOP (K1-K6, M1-M15) dan skenario uji ulang di bagian 5. Wajib untuk task keamanan dan pengujian.
7. `TESTING.md`: matriks skenario uji. Wajib untuk TASK-028.
Cari semua berkas di atas di repo. Jika ada yang tidak ditemukan, sebutkan di laporan dan jangan merekonstruksi isinya.

## ATURAN KERJA
1. Kerjakan HANYA task target. Jangan menyentuh atau mendahului task lain.
2. Buat cabang kerja dari `develop` sesuai `GIT_WORKFLOW.md`.
3. Jangan mengubah file fitur lain tanpa alasan. Jika terpaksa, tulis alasannya di laporan.
4. Berhenti dan minta persetujuan manusia bila pekerjaan menyentuh skema data/post status, aturan bisnis/tarif, atau penyimpanan data identitas (`AI_WORKFLOW.md` §2).
5. Verifikasi sebelum klaim selesai: `php -l` pada file yang diubah, file uji di `wp-content/plugins/ryokourent-core/tests/`, dan `phpcs` (WordPress-Core, WordPress-Security) bila tersedia. Jika suatu alat tidak bisa dijalankan di environment Anda, catat sebagai TODO. Jangan menulis "lulus" untuk yang tidak dijalankan.
6. Setelah task selesai dan teruji, perbarui: `SESSION_STATE.md`, `TASKS.md`, `CHANGELOG.md`, lalu `docs/HANDOVER.md` (hanya blok TARGET PEKERJAAN).
7. Commit dengan Conventional Commits.

## FORMAT LAPORAN (8 POIN)
1. Task, 2. Tujuan, 3. File dibuat, 4. File diubah, 5. Implementasi, 6. Pengujian (apa yang dijalankan dan hasilnya), 7. Risiko / TODO, 8. Status (COMPLETED hanya setelah pengujian valid).
```
