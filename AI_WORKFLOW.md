# AI WORKFLOW & GOVERNANCE: RYOKOURENT

Dokumen ini mengatur pembagian peran kerja, alur tinjauan kode (*code review*), standar git branching, dan batasan operasional dalam pengembangan proyek Ryokourent.

---

## 1. Pembagian Peran AI & Pengembang

### A. AI Utama (Lead Developer & Architect)
Bertanggung jawab atas:
* Perancangan arsitektur, data model, dan kontrak API/AJAX.
* Implementasi logika bisnis kritis (kalkulasi harga, ketersediaan unit, pencegahan double booking, CPT & metabox).
* Integrasi sistem inti WordPress (hooks, filters, capabilities, role-based access control).
* Alokasi task: **TASK-001 s/d TASK-006**, **TASK-009 s/d TASK-010**, **TASK-013 s/d TASK-024**, **TASK-027**.

### B. AI Khusus UI / Frontend & Konten
Bertanggung jawab atas:
* Desain responsif mobile-first, micro-interactions, layout CSS, dan tipografi.
* Form booking visual, live preview generator WhatsApp, dan kartu katalog armada.
* Halaman FAQ, info pool, dan panduan rute wisata (Malang, Batu, Bromo).
* Alokasi task: **TASK-007 s/d TASK-008**, **TASK-011**, **TASK-018**, **TASK-025**, **TASK-026**.

### C. AI Reviewer & Security Auditor
Bertanggung jawab atas:
* Pemeriksaan kepatuhan terhadap WordPress Coding Standards (WPCS).
* Audit sanitasi input (`sanitize_text_field`), output escaping (`esc_html`), dan verifikasi nonce/capabilities.
* Analisis *race condition* pada pencegahan double booking.
* Validasi independen sebelum merge ke branch `develop` / `main`.
* Alokasi task: Review pada setiap task, khusus **TASK-027** dan **TASK-028**.

### D. AI Dokumentasi & DevOps
Bertanggung jawab atas:
* Panduan instalasi lokal dan server hosting (LiteSpeed/Apache/Nginx).
* SOP operasional untuk staf admin dan operator.
* Checklist deployment, backup, dan rencana rollback.
* Alokasi task: **TASK-029**, **TASK-030**.

---

## 2. Kapan Harus Melakukan Review Manual (Human Review Gate)
Review manual oleh *Lead Engineer / Product Owner (Human)* **WAJIB** dilakukan pada kondisi berikut:
1. **Perubahan Skema Data atau Post Status:** Penambahan atau perubahan field pada CPT `motor` dan `penyewaan`.
2. **Aturan Bisnis & Kebijakan Tarif:** Perubahan formula hitung harga harian/mingguan/bulanan atau aturan batas wilayah rute.
3. **Penyimpanan Data Identitas:** Jika terdapat kebutuhan menyimpan data sensitif pelanggan (e-KTP, SIM).
4. **Sebelum Merge ke Branch `main`:** Seluruh fungsionalitas harus lulus checklist FASE 3 sebelum siap masuk ke lingkungan produksi (FASE 4).

---

## 3. Aturan Penggunaan Branch Git (Git Flow)

### A. Struktur Cabang (Branches)
* `main`: Branch produksi resmi. Kode harus 100% stabil, telah diaudit, dan siap rilis.
* `develop`: Branch integrasi utama seluruh fitur.
* `feature/id-deskripsi-singkat`: Branch untuk mengerjakan task spesifik (dibuat dari `develop`).
* `fix/id-deskripsi-masalah`: Branch untuk perbaikan bug yang ditemukan saat testing.
* `hotfix/deskripsi-darurat`: Branch darurat dari `main` jika ditemukan masalah kritis di produksi.

### B. Konvensi Penamaan Branch
Mengikuti `GIT_WORKFLOW.md` (sumber acuan tunggal): `feature/id-deskripsi-singkat`, `fix/id-deskripsi-masalah`, `hotfix/deskripsi-darurat`.
* `feature/cpt-motor`
* `feature/cpt-booking`
* `feature/pricing`
* `feature/availability`
* `feature/whatsapp`
* `feature/admin-dashboard`
* `fix/overlapping-bookings`

### C. Standar Pesan Commit (Conventional Commits)
Format wajib: `<type>(<scope>): <subject>`

Contoh:
* `chore(core): initialize plugin structure and bootstrap loader`
* `feat(cpt): register motor custom post type and meta fields`
* `feat(booking): add availability check query and date filters`
* `feat(pricing): implement 24h daily and weekly rate calculation`
* `feat(whatsapp): implement live message generator with URL encoding`
* `fix(security): sanitize custom plate number array in metabox`
* `test(pricing): add unit tests for peak season bulk adjustments`
* `docs(manual): add operator operational guidelines and SLA`

---

## 4. Checklist Sebelum Pull Request (PR) Diterima
1. Tidak ada PHP fatal error, warning, atau notice pada `WP_DEBUG: true`.
2. Seluruh input disanitasi dengan fungsi WordPress yang relevan.
3. Seluruh output di-escape sesuai konteks (`esc_html`, `esc_attr`, `esc_url`).
4. Nonce dan capability check aktif pada setiap request modifikasi data.
5. Kode lulus linting WordPress Coding Standards (`.phpcs.xml.dist`).
6. Tidak ada kredensial, token, nomor telepon pribadi, atau API key rahasia yang ter-commit.
7. Langkah pengujian (*testing instructions*) disertakan dengan jelas pada deskripsi PR.
8. `SESSION_STATE.md`, `TASKS.md`, dan `CHANGELOG.md` telah diperbarui, dan blok TARGET PEKERJAAN pada prompt di `docs/HANDOVER.md` telah menunjuk task berikutnya.

---

## 5. Protokol Handover Antar-AI (`docs/HANDOVER.md`)
`docs/HANDOVER.md` hanya berfungsi sebagai prompt jembatan agar pengerjaan bisa berpindah antar model AI (Claude, Gemini, ChatGPT, dll.) tanpa kehilangan konteks. Status sesi dicatat di `SESSION_STATE.md`, bukan di berkas handover.
1. Setiap AI yang menyelesaikan sebuah task WAJIB memperbarui blok TARGET PEKERJAAN pada prompt di `docs/HANDOVER.md` (task berikutnya, peran AI sesuai bagian 1, catatan khusus bila ada).
2. Prompt tersebut harus mandiri (*self-contained*) dan menginstruksikan AI penerus membaca `SESSION_STATE.md`, `TASKS.md`, `AI_RULES.md`, `AI_WORKFLOW.md`, `BLUEPRINT.md`, `ARCHITECTURE.md`, `DATA_MODEL.md`, `DECISIONS.md`, `GIT_WORKFLOW.md`, dan `REVIEW-ARCHITECTURE.md`.
3. AI penerus tidak boleh melompat atau mencampurkan pekerjaan ke task di luar target yang tertera pada prompt.
