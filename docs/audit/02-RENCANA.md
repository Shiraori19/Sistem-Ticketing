# RENCANA PERBAIKAN SISTEM TICKETING DKIS (FASE 2)
**Tanggal Rencana:** 17 Oktober 2026  
**Branch:** `audit/perbaikan-20261017`  
**Dokumen Acuan:** `docs/audit/01-TEMUAN.md` & Hasil Keputusan GATE 1  

---

## 1. Ringkasan Eksekutif Rencana

Berdasarkan persetujuan dan keputusan bisnis GATE 1, perbaikan dikelompokkan dalam urutan prioritas yang ketat: **Kritis** (2 temuan), **Tinggi** (4 temuan), dan **Sedang** (7 temuan).  
Setiap perbaikan dilakukan secara terisolasi per commit dengan prinsip **test-first**, memindahkan logika bisnis ke Service, menjaga integritas transaksi database (`lockForUpdate`), serta menghindari modifikasi destruktif pada skema database.

---

## 2. Matriks Rencana Kerja Per Butir

### Prioritas 1: Kritis (Critical)

#### 1. SEC-01: Kebocoran `access_token` di Tautan Unduh Lampiran Halaman Publik
- **Tingkat:** 🔴 Kritis
- **File Diubah:**
  - `resources/views/public/index.blade.php` (baris 749)
  - `app/Http/Controllers/TicketFileController.php` (method `downloadPublic`)
- **Migrasi DB:** Tidak ada.
- **Risiko Regresi:** Rendah. Pelapor yang belum memasukkan PIN pada sesi aktif harus diminta otentikasi PIN terlebih dahulu sebelum mengunduh berkas.
- **Test yang Ditulis:** `tests/Feature/Audit/PublicFileDownloadSecurityTest.php`
  - Memastikan tautan berkas di HTML lacak tidak lagi menyertakan query parameter `auth_token`.
  - Memastikan request `GET /tiket/{kode}/lampiran/{file}` tanpa sesi PIN ditolak `403 Forbidden` / `401 Unauthorized`.
  - Memastikan request dengan sesi PIN valid berhasil mengunduh berkas.
- **Dampak Pengguna:** Lampiran publik terlindungi penuh dari akses pihak ketiga yang hanya mengetahui kode tiket.

#### 2. SEC-02: Ketiadaan Rate Limiting & Paparan Deskripsi Kendala pada Pelacakan Publik
- **Tingkat:** 🔴 Kritis
- **File Diubah:**
  - `routes/web.php` (route `public.index` & pencarian tiket)
  - `app/Http/Controllers/PublicTicketController.php`
  - `resources/views/public/index.blade.php`
- **Migrasi DB:** Tidak ada.
- **Risiko Regresi:** Rendah. Pelapor sah yang mencari tiket berkali-kali dalam 1 menit mungkin terkena limit jika batas terlalu ketat. Disetel wajar (30 request per menit).
- **Test yang Ditulis:** `tests/Feature/Audit/PublicTicketTrackingThrottleTest.php`
  - Memastikan request lacak melebihi batas (30 req/min) menghasilkan status `429 Too Many Requests`.
  - Memastikan pada tiket berstatus publik sebelum diverifikasi PIN, teks deskripsi keluhan dan identitas kontak pelapor disensor/masking.
- **Dampak Pengguna:** Mencegah bot/scraper mengeksploitasi data keluhan ASN Pemkot Cirebon secara massal.

---

### Prioritas 2: Tinggi (High)

#### 3. SEC-05: Enkripsi PIN Tiket Warisan (Legacy Plaintext PIN)
- **Tingkat:** 🟠 Tinggi
- **File Diubah:**
  - `app/Console/Commands/HardenLegacyPinsCommand.php` (Command Artisan baru)
  - `database/migrations/2026_10_17_000001_harden_legacy_ticket_pins.php` (Migrasi additive)
- **Migrasi DB:** Ya, migrasi eksekusi enkripsi nilai plaintext PIN lama di database.
- **Risiko Regresi:** Sangat rendah. Menggunakan `Hash::needsRehash` atau mengecek panjang string PIN <= 6 digit agar tidak melakukan double-hashing pada tiket baru.
- **Test yang Ditulis:** `tests/Feature/Audit/HardenLegacyPinsTest.php`
  - Memastikan tiket dengan PIN plaintext lama (seperti ID 43) terenkripsi menjadi Bcrypt hash.
  - Memastikan pelapor tiket lama tetap dapat login menggunakan PIN 6-digit aslinya.
- **Dampak Pengguna:** Tidak ada perubahan pada sisi pelapor, data tersimpan lebih aman.

