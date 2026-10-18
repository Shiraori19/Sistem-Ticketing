# 🏛️ Sistem Ticketing Layanan TIK & Keamanan Siber
### Dinas Komunikasi, Informatika, dan Statistik (DKIS) Pemerintah Kota Cirebon

[![Laravel](https://img.shields.io/badge/Laravel-10.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-15-4169E1?style=for-the-badge&logo=postgresql&logoColor=white)](https://www.postgresql.org/)
[![Docker](https://img.shields.io/badge/Docker-Enabled-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://www.docker.com/)
[![Nginx](https://img.shields.io/badge/Nginx-HTTP%2F2_SSL-009639?style=for-the-badge&logo=nginx&logoColor=white)](https://nginx.org/)
[![Tests](https://img.shields.io/badge/Tests-26_Passed_(135_Assertions)-success?style=for-the-badge)](#-pengujian-sistem-testing-suite)
[![Status](https://img.shields.io/badge/Status-Production_Ready-059669?style=for-the-badge)](#)

Platform permohonan bantuan teknologi informasi, pemeliharaan infrastruktur jaringan kedinasan, dan penanganan insiden keamanan siber terpadu untuk melayani **seluruh ASN di lingkungan Organisasi Perangkat Daerah (OPD) Pemerintah Kota Cirebon** sesuai standar arsitektur Sistem Pemerintahan Berbasis Elektronik (SPBE), ITIL v4, dan standar keamanan BSSN/CSIRT.

---

## 📑 Daftar Isi

- [Fitur Utama Sistem](#-fitur-utama-sistem)
- [Struktur Peran & Hak Akses (RBAC)](#-struktur-peran--hak-akses-rbac)
- [Alur Kerja Sistem (Workflow Lifecycle)](#-alur-kerja-sistem-workflow-lifecycle)
- [Standardisasi SLA & Protokol TLP](#-standardisasi-sla--protokol-tlp)
- [Arsitektur Keamanan & Proteksi Siber](#-arsitektur-keamanan--proteksi-siber)
- [Teknologi & Stack Pengembangan](#-teknologi--stack-pengembangan)
- [Panduan Instalasi Cepat (Docker Setup)](#-panduan-instalasi-cepat-docker-setup)
- [Pengujian Sistem (Testing Suite)](#-pengujian-sistem-testing-suite)
- [Akun Bawaan (Default Credentials)](#-akun-bawaan-default-credentials)
- [Pemeliharaan & Perintah Artisan](#-pemeliharaan--perintah-artisan)
- [Lisensi & Kepemilikan](#-lisensi--kepemilikan)

---

## 🌟 Fitur Utama Sistem

### 1. Portal Pelapor Ringan & Terverifikasi (*Zero Friction ASN Access*)
* ASN se-Kota Cirebon dapat mengajukan permohonan tanpa proses registrasi akun manual.
* Validasi identitas otomatis berbasis **NIP ASN**, **Unit Kerja (OPD)**, dan **Nomor WhatsApp aktif**.
* Dilengkapi sistem autocomplete OPD resmi serta opsi input manual untuk instansi vertikal, kelurahan, dan sekolah.

### 2. Pelacakan Tiket Privat & Autentikasi PIN Bcrypt
* Perlindungan privasi data aduan sesuai amanat **UU Perlindungan Data Pribadi (UU PDP)**.
* Pelapor menerima **Kode Tiket unik** dan **PIN Keamanan 6 Digit** (di-hash Bcrypt di database).
* Riwayat tiket, progres perbaikan, dan unduhan dokumen dilindungi sesi verifikasi PIN otomatis.

### 3. Manajemen Penugasan Teknisi Lapangan Lengkap (*Workload Balancing*)
* Admin Bidang menunjuk koordinator lapangan (**Lead PIC**) dan tim anggota teknisi pendamping.
* Sistem mengendalikan kapasitas beban kerja teknisi berdasarkan bobot TLP (RED = 3 poin, AMBER = 2 poin, GREEN = 1 poin, maksimal kuota 6 poin per teknisi).
* Batas waktu respon konfirmasi penugasan resmi (**15 Menit** untuk TLP RED dan **60 Menit** untuk tugas reguler) dengan pelepasan otomatis (*auto-release*) oleh Command Scheduler jika teknisi tidak merespon.

### 4. Portal Mandiri Teknisi Lapangan (*Mobile & Desktop Ready*)
* Teknisi memiliki dashboard kerja pribadi dengan hitung mundur SLA *real-time*.
* Aksi penerimaan tugas (*Accept*), penolakan beralasan formal (*Decline*), worklog progres berkala, pelaporan kendala eksternal (*Blocker*: ISP, vendor, suku cadang), dan pengajuan selesai pekerjaan (*Submit for Review*).
* Komunikasi langsung 1-klik via WhatsApp ke pelapor tanpa membocorkan data pribadi pelapor setelah tiket ditutup.

### 5. Klasifikasi Traffic Light Protocol (TLP) & CSIRT Bypass
* Pengelompokan tingkat kerahasiaan: **TLP: RED**, **TLP: AMBER**, **TLP: GREEN**, dan **TLP: CLEAR**.
* **TLP: RED (Insiden Kritis Siber):** Diisolasi secara ketat di bawah kendali Admin Pusat dan Bidang Persandian/CSIRT. Seluruh staf non-Persandian diblokir total (HTTP 404 Not Found) demi kerahasiaan keamanan negara.

### 6. Hak Veto Force Close & Penyelarasan Status Otomatis
* Admin Pusat berwenang melakukan *Force Close* pada tiket sengketa atau deadlock di status `eskalasi_pusat`.
* Sistem secara otomatis mengubah status seluruh penugasan aktif teknisi menjadi `released` dan menghentikan seluruh form aksi kerja lapangan.

---

## 👥 Struktur Peran & Hak Akses (RBAC)

| Peran Akun | Cakupan Tugas & Wewenang |
| :--- | :--- |
| **Pelapor (ASN OPD)** | Mengajukan aduan, melacak timeline progres, mengisi data susulan, mengonfirmasi penutupan tiket, dan memberikan rating kepuasan (1-5 bintang). |
| **Admin Pusat** | Verifikasi tiket masuk (*triage*), penentuan TLP & kategori, disposisi bidang, penanganan insiden siber TLP RED, penyelesaian eskalasi pusat, hak veto *force close*, rekap laporan kinerja SPBE, dan manajemen pengguna. |
| **Admin Bidang** | Koordinator tim bidang (ITIK, PEMDI, PERSANDIAN), pemantauan antrean bidang, alokasi penugasan teknisi (*lead* & *members*), kontrol beban kerja staf, dan review hasil penanganan lapangan. |
| **Teknisi Lapangan** | Menerima/menolak tugas, mencatat progres harian, melaporkan hambatan eksternal, menyelesaikan perbaikan di lokasi, dan mengunggah foto bukti penyelesaian. |

---

## 🔄 Alur Kerja Sistem (Workflow Lifecycle)

```mermaid
flowchart TD
    %% PENGAJUAN TIKET
    A["📝 ASN OPD Mengajukan Kendala<br/>(NIP, SKPD, WhatsApp, Deskripsi, Lampiran)"] --> A1["🔑 Generate Kode Tiket & PIN 6 Digit<br/>(Tersimpan Bcrypt di Database)"]
    A1 --> B["🔍 Verifikasi Admin Pusat<br/>(Triage, Penetapan TLP & Disposisi Bidang)"]
    
    %% VERIFIKASI PUSAT
    B -->|Berkas Belum Lengkap| B1["⚠️ Minta Info Tambahan<br/>(Status: pending_info)"]
    B1 --> A
    B -->|Insiden Siber Kritis TLP RED| CSIRT["🚨 CSIRT Persandian<br/>(Isolasi Penuh 404 bagi Non-Persandian)"]
    B -->|Kasus Reguler| C["📋 Disposisi ke Bidang Pengampu<br/>(Status: verified, SLA Resmi Dimulai)"]
    
    %% PENUGASAN TEKNISI
    C --> D["⚖️ Penugasan Teknisi oleh Admin Bidang<br/>(Workload Balancing kuota 6 poin & Respond Deadline)"]
    CSIRT --> D
    D --> E["🛠️ Penanganan oleh Teknisi Lapangan<br/>• Terima Tugas (Accept) -> in_progress<br/>• Catat Progres Harian & Hambatan Blocker<br/>• Selesaikan Tugas & Unggah Foto Bukti"]
    
    %% PENYELESAIAN
    E --> F["🚀 Selesai Dikerjakan (Resolved)<br/>Diteruskan ke Pelapor untuk Konfirmasi"]
    
    %% RESPON PELAPOR
    F --> G{"👤 Konfirmasi Pelapor ASN"}
    G -->|Puas / Tuntas| H["✅ Tutup Tiket & Rating Bintang (1-5)<br/>(Status: closed)"]
    G -->|Belum Tuntas (Maks. 2x Reopen)| D
    G -->|Reopen ke-3 (Deadlock)| I["🚨 Eskalasi ke Admin Pusat<br/>(Status: eskalasi_pusat)"]
    
    %% VETO PUSAT
    I --> J["⚖️ Audit Teknis & Force Close Pusat<br/>(Auto-Release Penugasan Teknisi)"]
```

---

## ⏱️ Standardisasi SLA & Protokol TLP

### Target Standar Service Level Agreement (SLA) Resmi DKIS

| No | Kategori Layanan | Bidang | SLA Respon | SLA Resolusi |
| :-: | :--- | :--- | :---: | :---: |
| 1 | **Bug/Error Aplikasi Kritis (Layanan Publik Terganggu)** | Pemerintahan Digital (PEMDI) | 15 Menit | **4 Jam** |
| 2 | **Permohonan Penambahan Fitur Minor Aplikasi** | Pemerintahan Digital (PEMDI) | 8 Jam | **72 Jam (3 Hari)** |
| 3 | **Permohonan Integrasi Data / API Interoperabilitas** | Pemerintahan Digital (PEMDI) | 8 Jam | **120 Jam (5 Hari)** |
| 4 | **Pengembangan / Rancang Bangun Aplikasi Baru** | Pemerintahan Digital (PEMDI) | 16 Jam | **720 Jam (30 Hari)** |
| 5 | **Putus Koneksi Jaringan Utama / FO Pemda** | Infrastruktur TIK (ITIK) | 15 Menit | **4 Jam** |
| 6 | **Gangguan Internet / Wi-Fi Kantor OPD** | Infrastruktur TIK (ITIK) | 30 Menit | **24 Jam (1 Hari)** |
| 7 | **Penyediaan Server Baru (Alokasi VPS / Cloud)** | Infrastruktur TIK (ITIK) | 2 Jam | **48 Jam (2 Hari)** |
| 8 | **Pemasangan Jaringan / Penarikan Titik FO Baru** | Infrastruktur TIK (ITIK) | 8 Jam | **96 Jam (4 Hari)** |
| 9 | **Insiden Siber Kritis (Defacement / Ransomware)** | Persandian & CSIRT | 15 Menit | **6 Jam** |
| 10 | **Penerbitan / Lupa PIN TTE (BSrE)** | Persandian & CSIRT | 1 Jam | **24 Jam (1 Hari)** |
| 11 | **Penerbitan Sertifikat SSL / TLS Domain Pemda** | Persandian & CSIRT | 2 Jam | **24 Jam (1 Hari)** |
| 12 | **Uji Kerentanan Aplikasi (VA / Pentest)** | Persandian & CSIRT | 8 Jam | **168 Jam (7 Hari)** |
| 13 | **Belum Tahu / Konsultasi Masalah Lainnya** | Layanan Umum | 1 Jam | **24 Jam (1 Hari)** |

---

## 🛡️ Arsitektur Keamanan & Proteksi Siber

1. **Zero Secret Leakage:** Kunci privat `.env`, token API WhatsApp, dan sertifikat SSL dilindungi penuh oleh `.gitignore` dan tidak pernah terekspos ke repositori publik.
2. **PostgreSQL Trigger Append-Only:** Tabel `ticket_events` dan `audit_logs` dilindungi trigger basis data yang menolak operasi `UPDATE` dan `DELETE` demi integritas audit forensik SPBE.
3. **Optimistic Concurrency Control:** Kolom `row_version` mencegah insiden balapan data (*race condition*) antar-petugas dengan melempar respons `409 Conflict`.
4. **Isolasi Database Docker:** Port database PostgreSQL (`5432`) dan PHP-FPM (`9000`) tidak dibuka ke jaringan publik host, melainkan terisolasi di dalam bridge internal `ticketing_net`.

---

## 💻 Teknologi & Stack Pengembangan

* **Backend Framework:** PHP 8.2 (Alpine), Laravel 10.x
* **Database Engine:** PostgreSQL 15 (JSONB support & native performance indexing)
* **Web Server & Reverse Proxy:** Nginx Alpine (HTTP/2, TLS 1.2/1.3 Termination, Gzip Compression)
* **Frontend:** Laravel Blade, Vanilla CSS Modern, Tailwind CSS, Alpine.js, Chart.js
* **Asynchronous Queue & Scheduler:** Database Queue Worker (`ticketing_queue`) & Command Scheduler (`ticketing_scheduler`)
* **Kontainerisasi:** Docker Engine & Docker Compose

---

## 🚀 Panduan Instalasi Cepat (Docker Setup)

### 1. Kloning Repositori
```bash
git clone https://github.com/Shiraori19/Sistem-Ticketing.git
cd Sistem-Ticketing
```

### 2. Konfigurasi Environment (`.env`)
Salin file template environment bawaan:
```bash
cp .env.example .env
```
*(Sesuaikan `DB_PASSWORD` dan konfigurasi opsional lainnya di dalam file `.env`).*

### 3. Jalankan Kontainer Docker
```bash
docker compose up -d
```
Pastikan seluruh 5 kontainer berjalan normal:
* `ticketing_web` (Nginx di Port 80, 443, dan 8443)
* `ticketing_app` (PHP-FPM 8.2)
* `ticketing_db` (PostgreSQL 15)
* `ticketing_queue` (Queue Worker)
* `ticketing_scheduler` (Cron Scheduler)

### 4. Inisialisasi Database & Seeding
```bash
docker exec -it ticketing_app php artisan migrate:fresh --seed
```

### 5. Akses Aplikasi
* **Portal Layanan ASN (Publik):** [https://localhost:8443](https://localhost:8443) (atau [http://localhost:8080](http://localhost:8080))
* **Portal Masuk Petugas DKIS:** [https://localhost:8443/login](https://localhost:8443/login)

---

## 🧪 Pengujian Sistem (Testing Suite)

Sistem telah dilengkapi dengan automated test suite berbasis PHPUnit yang memvalidasi seluruh alur bisnis, proteksi keamanan, dan sinkronisasi lintas peran:

```bash
docker exec -it ticketing_app php artisan test
```

**Status Terkini: 26 Tests Passed (135 Assertions, 0 Failed)**:
* `BatchThreeHardeningTest`: Validasi berkas susulan, kalkulasi kepatuhan SLA, integrasi antrean notifikasi WhatsApp, dan pencarian case-insensitive.
* `EndToEndWorkflowTest`: Siklus hidup lengkap Pelapor -> Admin Pusat -> Admin Bidang -> Teknisi Lapangan -> Konfirmasi Pelapor.
* `PublicTicketSecurityTest`: Pembatasan unduhan privat ber-PIN, rate limiting pelacakan, dan masking data sensitif.
* `WorkflowAndSlaHardeningTest`: Kompatibilitas PIN, pewarisan SLA tiket anak (parent-child), retensi waktu SLA redisposisi, dan pemisahan antrean TLP RED.
* `TechnicianAssignmentWorkflowTest`: Penerimaan tugas dalam deadline, penolakan formal, pelepasan penugasan kedaluwarsa, integritas tiket ditutup paksa (*Force Closed*), optimistic locking (409 Conflict), dan append-only event trigger.

---

## 🔑 Akun Bawaan (Default Credentials)

Semua akun terdaftar menggunakan domain kedinasan resmi Pemerintah Kota Cirebon:

| Nama Pengguna / Email | Peran | Bidang Pengampu | Password Bawaan |
| :--- | :--- | :--- | :--- |
| `admin.pusat@cirebonkota.go.id` | Admin Pusat | Lintas Bidang (DKIS Pusat) | `Password123!` |
| `dewi.lestari@cirebonkota.go.id` | Admin Bidang | Infrastruktur TIK (ITIK) | `Password123!` |
| `riko.pratama@cirebonkota.go.id` | Admin Bidang | Persandian & CSIRT | `Password123!` |
| `fazra.fatimah@cirebonkota.go.id`| Teknisi Lapangan | Infrastruktur TIK (ITIK) | `Password123!` |
| `rian.pratama@cirebonkota.go.id` | Teknisi Lapangan | Persandian & CSIRT | `Password123!` |

> [!TIP]
> Demi keamanan di lingkungan produksi, segera ubah seluruh kata sandi akun default setelah instalasi selesai melalui menu **Profil Petugas**.

---

## 🔧 Pemeliharaan & Perintah Artisan

```bash
# Membersihkan dan mengompilasi ulang cache
docker exec -it ticketing_app php artisan optimize:clear
docker exec -it ticketing_app php artisan optimize

# Memeriksa log aktivitas container
docker logs -f ticketing_app
docker logs -f ticketing_web
docker logs -f ticketing_scheduler

# Menjalankan manual pelepasan penugasan teknisi yang kedaluwarsa
docker exec -it ticketing_app php artisan tickets:release-expired-assignments
```

---

## 🏛️ Lisensi & Kepemilikan

Hak Cipta © 2026 **Dinas Komunikasi, Informatika, dan Statistik (DKIS) Pemerintah Kota Cirebon**.  
Dikembangkan untuk meningkatkan tata kelola TIK, transparansi pelayanan kedinasan, dan efisiensi birokrasi pemerintahan daerah.
