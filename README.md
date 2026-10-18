# 🏛️ Sistem Ticketing Layanan TIK & Keamanan Siber
### Dinas Komunikasi, Informatika, dan Statistik (DKIS) Pemerintah Kota Cirebon

[![Laravel](https://img.shields.io/badge/Laravel-10.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-15-4169E1?style=for-the-badge&logo=postgresql&logoColor=white)](https://www.postgresql.org/)
[![Docker](https://img.shields.io/badge/Docker-Enabled-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://www.docker.com/)
[![Nginx](https://img.shields.io/badge/Nginx-HTTP%2F2_SSL-009639?style=for-the-badge&logo=nginx&logoColor=white)](https://nginx.org/)
[![Status](https://img.shields.io/badge/Status-Production_Ready-success?style=for-the-badge)](#)

Sistem Ticketing DKIS Kota Cirebon adalah platform permohonan layanan TIK, pemeliharaan infrastruktur jaringan, dan penanganan insiden keamanan siber terpadu untuk melayani **~750 ASN di seluruh Organisasi Perangkat Daerah (OPD)** lingkungan Pemerintah Kota Cirebon sesuai standar Sistem Pemerintahan Berbasis Elektronik (SPBE).

---

## 📑 Daftar Isi

- [Fitur Utama](#-fitur-utama)
- [Alur Kerja Sistem (Workflow)](#-alur-kerja-sistem-workflow)
- [Struktur Peran & Akun Bawaan](#-struktur-peran--akun-bawaan)
- [Standardisasi SLA & Skema TLP](#-standardisasi-sla--skema-tlp)
- [Arsitektur Keamanan & Jaringan](#-arsitektur-keamanan--jaringan)
- [Teknologi yang Digunakan](#-teknologi-yang-digunakan)
- [Struktur Direktori Proyek](#-struktur-direktori-proyek)
- [Panduan Instalasi & Menjalankan](#-panduan-instalasi--menjalankan)
- [Pengujian Sistem (Testing Suite)](#-pengujian-sistem-testing-suite)
- [Pemeliharaan & Troubleshooting](#-pemeliharaan--troubleshooting)
- [Buku Panduan & Dokumentasi Lengkap](#-buku-panduan--dokumentasi-lengkap)

---

## 🌟 Fitur Utama

### 1. Aksesibilitas 750 ASN Tanpa Akun (*Lightweight Verified Form*)
* Seluruh ASN di lingkungan Pemkot Cirebon dapat mengajukan permohonan bantuan teknis **tanpa perlu registrasi akun manual**.
* Cukup memvalidasi identitas dengan **NIP**, **Nama Lengkap**, **Unit Kerja (SKPD/OPD)**, dan **Nomor WhatsApp aktif**.
* Mengakomodasi OPD dinas resmi dan opsi pengetikan manual untuk instansi vertikal/kelurahan/sekolah.

### 2. Pelacakan Tiket Privat (*Tokenized Magic Link WhatsApp*)
* Mencegah potensi kebocoran data pengaduan antar-OPD sesuai prinsip **UU Perlindungan Data Pribadi (UU PDP)** dengan meniadakan pencarian kode tiket terbuka secara liar.
* Pelapor menerima **Magic Link ber-token resmi** (`auth_token`) via WhatsApp Gateway detik itu juga.
* Dilengkapi verifikasi PIN 4 digit jika tautan dibuka dari perangkat lain tanpa token otomatis.

### 3. Klasifikasi Kerahasiaan *Traffic Light Protocol* (TLP)
* Setiap tiket dinilai tingkat kerahasiaannya: **TLP: RED**, **TLP: AMBER**, **TLP: GREEN**, atau **TLP: CLEAR**.
* **TLP: RED (Insiden Kritis Siber):** Tiket otomatis di-*bypass* langsung ke status `in_progress` di bawah kendali Admin Pusat / Tim CSIRT Persandian tanpa melalui antrean bidang umum. Bukti penyelesaian bersifat rahasia negara (*confidential evidence*).

### 4. Perhitungan SLA Otomatis & Akuntabel
* Target *Response Time* dan *Resolution Time* dihitung mundur secara otomatis per kategori kendala.
* Penghitungan jam kerja teknisi resmi dimulai sejak tiket didisposisikan oleh Admin Pusat (tidak memotong durasi verifikasi berkas).
* Visualisasi indikator waktu tersisa dan peringatan otomatis jika terjadi *SLA Breach*.

### 5. Mekanisme Reopen & Hak Veto *Force Close*
* Pelapor dapat mengajukan *Reopen* tiket maksimal 2 kali jika solusi teknisi belum tuntas.
* Jika pelapor melakukan *Reopen* ke-3 kalinya, tiket **otomatis tereskalasi ke Admin Pusat** (`eskalasi_pusat`).
* Admin Pusat memegang wewenang **Force Close** dengan catatan resmi bila terjadi kebuntuan koordinasi (*deadlock*).

### 6. Manajemen Petugas & Audit Trail Lengkap
* Admin Pusat dapat mengelola akun koordinator bidang dan teknisi lapangan.
* Pencatatan log komprehensif pada tabel `audit_logs` (pencatatan user, aksi, IP address, waktu, serta perubahan status tiket).

---

## 🔄 Alur Kerja Sistem (Workflow)

```mermaid
flowchart TD
    %% PENGAJUAN
    A["📝 ASN OPD Mengajukan Kendala<br/>(NIP 18 Digit, SKPD, No. WA, Bukti)"] --> A1["⚙️ Generate Kode Tiket & PIN 6 Digit<br/>Kirim Magic Link Privat via WhatsApp"]
    A1 --> B["🔍 Verifikasi Admin Pusat<br/>(Triage, Penetapan TLP & Rujukan <b>Katalog Layanan TIK</b>)"]
    
    %% TRIAGE & KATALOG
    B -->|Berkas Belum Lengkap| B1["⚠️ Minta Kelengkapan Data<br/>(Status: pending_info, Notifikasi WA ke Pelapor)"]
    B1 --> A
    B -->|Kategori Salah / Ambigu| B2["📖 Penyelarasan Kategori via Katalog Layanan<br/>(Update Standar SLA Respon & Resolusi)"]
    B2 --> B
    
    %% CABANG TLP RED vs NORMAL
    B -->|Kasus Kritis / TLP RED| CSIRT["🚨 Penanganan CSIRT Persandian<br/>(Auto-Bypass ke Admin Pusat/CSIRT)"]
    B -->|Kasus Normal / TLP Clear-Green| C["📋 Disposisi ke Admin Bidang<br/>(Status: Verified)"]
    
    %% BEBAN KERJA & DISPOSISI TEKNISI
    C --> D["⚖️ Evaluasi Beban Kerja Staf & Disposisi Teknisi<br/>(Indikator Real-Time: 🟢 Senggang / 🟡 Sedang / 🔴 Padat)"]
    D --> E["👷 Pengerjaan oleh Akun Teknisi Lapangan<br/>• Tab Tugas Saya & Live Countdown SLA<br/>• 1-Click WhatsApp Direct ke Pelapor<br/>• Catatan Progres Lapangan (Worklog)"]
    CSIRT --> E
    
    %% PENYELESAIAN & SMART EVIDENCE
    E --> F["📸 Penyelesaian Tiket (Resolved)<br/><b>Smart Evidence Engine (Tanpa Checkbox Manual):</b><br/>• TLP RED = Otomatis Terkunci Rahasia Negara<br/>• TLP Green/Clear = Terbuka Transparan ke Pelapor"]
    
    %% RESPON PELAPOR
    F --> G{"👤 Respon Pelapor ASN<br/>(Maksimal 2x Reopen)"}
    G -->|Puas / Masalah Selesai| H["✅ Konfirmasi Tutup & Beri Rating Bintang (1-5)<br/>(Status: Closed)"]
    G -->|Belum Tuntas (Reopen 1 & 2)| D
    G -->|Reopen ke-3 (Sengketa Deadlock)| I["🚨 Eskalasi ke Admin Pusat<br/>(Status: Eskalasi Pusat)"]
    
    I --> J["⚖️ Mediasi & Veto Force Close oleh Pusat<br/>(Status: Force Closed)"]
```

---

## 👥 Struktur Peran & Akun Bawaan

Sistem menerapkan prinsip *Least Privilege* dengan 3 peran (*Role-Based Access Control*):

| Peran | Lingkup Wewenang |
| :--- | :--- |
| **Admin Pusat** | Verifikasi awal tiket (*triage*), penentuan TLP, penyelarasan kategori via Katalog Layanan TIK, disposisi bidang, penanganan insiden siber (TLP RED), mediasi eskalasi, *force close*, rekap laporan, dan manajemen seluruh akun. |
| **Admin Bidang** | Mengelola antrean bidang, memantau beban kerja staf (*workload monitoring*), menunjuk teknisi lapangan (*Lead PIC* & tim), mereview catatan pengerjaan, dan menangani tiket yang di-*reopen*. |
| **Teknisi Lapangan** *(Baru)* | Mengakses portal kerja pribadi (*mobile-friendly*), memantau hitung mundur SLA tugas, komunikasi langsung (*1-Click WA*) ke pelapor, mencatat kemajuan lapangan (*worklog*), dan mengunggah foto bukti penyelesaian perbaikan. |

### Akun Bawaan (Seeded Credentials)

Seluruh akun menggunakan format email kedinasan resmi Pemerintah Kota Cirebon:

| Email Kedinasan | Peran | Bidang Pengampu | Kata Sandi Default |
| :--- | :--- | :--- | :--- |
| `admin.pusat@cirebonkota.go.id` | Admin Pusat | *Lintas Bidang (Pusat)* | `Dkis#Cirebon2026!` |
| `jaringan@cirebonkota.go.id` | Admin Bidang | Infrastruktur TIK (ITIK) | `Dkis#Cirebon2026!` |
| `persandian@cirebonkota.go.id` | Admin Bidang | Persandian & CSIRT | `Dkis#Cirebon2026!` |
| `aplikasi@cirebonkota.go.id` | Admin Bidang | Pemerintahan Digital (PEMDI) | `Dkis#Cirebon2026!` |
| `teknisi.jaringan@cirebonkota.go.id` | Teknisi Lapangan | Infrastruktur TIK (ITIK) | `Dkis#Cirebon2026!` |
| `teknisi.aplikasi@cirebonkota.go.id` | Teknisi Lapangan | Pemerintahan Digital (PEMDI) | `Dkis#Cirebon2026!` |
| `teknisi.persandian@cirebonkota.go.id` | Teknisi Lapangan | Persandian & CSIRT | `Dkis#Cirebon2026!` |

> [!WARNING]
> **Proteksi Keamanan Login:** Form login dilengkapi sistem penguncian otomatis (*Rate Limiter Lockout*). Jika terjadi 5 kali percobaan login yang salah berturut-turut, akun dan alamat IP akan **ditangguhkan selama 5 menit (300 detik)**.

---

## ⏱️ Standardisasi SLA & Skema TLP

### Target Service Level Agreement (SLA) Resmi DKIS (13 Kategori Standar)

Sistem mengadopsi 13 kategori layanan standar sesuai rujukan SPBE, BSSN, PermenPANRB, dan ITIL v4:

| No | Kategori Layanan Standar | Bidang Pengampu | SLA Respon | SLA Resolusi |
| :-: | :--- | :--- | :---: | :---: |
| 1 | **Bug/Error Aplikasi Kritis (Layanan Publik Terganggu)** | Pemerintahan Digital (PEMDI) | 15 Menit (0.25 Jam) | **4 Jam** |
| 2 | **Permohonan Penambahan Fitur Minor Aplikasi** | Pemerintahan Digital (PEMDI) | 1 Hari Kerja (8 Jam) | **72 Jam (3 Hari Kerja)** |
| 3 | **Permohonan Integrasi Data / API Interoperabilitas** | Pemerintahan Digital (PEMDI) | 1 Hari Kerja (8 Jam) | **120 Jam (5 Hari Kerja)** |
| 4 | **Pengembangan / Rancang Bangun Aplikasi Baru** | Pemerintahan Digital (PEMDI) | 2 Hari Kerja (16 Jam) | **720 Jam (30 Hari Kerja)** |
| 5 | **Putus Koneksi Jaringan Utama / FO Pemda** | Infrastruktur TIK (ITIK) | 15 Menit (0.25 Jam) | **4 Jam** |
| 6 | **Gangguan Internet / Wi-Fi Kantor OPD** | Infrastruktur TIK (ITIK) | 30 Menit (0.50 Jam) | **24 Jam (1 Hari Kerja)** |
| 7 | **Penyediaan Server Baru (Alokasi VPS / Cloud Hosting)** | Infrastruktur TIK (ITIK) | 2 Jam | **48 Jam (2 Hari Kerja)** |
| 8 | **Pemasangan Jaringan / Penarikan Titik FO Baru** | Infrastruktur TIK (ITIK) | 1 Hari Kerja (8 Jam) | **96 Jam (4 Hari Kerja)** |
| 9 | **Insiden Siber Kritis (Defacement / Ransomware / Data Leak)** | Persandian & CSIRT | 15 Menit (0.25 Jam) | **6 Jam (Containment)** |
| 10 | **Penerbitan / Lupa PIN Sertifikat Elektronik (TTE BSrE)** | Persandian | 1 Jam | **24 Jam (1 Hari Kerja)** |
| 11 | **Penerbitan Sertifikat SSL / TLS Domain Publik Pemda** | Persandian | 2 Jam | **24 Jam (1 Hari Kerja)** |
| 12 | **Uji Kerentanan Aplikasi (VA / Pentest)** | Persandian | 1 Hari Kerja (8 Jam) | **168 Jam (7 Hari Kerja)** |
| 13 | **Belum Tahu / Konsultasi Masalah Lainnya** | Konsultasi / Umum | 1 Jam | **24 Jam (1 Hari Kerja)** |

### Indikator Status Visual SLA (*Real-Time Badge*)
* `⚪ Antrean Verifikasi`: Tiket baru, belum diverifikasi Admin Pusat (`sla_dimulai_at` kosong).
* `🟢 Aman`: Waktu berjalan normal dengan sisa durasi pengerjaan $\ge 20\%$.
* `🟡 <20% Sisa`: Peringatan siaga saat sisa waktu pengerjaan tersisa di bawah $20\%$.
* `🔴 Breach`: Batas waktu penyelesaian resmi telah terlampaui (dilengkapi animasi *pulse*).
* `🟢 Selesai (Aman)`: Tiket telah berhasil diselesaikan teknisi dalam batas waktu yang ditentukan.

### Klasifikasi Traffic Light Protocol (TLP)

* 🔴 **TLP: RED**: Insiden fatal, kebocoran data, server breached. Penanganan diambil alih oleh Admin Pusat/CSIRT (auto-bypass antrean umum). Bukti resolusi otomatis berstatus *confidential* dan tidak dapat diunduh oleh publik.
* 🟡 **TLP: AMBER**: Akses kredensial, konfigurasi internal, topologi server. Terbatas untuk tim teknis dan pelapor.
* 🟢 **TLP: GREEN**: Troubleshooting operasional rutin dinas antar-OPD.
* ⚪ **TLP: CLEAR**: Permasalahan umum perangkat kerja, panduan SOP teknis.

---

## 🛡️ Arsitektur Keamanan & Jaringan

```
[ Klien / Browser ASN ] ──Port 80 (HTTP)──> [ Redirect 301 ke Port 443 ]
           │
           └──Port 443 (HTTPS)──> [ Nginx Web Server Container ]
                                     ├── HTTP/2 & TLS v1.2/v1.3
                                     ├── Rate Limiting (5 POST/menit)
                                     ├── Gzip Compression (Level 6)
                                     └── FastCGI Keepalive (16 persistent conn)
                                                 │
                                           [ ticketing_net ] (Bridge Terisolasi)
                                                 │
                                                 ├──> [ PHP-FPM 8.2 Container ]
                                                 │       ├── OPcache Memory 512MB
                                                 │       └── Realpath Cache 16MB
                                                 │
                                                 └──> [ PostgreSQL 15 Container ]
                                                         └── Port 5432 Internal
                                                             (Tanpa Expose ke Host)
```

1. **Isolasi Database & Backend:** Port PostgreSQL (`5432`) dan PHP-FPM (`9000`) **tidak dibuka ke jaringan publik/host**. Komunikasi murni berjalan via bridge network Docker internal (`ticketing_net`).
2. **Koneksi Terenkripsi:** Nginx menegakkan protokol HTTPS (TLS 1.2 / 1.3) dan mengalihkan seluruh lalu lintas HTTP Port 80 secara otomatis.
3. **Anti-Brute Force:** Rate limiter 5 kali/menit pada rute submit tiket dan 5 kali percobaan gagal sebelum lockout 5 menit pada rute login.
4. **Proteksi Bukti Rahasia:** File unduhan bukti teknis berstatus *confidential* dijaga ketat pada level controller dan rute private (`/storage/` langsung diblokir di Nginx).

---

## 💻 Teknologi yang Digunakan

* **Bahasa & Framework:** PHP 8.2 (Alpine Linux), Laravel 10.x
* **Basis Data:** PostgreSQL 15 (JSONB support untuk audit trail riwayat status)
* **Web Server:** Nginx Alpine (Reverse Proxy, HTTP/2, SSL Termination, Gzip)
* **Antarmuka (Frontend):** Blade Templating, Tailwind CSS 3.x, Alpine.js, Chart.js
* **Asynchronous Queue:** Database Driver Queue Worker (`ticketing_queue`)
* **Containerization:** Docker & Docker Compose

---

## 📁 Struktur Direktori Proyek

```text
Sistem_Ticketing_Doc/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/               # Controller Dashboard, Auth, Tiket, Verifikasi, Eskalasi, User
│   │   │   ├── PublicTicketController.php # Form Pengaduan Publik & Pelacakan Magic Link
│   │   │   └── TicketFileController.php   # Manajemen Akses Unduh File Aman / Confidential
│   │   └── Middleware/              # Middleware Role Pusat, Bidang, & Throttle
│   ├── Models/                      # Model Eloquent: Ticket, User, Bidang, Category, AuditLog
│   └── Services/
│       └── TicketWorkflowService.php # Business logic perpindahan status tiket & SLA
├── database/
│   ├── migrations/                  # Skema tabel database PostgreSQL
│   └── seeders/                     # Seeder role, bidang, kategori, dan user kedinasan
├── docker/
│   ├── nginx/
│   │   ├── default.conf             # Konfigurasi Nginx (SSL, FastCGI keepalive, Gzip)
│   │   └── ssl/                     # Sertifikat SSL TLS (cert.pem, key.pem)
│   └── php/
│       └── custom.ini               # Optimasi OPcache & Realpath Cache
├── resources/
│   └── views/
│       ├── admin/                   # Antarmuka Admin (Dashboard, Verifikasi, Tiket, User, Laporan)
│       └── public/                  # Antarmuka Form Pengaduan ASN & Tracking Pelapor
├── routes/
│   └── web.php                      # Definisi rute publik dan grup rute admin terotentikasi
├── tests/                           # Suite pengujian fungsional & verifikasi kecepatan sistem
├── docker-compose.yml               # Konfigurasi 4 container utama (app, web, db, queue)
└── Dockerfile                       # Definisi build image PHP-FPM dengan ekstensi PostgreSQL & OPcache
```

---

## 🚀 Panduan Instalasi & Menjalankan

### Prasyarat Sistem
* [Docker Desktop](https://www.docker.com/products/docker-desktop/) (Windows / macOS / Linux)
* Git

### 1. Kloning Repositori
```bash
git clone https://github.com/username/Sistem_Ticketing_Doc.git
cd Sistem_Ticketing_Doc
```

### 2. Konfigurasi Environment (`.env`)
Salin file `.env.example` menjadi `.env`:
```bash
cp .env.example .env
```
Pastikan pengaturan database dan cache di `.env` sesuai dengan konfigurasi Docker:
```env
APP_NAME="Sistem Ticketing DKIS Kota Cirebon"
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://localhost

DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=ticketing_dkis
DB_USERNAME=postgres
DB_PASSWORD=secretpassword

QUEUE_CONNECTION=database
CACHE_DRIVER=database
```

### 3. Menjalankan Docker Container
Jalankan seluruh container di latar belakang:
```bash
docker compose up -d
```
Pastikan 4 container berikut berjalan normal (`running`):
* `ticketing_web` (Nginx di Port 80 & 443)
* `ticketing_app` (PHP 8.2-FPM)
* `ticketing_db` (PostgreSQL 15)
* `ticketing_queue` (Queue Worker)

### 4. Setup Database & Seeding Awal
Jalankan migrasi database dan pengisian data master:
```bash
docker exec -it ticketing_app php artisan migrate:fresh --seed
```

### 5. Kompilasi Cache Produksi
Lakukan warming cache untuk memaksimalkan kecepatan respon sistem:
```bash
docker exec -it ticketing_app php artisan config:cache
docker exec -it ticketing_app php artisan route:cache
docker exec -it ticketing_app php artisan view:cache
```

### 6. Mengakses Aplikasi
Buka peramban (browser) dan akses alamat berikut:
* **Portal Layanan ASN (Publik):** [https://localhost](https://localhost)
* **Panel Masuk Petugas DKIS:** [https://localhost/login](https://localhost/login)

---

## 🧪 Pengujian Sistem (Testing Suite)

Sistem telah dilengkapi suite pengujian otomatis komprehensif yang mencakup seluruh alur bisnis:

```bash
# 1. Pengujian 11 Checklist Utama Alur Tiket & Kasus TLP RED (Fase 5)
docker exec -it ticketing_app php tests/test_fase5.php

# 2. Pengujian Otorisasi Token, OPD Custom, dan Proteksi Bukti (Enhancements)
docker exec -it ticketing_app php tests/test_enhancements.php

# 3. Pengujian Tata Kelola Akun Petugas & Keamanan Layout
docker exec -it ticketing_app php tests/test_user_management.php

# 4. Pengujian Tolok Ukur Kecepatan Navigasi Menu Admin (Benchmark)
docker exec -it ticketing_app php tests/test_navigation_speed.php
```

> [!NOTE]
> **Status Pengujian Terkini:**
> Seluruh 61 pengujian otomatis (**31/31 Fase 5**, **16/16 Enhancements**, **14/14 User Management**) berstatus **100% PASSED**.

---

## 🔧 Pemeliharaan & Troubleshooting

### Mengosongkan & Memperbarui Cache
Jika terdapat perubahan kode blade atau konfigurasi environment:
```bash
docker exec -it ticketing_app php artisan optimize:clear
docker exec -it ticketing_app php artisan optimize
```

### Memeriksa Log Container
```bash
# Log Nginx Web Server
docker logs -f ticketing_web

# Log Aplikasi PHP-FPM
docker logs -f ticketing_app

# Log Antrean Notifikasi
docker logs -f ticketing_queue
```

### Mengatur Ulang Izin Direktori Storage
```bash
docker exec -it ticketing_app sh -c "chown -R www-data:www-data storage bootstrap/cache && chmod -R 775 storage bootstrap/cache"
```

---

## 📘 Buku Panduan & Dokumentasi Lengkap

Dokumentasi komprehensif sistem (mencakup landasan hukum SPBE/CSIRT/ITIL v4, arsitektur teknis Docker & DB, diagram ERD, matriks SLA 13 kategori, panduan operasional per peran ASN & Admin, panduan migrasi domain, serta SOP backup-restore) tersedia pada dokumen resmi:

👉 **[Buku Panduan dan Dokumentasi Lengkap Sistem Ticketing DKIS Kota Cirebon](file:///d:/Sistem_Ticketing_Doc/anti%20gravity%20sistem%20ticketing/dokumen%20sistem%20ticketing/BUKU_PANDUAN_DAN_DOKUMENTASI_LENGKAP_SISTEM_TICKETING_DKIS.md)**

---

## 🏛️ Lisensi & Kepemilikan

Hak Cipta © 2026 **Dinas Komunikasi, Informatika, dan Statistik (DKIS) Pemerintah Kota Cirebon**.  
Dikembangkan untuk mendukung tata kelola TIK dan pelayanan publik yang akuntabel, transparan, dan terpercaya.