#### 4. SEC-04: Jejak Audit Log Pengunduhan Berkas Sensitif
- **Tingkat:** 🟠 Tinggi
- **File Diubah:**
  - `app/Http/Controllers/TicketFileController.php` (`downloadAdmin` & `downloadPublic`)
- **Migrasi DB:** Tidak ada (tabel `audit_log` sudah tersedia).
- **Risiko Regresi:** Rendah.
- **Test yang Ditulis:** `tests/Feature/Audit/FileDownloadAuditLoggingTest.php`
  - Memastikan setiap kali berkas privat diunduh oleh Admin atau Pelapor, entri baru terbentuk di tabel `audit_log` dengan detail nama berkas dan kode tiket.
- **Dampak Pengguna:** Tidak terlihat oleh pengguna; meningkatkan akuntabilitas dan jejak digital forensik.

#### 5. FLOW-02: Validasi Status Parent dan Inisialisasi SLA pada Penautan Tiket Anak (`linkToParent`)
- **Tingkat:** 🟠 Tinggi
- **File Diubah:**
  - `app/Services/TicketWorkflowService.php` (`linkToParent`)
  - `app/Http/Controllers/Admin/VerificationController.php` (`batchLink`)
- **Migrasi DB:** Tidak ada.
- **Risiko Regresi:** Rendah. Mencegah anomali SLA `null` pada tiket anak.
- **Test yang Ditulis:** `tests/Feature/Audit/BatchLinkParentChildTest.php`
  - Menolak penautan tiket anak ke induk yang berstatus `closed`, `force_closed`, atau `cancelled`.
  - Memastikan tiket anak yang ditautkan mewarisi `sla_dimulai_at` dari tiket induk.
- **Dampak Pengguna:** Perhitungan SLA insiden massal menjadi akurat dan konsisten.

#### 6. SEC-03: Hardening Database Non-Superuser & Panduan Konfigurasi
- **Tingkat:** 🟠 Tinggi
- **File Diubah:**
  - `docker-compose.yml` (konfigurasi opsi user non-root)
  - `docs/audit/07-DATABASE_HARDENING.md`
- **Migrasi DB:** Script SQL inisialisasi privilege terbatas.
- **Risiko Regresi:** Perlu memastikan user baru memiliki hak akses DDL untuk migrasi Laravel.
- **Test yang Ditulis:** Verifikasi koneksi DB dan hak akses terbatas.
- **Dampak Pengguna:** Lingkungan produksi terlindungi dari kompromi superuser mesin database.

---

### Prioritas 3: Sedang (Medium)

#### 7. FLOW-03: Retensi Timestamp SLA Awal pada Re-Disposisi Pengembalian Tiket (`returnToPusat`)
- **Tingkat:** 🟡 Sedang
- **Keputusan GATE 1:** Lanjutkan jam SLA awal (waktu `sla_dimulai_at` tidak direset) agar tidak ada celah kolusi manipulasi timer resolusi.
- **File Diubah:**
  - `app/Services/TicketWorkflowService.php` (`verifyAndDispose`)
- **Migrasi DB:** Tidak ada.
- **Risiko Regresi:** Rendah.
- **Test yang Ditulis:** `tests/Feature/Audit/TicketReturnSlaPreservationTest.php`
  - Memastikan tiket yang dikembalikan bidang lalu diverifikasi ulang ke bidang baru tetap mempertahankan `sla_dimulai_at` aslinya.
- **Dampak Pengguna:** Laporan kepatuhan SLA lebih akurat dan objektif bagi pimpinan instansi.

#### 8. FLOW-04: Pemisahan Antrean Tiket TLP RED ke Tab 'Siaran Insiden Siber'
- **Tingkat:** 🟡 Sedang
- **Keputusan GATE 1:** Pisahkan dari antrean operasional bidang lain; taruh pada tab khusus 'Siaran Insiden Siber' agar bidang lain terinformasi tanpa memicu kebingungan aksi/error 403.
- **File Diubah:**
  - `app/Http/Controllers/Admin/BidangTicketController.php` (`index`)
  - `resources/views/admin/bidang/tiket/index.blade.php`
- **Migrasi DB:** Tidak ada.
- **Risiko Regresi:** Rendah.
- **Test yang Ditulis:** `tests/Feature/Audit/TlpRedBroadcastTabTest.php`
  - Memastikan tiket TLP RED tidak muncul di antrean operasional harian bidang non-Persandian.
  - Memastikan tiket TLP RED tampil di tab terpisah 'Siaran Insiden Siber'.
- **Dampak Pengguna:** Admin Bidang ITIK & Pemdi tidak terdistraksi tiket siber yang tidak bisa mereka kerjakan.

#### 9. FLOW-05: Multi-Lampiran Data Susulan Pelapor
- **Tingkat:** 🟡 Sedang
- **File Diubah:**
  - `app/Http/Controllers/PublicTicketController.php` (`submitPendingInfo`)
  - `app/Services/TicketWorkflowService.php`
- **Migrasi DB:** Tidak ada (menggunakan tabel `ticket_files` yang sudah ada).
- **Risiko Regresi:** Rendah.
- **Test yang Ditulis:** `tests/Feature/Audit/PendingInfoMultipleFilesTest.php`
  - Memastikan berkas susulan dicatat ke tabel `ticket_files` tanpa menimpa berkas lampiran awal pelapor.
- **Dampak Pengguna:** Seluruh berkas revisi pelapor tersimpan utuh dan dapat ditinjau oleh Admin.

#### 10. FLOW-06: Penyaringan Pembagi Metrik Kepatuhan SLA
- **Tingkat:** 🟡 Sedang
- **File Diubah:**
  - `app/Http/Controllers/Admin/TicketController.php` (method laporan SLA & ekspor)
- **Migrasi DB:** Tidak ada.
- **Risiko Regresi:** Rendah.
- **Test yang Ditulis:** `tests/Feature/Audit/SlaComplianceCalculationTest.php`
  - Memastikan tiket `pending_verification` dan `cancelled` tidak dimasukkan dalam pembagi total kepatuhan SLA.
- **Dampak Pengguna:** Angka persentase kepatuhan SLA instansi menjadi valid dan dapat dipertanggungjawabkan.

#### 11. OPS-01: Kontainer Cron Scheduler Laravel di Docker
- **Tingkat:** 🟡 Sedang
- **File Diubah:**
  - `docker-compose.yml`
- **Migrasi DB:** Tidak ada.
- **Risiko Regresi:** Rendah.
- **Test yang Ditulis:** Verifikasi container running dan eksekusi command terjadwal.
- **Dampak Pengguna:** SLA breach dan pelepasan penugasan kedaluwarsa berjalan otomatis di latar belakang setiap menit.

#### 12. DB-01: Pencarian Case-Insensitive PostgreSQL (`ILIKE`)
- **Tingkat:** 🟡 Sedang
- **File Diubah:**
  - Controller pencarian tiket publik dan admin.
- **Migrasi DB:** Tidak ada.
- **Risiko Regresi:** Rendah.
- **Test yang Ditulis:** `tests/Feature/Audit/CaseInsensitiveSearchTest.php`
  - Memastikan pencarian keyword huruf kecil dapat mencocokkan judul tiket huruf kapital di PostgreSQL.
- **Dampak Pengguna:** Pencarian tiket menjadi ramah pengguna.

#### 13. NOTIF-01: Standarisasi Kerangka WhatsApp Service
- **Tingkat:** 🟡 Sedang
- **File Diubah:**
  - `app/Services/Notification/WhatsAppService.php`
  - `app/Jobs/SendTicketNotificationJob.php`
- **Migrasi DB:** Tidak ada.
- **Risiko Regresi:** Rendah. Sesuai aturan audit sistem, tidak memasang layanan pihak ketiga berbayar; menyediakan mock/adapter yang teruji.
- **Test yang Ditulis:** `tests/Feature/Audit/WhatsAppServiceMockTest.php`
- **Dampak Pengguna:** Sistem siap terhubung ke vendor WA Gateway resmi Pemkot cukup dengan mengubah konfigurasi `.env`.

---

## 3. Jadwal dan Urutan Eksekusi

| Tahap | ID Temuan | Fokus Pekerjaan | Estimasi Test |
| :--- | :--- | :--- | :--- |
| **Batch 1 (Kritis)** | SEC-01, SEC-02 | Keamanan download publik & masking lacak + throttle | 2 Feature Tests |
| **Batch 2 (Tinggi)** | SEC-05, SEC-04, FLOW-02 | Hash legacy PIN, Audit log download, Validasi Parent-Child SLA | 3 Feature Tests |
| **Batch 3 (Sedang)** | FLOW-03, FLOW-04, FLOW-05, FLOW-06 | Retensi SLA disposisi, Tab TLP RED, Multi berkas susulan, Metrik SLA | 4 Feature Tests |
| **Batch 4 (Operasional)** | DB-01, OPS-01, NOTIF-01 | Case-insensitive query, Docker scheduler, WA Service frame | 2 Feature Tests |

---
*Dokumen ini merupakan artefak resmi FASE 2 sebagai panduan eksekusi perbaikan FASE 3.*
